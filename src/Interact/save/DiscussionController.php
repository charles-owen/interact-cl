<?php
/**
 * @file
 * Controller class for the Interaction system discussions
 */

namespace Interact;

/**
 * Controller class for the Interaction system discussions
 */
class DiscussionController {
	/**
	 * Constructor
	 * @param \Course $course Course object
	 * @param \User $user Current user
	 * @param $time Timestamp
	 * @param \Email|null $email Email object or null for default email component
	 */
    public function __construct(\Course $course, \User $user, $time, \Email $email=null) {
        $this->course = $course;
        $this->user = $user;
        $this->time = $time;
		$this->email = $email;
    }

	/**
	 * Handle a post
	 * @param $post $_POST
	 * @return string JSON response
	 */
    public function post($post) {
        if($this->user->is_guest()) {
            return $this->error("Not Authorized");
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

            case 'endorse':
                return $this->execute_endorse($post);

            case "open":
                return $this->execute_open($post);
        }

        return $this->error("Invalid Request");
    }

    private function error($msg) {
        return json_encode(array('ok'=>false, 'msg'=>$msg));
    }

    /**
     * Handle posting of a new interaction discussion item.
     * @param $post $_POST
     * @return string JSON
     */
    private function execute_post($post) {
        $html = self::sanitize($post['discuss']);

        /* Indicate the user is no longer editing an interaction */
        $interactive = new InterActive($this->course);
        $interactive->delete($this->user);

        if(strlen($html) == 0) {
            return $this->error("You must provide some discussion!");
        }

        $interactId = $post['interactid'];
        $interacts = new Interacts($this->course);

        $discussion = new Discussion($this->course,
            $this->user,
            $interactId,
            $this->time);

        $discussion->set_html($html);

        $discussions = new Discussions($this->course);
        $id = $discussions->add($discussion, $this->time);
        $discussion->set_id($id);

        $view = new \Interact\DiscussionView($this->course, $this->user, $interactId);
        $result = $view->discussion_to_send($discussion, $this->time);

        $interaction = $interacts->get($interactId);

        if($interaction === null) {
            return $this->error("Interaction is no longer available");
        }

        /*
         * User is now following this interaction
         */
        $interFollows = new InterFollows($this->course);
        $interFollows->set_following($this->user->get_id(), $interactId, InterFollows::FOLLOWING);

		$email = new InteractEmail($this->course, $this->user, $this->email);
		$email->new_discussion($interaction, $discussion);

        $view = new \InteractView($this->course, $this->user);
        $result['img'] = $view->follow_image(true);
        $result['interaction'] = $interaction->to_array($this->user);

        $result['ok']  = true;
        return json_encode($result);
    }


    /**
     * Handle an edit of an existing discussion item.
     * @param $post $_POST
     * @return string JSON response
     */
	private function execute_edit($post) {
		$html = self::sanitize($post['discuss']);
		$id = $post['id'];

        /* Indicate the user is no longer editing an interaction */
        $interactive = new InterActive($this->course);
        $interactive->delete($this->user);

		if(strlen($html) == 0) {
			return $this->error("You must provide a comment!");
		}

		$discussions = new Discussions($this->course);
		$discussion = $discussions->get($id);
		if($discussion === null) {
			return $this->error("Discussion item no longer exists");
		}

		if(!$this->user->is_staff() && $discussion->get_user_id() != $this->user->get_id()) {
			return $this->error("You do not have permission to edit");
		}

		$discussion->update($html, $post);
		$discussion->add_edit($this->time, $this->user);

		/*
		 * Update the table
		 */
		$id = $discussions->update($discussion);

		$view = new \Interact\DiscussionView($this->course, $this->user, $discussion->get_interact_id());
		$result = $view->discussion_to_send($discussion, $this->time);

		/*
		 * Update the left side information
		 */
		$interactId = $discussion->get_interact_id();
		$interacts = new Interacts($this->course);
		$interaction = $interacts->get($interactId);

		$view = new \InteractView($this->course, $this->user);
		$result['img'] = $view->follow_image(true);
		$result['interaction'] = $interaction->to_array($this->user);

		$result['ok']  = true;
		return json_encode($result);
	}


    /**
     * Endorse a discussion item.
     * @param $post $_POST
     * @return string JSON
     */
	private function execute_endorse($post) {
        if(!$this->user->is_staff()) {
            return $this->error("Not authorized!");
        }

        $id = $post['id'];

        $discussions = new Discussions($this->course);
        $discussion = $discussions->get($id);
        if($discussion === null) {
            return $this->error("Discussion does not exist");
        }

        $discussion->toggle_endorsement($this->time, $this->user);
        $discussions->update($discussion);

        $view = new \Interact\DiscussionView($this->course, $this->user, $discussion->get_interact_id());
        $result = $view->discussion_to_send($discussion, $this->time);

        $result['ok']  = true;
        return json_encode($result);
    }

    /**
     * Handle the case of a user opening the editor to add or edit discussion
     * on an Interact item. Indicates in the interactive table that the user
     * is editing the interaction.
     * @param $post @_POST
     * @return string JSON response
     */
    private function execute_open($post) {
        if(!isset($post['id'])) {
            return $this->error("Invalid command");
        }

        $interactive = new InterActive($this->course);
        $interactive->set($this->user, strip_tags($post['id']));

        return json_encode(['ok'=>true]);
    }

    /**
     * Return a cleaned value of $post[$key] if it exists or null if not
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

    /**
     * Delete a discussion item
     * @param $post $_POST
     * @return string JSON response
     */
    private function execute_delete($post) {
        $id = $post['id'];

        $discussions = new Discussions($this->course);
        $discussion = $discussions->get($id);

        $result = array('ok' => true);

        /*
         * Validate permission first
         */
        if(!$this->user->is_staff()) {
            if($discussion !== null && $this->user->get_id() !== $discussion->get_user_id()) {
                return $this->error("Does not have permission to delete");
            }
        }

        $discussions->delete($id);

        $interacts = new Interacts($this->course);
        $interaction = $interacts->get($discussion->get_interact_id());

        $view = new \InteractView($this->course, $this->user);
        $result['interaction'] = $interaction->to_array($this->user);

        return json_encode($result);
    }

    private $course;
    private $user;
    private $time;
	private $email;
}