<?php
/**
 * @file
 * @brief Class that manages Interact! system email
 */

namespace Interact;

/**
 * @brief Class that manages Interact! system email
 */
class InteractEmail {
	/**
	 * Constructor
	 * @param \Course $course Course object
	 * @param \User $user The user who is interacting
	 * @param \Email|null $email Optional Email object (for testing)
	 */
	public function __construct(\Course $course, \User $user, \Email $email=null) {
		$this->course = $course;
		$this->user = $user;

		if($email === null) {
			$this->email = new \Email($course);
		} else {
			$this->email = $email;
		}
	}

	/**
	 * Handle a new interaction that has been added.
	 *
	 * This is also called when all users are forced to follow an existing interaction
	 * @param Interaction $interaction The new Interaction that has been added
	 * @param bool $forceFollow If true, force all users to follow the interaction
	 */
	public function new_interaction(Interaction $interaction, $forceFollow=false, $sendAll=false) {
		$courseName = $this->course->get_name();
		$id = $interaction->get_id();
		$summary = $interaction->get_summary();
		$html = $interaction->get_html();

		$view = new \InteractView($this->course, $this->user);
		$attribution = $view->attribution($interaction, true);
		$url = $this->course->get_server() . $this->course->get_libroot() . '/interact/?i=' . $id;
		$announce = $forceFollow ? 'You are now following interaction' : 'New interaction';

		$msg = <<<MSG
<p>$announce @$id in the $courseName <a href="$url" target="INTERACTION">Interact! system</a>.</p>
<p>$summary<br />
$attribution</p>
$html
<p class="notice">You are receiving this message because you are following this interaction.
You will automatically follow all interactions you create or contribute to the
discussion in and course staff can force all students in the course to follow
an interaction. You can turn off following in the Interact! system.</p>
MSG;

		if($sendAll) {
			$msg .= <<<MSG
<p>Course staff has sent this interaction to all course participants. Future discussions
will not be automatically sent unless you choose to follow the interaction.</p>
MSG;
		} else if($forceFollow) {
			$msg .= <<<MSG
<p>Course staff has set this interaction to be followed by all course participants.</p>
MSG;
		}

		$userName = $this->user->displayname;
		$staff = <<<MSG
<p>@$id created by $userName</p>
MSG;

		$this->notify_followers($interaction, $msg, $staff, $sendAll);
	}

	/**
	 * Handle a new discussion added to an existing interaction
	 * @param Interaction $interaction The interaction we are added to
	 * @param Discussion $discussion The new discussion item
	 */
	public function new_discussion(Interaction $interaction, Discussion $discussion) {
		$courseName = $this->course->get_name();
		$id = $interaction->get_id();
		$summary = $interaction->get_summary();
		$html = $interaction->get_html();

		$view = new \InteractView($this->course, $this->user);
		$attribution = $view->attribution($interaction, true);
		$url = $this->course->get_server() . $this->course->get_libroot() . '/interact/?i=' . $id;

		$discussHtml = $discussion->get_html();

		$msg = <<<MSG
<p>New interaction @$id in the $courseName <a href="$url">Interact! system</a>.</p>
<p>$summary<br />
$attribution</p>
<p><em>New discussion contribution:</em></p>
$discussHtml
<p><em>Interaction:</em></p>
$html
<p class="notice">You are receiving this message because you are following this interaction.
You will automatically follow all interactions you create or contribute to the
discussion in and course staff can force all students in the course to follow
an interaction. You can turn off following in the Interact! system.</p>
MSG;

		$this->notify_followers($interaction,  $msg, "");
	}

	private function notify_followers(Interaction $interaction, $message, $staffMessage, $sendAll=false) {
		$interFollows = new InterFollows($this->course);
		$followers = $interFollows->get_followers($interaction->get_id());
		$courseName = $this->course->get_name();
		$summary = $interaction->get_summary();

		$interactDir = __DIR__ . '/../../interact';
		$style = file_get_contents($interactDir . '/ck_interact_content.css');
		$head = <<<HTML
<style>$style</style>
<div class="interact-content">
HTML;

		$tail = <<<HTML
</div>
HTML;

		set_time_limit(120);

		if($sendAll) {
			$users = new \Users($this->course);
			$values = '';
			foreach($users->get_users($this->user->get_section()) as $user) {
				// Ignore any guest users
				if ($user->is_guest()) {
					continue;
				}

				$send = $message;
				if($user->at_least(\User::STAFF)) {
				    $send .= $staffMessage;
                }

				$this->email->send($user, "$courseName Interact!: " . $summary, $head . $send . $tail);
			}

		} else {
			foreach($followers as $follower) {
				// Don't send to the poster...
				if($follower->get_id() === $this->user->get_id()) {
					continue;
				}

                $send = $message;
                if($follower->at_least(\User::STAFF)) {
                    $send .= $staffMessage;
                }

				$this->email->send($follower, "$courseName Interact!: " . $summary, $head . $send . $tail);
			}
		}


	}

	private $course;
	private $user;
	private $email;
}