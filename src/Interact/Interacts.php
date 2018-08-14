<?php
/**
 * @file
 * Table class implementing the table interact
 */

namespace CL\Interact;

/**
 * Table class implementing the table interact
 *
 * This is the main table for the Interact course discussion system.
 */
class Interacts extends \CL\Tables\Table {
    const DEFAULT_LIMIT = 200;

	/**
	 * Constructor
	 * @param \CL\Tables\Config $config The Database configuration object
	 */
	public function __construct(\CL\Tables\Config $config) {
		parent::__construct($config, 'interact');
	}

	/**
	 * Create an SQL create table command for the users table
	 * @return string SQL
	 */
	public function createSQL() {

		$query = <<<SQL
CREATE TABLE IF NOT EXISTS `$this->tablename` (
  id         int(10) NOT NULL AUTO_INCREMENT, 
  memberid   int(11) NOT NULL, 
  assigntag  varchar(30) NOT NULL, 
  sectiontag varchar(30), 
  created    datetime NOT NULL, 
  time       datetime NOT NULL, 
  type       char(1) NOT NULL, 
  pin        bit(1) NOT NULL, 
  private    bit(1) NOT NULL, 
  summary    varchar(100) NOT NULL, 
  message    mediumtext NOT NULL, 
  metadata   mediumtext, 
  PRIMARY KEY (id), 
  INDEX (assigntag), 
  INDEX (time), 
  INDEX (pin));

SQL;

		return $query;
	}


	/**
	 * Table alteration to add full-text indexing to table.
	 */
	public function alter() {
		$indexname = $this->tablename . "_fulltext";
		if($this->indexExists($indexname)) {
			return true;
		}

		$pdo = $this->pdo();

		$sql = <<<SQL
CREATE FULLTEXT INDEX $indexname
  ON $this->tablename (message, summary);
SQL;

		$pdo->query($sql);
		return true;
	}


    public function __get($key) {
        switch($key) {
            case "limit":
                return $this->limit;

            default:
                return parent::__get($key);
        }
    }


