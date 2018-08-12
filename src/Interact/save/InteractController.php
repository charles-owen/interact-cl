<?php
/**
 * @file
 * Controller class for the Interaction system
 */

namespace Interact;

/**
 * Controller class for the Interaction system
 */
class InteractController {
	/**
	 * Constructor
	 * @param \Course $course Course object
	 * @param \User $user User object
	 * @param $time Current time
	 * @param \Email $email An email object. If null, default is used.
	 */
    public function __construct(\Course $course, \User $user, $time, \Email $email = null) {
        $this->course = $course;
        $this->user = $user;
        $this->time = $time;
		$this->email = $email;
    }

	/**
	 * Handle $_POST array and a post command
	 * @param array $post
	 * @return string|void
	 */
    public function post($post) {
        if($this->user->is_guest()) {
            return;
        }

        if(!isset($post['cmd'])) {
            return $this->error("Invalid Request");
        }

        switch($post['cmd']) {
            case 'post':
                return $this->execute_post($post);

            case 'edit':
                return $this->execute_edit($post);

            case 'delete':
                return $this->execute_delete($post);

            case 'follow':
                return $this->execute_follow($post);

            case 'allfollow':
                return $this->execute_allfollow($post);

			case 'close':
				return $this->execute_close($post);

            case 'exit':
                return $this->execute_exit($post);
        }

        return $this->error("Invalid Request");
    }

    /**
     * Standard error return
     * @param $msg Error message
     * @return string JSON
     */
    private function error($msg) {
        return json_encode(array('ok'=>false, 'msg'=>$msg));
    }

    private function execute_allfollow($post) {
        if(!$this->user->is_staff()) {
            return error("Not authorized");
        }

        $id = $post['id'];
		$interacts = new Interacts($this->course);
		$interaction = $interacts->get($id);
		if($interaction === null) {
			return error("Interaction does not exist");
		}

        $interFollows = new InterFollows($this->course);

        // set_all_following will not set a user who has indicated
        // "never follow", but if that is this user, we want to override
        // that so this user is following
        $interFollows->set_following($this->user->get_id(), $id, InterFollows::FOLLOWING);
        $interFollows->set_all_following($this->user->get_section(), $id);

		$email = new InteractEmail($this->course, $this->user, $this->email);
		$email->new_interaction($interaction, true);

        $result = array('ok' => true);
        $view = new \InteractView($this->course, $this->user);
        $result['img'] = $view->follow_image(true);

        return json_encode($result);
    }

	/**
	 * Close interaction for further discussion
	 * @param $post $_POST
	 * @return string json response
	 */
	private function execute_close($post) {
		if(!$this->user->is_staff()) {
			return error("Not authorized");
		}

		$id = $post['id'];
		$interacts = new Interacts($this->course);
		$interaction = $interacts->get($id);
		if($interaction === null) {
			return error("Interaction does not exist");
		}

		$interaction->set_closed(true);
		$interacts = new Interacts($this->course);
		$interacts->update($interaction);

		$result = array('ok' => true);
		$view = new \InteractView($this->course, $this->user);
		$result['html'] = $view->interaction_to_right_html($interaction, $this->time);
		$result['interaction'] = $interaction->to_array($this->user);

		return json_encode($result);
	}



	private function execute_follow($post) {
        $id = $post['id'];

        $interFollows = new InterFollows($this->course);
        $follow = $interFollows->get_following($this->user->get_id(), $id);
        $result = array('ok' => true);

        $view = new \InteractView($this->course, $this->user);

        if($follow === InterFollows::FOLLOWING) {
            $interFollows->set_following($this->user->get_id(), $id, InterFollows::NEVERFOLLOW);
            $result['follow'] = false;
            $result['img'] = $view->follow_image(false);
        } else {
            $interFollows->set_following($this->user->get_id(), $id, InterFollows::FOLLOWING);
            $result['follow'] = true;
            $result['img'] = $view->follow_image(true);
        }

        return json_encode($result);
    }

