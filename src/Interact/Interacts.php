<?php
/**
 * @file
 * Table class implementing the table interact
 */

namespace CL\Interact;

use CL\Course\Member;
use CL\Course\Members;
use CL\Site\Site;
use CL\Tables\TableException;
use CL\Users\User;

/**
 * Table class implementing the table interact
 *
 * This is the main table for the Interact course discussion system.
 */
class Interacts extends \CL\Tables\Table {
	/// Maximum summary replies per query
	const MAX_SUMMARIES = 100;


	/**
	 * Constructor
	 * @param \CL\Tables\Config $config The Database configuration object
	 */
	public function __construct(\CL\Tables\Config $config) {
		parent::__construct($config, 'interact');
	}

	/**
	 * Create an SQL create table command for the interact table
	 * @return string SQL
	 */
	public function createSQL() {

		$query = <<<SQL
CREATE TABLE IF NOT EXISTS `$this->tablename` (
  id         int(11) NOT NULL AUTO_INCREMENT, 
  memberid   int(11) NOT NULL, 
  assigntag  varchar(30) NOT NULL, 
  sectiontag varchar(30), 
  created    datetime NOT NULL, 
  time       datetime NOT NULL, 
  type       char(1) NOT NULL, 
  pin        tinyint NOT NULL, 
  private    tinyint NOT NULL, 
  summary    varchar(100) NOT NULL, 
  message    mediumtext NOT NULL, 
  metadata   mediumtext, 
  deleted    tinyint NOT NULL, 
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



	/**
	 * Add a new interaction to the table
	 * @param Interaction $interaction Interaction to add
	 * @return int ID for the inserted interaction
	 */
    public function add(Interaction $interaction) {
        $pdo = $this->pdo();

        $sql = <<<SQL
insert into $this->tablename(memberid, assigntag, sectiontag, created, time, 
							`type`, pin, private, summary, message, metadata, deleted)
values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
SQL;

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

	/**
	 * @param array $params Parameters to the query.
	 * @return array Array of Interaction objects.
	 */
    public function summaries($params = []) {
	    $where = new \CL\Tables\TableWhere($this);
	    $discussions = new Discussions($this->config);
	    $discussionsTable = $discussions->tablename;

	    if(isset($params['semester'])) {
		    $where->append('member.semester=?', $params['semester']);
	    }

	    if(isset($params['section'])) {
		    $where->append('member.section=?', $params['section']);
	    }

	    if(isset($params['sectionId'])) {
		    $where->append('member.section=?', $params['sectionId']);
	    }

	    if(isset($params['before'])) {
		    $where->append('interact.time<?', $this->timeStr($params['before']));
	    }

	    if(isset($params['after'])) {
		    $where->append('interact.time>?', $this->timeStr($params['after']));
	    }

	    if(isset($params['assignTag'])) {
		    $where->append('interact.assigntag=?', $params['assignTag']);
	    }

	    if(isset($params['sectionTag'])) {
		    $where->append('interact.sectiontag=?', $params['sectionTag']);
	    }

	    if(isset($params['privateMember'])) {
			$where->append('(private=0 or member.id=?)', $params['privateMember'], \PDO::PARAM_INT);
	    }

	    if(isset($params['deleted']) && $params['deleted']) {
			// Don't filter them out
	    } else {
		    $where->append('interact.deleted=?', 0, \PDO::PARAM_INT);
	    }

	    $fields = <<<FIELDS
interact.id as id, assigntag, sectiontag, 
interact.created as created, interact.time as time, interact.type as type,
pin, private, interact.summary as summary, interact.message as message,
interact.metadata as metadata, interact.deleted as deleted, count(discussion.id) as discussions
FIELDS;

	    $members = new Members($this->config);
	    $sql = $members->memberUserJoinSQL($fields, false, 'user_', 'member_');

	    $sql .= <<<SQL
join $this->tablename interact
on member.id=interact.memberid
left join $discussionsTable discussion
on interact.id=discussion.interactid and discussion.deleted=0
$where->where
group by interact.id
order by pin desc, time desc 
SQL;

	    if(isset($params['limit'])) {
		    $sql .= "\nlimit ?";
		    $where->append(null, intval($params['limit']), \PDO::PARAM_INT);
	    }

	    // echo "\n" . $where->sub_sql($sql) . "\n";
	    $result = $where->execute($sql);
	    $summaries = [];
	    foreach($result->fetchAll(\PDO::FETCH_ASSOC) as $row) {
	    	$summary = new Interaction($row);
	    	$summaries[] = $summary;
	    }


	    return $summaries;
    }

	/**
	 * Get the counts of numbers of questions and announcements.
	 * @param array $params Parameters to the query.
	 * @return array Array of Interaction objects.
	 */
	public function counts($params = []) {
		$where = new \CL\Tables\TableWhere($this);

		if(isset($params['semester'])) {
			$where->append('member.semester=?', $params['semester']);
		}

		if(isset($params['section'])) {
			$where->append('member.section=?', $params['section']);
		}

		if(isset($params['sectionId'])) {
			$where->append('member.section=?', $params['sectionId']);
		}

		if(isset($params['assignTag'])) {
			$where->append('interact.assigntag=?', $params['assignTag']);
		}

		if(isset($params['sectionTag'])) {
			$where->append('interact.sectiontag=?', $params['sectionTag']);
		}

		if(isset($params['privateMember'])) {
			$where->append('(private=0 or member.id=?)', $params['privateMember'], \PDO::PARAM_INT);
		}

		if(isset($params['deleted']) && $params['deleted']) {
			// Don't filter them out
		} else {
			$where->append('interact.deleted=?', 0, \PDO::PARAM_INT);
		}

		$members = new Members($this->config);
		$membersTable = $members->tablename;

		$sql = <<<SQL
select interact.type as type, count(*) as count
from $membersTable member		
join $this->tablename interact
on member.id=interact.memberid
$where->where
group by interact.type
order by pin desc, time desc 
SQL;

		// echo "\n" . $where->sub_sql($sql) . "\n";
		try {
			$result = $where->execute($sql);
			return $result->fetchAll(\PDO::FETCH_ASSOC);
		} catch(TableException $exception) {
			return [];
		}
	}


	/**
	 * Get a single Interaction
	 * @param int $id ID for the interaction
	 * @param bool $discussionCnt True if we want to load the discussion count
	 * @return Interaction|null Interaction found or null if does not exist
	 */
    public function get($id, $discussionCnt = true) {
	    $where = new \CL\Tables\TableWhere($this);
	    $discussions = new Discussions($this->config);
	    $discussionsTable = $discussions->tablename;

	    $where->append('interact.id=?', $id, \PDO::PARAM_INT);

	    $fields = <<<FIELDS
interact.id as id, assigntag, sectiontag, 
interact.created as created, interact.time as time, interact.type as type,
pin, private, interact.summary as summary, interact.message as message,
interact.metadata as metadata, interact.deleted as deleted
FIELDS;

	    if($discussionCnt) {
	    	$fields .= ', count(discussion.id) as discussions';
	    	$discussionsJoin = <<<SQL
left join $discussionsTable discussion
on interact.id=discussion.interactid
SQL;

	    } else {
	    	$discussionsJoin = '';
	    }

	    $members = new Members($this->config);
	    $sql = $members->memberUserJoinSQL($fields, false, 'user_', 'member_');

	    $sql .= <<<SQL
join $this->tablename interact
on member.id=interact.memberid
$discussionsJoin
$where->where
group by interact.id
order by pin desc, time desc 
SQL;

	    try {
		    // echo $where->sub_sql($sql);
		    $result = $where->execute($sql);
		    $row = $result->fetch(\PDO::FETCH_ASSOC);
		    if($row === false || $row === null) {
			    return null;
		    }
	    } catch(\CL\Tables\TableException $exception) {
	    	return null;
	    }

	    $interaction = new Interaction($row);

	    return $interaction;
    }

    /**
     * Update an interaction in the table
     * @param Interaction $interaction New values for the interaction to set
     */
    public function update(Interaction $interaction) {
        $pdo = $this->pdo();

        $sql = <<<SQL
update $this->tablename
set assigntag=?, sectiontag=?, time=?, type=?, pin=?, private=?, summary=?, message=?, metadata=?
where id=?
SQL;

        $stmt = $pdo->prepare($sql);
        $exec = [
	        $interaction->assignTag,
	        $interaction->sectionTag,
	        $this->timeStr($interaction->time),
	        $interaction->type,
	        $interaction->pin ? 1 : 0,
	        $interaction->private ? 1 : 0,
	        $interaction->summary,
	        $interaction->message,
	        $interaction->meta->json(),
	        $interaction->id
        ];

        // echo $this->sub_sql($sql, $exec);
        $stmt->execute($exec);
    }

	/**
	 * Just update the Interaction time.
	 * @param int $interactId Id to update
	 * @param int $time Time to set
	 */
    public function updateTime($interactId, $time) {
	    $pdo = $this->pdo();

	    $sql = <<<SQL
update $this->tablename
set time=?
where id=?
SQL;

	    $stmt = $pdo->prepare($sql);
	    $exec = [
		    $this->timeStr($time),
		    $interactId
	    ];

	    // echo $this->sub_sql($sql, $exec);
	    $stmt->execute($exec);
    }

	/**
	 * Delete an interaction.
	 * @param Interaction $interaction Interaction to delete.
	 */
	public function delete(Interaction $interaction) {
		$pdo = $this->pdo();

		$sql = <<<SQL
update $this->tablename
set deleted=1
where id=?
SQL;

		$stmt = $pdo->prepare($sql);
		$stmt->execute([$interaction->id]);
	}

	/**
	 * /api/interact/summaries
	 *
	 * GET gets summaries of interactions
	 *
	 * @param Site $site
	 * @param User $user
	 * @param array $get Parameters for the query
	 * @return array
	 */
	public function summariesData(Site $site, User $user, $get) {
		$query = [
			'semester'=>$user->member->semester,
			'section'=>$user->member->sectionId
		];

		if(!empty($get['assign'])) {
			$query['assignTag'] = $get['assign'];
		}

		if(!empty($get['section'])) {
			$query['sectionTag'] = $get['section'];
		}

		if(!empty($get['deleted'])) {
			$query['deleted'] = $get['deleted'];
		}

		if(!$user->atLeast(Member::STAFF)) {
			$query['privateMember'] = $user->member->id;
		}

		$limit = self::MAX_SUMMARIES;
		if(!empty($get['limit'])) {
			if($get['limit'] < $limit) {
				$limit = $get['limit'];
			}
		}

		$query['limit'] = $limit + 1;
		if(!empty($get['before'])) {
			$query['before'] = +$get['before'];
		}

		if(!empty($get['after'])) {
			$query['after'] = +$get['after'];
		}

		$summaries = $this->summaries($query);

		$data = [];
		for($i=0; $i<count($summaries) && $i<$limit; $i++) {
			$data[] = $summaries[$i]->summaryData($site, $user);
		}

		if(count($summaries) > $limit) {
			$data[] = ['more'=>true];
		}

		return $data;
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
count(*) as interactions
FIELDS;

        $members = new Members($this->config);
        $sql = $members->memberUserJoinSQL($fields, false, 'user_', 'member_');

        $sql .= <<<SQL
join $this->tablename interact
on member.id=interact.memberid
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

            $stat = ['user'=>$user, 'interactions'=>$row['interactions']];
            $stats[] = $stat;
        }

        return $stats;
    }

}