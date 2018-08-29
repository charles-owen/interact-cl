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
			// /api/interact/summaries/stats
			case 'summaries':
				return $this->summaries($site, $user, $server, $params);

			// /api/interact/interaction
			// /api/interact/interaction/:id
			// /api/interact/interaction/:id/delete
			// /api/interact/interaction/:id/edit
			// /api/interact/interaction/:id/follow
			case 'interaction':
				return $this->interaction($site, $user, $server, $params, $time);

			// /api/interact/discuss/:interactid

			case 'discuss':
				return $this->discuss($site, $user, $server, $params, $time);

			// /api/interact/discussion/:discussionid/delete
			// /api/interact/discussion/:discussionid/edit
			// /api/interact/discussion/:discussionid/endorse
			case 'discussion':
				return $this->discussion($site, $user, $server, $params, $time);

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
	 * /api/interact/discussion/:discussionid/delete
	 * /api/interact/discussion/:discussionid/edit
	 * /api/interact/discussion/:discussionid/endorse
	 *
	 * Modifications to discussion items.
	 *
	 * @param Site $site
	 * @param User $user
	 * @param Server $server
	 * @param array $params
	 * @param $time
	 * @return JsonAPI
	 * @throws APIException
	 */
	private function discussion(Site $site, User $user, Server $server, array $params, $time)
	{
		if($server->requestMethod !== 'POST') {
			throw new APIException("Invalid API Usage", APIException::INVALID_API_USAGE);
		}

		$interacts = new Interacts($site->db);
		$discussions = new Discussions($site->db);

		if(count($params) < 3) {
			throw new APIException("Invalid API Usage", APIException::INVALID_API_USAGE);
		}

		$discussionId = +$params[1];
		$discussion = $discussions->get($discussionId);
		if ($discussion === null) {
			throw new APIException("Discussion item no longer exists");
		}

		$interactId = $discussion->interactId;

		$cmd = $params[2];

		if ($cmd === 'delete') {
			if(!$user->atLeast(Member::STAFF)) {
				if($discussion->user->id != $user->id) {
					throw new APIException('Not authorized to delete this discussion item');
				}
			}

			$discussions->delete($discussion);
		} else if($cmd === 'edit') {
			if(!$user->atLeast(Member::STAFF)) {
				if($discussion->user->id != $user->id) {
					throw new APIException('Not authorized to edit this discussion item');
				}
			}

			$post = $server->post;
			$this->ensure($post, 'message');

			$discussion->message = Interact::sanitize($post['message']);
			$history = $discussion->meta->get('public', Interact::HISTORY, []);
			$history[] = [
				'op'=>'edit',
				'time'=>$time,
				'user'=>$user->id,
				'member'=>$user->member->id,
				'name'=>$user->displayName,
				'role'=>$user->role
			];
			$discussion->meta->set('public', Interact::HISTORY, $history);

			$discussions->update($discussion);
		} else if($cmd === 'endorse') {
			if(!$user->atLeast(Member::STAFF)) {
				throw new APIException('Not authorized to edit this discussion item');
			}

			$endorsement = $discussion->meta->get(Interact::ENDORSEMENTS, $user->member->id, null);
			if($endorsement === null) {
				$endorsement = [
					'name'=>$user->displayName,
					'role'=>$user->role,
					'time'=>$time
				];

				$discussion->meta->set(Interact::ENDORSEMENTS, $user->member->id, $endorsement);
			} else {
				$discussion->meta->set(Interact::ENDORSEMENTS, $user->member->id, null);
			}

			$discussions->update($discussion);
		} else {
			throw new APIException("Invalid API Path", APIException::INVALID_API_PATH);
		}

		$interaction = $this->getInteraction($site, $user, $interactId);

		$json = new JsonAPI();
		$json->addData('interaction', $interaction->id, $interaction->data($site, $user));
		return $json;
	}

	/**
	 * /api/interact/discuss/:interactid
	 *
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
		$discussions = new Discussions($site->db);

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
			if($interaction->private && $interaction->user->id != $user->id) {
				throw new APIException('Not authorized to discuss this interaction');
			}
		}

		$post = $server->post;
		$this->ensure($post, ['message']);

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

		$discussions->add($discussion);

		// Interaction time is set to the time of the discussion item
		$interaction->time = $time;
		$interacts->update($interaction);

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
	 * /api/interact/interaction/:id/delete
	 * /api/interact/interaction/:id/follow
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
		$interaction = null;
		if(count($params) > 1) {
			$interactId = +$params[1];
			$interaction = $this->getInteraction($site, $user, $interactId);
		}

		if($server->requestMethod === 'POST') {
			if($interaction !== null && count($params) === 3 && $params[2] === 'delete') {
				if(!$user->atLeast(Member::STAFF)) {
					if($interaction->user->id != $user->id) {
						throw new APIException('Not authorized to delete this interaction');
					}
				}

				$interacts->delete($interaction);
				return new JsonAPI();
			}

			if($interaction !== null && count($params) === 3 && $params[2] === 'follow') {
				// Toggle following
				$interFollows = new InterFollows($site->db);
				$following = $interFollows->getFollowing($user->member->id, $interaction->id);
				switch($following) {
					case InterFollows::FOLLOWING:
						$interFollows->setFollowing($user->member->id, $interaction->id, InterFollows::NOTFOLLOWING);
						break;

					case InterFollows::NOTFOLLOWING:
						$interFollows->setFollowing($user->member->id, $interaction->id, InterFollows::FOLLOWING);
						break;
				}
			} else if($interaction !== null && count($params) === 3 && $params[2] === 'edit') {
				//
				// Editing an interaction
				//
				$post = $server->post;
				$keys = ['type', 'assign', 'summary', 'message'];
				$this->ensure($post, $keys);

				$interaction->assignTag = $post['assign'];
				$interaction->sectionTag = !empty($post['section']) ? $post['section'] : null;
				$interaction->type = $post['type'];
				$interaction->pin = isset($post['pin']);
				$interaction->private = isset($post['private']);
				$interaction->summary = Interact::sanitize($post['summary']);
				$interaction->message = Interact::sanitize($post['message']);
				$interaction->time = $time;

				$history = $interaction->meta->get('public', Interact::HISTORY, []);
				$history[] = [
					'op'=>'edit',
					'time'=>$time,
					'user'=>$user->id,
					'member'=>$user->member->id,
					'name'=>$user->displayName,
					'role'=>$user->role
				];
				$interaction->meta->set('public', Interact::HISTORY, $history);

				$interacts->update($interaction);

			} else if($interaction === null) {
				// New posting!
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

				// TODO: Restore autoanswer system

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

		}

		$json = new JsonAPI();
		$json->addData('interaction', $interaction->id, $interaction->data($site, $user));
		return $json;
	}


	/**
	 * Get an interaction and discussions.
	 * @param Site $site
	 * @param User $user
	 * @param $interactId
	 * @return Interaction|null
	 * @throws APIException
	 */
	private function getInteraction(Site $site, User $user, $interactId) {
		$interacts = new Interacts($site->db);

		// Get this interaction
		$interaction = $interacts->get($interactId);
		if($interaction === null) {
			throw new APIException('Interaction does not exist');
		}

		if(!$user->atLeast(Member::STAFF)) {
			if($interaction->private && $interaction->user->id != $user->id) {
				throw new APIException('Not authorized to view this interaction');
			}
		}

		// Get the discussions
		$discussions = new Discussions($site->db);
		$interaction->discussions = $discussions->getFor($interactId);

		return $interaction;
	}


	/**
	 * /api/interact/summaries
	 *
	 * GET gets summaries of interactions
	 *
	 * @param Site $site
	 * @param User $user
	 * @param Server $server
	 * @param array $params
	 * @return JsonAPI
	 * @throws APIException
	 */
	private function summaries(Site $site, User $user, Server $server, array $params) {
		$interacts = new Interacts($site->db);
		$get = $server->get;

		$query = [
			'semester'=>$user->member->semester,
			'section'=>$user->member->sectionId
		];

		if(!empty($get['assign'])) {
			$query['assignTag'] = $get['assign'];
		}

		if(!empty($get['section'])) {
			$query['sectionTag'] = $get['section'];
		}

		if(!$user->atLeast(Member::STAFF)) {
			$query['privateMember'] = $user->member->id;
		}

		if(count($params) === 2 && $params[1] === 'stats') {
			$questions = 0;
			$announcements = 0;

			foreach($interacts->counts($query) as $count) {
				if($count['type'] === Interaction::QUESTION) {
					$questions = +$count['count'];
				}

				if($count['type'] === Interaction::ANNOUNCEMENT) {
					$announcements = +$count['count'];
				}
			}

			$data = ['questions'=>$questions, 'announcements'=>$announcements];
			$json = new JsonAPI();
			$json->addData('interact-stats', 0, $data);
			return $json;
		}

		$limit = self::MAX_SUMMARIES;
		if(!empty($get['limit'])) {
			if($get['limit'] < $limit) {
				$limit = $get['limit'];
			}
		}

		$query['limit'] = $limit + 1;
		if(!empty($get['before'])) {
			$query['before'] = +$get['before'];
		}

		$summaries = $interacts->summaries($query);

		$data = [];
		for($i=0; $i<count($summaries) && $i<$limit; $i++) {
			$data[] = $summaries[$i]->summaryData($site, $user);
		}

		if(count($summaries) > $limit) {
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

				$member = $members->getAsUser($memberId);
				if ($member === null) {
					throw new APIException("Member does not exist");
				}

				$member->member->meta->set(Interact::INTERACT_CATEGORY, Interact::RECEIVE_MAIL, $post['email']);
				$members->writeMetaData($member->member);
			} else {
				$user->member->meta->set(Interact::INTERACT_CATEGORY, Interact::RECEIVE_MAIL, $post['email']);
				$members->writeMetaData($user->member);
			}
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