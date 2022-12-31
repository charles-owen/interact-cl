<?php
/** @file
 * Unit tests for the class InteractApi
 * @cond 
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/cls/InteractDatabaseTestBase.php';

use CL\Course\Member;
use CL\Interact\Interacts;
use CL\Site\Test\ServerMock;
use CL\Course\Members;
use CL\Users\Users;
use CL\Interact\InteractApi;
use CL\Interact\InterFollows;
use CL\Course\Section;

class InteractApiTest extends InteractDatabaseTestBase {

	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	public function getDataSet() {
		return $this->dataSets(['interact.xml', 'interfollow.xml', 'member-many.xml', 'user-many.xml']);
	}

	public function ensureTables() {
		$this->ensureTable(new Interacts($this->site->db));
		$this->ensureTable(new Members($this->site->db));
		$this->ensureTable(new Users($this->site->db));
		$this->ensureTable(new InterFollows($this->site->db));
	}

	public function test_api() {
		$this->site->server = 'https://whatever.edu';
		$course = $this->site->course;
		$course->define('test', 'Test Course');
		$course->add_section('FS18', '799', Section::Normal);

		$members = new Members($this->site->db);
		$member22 = $members->getAsUser(22);
		$member35 = $members->getAsUser(35);
		$admin = $members->getAsUser(10);

		$server = new ServerMock();
		$server->setServer('REQUEST_URI', '/api/interact/interaction');
		$server->setServer('REQUEST_METHOD', 'POST');
		$server->setPost('type', 'Q');
		$server->setPost('assign', 'step1');
		$server->setPost('summary', 'A Question Summary');
		$server->setPost('message', '<p>A Question Message<script>alert()</script></p>');
		$time1 = time() + 1234;

		$api = new InteractApi();

		$this->site->users->user = $member22;
		$ret = $api->apiDispatch($this->site, $server, ['interaction'], [], $time1);

		//print_r($server->email);

		$this->assertContains('New interaction @1 in the CSE999',
			$server->email->log[0]['body']);

		$interacts = new Interacts($this->site->db);
		$sql = <<<SQL
select *
from $interacts->tablename
SQL;

		$stmt = $interacts->pdo->prepare($sql);
		$stmt->execute([]);
		$rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
		$this->assertCount(1, $rows);
		$this->assertEquals(22, $rows[0]['memberid']);

//		$dummy = new DummyMember();
//		$user = $dummy->create(12, 87, Member::STUDENT);
//		$this->site->users->user = $user;
//
//		$assignTag = 'step1';
//		$section = $this->course->get_section_for($user);
//		$assignment = $section->get_assignment($assignTag);
//		$time1 = strtotime('8/28/2018 12:00pm');
//
//		$quiz = $this->createQuiz();
//
//		// Make a session for this quiz
//		$session = new QuizSession($quiz);
//		$session->persist($this->site, $time1);
//
//		$token = $session->token;
//
//		$api = new QuizApi();
//
//
//
//
//
//		print_r($ret);


//
//
//		$token = $session->token;
//		$time1 = time() + 101101;
//		$sessions->create($member87, 'step1', 'quiz1', $session, $time1);
//
//		$sql = <<<SQL
//select * from $sessions->tablename
//SQL;
//
//		$ret = $sessions->pdo->query($sql);
//		$this->assertCount(1, $ret->fetchAll(PDO::FETCH_ASSOC));
//
//		// Can we get it back?
//		$quiz2 = $sessions->get($token);
//		$this->assertEquals(1, $quiz2->quiz->length);
//
//		$sessions->clear($member87, 'step1', 'quiz1');
//
//		$ret = $sessions->pdo->query($sql);
//		$this->assertCount(0, $ret->fetchAll(PDO::FETCH_ASSOC));
//
//		$quiz3 = $sessions->get($token);
//		$this->assertNull($quiz3);
	}

	public function test_send_all() {
		$this->site->server = 'https://whatever.edu';
		$course = $this->site->course;
		$course->define('test', 'Test Course');
		$course->add_section('FS18', '799', Section::Normal);

		$members = new Members($this->site->db);
		$member22 = $members->getAsUser(22);
		$member35 = $members->getAsUser(35);
		$admin = $members->getAsUser(7);

		$server = new ServerMock();
		$server->setServer('REQUEST_URI', '/api/interact/interaction');
		$server->setServer('REQUEST_METHOD', 'POST');
		$server->setPost('type', 'Q');
		$server->setPost('assign', 'step1');
		$server->setPost('summary', 'A Question Summary');
		$server->setPost('message', '<p>A Question Message<script>alert()</script></p>');
		$server->setPost('sendall', true);
		$time1 = time() + 1234;

		$api = new InteractApi();

		$this->site->users->user = $admin;
		$ret = $api->apiDispatch($this->site, $server, ['interaction'], [], $time1);

		$this->assertCount(5, $server->email->log);
	}

}

/// @endcond
