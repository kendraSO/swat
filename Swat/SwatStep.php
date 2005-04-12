<?php
require_once('Swat/SwatContainer.php');
require_once('Swat/SwatHtmlTag.php');

/**
 * A step container used for wizards
 *
 * @package Swat
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright silverorange 2004
 */
class SwatStep extends SwatContainer {

	public $visible = false;
	public $step;

	public function display() {
		if ($this->visible)
			parent::display();
	}
}

?>
