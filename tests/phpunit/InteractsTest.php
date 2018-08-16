<?php
/** @file
 * Unit tests for the class Interacts
 * @cond 
 */
require_once __DIR__ . '/init.php';
require_once __DIR__ . '/cls/InteractDatabaseTestBase.php';

use CL\Users\User;
use CL\Course\Member;
use CL\Interact\Interacts;
use CL\Interact\Interaction;
use CL\Site\Test\ServerMock;
use CL\Course\Members;
use CL\Users\Users;
use CL\Interact\InteractApi;
use CL\Interact\InterFollows;
use CL\Course\Section;
use CL\Interact\Discussions;

class InteractsTest extends InteractDatabaseTestBase {
	/**
	 * @return PHPUnit_Extensions_Database_DataSet_IDataSet
	 */
	public function getDataSet() {
		return $this->dataSets(['interact.xml', 'interfollow.xml', 'discussion.xml',
			'member-many.xml', 'user-many.xml']);
	}

	public function ensureTables() {
		$this->ensureTable(new Interacts($this->site->db));
		$this->ensureTable(new Members($this->site->db));
		$this->ensureTable(new Users($this->site->db));
		$this->ensureTable(new InterFollows($this->site->db));
		$this->ensureTable(new Discussions($this->site->db));
	}

	public function test_summaries() {
		$members = new Members($this->site->db);
		$member22 = $members->getAsUser(22);
		$member35 = $members->getAsUser(35);
		$admin = $members->getAsUser(10);

		$interacts = new Interacts($this->site->db);

		$time1 = time() + 10010;

		$interaction = new Interaction();
		$interaction->user = $member22;
		$interaction->assignTag = 'step1';
		$interaction->sectionTag = null;
		$interaction->type = Interaction::QUESTION;
		$interaction->pin = false;
		$interaction->private = false;
		$interaction->summary = 'Iteraction 1';
		$interaction->message = '<p>This is Interaction 1</p>';
		$interaction->time = $time1;
		$interaction->created = $time1;

		$id = $interacts->add($interaction);

		$result = $interacts->summaries([]);
		$this->assertCount(1, $result);
		$this->assertEquals($id, $result[0]->id);

		$time2 = $time1 + 1000;
		$time = $time2;
		for($i=2; $i<=5; $i++) {
			switch(rand(1, 3)) {
				case 1:
					$user = $member22;
					break;

				case 2:
					$user = $member35;
					break;

				case 3:
					$user = $admin;
					break;
			}

			$this->createInteraction($user, $time, $i);
			$time += 1000;
		}

		$result = $interacts->summaries(['before'=>$time2]);
		$this->assertCount(1, $result);

		$result = $interacts->summaries(['after'=>$time2]);
		$this->assertCount(3, $result);

		// Add a pinned
		$time1a = $time1 + 500;
		$this->createInteraction($admin, $time1a, 'pinned', true);

		$result = $interacts->summaries([]);
		$this->assertCount(6, $result);
		$this->assertTrue($result[0]->pin);

		// Add a private post
		$time2a = $time1 + 1500;
		$this->createInteraction($member22, $time1a, 'pinned', false, true);
		$result = $interacts->summaries([]);
		$this->assertCount(7, $result);

		// Member 22 can see if
		$result = $interacts->summaries(['privateMember'=>22]);
		$this->assertCount(7, $result);

		// Member 35 cannot
		$result = $interacts->summaries(['privateMember'=>35]);
		$this->assertCount(6, $result);
	}

