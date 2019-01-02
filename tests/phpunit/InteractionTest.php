<?php
/** @file
 * @brief Unit tests for the class Interaction
 * @cond
 */
require_once __DIR__ . '/init.php';

use CL\Site\Test\TestBase;
use CL\Course\Member;
use CL\Course\Test\DummyMember;
use CL\Interact\Interaction;

class InteractionTest extends TestBase {


	public function __construct() {
		parent::__construct(__DIR__);
	}

	private function make_interaction() {
		// Create a dummy member
		$dummy = new DummyMember();
		$member22 = $dummy->create(12, 22, Member::STUDENT);

		$time = '2015-01-12 14:02:11';

		$interaction = new Interaction([
			'id'=>23,
			'user_id'=>$member22->id,
			'member_id'=>$member22->member->id,
			'member_userid'=>$member22->id,
			'member_semester'=>$member22->member->semester,
			'member_section'=>$member22->member->sectionId,
			'member_role'=>$member22->member->role,
			'member_created'=>$time,
			'created'=>$time,
			'time'=>$time,
			'assigntag'=>'general',
			'sectiontag'=>null,
			'type'=>Interaction::ANNOUNCEMENT,
			'private'=>false,
			'summary'=>'summary',
			'metadata'=>null,
			'pin'=>0
		]);

		return $interaction;
	}

	public function test_construct() {
		$interaction = $this->make_interaction();

		$this->assertInstanceOf('\CL\Interact\Interaction', $interaction);

		$this->assertEquals('general', $interaction->assignTag);

		$interaction->summary = 'Example summary';
		$this->assertEquals('Example summary', $interaction->summary);
	}



//	public function test_update() {
//		$interaction = $this->make_interaction();
//
//		$this->assertFalse($interaction->pin);
//		$this->assertFalse($interaction->private);
//
//		$post = array('pin' => 1, 'type' => "Question");
//
//		$summary = "New Summary";
//		$html = "<p>New HTML</p>";
//		$interaction->update($summary, $html, $post);
//
//		$this->assertTrue($interaction->pin);
//		$this->assertFalse($interaction->private);
//
//		$this->assertEquals($summary, $interaction->summary);
//		$this->assertEquals($html, $interaction->html);
//		$this->assertEquals(Interaction::Question, $interaction->type);
//
//		$post = array('private' => 1, 'type' => "Announcement");
//		$interaction->update($summary, $html, $post);
//
//		$this->assertEquals($summary, $interaction->summary);
//		$this->assertEquals($html, $interaction->html);
//		$this->assertEquals(Interaction::Announcement, $interaction->type);
//
//		$this->assertFalse($interaction->pin);
//		$this->assertTrue($interaction->private);
//	}
//
//	public function test_summarize() {
//		$interaction = $this->make_interaction();
//
//		$interaction->html = '<p>This is some HTML <a href="">with a link</a>.</p>';
//		$summarize = $interaction->summarize();
//
//		$this->assertEquals('This is some HTML with a link.', $summarize);
//	}
}

/// @endcond
