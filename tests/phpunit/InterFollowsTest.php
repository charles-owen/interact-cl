<?php
/** @file
 * Unit tests for the class InterFollows
 * @cond 

 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/cls/InteractDatabaseTestBase.php';

use CL\Interact\InterFollows;
use CL\Course\Members;
use CL\Users\Users;

class InterFollowsTest extends InteractDatabaseTestBase {
	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	public function getDataSet() {
		return $this->dataSets(['interfollow.xml', 'user-many.xml', 'member-many.xml']);
	}

	public function ensureTables() {
		$this->ensureTable(new Members($this->site->db));
		$this->ensureTable(new Users($this->site->db));
		$this->ensureTable(new InterFollows($this->site->db));
	}


    public function test_set() {
        $table = new InterFollows($this->site->db);

        $this->assertEquals(InterFollows::NOTFOLLOWING, $table->getFollowing(5, 22));

        $table->setFollowing(5, 22, InterFollows::FOLLOWING);

        $this->assertEquals(InterFollows::FOLLOWING, $table->getFollowing(5, 22));
    }


	public function test_getFollowers() {
		$table = new InterFollows($this->site->db);

		$interactId = 22;

		$table->setFollowing(22, $interactId, InterFollows::FOLLOWING);
		$table->setFollowing(35, $interactId, InterFollows::FOLLOWING);
		$table->setFollowing(11, $interactId, InterFollows::FOLLOWING);
		$table->setFollowing(10, $interactId, InterFollows::NEVERFOLLOW);

		$followers = $table->getFollowers($interactId);
		$this->assertEquals(3, count($followers));

		$user22 = $followers[22];
		$this->assertEquals('bob', $user22->userId);

		$user35 = $followers[35];
		$this->assertEquals('bart', $user35->userId);
	}
	
}

/// @endcond