	/**
	 * Handle a new interaction post
	 * @param $post $_POST
	 * @return string json result
	 */
    private function execute_post($post) {
        $summary = strip_tags($post['summary']);
        $html = self::sanitize($post['question']);

        if(strlen($summary) == 0) {
            return $this->error("You must provide a question summary!");
        }

        if(strlen($html) == 0) {
            return $this->error("You must provide a question!");
        }

        $type = $post['type'] === 'Announcement' ? Interaction::Announcement : Interaction::Question;
        $assignTag = $this->exists_or_null($post, 'category');
        $sectionTag = $this->exists_or_null($post, 'section');
        $forceFollow = $this->user->is_staff() && isset($post['forcefollow']);
		$sendAll = $this->user->is_staff() && isset($post['sendall']);

		if($assignTag === null) {
			$assignTag = "general";
		}

        $interaction = new Interaction($this->course,
            $this->user,
            $assignTag,
            $sectionTag,
            $type,
            isset($post['pin']),
            isset($post['private']),
            $this->time);

        $interaction->set_summary($summary);
        $interaction->set_html($html);

        $interacts = new Interacts($this->course);
        $id = $interacts->add($interaction, $this->time);
		if($id == 0) {
			return $this->error("Error adding interaction to database");
		}

        $interaction->set_id($id);

        /*
         * User is following this post
         */
        $interFollows = new InterFollows($this->course);
        $interFollows->set_following($this->user->get_id(), $id, InterFollows::FOLLOWING);

		$email = new InteractEmail($this->course, $this->user, $this->email);

        if($forceFollow) {
			// Set all users following
			$interFollows->set_all_following($this->user->get_section(), $id);

			$email->new_interaction($interaction, true, $sendAll);
        } else {
            // Set all staff who currently are receiving email as following
            $users = new \Users($this->course);
            foreach($users->get_users(null, null, true) as $staffUser) {
                if($staffUser->get_preferences()->get(Interact::RECEIVE_MAIL, false)) {
                    $interFollows->set_following($staffUser->id, $id, InterFollows::FOLLOWING);
                }
            }

			$email->new_interaction($interaction, false, $sendAll);
		}

		if($interaction->get_type() === Interaction::Question) {
            //
            // Autoanswer system
            //
            $answerer = new \AutoAnswer\Answerer($this->course, $this->user);
            $answer = $answerer->lookup($html, $assignTag, $sectionTag);
            if($answer !== null) {
                $users = new \Users($this->course);
                $answeruser = $users->get_user_by_userid($answerer->answerer);
                if($answeruser !== null) {
                    $discussion = new Discussion($this->course,
                        $answeruser,
                        $interaction->get_id(),
                        $this->time);

                    $discussion->set_html($answer['text']);

                    $discussions = new Discussions($this->course);
                    $discussionId = $discussions->add($discussion, $this->time);
                    $discussion->set_id($discussionId);

                    $email->new_discussion($interaction, $discussion);
                }
            }
        }

        $view = new \InteractView($this->course, $this->user, array($assignTag), $sectionTag);
        $result['ok']  = true;
        $result['interaction'] = $interaction->to_array($this->user);

        return json_encode($result);
    }

    private function execute_edit($post) {
        $summary = strip_tags($post['summary']);
        $html = self::sanitize($post['question']);
        $id = $post['id'];
        $sectionTag = $this->exists_or_null($post, 'section');
		$assignTag = $this->exists_or_null($post, 'category');

        if(strlen($summary) == 0) {
            return $this->error("You must provide a question summary!");
        }

        if(strlen($html) == 0) {
            return $this->error("You must provide a question!");
        }


        $interacts = new Interacts($this->course);
        $interaction = $interacts->get($id);
        if($interaction === null) {
            return $this->error("Interaction no longer exists");
        }

		if($assignTag !== null && $assignTag !== $interaction->get_assign_tag()) {
			$interaction->set_assign_section($assignTag, null);
		} else {
			$assignTag = $interaction->get_assign_tag();
		}

        if(!$this->user->is_staff() && $interaction->get_user_id() != $this->user->get_id()) {
            return $this->error("You do not have permission to edit");
        }

        $interaction->update($summary, $html, $post);
        $interaction->add_edit($this->time, $this->user);

        /*
         * Update the table
         */
        $id = $interacts->update($interaction);

        $view = new \InteractView($this->course, $this->user, array($assignTag), $sectionTag);
        $result['ok']  = true;
        $result['interaction'] = $interaction->to_array($this->user);

        return json_encode($result);
    }

    private function execute_exit($post) {
        /* Indicate the user is no longer editing an interaction */
        $interactive = new InterActive($this->course);
        $interactive->delete($this->user);

        return json_encode(["ok" => true]);
    }

    /**
     * @brief Return a cleaned value of $post[$key] if it exists or null if not
     * @param $post
     * @param $key
     */
    private function exists_or_null($post, $key) {
        if(isset($post[$key]) && strlen($post[$key]) > 0) {
            return strip_tags($post[$key]);
        }

        return null;
    }

    private static function sanitize($str) {
        // Only allow certain tags (no scripts for example)
        $str = strip_tags($str, '<code><span><div><label><a><br><p><b><i><del><strike><u><img><video><audio><iframe><param><blockquote><mark><cite><small><ul><ol><li><hr><dl><dt><dd><sup><sub><big><pre><code><figure><figcaption><strong><em><table><tr><td><th><tbody><thead><tfoot><h1><h2><h3><h4><h5><h6>');

        // HTML purifier cleans possible attribute-based exploits from the HTML
        $config = \HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
        $purifier = new \HTMLPurifier($config);
        return $purifier->purify($str);
    }

    private function execute_delete($post) {
        $id = $post['id'];

        $interacts = new Interacts($this->course);

        /*
         * Validate permission first
         */
        if(!$this->user->is_staff()) {
            $interaction = $interacts->get($id);
            if($interaction !== null && $this->user->get_id() !== $interaction->get_user_id()) {
                return $this->error("Does not have permission to delete");
            }
        }

        $interacts->delete($id);

        $result = array('ok' => true);
        return json_encode($result);
    }

    private $course;
    private $user;
    private $time;
	private $email;
}