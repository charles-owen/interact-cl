<?php
/**
 * @file
 * @brief Table class implementing the table interfollow
 */

namespace Interact;

/**
 * @brief Table class implementing the table interfollow
 *
 * This table manages the following status for users and interactions
 */
class InterFollows extends \Table {
    const FOLLOWING = 'F';          ///< User is following the interaction
    const NOTFOLLOWING = 'N';       ///< User is not following
    const NEVERFOLLOW = 'X';        ///< User will never follow

    /** @brief Constructor
     * @param $course Course object
     */
    public function __construct($course) {
        parent::__construct($course, 'interfollow');
    }

    /**
     * @brief Get the following status for a user and interaction
     * @param $userid The user id
     * @param $interactid Interaction id
     * @return string InterFollow::FOLLOWING, InterFollow::NOTFOLLOWING, InterFollow::NEVERFOLLOW
     */
    public function get_following($userid, $interactid) {
        $sql = <<<SQL
select code from $this->tablename where userid=? and interactid=?
SQL;

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute(array($userid, $interactid));

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
     * @brief Set the following status for a user/interaction
     * @param $userid The user id
     * @param $interactid Interaction id
     * @param $code InterFollow::FOLLOWING, InterFollow::NOTFOLLOWING, InterFollow::NEVERFOLLOW
     */
    public function set_following($userid, $interactid, $code) {
        $sql = <<<SQL
replace into $this->tablename(userid, interactid, code)
values(?, ?, ?)
SQL;

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute(array($userid, $interactid, $code));
    }

	/**
	 * @param \Section|null $section Section of course to set
	 * @param $interactid Interaction ID value
	 * @param bool $staffonly If true, set only staff members as following
	 */
    public function set_all_following(\Section $section=null, $interactid, $staffonly=false) {
        $pdo = $this->pdo();

        $qiid = $pdo->quote($interactid);
        $qfollowing = $pdo->quote(self::FOLLOWING);

        $users = new \Users($this->course);
        $values = '';
        foreach($users->get_users($section, null, $staffonly) as $user) {
            // Ignore any guest users
            if($user->is_guest()) {
                continue;
            }

            // Graders are not set to follow Interact messages
            if($staffonly && $user->role === \User::GRADER) {
				continue;
			}

            if(strlen($values) > 0) {
                $values .= ",";
            }

            $qid = $pdo->quote($user->get_id());
            $values .= "($qid, $qiid, $qfollowing)";
        }

        $f = InterFollows::FOLLOWING;
        $x = InterFollows::NEVERFOLLOW;

        $sql = <<<SQL
update $this->tablename set code='$f' where code <> '$x';
insert ignore into $this->tablename(userid, interactid, code)
values $values
SQL;

        $pdo->query($sql);
    }

	/**
	 * Get all followers for a given interaction
	 * @param $interactId The InteractID we are getting
	 * @return array of records, each with keys 'id', 'name', 'email', 'user'
	 */
	public function get_followers($interactId) {
		$users = new \Users($this->course);
		$usersTable = $users->get_tablename();

		$sql = <<<SQL
select *
from $this->tablename interfollow
join $usersTable users
on id=userid and interactid=? and code=?
SQL;

		$stmt = $this->pdo()->prepare($sql);
		$stmt->execute(array($interactId, self::FOLLOWING));

		$result = array();
		while(($row = $stmt->fetch(\PDO::FETCH_ASSOC)) !== false) {
			$result[$row['id']] = new \User($this->course, $row);
		}

		return $result;
	}


    /** Function to create an SQL create table command for the submissions table */
    public function create_sql() {
        //IF NOT EXISTS $this->tablename
        $sql = <<<SQL
CREATE TABLE IF NOT EXISTS $this->tablename (userid int(10) NOT NULL, interactid int(10) NOT NULL, code char(1) NOT NULL, PRIMARY KEY (userid, interactid));
SQL;

        return $sql;
    }


}