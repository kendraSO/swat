<?php
/**
 * @package Swat
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License
 * @copyright silverorange 2004
 */
require_once('Swat/SwatEntry.php');

/**
 * An integer entry widget.
 */
class SwatEntryInteger extends SwatEntry {

	public function init() {
		$this->size = 5;
	}

	public function process() {
		parent::process();

		if (is_numeric($this->value))
			$this->value = intval($this->value);
		else
			$this->addErrorMessage(_S("The %s field must be an integer."));
	}
}

?>
