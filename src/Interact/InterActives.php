<?php
/**
 * @file
 * Table class for the table interactive
 * This table keeps track of members actively editing discussions.
 */

namespace CL\Interact;

use CL\Users\User;

/**
 * Table class for the table interactive
 * This table keeps track of members actively editing discussions.
 */
class InterActives extends \CL\Tables\Table {
	/// Number of seconds an active record is considered valid
	const ACTIVE_DURATION = 30;

	/**
	 * Constructor
	 * @param \CL\Tables\Config $config The Database configuration object
	 */
	public function __construct(\CL\Tables\Config $config) {
		parent::__construct($config, 'interactive');
	}

	/**
	 * Create an SQL create table command for the interactive table
	 * @return string SQL
	 */
	public function createSQL() {

		$query = <<<SQL
CREATE TABLE IF NOT EXISTS `$this->tablename` (
  instance   char(32) NOT NULL, 
  memberid   int(11) NOT NULL, 
  interactid int(11) NOT NULL, 
  time       datetime NOT NULL, 
  username   varchar(150) NOT NULL, 
  userrole   char(1) NOT NULL, 
  PRIMARY KEY (instance), 
  INDEX (interactid));


SQL;

		return $query;
	}

	/**
	 * Create an active user record
	 * @param User $user User we are creating for
	 * @param string $instance Interact browser instance
	 * @param int $interactId Interact ID
	 * @param int $time Current time
	 * @return bool True if successful
	 */
	public function set(User $user, $instance, $interactId, $time) {
		$sql = <<<SQL
replace into $this->tablename(instance, memberid, interactid, time, username, userrole)
values(?, ?, ?, ?, ?, ?)
SQL;

		$exec = [$instance, $user->member->id, $interactId,
			$this->timeStr($time), $user->displayName, $user->role()];
//echo $this->sub_sql($sql, $exec);
		$stmt = $this->pdo->prepare($sql);
		return $stmt->execute($exec);
	}

	/**
	 * Reset an active user record. Indicate no longer editing.
	 * @param string $instance Interact browser instance
	 * @return bool
	 */
	public function reset($instance) {
		$sql = <<<SQL
delete from $this->tablename
where instance=?
SQL;

		$exec = [$instance];

		$stmt = $this->pdo->prepare($sql);
		return $stmt->execute($exec);
	}

	/**
	 * Get all valid active users for a given interact ID
	 * @param int $interactId Interact ID
	 * @param int $time Current time
	 * @return array of records.
	 */
	public function get($interactId, $time) {
		$sql = <<<SQL
select distinct memberid, username, userrole 
from $this->tablename
where interactid=? and time>?
SQL;

		$exec = [$interactId, $this->timeStr($time - self::ACTIVE_DURATION)];
		$stmt = $this->pdo->prepare($sql);
		//echo $this->sub_sql($sql, $exec);
		$stmt->execute($exec);
		return $stmt->fetchAll(\PDO::FETCH_ASSOC);
	}

	/**
	 * Clean out expired records
	 * @param int $time Current time
	 * @return bool
	 */
	public function clean($time) {
		$sql = <<<SQL
delete from $this->tablename
where time < ?
SQL;

		$exec = [$this->timeStr($time - self::ACTIVE_DURATION)];

		$stmt = $this->pdo->prepare($sql);
		return $stmt->execute($exec);
	}

}