	/**
	 * Add a new interaction to the table
	 * @param Interaction $interaction Interaction to add
	 * @return int ID for the inserted interaction
	 */
    public function add(Interaction $interaction) {
        $pdo = $this->pdo();

        $sql = <<<SQL
insert into $this->tablename(memberid, assigntag, sectiontag, created, time, 
							`type`, pin, private, summary, message, metadata)
values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
SQL;

        $timeStr =

		$a = [$interaction->user->member->id,
			$interaction->assignTag,
			$interaction->sectionTag,
			$this->timeStr($interaction->created),
			$this->timeStr($interaction->time),
			$interaction->type,
			$interaction->pin ? 1 : 0,
			$interaction->private ? 1 : 0,
			$interaction->summary,
			$interaction->message,
			$interaction->meta->json()];

		// echo $this->sub_sql($sql, $a);

        $stmt = $pdo->prepare($sql);
        $stmt->execute($a);

        $interaction->id = $pdo->lastInsertId();
        return $interaction->id;
    }


//
//
//    /**
//     * @param \Section $section
//     * @param array|null $categories
//     * @param null $sectiontag
//     * @return array
//     */
//    public function counts(\Section $section, array $categories=null, $sectiontag=null) {
//        $users = new \Users($this->course);
//        $usersTable = $users->get_tablename();
//
//        $where = '';
//        $exec = array();
//
//        if(!(count($categories) == 1 && current($categories) === 'all')) {
//            if ($categories !== null && count($categories) > 0) {
//                $where .= "where (";
//                $first = true;
//                foreach ($categories as $tag) {
//                    if ($first) {
//                        $first = false;
//                    } else {
//                        $where .= " or ";
//                    }
//                    $where .= "assigntag=?";
//                    $exec[] = $tag;
//                }
//                $where .= ")";
//            }
//        }
//
//        if($sectiontag !== null) {
//            $exec[] = $sectiontag;
//            if(strlen($where) > 0) {
//                $where .= ' and sectiontag=?';
//            } else {
//                $where .= 'where sectiontag=?';
//            }
//        }
//
//        $qsection = $this->pdo()->quote($section->get_id());
//
//        $sql = <<<SQL
//select type, count(*) as count
//from $this->tablename interact
//join $usersTable user
//on interact.userid = user.id and user.section=$qsection
//$where
//group by type
//SQL;
//
//        $stmt = $this->pdo()->prepare($sql);
//        $stmt->execute($exec);
//
//        $result = array(Interaction::Announcement => 0, Interaction::Question => 0);
//        foreach($stmt as $row) {
//            $result[$row['type']] = $row['count'];
//        }
//
//        return $result;
//    }

//    /**
//     * Update an interaction in the tables
//     * @param Interaction $interaction New values for the interaction to set
//     */
//    public function update(Interaction $interaction) {
//        $pdo = $this->pdo();
//
//        $sql = <<<SQL
//update $this->tablename
//set userid=?, assigntag=?, sectiontag=?, time=?, type=?, pin=?, private=?, summary=?, message=?
//where id=?
//SQL;
//
//        $stmt = $pdo->prepare($sql);
//        $stmt->execute(array($interaction->get_user_id(),
//            $interaction->get_assign_tag(),
//            $interaction->get_section_tag(),
//            date("Y-m-d H:i:s", $interaction->get_time()),
//            $interaction->get_type(),
//            $interaction->is_pin() ? 1 : 0,
//            $interaction->is_private() ? 1 : 0,
//            $interaction->get_summary(),
//            $interaction->to_xml(),
//            $interaction->get_id()));
//    }
//
//	/**
//	 * Get a single Interaction
//	 * @param $id ID for the interaction
//	 * @return Interaction|null Interaction found or null if does not exist
//	 */
//    public function get($id) {
//        $pdo = $this->pdo();
//
//        $users = new \Users($this->course);
//        $usersTable = $users->get_tablename();
//        $discussions = new Discussions($this->course);
//        $discussionsTable = $discussions->get_tablename();
//
//        $sql = <<<SQL
//select interact.id as id, user.id as userid, assigntag, sectiontag, interact.time as time,
//type, pin, private, summary, message, name, role, count(discussion.discuss) as discussions
//from $this->tablename interact
//join $usersTable user
//on interact.userid=user.id
//left join $discussionsTable discussion
//on interact.id = discussion.interactid
//where interact.id=?
//group by interact.id
//order by pin desc, time desc
//SQL;
//
///*        $sql = <<<SQL
//select interact.id as id, userid, assigntag, sectiontag, time,
//type, pin, private, summary, message, name, role, 0 as discussions
//from $this->tablename interact
//join $usersTable user
//on interact.userid=user.id
//where interact.id=?
//SQL;*/
//
//        $stmt = $pdo->prepare($sql);
//        $stmt->execute(array($id));
//        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
//
//        if($row === false) {
//            return null;
//        }
//
//        return $this->interaction_from_row($row);
//    }
//
//    private function interaction_from_row($row) {
//        $interaction = new Interaction($this->course,
//            null,
//            $row['assigntag'],
//            $row['sectiontag'],
//            $row['type'],
//            $row['pin'] == 1,
//            $row['private'] == 1,
//            strtotime($row['time']));
//
//        $interaction->set_id($row['id']);
//        $interaction->set_user($row['userid'], $row['name'], $row['role']);
//        $interaction->from_xml($row['message']);
//        $interaction->set_summary($row['summary']);
//        $interaction->set_discussions($row['discussions']);
//        return $interaction;
//    }
//
//    /**
//     * Delete an interaction from the table
//     * @param $id ID for the interaction to delete
//     */
//    public function delete($id)
//    {
//        $sql = <<<SQL
//delete from $this->tablename
//where id=?
//SQL;
//
//        $stmt = $this->pdo()->prepare($sql);
//        $stmt->execute(array($id));
//    }

//    /*
//     * The interactions iterator functionality
//     */
//
//    /**
//     * Start the process of iterating over interactions one at a time
//     *
//     * This is called first and starts the database query. The first record
//     * is fetched and is available by calling $this->current. To advance to the next
//     * record, call $this->advance
//     *
//     * @param \User $user User we are searching for.
//     * @param array $categories Array of categories to search (usually assignment tags)
//     * @param null $sectionTag Optional section tag to require
//     * @param $before Only interactions before this date/time (null or empty string for all)
//     * @param $after Only interactions after this date/time (null or empty string for all)
//     * @param $query Optional query
//     */
//    public function select(\User $user, array $categories=null,
//                           $sectionTag=null, $before=null, $after=null, $query='') {
//        $exec = array();
//        $exec[] = $user->get_section()->get_id();
//        $where = '';
//
//        $pdo = $this->pdo();
//
//        $users = new \Users($this->course);
//        $usersTable = $users->get_tablename();
//        $discussions = new Discussions($this->course);
//        $discussionsTable = $discussions->get_tablename();
//
//        // Optional before time parameter
//        if($before !== null && $before !== '') {
//            $where = "where interact.time < ?";
//            $exec[] = date("Y-m-d H:i:s", $before);
//        }
//
//        // Optional after time parameter
//        if($after !== null && $after !== '') {
//            if($where === '') {
//                $where = "where interact.time > ?";
//            } else {
//                $where .= " and interact.time > ?";
//            }
//
//            $exec[] = date("Y-m-d H:i:s", $after);
//        }
//
//        if(!(count($categories) == 1 && current($categories) === 'all')) {
//            if ($categories !== null && count($categories) > 0) {
//                if($where === '') {
//                    $where = "where (";
//                } else {
//                    $where .= " and (";
//                }
//
//                $first = true;
//                foreach ($categories as $tag) {
//                    if ($first) {
//                        $first = false;
//                    } else {
//                        $where .= " or ";
//                    }
//                    $where .= "assigntag=?";
//                    $exec[] = $tag;
//                }
//
//                $where .= ")";
//            }
//        }
//
//        if($sectionTag !== null && $sectionTag !== '') {
//            if($where === '') {
//                $where = "where sectiontag=?";
//            } else {
//                $where .= " and sectiontag=?";
//            }
//
//            $exec[] = $sectionTag;
//        }
//
//        if(!$user->is_staff()) {
//            if($where === '') {
//                $where = "where (private='0' or user.id=?)";
//            } else {
//                $where .= " and (private='0' or user.id=?)";
//            }
//
//            $exec[] = $user->get_id();
//        }
//
//        if($query !== null && $query !== '') {
//            if($where === '') {
//                $where = "where ";
//            } else {
//                $where .= " and ";
//            }
//
//            $where .= "match(message,summary) against(? in natural language mode)";
//            $exec[] = $query;
//        }
//
//        $sql = <<<SQL
//select interact.id as id, user.id as userid, assigntag, sectiontag, interact.time as time,
//type, pin, private, summary, message, name, role, count(discussion.discuss) as discussions
//from $this->tablename interact
//join $usersTable user
//on interact.userid=user.id and user.section=?
//left join $discussionsTable discussion
//on interact.id = discussion.interactid
//$where
//group by interact.id
//order by pin desc, time desc
//limit $this->limit
//SQL;
//
//       // echo "\n" . $this->sub_sql($sql, $exec) . "\n";
//        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
//        try {
//            $this->stmt = $pdo->prepare($sql);
//            $this->stmt->execute($exec);
//            $this->advance();
//        } catch(\PDOException $exception) {
//            echo $exception->getMessage();
//        }
//    }
//
//    /**
//     * Get the current interaction
//     * @return Interaction|null Current result or null if none. Array with key for each field
//     */
//    public function get_current() {
//        return $this->current;
//    }
//
//    /**
//     * Advance to the next interaction
//     * @return bool True if another thought exists
//     */
//    public function advance() {
//        if(($row = $this->stmt->fetch(\PDO::FETCH_ASSOC))) {
//            $this->current = $this->interaction_from_row($row);
//            return true;
//        } else {
//            // Nothing to fetch
//            $this->current = null;
//            return false;
//        }
//    }
//
//    /**
//     * Close the interaction iteration
//     */
//    public function close() {
//        $this->stmt->closeCursor();
//    }

//    private $stmt = null;
//    private $current = null;


    /// Limit on number of responses
    private $limit = self::DEFAULT_LIMIT;
}