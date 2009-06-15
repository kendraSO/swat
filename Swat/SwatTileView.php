<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Swat/SwatView.php';
require_once 'Swat/SwatUIParent.php';
require_once 'Swat/SwatCheckAll.php';
require_once 'Swat/SwatHtmlTag.php';
require_once 'Swat/exceptions/SwatInvalidClassException.php';

/**
 * A tile view widget for containing a {@link SwatTile} tile
 *
 * Records in this tile-view's model may be selected by the user by adding a
 * view-selector to this tile-view. See {@link SwatView} for details on how to
 * use {@link SwatViewSelector} objects.
 *
 * @package   Swat
 * @copyright 2007 silverorange
 * @lisence   http://www.gnu.org/copyleft/lesser.html LGPL Lisence 2.1
 * @see       SwatTile
 */
class SwatTileView extends SwatView implements SwatUIParent
{
	// {{{ public properties

	/**
	 * Whether to show a "check all" widget
	 *
	 * For this option to have an effect, this tile view's tile must contain a
	 * {@link SwatCheckboxCellRenderer}. This is a tri-state value:
	 * null (default) = display checkbox if their is more than one record,
	 * true = always display checkbox, false = never display checkbox.
	 *
	 * @var boolean
	 */
	public $show_check_all = null;

	/**
	 * Optional label title for the check-all widget
	 *
	 * Defaults to "Check All".
	 *
	 * @var string
	 */
	public $check_all_title;

	/**
	 * Optional content type for check-all widget title
	 *
	 * Defaults to text/plain, use text/xml for XHTML fragments.
	 *
	 * @var string
	 */
	public $check_all_content_type = 'text/plain';

	/**
	 * Count for displaying an extended-all checkbox
	 *
	 * When the check-all checkbox has been checked, an additional
	 * checkbox will appear allowing the user to specify that they wish to
	 * select all possible items. This is useful in cases where pagination
	 * makes selecting all possible items impossible.
	 *
	 * @var integer
	 */
	public $check_all_extended_count = 0;

	/**
	 * Count for all visible items when displaying an extended-all checkbox
	 *
	 * @var integer
	 */
	public $check_all_visible_count = 0;

	/**
	 * Optional extended-all checkbox unit.
	 *
	 * Used for displaying a "check-all" message. Defaults to "items".
	 */
	public $check_all_unit;

	// }}}
	// {{{ protected properties

	/**
	 * The groups of this tile-view indexed by their unique identifier
	 *
	 * A unique identifier is not required so this array does not necessarily
	 * contain all groups in the view. It serves as an efficient data structure
	 * to lookup groups by their id.
	 *
	 * The array is structured as id => group reference.
	 *
	 * @var array
	 */
	protected $groups_by_id = array();

	/**
	 * Grouping objects for this tile-view
	 *
	 * @var array
	 *
	 * @see SwatTileView::addGroup()
	 */
	protected $groups = array();

	// }}}
	// {{{ private properties

	/**
	 * The tile of this tile view
	 *
	 * @var SwatTile
	 */
	private $tile = null;

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new tile view
	 *
	 * @param string $id a non-visable unique id for this widget.
	 *
	 * @see SwatWidget:__construct()
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->addStyleSheet('packages/swat/styles/swat-tile-view.css',
			Swat::PACKAGE_ID);

