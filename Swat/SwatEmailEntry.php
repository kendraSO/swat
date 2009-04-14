<?php

/* vim: set noexpandtab tabstop=4 shiftwidth=4 foldmethod=marker: */

require_once 'Swat/SwatEntry.php';

/**
 * An email entry widget
 *
 * Automatically verifies that the value of the widget is a valid
 * email address.
 *
 * @package   Swat
 * @copyright 2005-2009 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class SwatEmailEntry extends SwatEntry
{
	// {{{ public function __construct()

	/**
	 * Creates a new Email Entry widget
	 *
	 * Sets autotrim to true by default.
	 *
	 * @param string $id a non-visible unique id for this widget.
	 */
	public function __construct($id = null)
	{
		parent::__construct($id);

		$this->autotrim = true;
	}

	// }}}
	// {{{ public function process()

	/**
	 * Processes this email entry
	 *
	 * Ensures this email address is formatted correctly. If the email address
	 * is not formatted correctly, adds an error message to this entry widget.
	 */
	public function process()
	{
		parent::process();

		if ($this->value === null)
			return;

		if ($this->value == '') {
			$this->value = null;
			return;
		}

		if ($this->validateEmailAddress($this->value)) {
			$message = Swat::_('The email address you have entered is not '.
				'properly formatted.');

			$this->addMessage(new SwatMessage($message, SwatMessage::ERROR));
		}
	}

	// }}}
	// {{{ protected function validateEmailAddress()

	/**
	 * Validates an email address
	 *
	 * This uses the PHP 5.2.x filter_var() function if it is available.
	 *
	 * @param string $value the email address to validate.
	 *
	 * @return boolean true if <i>$value</i> is a valid email address and
	 *                  false if it is not.
	 */
	protected function validateEmailAddress($value)
	{
		$valid = false;

		if (extension_loaded('filter')) {
			$valid =
				(filter_var($this->value, FILTER_VALIDATE_EMAIL) === false);
		} else {
			$valid_name_word = '[-!#$%&\'*+.\\/0-9=?A-Z^_`{|}~]+';
			$valid_domain_word = '[-!#$%&\'*+\\/0-9=?A-Z^_`{|}~]+';
			$valid_address_regexp = '/^'.$valid_name_word.'@'.
				$valid_domain_word.'(\.'.$valid_domain_word.')+$/ui';

			$valid = (preg_match($valid_address_regexp, $this->value) === 0);
		}

		return $valid;
	}

	// }}}
	// {{{ protected function getCSSClassNames()

	/**
	 * Gets the array of CSS classes that are applied to this entry
	 *
	 * @return array the array of CSS classes that are applied to this
	 *                entry.
	 */
	protected function getCSSClassNames()
	{
		$classes = array('swat-email-entry');
		$classes = array_merge($classes, parent::getCSSClassNames());
		return $classes;
	}

	// }}}
}

?>
