<?php
/**
 * @file
 * Table class implementing the table interactive
 */

namespace Interact;

/**
 * Table class implementing the table interactive
 *
 * This table keeps track of users who are currently interacting.
 */
class InterActive extends \Table {
    /** Constructor
     * @param $course Course object
     */
    public function __construct($course) {
        parent::__construct($course, 'interactive');
    }

    /**
     * Set a user as active for a specific interact.
     * @param \User $user
     * @param $interactid
     */
    public function set(\User $user, $interactid, $time=null) {
        if($time === null) {
            $time = time();
        }

         $sql = <<<SQL
replace into $this->tablename(userid, interactid, time, username, userrole)
values(?, ?, ?, ?, ?)
SQL;

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute(array($user->id, $interactid,
            date("Y-m-d H:i:s", $time), $user->displayname, $user->role));
    }

    /**
     * Delete an interaction activity from the table
     * @param $id ID for the user activity to delete.
     */
    public function delete(\User $user) {
        $sql = <<<SQL
delete from $this->tablename
where userid=?
SQL;

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute(array($user->id));
    }

    /**
     * Query the active interact editing.
     * @param $interactid Interact ID we are interested in
     * @return array of arrays, each with these keys:
     * interactid, userid, username, userrole, time
     */
    public function query($interactid, $time) {
        $sql = <<<SQL
select * from $this->tablename
where interactid=? and time >= ?
SQL;

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute(array($interactid, date("Y-m-d H:i:s", $time)));

        $result = [];
        foreach($stmt as $row) {
            $result[] = ["interactid"=>$row['interactid'],
                'userid'=>$row['userid'],
                'username'=>$row['username'],
                'userrole'=>$row['userrole'],
                'time'=>strtotime($row['time'])];
        }

        return $result;
    }


    /** Function to create an SQL create table command for the table */
    public function create_sql() {

        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS $this->tablename (
  userid     int(10) NOT NULL, 
  interactid int(10) NOT NULL, 
  time       datetime NOT NULL, 
  username   varchar(150) NOT NULL, 
  userrole   char(1) NOT NULL, 
  PRIMARY KEY (userid), 
  INDEX (interactid));
SQL;

        return $sql;
    }

}