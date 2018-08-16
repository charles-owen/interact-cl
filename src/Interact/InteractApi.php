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
	/// Maximum summary replies per query
	const MAX_SUMMARIES = 100;

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
			// /api/interact/summaries
			case 'summaries':
				return $this->summaries($site, $user, $server);

			// /api/interact/interaction
			// /api/interact/interaction/:id
			case 'interaction':
				return $this->interaction($site, $user, $server, $params, $time);

			// /api/interact/discuss/:id
			case 'discuss':
				return $this->discuss($site, $user, $server, $params, $time);

			// /api/interact/email
			// /api/interact/email/:memberid
			case 'email':
				return $this->email($site, $user, $server, $params, $time);

			// /api/interact/tables
			case 'tables':
				return $this->tables($site, $server, new InteractTables($site->db));
		}

		throw new APIException("Invalid API Path", APIException::INVALID_API_PATH);
	}


	/**
	 * /api/interact/discuss/:id
	 * POST creates a new discussion on an interaction
	 *
	 * @param Site $site
	 * @param User $user
	 * @param Server $server
	 * @param array $params
	 * @param $time
	 * @return JsonAPI
	 * @throws APIException
	 */
	private function discuss(Site $site, User $user, Server $server, array $params, $time) {
		$interacts = new Interacts($site->db);

		$post = $server->post;
		$this->ensure($post, ['message']);

		if(count($params) < 2) {
			throw new APIException("Invalid API Usage", APIException::INVALID_API_USAGE);
		}

		$interactId = +$params[1];

		// Get this interaction
		$interaction = $interacts->get($interactId);
		if($interaction === null) {
			throw new APIException('Interaction is no longer available');
		}

		if(!$user->atLeast(Member::STAFF)) {
			if($interaction->private && $interaction->user->id !== $user->id) {
				throw new APIException('Not authorized to discuss this interaction');
			}
		}

		$message = Interact::sanitize($post['message']);

//		/* Indicate the user is no longer editing an interaction */
//		$interactive = new InterActive($this->course);
//		$interactive->delete($this->user);

		if(strlen($message) === 0) {
			throw new APIException("You must provide some discussion!");
		}

		$discussion = new Discussion();
		$discussion->interactId = $interactId;
		$discussion->user = $user;
		$discussion->time = $time;
		$discussion->message = $message;

		$discussions = new Discussions($site->db);
		$discussions->add($discussion);

		// Get the discussions for this interaction
		$interaction->discussions = $discussions->getFor($interactId);

		/*
		 * User is following this interaction
		 */
		$interFollows = new InterFollows($site->db);
		$interFollows->setFollowing($user->member->id, $interactId, InterFollows::FOLLOWING);

		$email = new InteractEmail($site, $user, $server->email);
		$email->newDiscussion($interaction, $discussion);

		$json = new JsonAPI();
		$json->addData('interaction', $interaction->id, $interaction->data($site, $user));
		return $json;
	}



	/**
	 * /api/interact/interaction
	 * /api/interact/interaction/:id
	 * POST creates a new interaction or edits an existing interaction
	 *
	 * @param Site $site
	 * @param User $user
	 * @param Server $server
	 * @param array $params
	 * @param $time
	 * @return JsonAPI
	 * @throws APIException
	 */
	private function interaction(Site $site, User $user, Server $server, array $params, $time) {
		$interacts = new Interacts($site->db);

		$interactId = 0;
		if(count($params) > 1) {
			$interactId = +$params[1];

			// Get this interaction
			$interaction = $interacts->get($interactId);
			if($interaction === null) {
				throw new APIException('Interaction does not exist');
			}

			if(!$user->atLeast(Member::STAFF)) {
				if($interaction->private && $interaction->user->id !== $user->id) {
					throw new APIException('Not authorized to view this interaction');
				}
			}

			// Get the discussions
			$discussions = new Discussions($site->db);
			$interaction->discussions = $discussions->getFor($interactId);
		}

		if($server->requestMethod === 'POST') {
			if(count($params) > 1) {
				// Until we get editing working
				throw new APIException("Invalid API Usage", APIException::INVALID_API_USAGE);
			}

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
			$sendAll = isset($post['sendall']) && $user->atLeast(Member::TA);
			$interaction->time = $time;
			$interaction->created = $time;

			$id = $interacts->add($interaction);
			if($id === 0) {
				throw new APIException('Error adding interaction to database');
			}

			/*
			 * User is following this post
			 */
			$interFollows = new InterFollows($site->db);
			$interFollows->setFollowing($user->member->id, $id, InterFollows::FOLLOWING);

			// Set all staff who currently are receiving email as following
			$members = new Members($site->db);
			$staff = $members->query(['atLeast'=>Member::STAFF, 'metadata'=>true]);
			foreach($staff as $staffUser) {
				$receiving = $staffUser->member->meta->get(Interact::INTERACT_CATEGORY, Interact::RECEIVE_MAIL, $staffUser->atLeast(Member::TA));
				if($receiving) {
					$interFollows->setFollowing($staffUser->member->id, $id, InterFollows::FOLLOWING);
				}
			}

			$email = new InteractEmail($site, $user, $server->email);
			$email->newInteraction($interaction, $sendAll);

			// Restore autoanswer system

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

		}

		$json = new JsonAPI();
		$json->addData('interaction', $interaction->id, $interaction->data($site, $user));
		return $json;
	}


	/**
	 * /api/interact/summaries
	 *
	 * GET gets summaries of interactions
	 *
	 * @param Site $site
	 * @param User $user
	 * @param Server $server
	 * @return JsonAPI
	 * @throws APIException
	 */
	private function summaries(Site $site, User $user, Server $server) {
		$interacts = new Interacts($site->db);

		$get = $server->get;

		$params = [
			'limit'=>self::MAX_SUMMARIES + 1,
			'semester'=>$user->member->semester,
			'section'=>$user->member->sectionId
		];

		if(!empty($get['before'])) {
			$params['before'] = +$get['before'];
		}

		if(!$user->atLeast(Member::STAFF)) {
			$params['privateMember'] = $user->member->id;
		}

		$summaries = $interacts->summaries($params);

		$data = [];
		for($i=0; $i<count($summaries) && $i<self::MAX_SUMMARIES; $i++) {
			$data[] = $summaries[$i]->summaryData($site, $user);
		}

		if(count($summaries) > self::MAX_SUMMARIES) {
			$data[] = ['more'=>true];
		}

		$json = new JsonAPI();
		$json->addData('interact-summaries', 0, $data);
		return $json;
	}


	/**
	 * /api/interact/email
	 * /api/interact/email/:memberid
	 *
	 * GET will get all staff interact email statuses
	 * POST with no member ID set's current user email status
	 * POST with memberid set's member email status
	 *
	 * @param Site $site
	 * @param User $user
	 * @param Server $server
	 * @param array $params
	 * @param $time
	 * @return JsonAPI
	 * @throws APIException
	 */
	private function email(Site $site, User $user, Server $server, array $params, $time) {
		$this->isUser($site, Member::STAFF);

		$members = new Members($site->db);


		if($server->requestMethod === 'POST') {
			$post = $server->post;
			$this->ensure($post, 'email');

			if (count($params) > 1) {
				$this->isUser($site, Member::TA);

				$memberId = +$params[1];
			} else {
				$memberId = $user->member->id;
			}

			$member = $members->getAsUser($memberId);
			if ($member === null) {
				throw new APIException("Member does not exist");
			}

			$member->member->meta->set(Interact::INTERACT_CATEGORY, Interact::RECEIVE_MAIL, $post['email']);
			$members->writeMetaData($member->member);
		}

		$ret = [];

		if($user->atLeast(Member::TA)) {
			$staff = $members->query(['atLeast'=>Member::STAFF, 'metadata'=>true]);

			foreach($staff as $staffUser) {
				$ret[] = ['user'=>$staffUser->data(),
					'email'=>$staffUser->member->meta->get(Interact::INTERACT_CATEGORY, Interact::RECEIVE_MAIL, $staffUser->atLeast(Member::TA))];
			}
		} else {
			$ret[] = ['user'=>$user->data(),
				'email'=>$user->member->meta->get(Interact::INTERACT_CATEGORY, Interact::RECEIVE_MAIL, $user->atLeast(Member::TA))];
		}

		$json = new JsonAPI();
		$json->addData('interact-email', 0, $ret);
		return $json;
	}
}