		$this->addJavaScript('packages/swat/javascript/swat-tile-view.js',
			Swat::PACKAGE_ID);
	}

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this tile view
	 *
	 * This initializes the tile view and the tile contained in the view.
	 *
	 * @see SwatView::init()
	 */
	public function init()
	{
		parent::init();

		if ($this->tile !== null)
			$this->tile->init();

		foreach ($this->groups as $group) {
			$group->init();
			// index the group by id if it is not already indexed
			if (!array_key_exists($group->id, $this->groups_by_id))
				$this->groups_by_id[$group->id] = $group;
		}
	}

	// }}}
	// {{{ public function process()

	/**
	 * Processes this tile view
	 *
	 * Process the tile contained by this tile view.
	 *
	 * Unlike SwatWidget, composite widgets of this tile are not automatically
	 * processed. This allows tile-views to be created outside a SwatForm.
	 */
	public function process()
	{
		if (!$this->isInitialized())
			$this->init();

		if ($this->getFirstAncestor('SwatForm') !== null)
			$this->getCompositeWidget('check_all')->process();

		$this->processed = true;

		if ($this->tile !== null)
			$this->tile->process();
	}

	// }}}
	// {{{ public function isExtendedCheckAllSelected()

	/**
	 * Whether or not the extended-check-all check-box was checked
	 *
	 * @return boolean Whether or not the extended-checkbox was checked
	 */
	public function isExtendedCheckAllSelected()
	{
		$check_all = $this->getCompositeWidget('check_all');
		return $check_all->isExtendedSelected();
	}

	// }}}
	// {{{ public function display()

	/**
	 * Displays this tile view
	 */
	public function display()
	{
		if (!$this->visible)
			return;

		if ($this->model === null)
			return;

		parent::display();

		$tile_view_tag = new SwatHtmlTag('div');
		$tile_view_tag->id = $this->id;
		$tile_view_tag->class = $this->getCSSClassString();
		$tile_view_tag->open();

		$this->displayTiles();

		if ($this->showCheckAll()) {
			$check_all = $this->getCompositeWidget('check_all');

			if ($this->check_all_title !== null) {
				$check_all->title = $this->check_all_title;
				$check_all->content_type = $this->check_all_content_type;
			}

			$check_all->extended_count = $this->check_all_extended_count;
			$check_all->visible_count = $this->check_all_visible_count;
			$check_all->unit = $this->check_all_unit;
			$check_all->display();
		}

		$clear_div_tag = new SwatHtmlTag('div');
		$clear_div_tag->style = 'clear: left;';
		$clear_div_tag->setContent('');
		$clear_div_tag->display();

		$tile_view_tag->close();

		Swat::displayInlineJavaScript($this->getInlineJavaScript());
	}

	// }}}
	// {{{ public function displayTiles()

	/**
	 * Displays the tiles of this tile view
	 */
	public function displayTiles()
	{
		// this uses read-ahead iteration

		$this->model->rewind();
		$record = ($this->model->valid()) ? $this->model->current() : null;

		$this->model->next();
		$next_record = ($this->model->valid()) ? $this->model->current() : null;

		while ($record !== null) {

			$this->displayTileGroupHeaders($record, $next_record);
			$this->displayTile($record, $next_record);
			$this->displayTileGroupFooters($record, $next_record);

			$record = $next_record;
			$this->model->next();
			$next_record = ($this->model->valid()) ?
				$this->model->current() : null;
		}
	}

	// }}}
	// {{{ public function displayTile()

	/**
	 * Displays a simgle tile of this tile view
	 *
	 * @param mixed $record the record to display.
	 * @param mixed $next_record the next record to display. If there is no
	 *                            next record, this is null.
	 */
	public function displayTile($record, $next_record)
	{
		$this->tile->display($record);
	}

	// }}}
	// {{{ public function displayTileGroupHeaders()

	/**
	 * Displays time group headers
	 *
	 * @param mixed $record the record to display.
	 * @param mixed $next_record the next record to display. If there is no
	 *                            next record, this is null.
	 */
	public function displayTileGroupHeaders($record, $next_record)
	{
		foreach ($this->groups as $group)
			$group->display($record);
	}

	// }}}
	// {{{ public function displayTileGroupFooters()

	/**
	 * Displays time group footers
	 *
	 * @param mixed $record the record to display.
	 * @param mixed $next_record the next record to display. If there is no
	 *                            next record, this is null.
	 */
	public function displayTileGroupFooters($record, $next_record)
	{
		foreach ($this->groups as $group)
			$group->displayFooter($record, $next_record);
	}

	// }}}
	// {{{ public function addChild()

	/**
	 * Adds a child object
	 *
	 * This method fulfills the {@link SwatUIParent} interface. It is used
	 * by {@link SwatUI} when building a widget tree and should not need to be
	 * called elsewhere.
	 *
	 * To set the SwatTile to use, use {@link SwatTileView::setTile()}.
	 * To add a SwatTileViewGroup use {@link SwatTileView::appendGroup()}.
	 *
	 * @param SwatTile|SwatTileViewGroup $child a reference to a child object to
	 *                               add.
	 *
	 * @throws SwatInvalidClassException if the added object is not a tile.
	 * @throws SwatException if more than one tile is added to this tile view.
	 *
	 * @see SwatUIParent
	 * @see SwatTileView::setTile()
	 * @see SwatTileView::appendGroup()
	 */
	public function addChild(SwatObject $child)
	{
		if ($child instanceof SwatTileViewGroup)
			$this->appendGroup($child);
		elseif ($child instanceof SwatTile) {
			if ($this->tile !== null)
				throw new SwatException(
					'Only one tile may be added to a tile view.');

			$this->setTile($child);
		} else {
			throw new SwatInvalidClassException(
				'Only SwatTile objects can be added to a SwatTileView.',
				0, $child);
		}
	}

	// }}}
	// {{{ public function getDescendants()

	/**
	 * Gets descendant UI-objects
	 *
	 * @param string $class_name optional class name. If set, only UI-objects
	 *                            that are instances of <i>$class_name</i> are
	 *                            returned.
	 *
	 * @return array the descendant UI-objects of this tile view. If
	 *                descendant objects have identifiers, the identifier is
	 *                used as the array key.
	 *
	 * @see SwatUIParent::getDescendants()
	 */
	public function getDescendants($class_name = null)
	{
		if (!($class_name === null ||
			class_exists($class_name) || interface_exists($class_name)))
			return array();

		$out = array();

		if ($this->tile !== null) {
			if ($class_name === null || $this->tile instanceof $class_name) {
				if ($this->tile->id === null)
					$out[] = $this->tile;
				else
					$out[$this->tile->id] = $this->id;
			}

			if ($this->tile instanceof SwatUIParent)
				$out = array_merge($out,
					$this->tile->getDescendants($class_name));
		}

		foreach ($this->groups as $group) {
			if ($class_name === null || $group instanceof $class_name) {
				if ($group->id === null)
					$out[] = $group;
				else
					$out[$group->id] = $group;
			}

			if ($group instanceof SwatUIParent)
				$out = array_merge($out, $group->getDescendants($class_name));
		}

		return $out;
	}

	// }}}
	// {{{ public function getFirstDescendant()

	/**
	 * Gets the first descendant UI-object of a specific class
	 *
	 * @param string $class_name class name to look for.
	 *
	 * @return SwatUIObject the first descendant UI-object or null if no
	 *                       matching descendant is found.
	 *
	 * @see SwatUIParent::getFirstDescendant()
	 */
	public function getFirstDescendant($class_name)
	{
		if (!class_exists($class_name) && !interface_exists($class_name))
			return null;

		$out = null;

		if ($this->tile instanceof $class_name)
			$out = $this->tile;

		if ($out === null && $this->tile instanceof SwatUIParent)
			$out = $this->tile->getFirstDescendant($class_name);

		return $out;
	}

	// }}}
	// {{{ public function getDescendantStates()

	/**
	 * Gets descendant states
	 *
	 * Retrieves an array of states of all stateful UI-objects in the widget
	 * subtree below this tile view.
	 *
	 * @return array an array of UI-object states with UI-object identifiers as
	 *                array keys.
	 */
	public function getDescendantStates()
	{
		$states = array();

		foreach ($this->getDescendants('SwatState') as $id => $object)
			$states[$id] = $object->getState();

		return $states;
	}

	// }}}
	// {{{ public function setDescendantStates()

	/**
	 * Sets descendant states
	 *
	 * Sets states on all stateful UI-objects in the widget subtree below this
	 * tile view.
	 *
	 * @param array $states an array of UI-object states with UI-object
	 *                       identifiers as array keys.
	 */
	public function setDescendantStates(array $states)
	{
		foreach ($this->getDescendants('SwatState') as $id => $object)
			if (isset($states[$id]))
				$object->setState($states[$id]);
	}

	// }}}
	// {{{ public function getMessages()

	/**
	 * Gathers all messages from this tile view
	 *
	 * @return array an array of {@link SwatMessage} objects.
	 */
	public function getMessages()
	{
		$messages = parent::getMessages();
		if ($this->tile !== null)
			$messages = array_merge($messages, $this->tile->messages);

		return $messages;
	}

	// }}}
	// {{{ public function hasMessage()

	/**
	 * Gets whether or not this tile view has any messages
	 *
	 * @return boolean true if this tile view has one or more messages and
	 *						false if it does not.
	 */
	public function hasMessage()
	{
		$has_message = parent::hasMessage();
		if (!$has_message && $this->tile !== null)
			$has_message = $this->tile->hasMessage();

		return $has_message;
	}

	// }}}
	// {{{ public function getHtmlHeadEntrySet()

	/**
	 * Gets the SwatHtmlHeadEntry objects needed by this tile view
	 *
	 * @return SwatHtmlHeadEntrySet the SwatHtmlHeadEntry objects needed by
	 *                               this tile view.
	 *
	 * @see SwatUIObject::getHtmlHeadEntrySet()
	 */
	public function getHtmlHeadEntrySet()
	{
		$set = parent::getHtmlHeadEntrySet();

		if ($this->tile !== null)
			$set->addEntrySet($this->tile->getHtmlHeadEntrySet());

		foreach ($this->groups as $group)
			$set->addEntrySet($group->getHtmlHeadEntrySet());

		return $set;
	}

	// }}}
	// {{{ public function copy()

	/**
	 * Performs a deep copy of the UI tree starting with this UI object
	 *
	 * @param string $id_suffix optional. A suffix to append to copied UI
	 *                           objects in the UI tree.
	 *
	 * @return SwatUIObject a deep copy of the UI tree starting with this UI
	 *                       object.
	 *
	 * @see SwatUIObject::copy()
	 */
	public function copy($id_suffix = '')
	{
		$copy = parent::copy($id_suffix);
		$copy->children_by_id = array();

		if ($this->tile !== null) {
			$copy_tile = $this->tile->copy($id_suffix);
			$copy_tile->parent = $copy;
			$copy->tile = $copy_tile;
		}

		$copy->groups_by_id = array();
		foreach ($this->groups as $key => $group) {
			$copy_group = $group->copy($id_suffix);
			$copy_group->parent = $copy;
			$copy->groups[$key] = $copy_group;
			if ($copy_group->id !== null) {
				$copy->groups_by_id[$copy_group->id] = $copy_group;
			}
		}

		// TODO: what to do with view selectors?

		return $copy;
	}

	// }}}
	// {{{ protected function getInlineJavaScript()

	/**
	 * Gets the inline JavaScript required for this tile view
	 *
	 * @return string the inline JavaScript required for this tile view.
	 *
	 * @see SwatTile::getInlineJavaScript()
	 */
	protected function getInlineJavaScript()
	{
		$javascript = sprintf("var %s = new SwatTileView('%s');",
			$this->id, $this->id);

		if ($this->tile !== null) {
			$tile_javascript = $this->tile->getRendererInlineJavaScript();
			if ($tile_javascript != '')
				$javascript.= $tile_javascript;

			$tile_javascript = $this->tile->getInlineJavaScript();
			if ($tile_javascript != '')
				$javascript.= "\n".$tile_javascript;
		}

		if ($this->showCheckAll()) {
			$check_all = $this->getCompositeWidget('check_all');

			$renderer = $this->getCheckboxCellRenderer();
			$javascript.= "\n".$check_all->getInlineJavascript();

			// set the controller of the check-all widget
			$javascript.= sprintf("\n%s_obj.setController(%s);",
				$check_all->id, $renderer->id);
		}

		return $javascript;
	}

	// }}}
	// {{{ protected function getCSSClassNames()

	/**
	 * Gets the array of CSS classes that are applied to this tile view
	 *
	 * @return array the array of CSS classes that are applied to this tile
	 *                view.
	 */
	protected function getCSSClassNames()
	{
		$classes = array('swat-tile-view');
		$classes = array_merge($classes, parent::getCSSClassNames());
		return $classes;
	}

	// }}}
	// {{{ protected function showCheckAll()

	/**
	 * Whether or not a check-all widget is to be displayed for the tiles
	 * of this tile view
	 *
	 * This depends on the {@link SwatTileView::$show_check_all} property as
	 * well as whether or not this tile view contains a
	 * {@link SwatCheckboxCellRenderer} and whether or not this tile view
	 * contains enough tiles to warrent having a check-all widget
	 *
	 * @return boolean true if a check-all widget is to be displayed for this
	 *                  tile view and false if it is not.
	 */
	protected function showCheckAll()
	{
		if ($this->getCheckboxCellRenderer() === null ||
			$this->getFirstAncestor('SwatForm') === null) {
			$show = false;
		} elseif ($this->show_check_all === null && count($this->model) > 1) {
			$show = true;
		} elseif ($this->show_check_all === true) {
			$show = true;
		} else {
			$show = false;
		}

		return $show;
	}

	// }}}
	// {{{ protected function getCheckboxCellRenderer()

	/**
	 * Gets the first checkbox cell renderer in this tile view's tile
	 *
	 * @return SwatCheckboxCellRenderer the first checkbox cell renderer in
	 *                                   this tile view's tile or null if no
	 *                                   such cell renderer exists.
	 */
	protected function getCheckboxCellRenderer()
	{
		$checkbox_cell_renderer = null;

		foreach ($this->tile->getRenderers() as $renderer) {
			if ($renderer instanceof SwatCheckboxCellRenderer) {
				$checkbox_cell_renderer = $renderer;
				break;
			}
		}

		return $checkbox_cell_renderer;
	}

	// }}}
	// {{{ protected function createCompositeWidgets()

	/**
	 * Creates the composite check-all widget used by this tile view
	 */
	protected function createCompositeWidgets()
	{
		if ($this->getFirstAncestor('SwatForm') !== null) {
			$check_all = new SwatCheckAll();
			$this->addCompositeWidget($check_all, 'check_all');
		}
	}

	// }}}

	// tile methods
	// {{{ public function getTile()

	/**
	 * Gets a reference to a tile contained in the view.
	 *
	 * @return SwatTile the requested tile
	 */
	public function getTile()
	{
		return $this->tile;
	}

	// }}}
	// {{{ public function setTile()

	/**
	 * Sets a tile of this tile view
	 *
	 * @param SwatTile $tile the tile to set
	 */
	public function setTile(SwatTile $tile)
	{
		// if we're overwriting an existing tile, remove it's parent link
		if ($this->tile !== null)
			$this->tile->parent = null;

		$this->tile = $tile;
		$tile->parent = $this;
	}

	// }}}

	// grouping methods
	// {{{ public function appendGroup()

	/**
	 * Appends a grouping object to this tile-view
	 *
	 * A grouping object affects how the data in the model is displayed
	 * in this tile-view. With a grouping, a row splits tiles into groups with
	 * special group headers above each group.
	 *
	 * Multiple groupings may be added to tile-views.
	 *
	 * @param SwatTileViewGroup $group the tile-view grouping to append to
	 *                                   this tile-view.
	 *
	 * @see SwatTileViewGroup
	 *
	 * @throws SwatDuplicateIdException if the group has the same id as a
	 *                                  group already in this tile-view.
	 */
	public function appendGroup(SwatTileViewGroup $group)
	{
		$this->validateGroup($group);

		$this->groups[] = $group;

		if ($group->id !== null)
			$this->groups_by_id[$group->id] = $group;

		$group->view = $this;
		$group->parent = $this;
	}

	// }}}
	// {{{ public function hasGroup()

	/**
	 * Returns true if a group with the given id exists within this tile-view
	 *
	 * @param string $id the unique identifier of the group within this tile-
	 *                    view to check the existance of.
	 *
	 * @return boolean true if the group exists in this tile-view and false if
	 *                  it does not.
	 */
	public function hasGroup($id)
	{
		return array_key_exists($id, $this->groups_by_id);
	}

	// }}}
	// {{{ public function getGroup()

	/**
	 * Gets a group in this tile-view by the group's id
	 *
	 * @param string $id the id of the group to get.
	 *
	 * @return SwatTileViewGroup the requested group.
	 *
	 * @throws SwatWidgetNotFoundException if no group with the specified id
	 *                                     exists in this tile-view.
	 */
	public function getGroup($id)
	{
		if (!array_key_exists($id, $this->groups_by_id))
			throw new SwatWidgetNotFoundException(
				"Group with an id of '{$id}' not found.");

		return $this->groups_by_id[$id];
	}

	// }}}
	// {{{ public function getGroups()

	/**
	 * Gets all groups of this tile-view as an array
	 *
	 * @return array the the groups of this tile-view.
	 */
	public function getGroups()
	{
		return $this->groups;
	}

	// }}}
	// {{{ protected function validateGroup()

	/**
	 * Ensures a group added to this tile-view is valid for this tile-view
	 *
	 * @param SwatTileViewGroup $group the group to check.
	 *
	 * @throws SwatDuplicateIdException if the group has the same id as a
	 *                                  group already in this tile-view.
	 */
	protected function validateGroup(SwatTileViewGroup $group)
	{
		// note: This works because the id property is set before children are
		// added to parents in SwatUI.
		if ($group->id !== null) {
			if (array_key_exists($group->id, $this->groups_by_id))
				throw new SwatDuplicateIdException(
					"A group with the id '{$group->id}' already exists ".
					'in this tile view.',
					0, $group->id);
		}
	}

	// }}}
}

?>
