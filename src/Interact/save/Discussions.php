<?php
/**
 * @file
 * Table class implementing the table discussion
 */

namespace Interact;

/**
 * Table class implementing the table discussion
 *
 * This is the table for the Interact course discussion system discussions.
 */
class Discussions extends \Table
{
    /** Constructor
     * @param $course Course object
     */
    public function __construct($course)
    {
        parent::__construct($course, 'discussion');
    }

	/**
	 * Add a new discussion item to the table
	 * @param Discussion $discussion Item to add
	 * @param $time Timestamp
	 * @return ID for the inserted discussion item
	 */
    public function add(Discussion $discussion, $time) {
        $pdo = $this->pdo();

        $sql = <<<SQL
insert into $this->tablename(userid, interactid, time, discuss)
values(?, ?, ?, ?)
SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($discussion->get_user_id(),
            $discussion->get_interact_id(),
            date("Y-m-d H:i:s", $time),
            $discussion->to_xml()));

        return $pdo->lastInsertId();
    }

	/**
	 * Get a discussion item by ID
	 * @param $id Id for the item
	 * @return Discussion|null Item retrieved or null if none
	 */
    public function get($id) {
        $pdo = $this->pdo();

        $users = new \Users($this->course);
        $usersTable = $users->get_tablename();

        $sql = <<<SQL
select discuss.id as id, userid, interactid, time, discuss, name, role
from $this->tablename discuss
join $usersTable user
on discuss.userid=user.id
where discuss.id=?
SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($id));
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if($row === false) {
            return null;
        }

        return $this->discussion_from_row($row);
    }

	/**
	 * Create a discussion item from a table row
	 * @param array $row Table row
	 * @return Discussion New item
	 */
    public function discussion_from_row($row) {
        $discussion = new Discussion($this->course,
            null,
            $row['interactid'],
            strtotime($row['time']));

        $discussion->set_id($row['id']);
        $discussion->set_user($row['userid'], $row['name'], $row['role']);
        $discussion->from_xml($row['discuss']);
        return $discussion;
    }


    /**
     * Update a discussion in the tables
     * @param Discussion $discussion New values for the discussion to set
     *
     * The interact ID cannot be changed. The UserID can be changed to allow
     * for future possible changing of post users
     */
    public function update(Discussion $discussion) {
        $pdo = $this->pdo();

        $sql = <<<SQL
update $this->tablename
set userid=?, time=?, discuss=?
where id=?
SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($discussion->get_user_id(),
            date("Y-m-d H:i:s", $discussion->get_time()),
            $discussion->to_xml(),
            $discussion->get_id()));
    }


    /**
     * Delete a discussion from the table
     * @param $id ID for the discussion to delete
     */
    public function delete($id) {
        $sql = <<<SQL
delete from $this->tablename
where id=?
SQL;

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute(array($id));
    }


    /*
     * The discussion iterator functionality
     */

	/**
	 * Start the process of iterating over discussions one at a time
	 *
	 * This is called first and starts the database query. The first record
	 * is fetched and is available by calling $this->current. To advance to the next
	 * record, call $this->advance
	 *
	 * @param \User $user User who is iterating (not currently used)
	 * @param $interactId Id for the interaction.
	 * @param $before Only discussions before this date (null or empty string for all)
	 * @return DiscussionsIterator Iterator on the result
	 */
    public function select(\User $user, $interactId, $before=null, $after=null) {
        $where = "where interactid = ?";
        $exec = [$interactId];

        // Convert $before to a string we can use in a query
        if($before !== null && $before !== '') {
            $where .= " and time < ?";
            $exec[] = date("Y-m-d H:i:s", $before);
        }

        if($after !== null && $after !== '') {
            $where .= " and time > ?";
            $exec[] = date("Y-m-d H:i:s", $after);
        }

        $users = new \Users($this->course);
        $usersTable = $users->get_tablename();

        $pdo = $this->pdo();

        $sql = <<<SQL
select discuss.id as id, userid, interactid, time, discuss, name, role
from $this->tablename discuss
join $usersTable user
on discuss.userid=user.id
$where
order by time
SQL;

       // echo "\n" . $this->sub_sql($sql, array($before, $interactId)) . "\n";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($exec);
        return new DiscussionsIterator($this, $stmt);
    }



    /** Function to create an SQL create table command for the submissions table */
    public function create_sql() {
        //IF NOT EXISTS $this->tablename
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS $this->tablename (id int(10) NOT NULL AUTO_INCREMENT, interactid int(10) NOT NULL, userid int(10) NOT NULL, time datetime NOT NULL, discuss mediumtext NOT NULL, PRIMARY KEY (id), INDEX (interactid));
SQL;

        return $sql;
    }

}

/**
 * Iterator class for the discussion table
 */
class DiscussionsIterator extends \TableIterator {
	/**
	 * Constructor
	 * @param Discussions $table Table iterating over
	 * @param \PDOStatement $stmt Prepared PDO statement
	 */
    public function __construct(Discussions $table, \PDOStatement $stmt) {
        parent::__construct($table, $stmt);

        $this->advance();
    }

    /**
     * Get the current interaction
     * @return Discussion|null Current result or null if none. Array with key for each field
     */
    public function get_current() {
        return $this->current;
    }

    /**
     * Advance to the next interaction
     * @return bool True if another thought exists
     */
    public function advance() {
        if(($row = $this->stmt->fetch(\PDO::FETCH_ASSOC))) {
            $this->current = $this->table->discussion_from_row($row);
            return true;
        } else {
            // Nothing to fetch
            $this->current = null;
            return false;
        }
    }

    private $current = null;
}