	private function createInteraction(User $user, $time, $number, $pin=false, $private=false) {
		$interacts = new Interacts($this->site->db);

		$interaction = new Interaction();
		$interaction->user = $user;
		$interaction->assignTag = 'step1';
		$interaction->sectionTag = null;
		$interaction->type = Interaction::QUESTION;
		$interaction->pin = $pin;
		$interaction->private = $private;
		$interaction->summary = "Iteraction $number";
		$interaction->message = "<p>This is Interaction $number</p>";
		$interaction->time = $time;
		$interaction->created = $time;

		return $interacts->add($interaction);
	}



//    public function test_count() {
//        global $course;
//        $section = $course->get_section("001");
//
//        $interacts = new \Interact\Interacts($course);
//        $counts = $interacts->counts($section, array('general'), null);
//        $this->assertEquals(0, $counts[\Interact\Interaction::Announcement]);
//        $this->assertEquals(0, $counts[\Interact\Interaction::Question]);
//
//        $this->loadup();
//
//        $counts = $interacts->counts($section, array('general'));
//        $this->assertEquals(1, $counts[\Interact\Interaction::Announcement]);
//        $this->assertEquals(3, $counts[\Interact\Interaction::Question]);
//
//        $counts = $interacts->counts($section, array('general'), 'subsection');
//        $this->assertEquals(1, $counts[\Interact\Interaction::Announcement]);
//        $this->assertEquals(1, $counts[\Interact\Interaction::Question]);
//
//        $counts = $interacts->counts($section, array('general', 'design1'));
//        $this->assertEquals(1, $counts[\Interact\Interaction::Announcement]);
//        $this->assertEquals(4, $counts[\Interact\Interaction::Question]);
//
//        $counts = $interacts->counts($section);
//        $this->assertEquals(1, $counts[\Interact\Interaction::Announcement]);
//        $this->assertEquals(4, $counts[\Interact\Interaction::Question]);
//
//        $counts = $interacts->counts($section, array(4 => 'all'));
//        $this->assertEquals(1, $counts[\Interact\Interaction::Announcement]);
//        $this->assertEquals(4, $counts[\Interact\Interaction::Question]);
//
//        $section = $course->get_section("002");
//        $counts = $interacts->counts($section, array('general'), null);
//        $this->assertEquals(0, $counts[\Interact\Interaction::Announcement]);
//        $this->assertEquals(0, $counts[\Interact\Interaction::Question]);
//
//    }
//
//    public function test_add() {
//        global $course;
//
//        $users = new Users($course);
//        $user = $users->get_user(5);
//
//        $time = strtotime('2015-01-12 14:02:11');
//
//        $interaction = new \Interact\Interaction($course,
//            $user,
//            'general',
//            null,
//            \Interact\Interaction::Announcement,
//            false,
//            false);
//
//        $html = '<p>This is a simple test</p><p class="someclass">Line 2</p>';
//        $interaction->set_html($html);
//
//        $time = time() + 1000;
//
//        $interacts = new \Interact\Interacts($course);
//        $id = $interacts->add($interaction, $time);
//
//        $interaction2 = $interacts->get($id);
//
//        $this->assertEquals($interaction->get_html(), $interaction2->get_html());
//        $this->assertEquals($interaction->get_summary(), $interaction2->get_summary());
//        $this->assertEquals($interaction->is_pin(), $interaction2->is_pin());
//        $this->assertEquals($interaction->is_private(), $interaction2->is_private());
//        $this->assertEquals($interaction->get_user_id(), $interaction2->get_user_id());
//        $this->assertEquals($interaction->get_user_name(), $interaction2->get_user_name());
//        $this->assertEquals($interaction->get_assign_tag(), $interaction2->get_assign_tag());
//        $this->assertEquals($interaction->get_section_tag(), $interaction2->get_section_tag());
//        $this->assertEquals($time, $interaction2->get_time());
//    }
//
//    public function test_iterator() {
//        $this->loadup();
//
//        global $course;
//
//        $interacts = new \Interact\Interacts($course);
//
//        /*
//         * Tests
//         */
//        $users = new Users($course);
//        $user5 = $users->get_user(5);
//        $user7 = $users->get_user(7);
//
//        $interacts->select($user5, array("general"), null, null);
//        $current = $interacts->get_current();
//        $this->assertNotNull($current);
//
//        $this->assertEquals("Question 2", $current->get_summary());
//
//        $this->assertTrue($interacts->advance());
//
//        $current = $interacts->get_current();
//        $this->assertNotNull($current);
//
//        $this->assertEquals("Question 3", $current->get_summary());
//
//        $this->assertTrue($interacts->advance());
//
//        $current = $interacts->get_current();
//        $this->assertNotNull($current);
//
//        $this->assertEquals("Question 1", $current->get_summary());
//
//        $this->assertFalse($interacts->advance());
//        $this->assertNull($interacts->get_current());
//
//        /*
//         * See if User 7 sees his post
//         */
//        $interacts->select($user7, array("general"), null, null);
//        $current = $interacts->get_current();
//        $this->assertNotNull($current);
//        $this->assertEquals("Question 2", $current->get_summary());
//
//        $this->assertTrue($interacts->advance());
//        $current = $interacts->get_current();
//        $this->assertNotNull($current);
//        $this->assertEquals("Question 4", $current->get_summary());
//
//        /*
//         * Search for multiple categories
//         */
//        $interacts->select($user5, array("general", "design1"), null, null);
//        $current = $interacts->get_current();
//        $this->assertNotNull($current);
//        $this->assertEquals("Question 2", $current->get_summary());
//
//        $this->assertTrue($interacts->advance());
//        $current = $interacts->get_current();
//        $this->assertEquals("Question 5", $current->get_summary());
//
//        /*
//         * Search for all
//         */
//        $interacts->select($user5);
//        $current = $interacts->get_current();
//        $this->assertNotNull($current);
//        $this->assertEquals("Question 2", $current->get_summary());
//
//        $this->assertTrue($interacts->advance());
//        $current = $interacts->get_current();
//        $this->assertEquals("Question 5", $current->get_summary());
//
//        $interacts->select($user5, array(99 => 'all'));
//        $current = $interacts->get_current();
//        $this->assertNotNull($current);
//        $this->assertEquals("Question 2", $current->get_summary());
//
//        $this->assertTrue($interacts->advance());
//        $current = $interacts->get_current();
//        $this->assertEquals("Question 5", $current->get_summary());
//    }
//
//    private function loadup() {
//        global $course;
//
//        $interacts = new \Interact\Interacts($course);
//
//        $users = new Users($course);
//        $user5 = $users->get_user(5);
//        $user7 = $users->get_user(7);
//
//        $time1 = strtotime('2015-01-12 14:02:11');
//        $time2 = $time1 + 10001;
//        $time3 = $time2 + 5667;
//        $time4 = $time3 + 155;
//        $time5 = $time4 + 155;
//
//        /*
//         * Normal post
//         */
//        $interaction = new \Interact\Interaction($course,
//            $user5,
//            'general',
//            'subsection',
//            \Interact\Interaction::Question,
//            false,
//            false);
//
//        $html = '<p>This is a simple test</p><p class="someclass">Line 2</p>';
//        $interaction->set_html($html);
//        $interaction->set_summary("Question 1");
//
//        $interacts->add($interaction, $time1);
//
//        /*
//         * Pinned
//         */
//        $interaction = new \Interact\Interaction($course,
//            $user5,
//            'general',
//            'subsection',
//            \Interact\Interaction::Announcement,
//            true,
//            false);
//        $interaction->set_html($html);
//        $interaction->set_summary("Question 2");
//
//        $interacts->add($interaction, $time2);
//
//        /*
//         * Normal
//         */
//        $interaction = new \Interact\Interaction($course,
//            $user5,
//            'general',
//            null,
//            \Interact\Interaction::Question,
//            false,
//            false);
//        $interaction->set_html($html);
//        $interaction->set_summary("Question 3");
//
//        $interacts->add($interaction, $time3);
//
//
//        /*
//         * private/user 7
//         */
//        $interaction = new \Interact\Interaction($course,
//            $user7,
//            'general',
//            null,
//            \Interact\Interaction::Question,
//            false,
//            true);
//        $interaction->set_html($html);
//        $interaction->set_summary("Question 4");
//
//        $interacts->add($interaction, $time4);
//
//        /*
//         * Different Category
//         */
//        $interaction = new \Interact\Interaction($course,
//            $user5,
//            'design1',
//            null,
//            \Interact\Interaction::Question,
//            false,
//            false);
//        $interaction->set_html($html);
//        $interaction->set_summary("Question 5");
//
//        $interacts->add($interaction, $time5);
//
//
//    }
}

/// @endcond
