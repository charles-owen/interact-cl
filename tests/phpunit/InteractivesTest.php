<?php
/** @file
 * Unit tests for the class InterActives
 * @cond

 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/cls/InteractDatabaseTestBase.php';

use CL\Interact\InterActives;
use CL\Course\Member;
use CL\Users\Users;
use CL\Course\Test\DummyMember;

class InterFollowsTest extends InteractDatabaseTestBase {
	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	public function getDataSet() {
		return $this->dataSets(['interactive.xml']);
	}

	public function ensureTables() {
		$this->ensureTable(new InterActives($this->site->db));
	}

	public function test() {
		$dummy = new DummyMember();
		$user1 = $dummy->create(887, 14, Member::STUDENT);
		$instance1 = 'fdsafdsa';

		$user2 = $dummy->create(893, 27, Member::STUDENT);
		$instance2a = '890fd9fdasf';
		$instance2b = '8607afaafdsa';

		$time = time();

		$interactives = new InterActives($this->site->db);
		$a = $interactives->get(217, $time);
		$this->assertCount(0, $a);

		$time1 = $time + 100;
		$interactives->set($user1, $instance1, 217, $time1);

		$a = $interactives->get(217, $time1);
		$this->assertCount(1, $a);
		$a = $interactives->get(218, $time1);
		$this->assertCount(0, $a);

		$time2 = $time1 + 10;
		$interactives->set($user2, $instance2a, 217, $time2);

		$time3 = $time2 + 10;
		$interactives->set($user2, $instance2b, 217, $time3);
		$a = $interactives->get(217, $time3);
		$this->assertCount(3, $a);

		// first fall off due to time
		$time4 = $time1 + InterActives::ACTIVE_DURATION + 8;
		$a = $interactives->get(217, $time4);
		$this->assertCount(2, $a);

		$interactives->set($user2, $instance2b, 299, $time4);
		$a = $interactives->get(217, $time4);
		$this->assertCount(1, $a);

		$interactives->reset($instance2a);
		$a = $interactives->get(217, $time4);
		$this->assertCount(0, $a);

		$interactives->clean($time4);
		$a = $interactives->get(217, $time2);
		$this->assertCount(0, $a);
	}
}