<?php
/**
 * @file
 * Table class implementing the table interfollow
 */

namespace CL\Interact;

use CL\Course\Members;
use CL\Course\Member;
use CL\Users\User;

/**
 * Table class implementing the table interfollow
 *
 * This table manages the following status for users and interactions
 */
class InterFollows extends \CL\Tables\Table {
    const FOLLOWING = 'F';          ///< User is following the interaction
    const NOTFOLLOWING = 'N';       ///< User is not following
    const NEVERFOLLOW = 'X';        ///< User will never follow

	/**
	 * Constructor
	 * @param \CL\Tables\Config $config The Database configuration object
	 */
	public function __construct(\CL\Tables\Config $config) {
		parent::__construct($config, 'interfollow');
	}


	/**
	 * Create an SQL create table command for the users table
	 * @return string SQL
	 */
	public function createSQL() {

		$query = <<<SQL
CREATE TABLE IF NOT EXISTS `$this->tablename` (
  memberid   int(11) NOT NULL, 
  interactid int(10) NOT NULL, 
  code       char(1) NOT NULL, 
  PRIMARY KEY (memberid, 
  interactid));
SQL;

		return $query;
	}


    /**
     * Get the following status for a user and interaction
     * @param int $memberId The member id
     * @param int $interactId Interaction id
     * @return string InterFollow::FOLLOWING, InterFollow::NOTFOLLOWING, InterFollow::NEVERFOLLOW
     */
    public function getFollowing($memberId, $interactId) {
        $sql = <<<SQL
select code from $this->tablename 
where memberid=? and interactid=?
SQL;

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute([$memberId, $interactId]);

        if($stmt === false) {
            return self::NOTFOLLOWING;
        }

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        if($row) {
            return $row['code'];
        }

        return self::NOTFOLLOWING;
    }

    /**
     * Set the following status for a user/interaction
     * @param int $memberId The user id
     * @param int $interactId Interaction id
     * @param string $code InterFollow::FOLLOWING, InterFollow::NOTFOLLOWING, InterFollow::NEVERFOLLOW
     */
    public function setFollowing($memberId, $interactId, $code) {
        $sql = <<<SQL
replace into $this->tablename(memberid, interactid, code)
values(?, ?, ?)
SQL;

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute(array($memberId, $interactId, $code));
    }


	/**
	 * Get all followers for a given interaction
	 * @param int $interactId The InteractID we are getting
	 * @return array of records, each with keys 'id', 'name', 'email', 'user'
	 */
	public function getFollowers($interactId) {
		$members = new Members($this->config);
		$sql = $members->memberUserJoinSQL();

		$sql .= <<<SQL
join $this->tablename interfollow
on member.id = interfollow.memberid
where interactid=? and interfollow.code=?
SQL;

		// echo "\n" . $this->sub_sql($sql, [$interactId, self::FOLLOWING]) . "\n";
		$stmt = $this->pdo()->prepare($sql);
		$stmt->execute([$interactId, self::FOLLOWING]);

		$result = array();
		while(($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
			$user = new User($row);
			$user->member = new Member($row);
			$result[$user->member->id] = $user;
		}

		return $result;
	}


}