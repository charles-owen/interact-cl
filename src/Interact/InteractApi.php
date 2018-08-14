<?php
/**
 * @file
 * API Resource for /api/interact
 */
namespace CL\Interact;

use CL\Site\Api\JsonAPI;
use CL\Site\Site;
use CL\Site\System\Server;
use CL\Site\Api\APIException;
use CL\Users\User;
use CL\Course\Member;
use CL\Course\Members;


/**
 * API Resource for /api/interact
 */
class InteractApi extends \CL\Users\Api\Resource {
	/**
	 * InteractApi constructor.
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Dispatch the API routing.
	 * @param Site $site The Site object
	 * @param Server $server The Server object
	 * @param array $params Parameters from the router
	 * @param array $properties Properties extracted from the route
	 * @param int $time Current time
	 * @return JsonAPI response
	 * @throws APIException If an error occurs.
	 */
	public function dispatch(Site $site, Server $server, array $params, array $properties, $time) {
		$user = $this->isUser($site, Member::STUDENT);

		if(count($params) < 1) {
			throw new APIException("Invalid API Path", APIException::INVALID_API_PATH);
		}

		switch($params[0]) {
			case 'interaction':
				return $this->interaction($site, $user, $server, $params, $time);

			// /api/interact/tables
			case 'tables':
				return $this->tables($site, $server, new InteractTables($site->db));
		}

		throw new APIException("Invalid API Path", APIException::INVALID_API_PATH);
	}

	private function interaction(Site $site, User $user, Server $server, array $params, $time) {

		if($server->requestMethod === 'POST') {
			$post = $server->post;
			$keys = ['type', 'assign', 'summary', 'message'];
			$this->ensure($post, $keys);

			$interaction = new Interaction();
			$interaction->user = $user;
			$interaction->assignTag = $post['assign'];
			$interaction->sectionTag = !empty($post['section']) ? $post['section'] : null;
			$interaction->type = $post['type'];
			$interaction->pin = isset($post['pin']);
			$interaction->private = isset($post['private']);
			$interaction->summary = Interact::sanitize($post['summary']);
			$interaction->message = Interact::sanitize($post['message']);
			$interaction->time = $time;
			$interaction->created = $time;

			$interacts = new Interacts($site->db);

			$id = $interacts->add($interaction);
			if($id === 0) {
				throw new APIException('Error adding interaction to database');
			}

			/*
			 * User is following this post
			 */
			$interFollows = new InterFollows($site->db);
			$interFollows->setFollowing($user->member->id, $id, InterFollows::FOLLOWING);

//		$email = new InteractEmail($this->course, $this->user, $this->email);
//
//		if($forceFollow) {
//			// Set all users following
//			$interFollows->set_all_following($this->user->get_section(), $id);
//
//			$email->new_interaction($interaction, true, $sendAll);
//		} else {
//			// Set all staff who currently are receiving email as following
//			$users = new \Users($this->course);
//			foreach($users->get_users(null, null, true) as $staffUser) {
//				if($staffUser->get_preferences()->get(Interact::RECEIVE_MAIL, false)) {
//					$interFollows->set_following($staffUser->id, $id, InterFollows::FOLLOWING);
//				}
//			}
//
//			$email->new_interaction($interaction, false, $sendAll);
//		}
//
//		if($interaction->get_type() === Interaction::Question) {
//			//
//			// Autoanswer system
//			//
//			$answerer = new \AutoAnswer\Answerer($this->course, $this->user);
//			$answer = $answerer->lookup($html, $assignTag, $sectionTag);
//			if($answer !== null) {
//				$users = new \Users($this->course);
//				$answeruser = $users->get_user_by_userid($answerer->answerer);
//				if($answeruser !== null) {
//					$discussion = new Discussion($this->course,
//						$answeruser,
//						$interaction->get_id(),
//						$this->time);
//
//					$discussion->set_html($answer['text']);
//
//					$discussions = new Discussions($this->course);
//					$discussionId = $discussions->add($discussion, $this->time);
//					$discussion->set_id($discussionId);
//
//					$email->new_discussion($interaction, $discussion);
//				}
//			}
//		}
//
//		$view = new \InteractView($this->course, $this->user, array($assignTag), $sectionTag);
//		$result['ok']  = true;
//		$result['interaction'] = $interaction->to_array($this->user);
//
//		return json_encode($result);
		}

		$json = new JsonAPI();
		return $json;
	}
}