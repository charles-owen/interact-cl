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
}