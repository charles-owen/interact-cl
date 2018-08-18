<?php
/**
 * @file
 * Class that manages Interact! system email
 */

namespace CL\Interact;

use CL\Site\Site;
use CL\Users\User;
use CL\Site\Email;
use CL\Course\Members;
use CL\Course\Member;

/**
 * Class that manages Interact! system email
 */
class InteractEmail {
	/**
	 * Constructor

	 */
	public function __construct(Site $site, User $user, Email $email) {
		$this->site = $site;
		$this->user = $user;
		$this->email = $email;
	}

	/**
	 * Handle a new interaction that has been added.
	 *
	 * This is also called when all users are forced to follow an existing interaction
	 * @param Interaction $interaction The new Interaction that has been added
	 * @param bool $sendAll If true, send out to all users
	 */
	public function newInteraction(Interaction $interaction, $sendAll=false) {
		$courseName = $this->site->course->name;
		$id = $interaction->id;
		$summary = $interaction->summary;
		$message = $interaction->message;

		$attribution = $interaction->attribution($this->site, $this->user, true);
		$url = $this->site->server . $this->site->root . '/cl/interact/' . $id;
		$announce = 'New interaction';

		$msg = <<<MSG
<p>$announce @$id in the $courseName <a href="$url" target="INTERACT">Interact! system</a>.</p>
<p>$summary<br />
$attribution</p>
$message
<p class="notice">You are receiving this message because you are following this interaction.
You will automatically follow all interactions you create or contribute to. You can turn off 
following in the Interact! system.</p>
MSG;

		if($sendAll) {
			$msg .= <<<MSG
<p>Course staff has sent this interaction to all course participants. Future discussions
will not be automatically sent unless you choose to follow the interaction.</p>
MSG;
		}

		$userName = $this->user->displayName;
		$staff = <<<MSG
<p>@$id created by $userName</p>
MSG;

		$this->notifyFollowers($interaction, $msg, $staff, $sendAll);
	}


	/**
	 * Handle a new discussion added to an existing interaction
	 * @param Interaction $interaction The interaction we are added to
	 * @param Discussion $discussion The new discussion item
	 */
	public function newDiscussion(Interaction $interaction, Discussion $discussion) {
		$courseName = $this->site->course->name;
		$id = $interaction->id;
		$summary = $interaction->summary;
		$interactMessage = $interaction->message;

		$attribution = $interaction->attribution($this->site, $this->user, true);
		$url = $this->site->server . $this->site->root . '/cl/interact/' . $id;

		$discussMessage = $discussion->message;

		$msg = <<<MSG
<p>New interaction @$id in the $courseName <a href="$url">Interact! system</a>.</p>
<p>$summary<br />
$attribution</p>
<p><em>New discussion contribution:</em></p>
$discussMessage
<p><em>Interaction:</em></p>
$interactMessage
<p class="notice">You are receiving this message because you are following this interaction.
You will automatically follow all interactions you create or contribute to the
discussion in and course staff can force all students in the course to follow
an interaction. You can turn off following in the Interact! system.</p>
MSG;

		$interacterName = $interaction->user->displayName;
		$discusserName = $discussion->user->displayName;
		$staff = <<<MSG
<p>@$id created by $interacterName<br>
Discussion by $discusserName</p>
MSG;

		$this->notifyFollowers($interaction, $msg, $staff, false);
	}

	private function notifyFollowers(Interaction $interaction, $message, $staffMessage, $sendAll=false) {
		$interFollows = new InterFollows($this->site->db);
		$followers = $interFollows->getFollowers($interaction->id);

		$course = $this->site->course;
		$courseName = $course->name;
		$summary = $interaction->summary;

		$interactDir = __DIR__ . '/../..';
		$style = file_get_contents($interactDir . '/interact_content.css');
		$head = <<<HTML
<style>$style</style>
<div class="interact-content">
HTML;

		$tail = <<<HTML
</div>
HTML;

		set_time_limit(120);

		if($sendAll) {
			$members = new Members($this->site->db);
			$values = '';
			$query = $members->query(['semester'=>$this->user->member->semester,
				'section'=>$this->user->member->sectionId]);
			foreach($query as $user) {
				// Ignore any guest users or drops
				if(!$user->atLeast(Member::STUDENT)) {
					continue;
				}

				$send = $message;
				if($user->atLeast(Member::STAFF)) {
				    $send .= $staffMessage;
                }

				$this->email->send($this->site, $user->email, $user->displayName,
					"$courseName Interact!: " . $summary, $head . $send . $tail);
			}

		} else {
			foreach($followers as $follower) {
				// Don't send to the poster...
				if($follower->member->id === $this->user->member->id) {
					continue;
				}

                $send = $message;
                if($follower->atLeast(Member::STAFF)) {
                    $send .= $staffMessage;
                }

				$this->email->send($this->site, $follower->email, $follower->displayName,
					"$courseName Interact!: " . $summary, $head . $send . $tail);
			}
		}

	}

	private $site;
	private $user;
	private $email;
}