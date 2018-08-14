<?php
/**
 * @file
 * Table maker for the Interact subsystem tables
 */

namespace CL\Interact;

use CL\Tables\Config;

/**
 * Table maker for the Interact subsystem tables
 */
class InteractTables extends \CL\Tables\TableMaker {

	/**
	 * Table maker for the Interact subsystem tables
	 * @param Config $config Database configuration object
	 */
	public function __construct(Config $config) {
		parent::__construct($config);

		$this->add(new Interacts($config));
	}
}
