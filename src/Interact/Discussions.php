<?php
/**
 * @file
 * Table class implementing the table discussion
 */

namespace CL\Interact;

use CL\Course\Member;
use CL\Course\Members;
use CL\Site\Site;
use CL\Tables\TableException;
use CL\Users\User;

/**
 * Table class implementing the table discussion
 *
 * This is the table for the Interact course discussion system discussions.
 */
class Discussions extends \CL\Tables\Table {

	/**
	 * Constructor
	 * @param \CL\Tables\Config $config The Database configuration object
	 */
	public function __construct(\CL\Tables\Config $config) {
		parent::__construct($config, 'discussion');
	}

	/**
	 * Create an SQL create table command for the discussion table
	 * @return string SQL
	 */
	public function createSQL() {

		$query = <<<SQL
CREATE TABLE IF NOT EXISTS `$this->tablename` (
  id         int(11) NOT NULL AUTO_INCREMENT, 
  interactid int(11) NOT NULL, 
  memberid   int(11) NOT NULL, 
  time       datetime NOT NULL, 
  message    mediumtext NOT NULL, 
  metadata   mediumtext, 
  deleted    tinyint NOT NULL, 
  PRIMARY KEY (id), 
  INDEX (interactid));

SQL;

		return $query;
	}


	/**
	 * Add a new discussion item to the table
	 * @param Discussion $discussion Item to add
	 * @return int ID for the inserted discussion item
	 */
    public function add(Discussion $discussion) {
        $pdo = $this->pdo();

        $sql = <<<SQL
insert into $this->tablename(interactid, memberid, time, message, metadata, deleted)
values(?, ?, ?, ?, ?, 0)
SQL;

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
        	$discussion->interactId,
	        $discussion->user->member->id,
            $this->timeStr($discussion->time),
            $discussion->message,
            $discussion->meta->json()
        ]);

        $discussion->id = $pdo->lastInsertId();
        return $discussion->id;
    }

	/**
	 * Update a new discussion item in the table
	 * @param Discussion $discussion Item new values to set
	 * @return int ID for the inserted discussion item
	 */
	public function update(Discussion $discussion) {
		$pdo = $this->pdo();

		$sql = <<<SQL
update $this->tablename
set message=?, metadata=?
where id=?
SQL;

		$stmt = $pdo->prepare($sql);
		$stmt->execute([
			$discussion->message,
			$discussion->meta->json(),
			$discussion->id
		]);

		$discussion->id = $pdo->lastInsertId();
		return $discussion->id;
	}

	/**
	 * Get the discussion items for a given interaction
	 * @param int $interactId The Interaction ID.
	 * @param int $before Only discussion items before this time are returned
	 * @return array
	 */
    public function getFor($interactId, $before=null) {
	    $where = new \CL\Tables\TableWhere($this);

	    $where->append('discussion.interactid=?', $interactId, \PDO::PARAM_INT);
	    $where->append('discussion.deleted=?', 0, \PDO::PARAM_INT);

	    if($before !== null) {
		    $where->append('discussion.time<=?', $this->timeStr($before));
	    }

	    $fields = <<<FIELDS
discussion.id as id, discussion.interactid as interactid, discussion.time as time, discussion.message as message, discussion.metadata as metadata
FIELDS;

	    $members = new Members($this->config);
	    $sql = $members->memberUserJoinSQL($fields, false, 'user_', 'member_');

	    $sql .= <<<SQL
join $this->tablename discussion
on member.id=discussion.memberid
$where->where
order by time 
SQL;

	    // echo $where->sub_sql($sql);
	    $result = $where->execute($sql);
	    $discussions = [];
	    foreach($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
	    	$discussions[] = new Discussion($row);
	    }

	    return $discussions;
    }

	/**
	 * Delete a discussion item
	 * @param Discussion $discussion item to delete.
	 */
	public function delete(Discussion $discussion) {
		$pdo = $this->pdo();

		$sql = <<<SQL
update $this->tablename
set deleted=1
where id=?
SQL;

		$stmt = $pdo->prepare($sql);
		$stmt->execute([$discussion->id]);
	}


	/**
	 * Get a discussion item by ID
	 * @param int $discussionId ID to delete
	 * @return Discussion object
	 */
	public function get($discussionId) {
		$where = new \CL\Tables\TableWhere($this);

		$where->append('discussion.id=?', $discussionId, \PDO::PARAM_INT);
		$where->append('discussion.deleted=?', 0, \PDO::PARAM_INT);

		$fields = <<<FIELDS
discussion.id as id, discussion.interactid as interactid, discussion.time as time, discussion.message as message, discussion.metadata as metadata
FIELDS;

		$members = new Members($this->config);
		$sql = $members->memberUserJoinSQL($fields, false, 'user_', 'member_');

		$sql .= <<<SQL
join $this->tablename discussion
on member.id=discussion.memberid
$where->where
order by time 
SQL;

		// echo $where->sub_sql($sql);
		$result = $where->execute($sql);
		$row = $result->fetch(\PDO::FETCH_ASSOC);
		if($row === null || $row === false) {
			return null;
		}

		return new Discussion($row);
	}


    /**
     * Compute statistics for interactions. This is the number of interactions by user.
     * @param Site $site The Site object
     * @param string $semester Semester code
     * @param string $sectionId Section ID
     * @return array Results as an array of arrays, each with keys 'user' and 'interactions'
     * @throws TableException
     */
    public function statistics(Site $site, $semester, $sectionId) {
        $fields = <<<FIELDS
count(*) as discussions
FIELDS;

        $members = new Members($this->config);
        $sql = $members->memberUserJoinSQL($fields, false, 'user_', 'member_');

        $sql .= <<<SQL
join $this->tablename discussion
on member.id=discussion.memberid
where member.semester=? and member.section=?
group by member.id
order by user.name
SQL;

        $pdo = $this->pdo();

        $exec = [$semester, $sectionId];
        $stmt = $pdo->prepare($sql);

        // echo "\n" . $this->sub_sql($sql, $exec) . "\n";
        $stmt->execute($exec);
        $stats = [];
        foreach($stmt->fetchAll(\PDO::FETCH_ASSOC) as $row) {
            $user = new User($row, 'user_');
            $member = new Member($row, 'member_');
            $user->member = $member;

            $stat = ['user'=>$user, 'discussions'=>$row['discussions']];
            $stats[] = $stat;
        }

        return $stats;
    }

}