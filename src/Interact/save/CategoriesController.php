<?php
/**
 * @file
 * @brief Controller class for the Interaction system discussions
 */

namespace Interact;

/**
 * @brief Controller class for the Interaction system category selection
 */
class CategoriesController {
	/**
	 * Constructor
	 * @param \Course $course Current course
	 * @param \User $user Current user
	 * @param $time Timestamp
	 */
    public function __construct(\Course $course, \User $user, $time) {
        $this->course = $course;
        $this->user = $user;
        $this->time = $time;
    }

	/**
	 * Handle a POST message
	 * @param $post $_POST
	 * @return string JSON response
	 */
    public function post($post) {
        if(!isset($post['cmd']) || !isset($post['tag'])) {
            return $this->error("Invalid Request");
        }

        $tag = strip_tags($post['tag']);

        switch($post['cmd']) {
            case 'select':
                return $this->execute_select($tag);

            case 'only':
                return $this->execute_only($tag);
        }

        return $this->error("Invalid Request");
    }

    private function error($msg) {
        return json_encode(array('ok'=>false, 'msg'=>$msg));
    }

    private function execute_select($tag) {
        $prefs = $this->user->get_preferences();
        $categories = $prefs->get('interact_categories', array('all'));

        if($tag === 'all_cat') {
            $categories = array('all');
        } else {
            if(count($categories) == 1 && current($categories) === 'all') {
                /*
                 * If it currently is all, add in all
                 * except the one we are now toggling off
                 */
                $all = $this->all_categories();
                $categories = array();
                foreach($all as $key => $name) {
                    if($key !== $tag) {
                        $categories[] = $key;
                    }
                }
            } else if(in_array($tag, $categories)) {
                // If in the array, remove it
                $categories = array_diff($categories, array($tag));

                // If we remove everything, put in general
                if(count($categories) == 0) {
                    $categories = array('general');
                }
            } else {
                $categories[] = $tag;

                // Do we now have all of them?
                $all = $this->all_categories();
                $anyMissing = false;
                foreach($all as $key => $name) {
                    if(!in_array($key, $categories)) {
                        $anyMissing = true;
                        break;
                    }
                }

                if(!$anyMissing) {
                    $categories = array('all');
                }
            }
        }

        $prefs->interact_categories = $categories;
        $prefs->write();

        $view = new \InteractView($this->course, $this->user);
        return $view->request(array('cmd' => 'ui'), $this->time);
    }

    private function execute_only($tag) {
        $prefs = $this->user->get_preferences();
        $prefs->interact_categories = array($tag);
        $prefs->write();

        $view = new \InteractView($this->course, $this->user);
        return $view->request(array('cmd' => 'ui'), $this->time);
    }

    private function all_categories() {
        $categories = array();
        foreach(\Interact\Interaction::standard_categories() as $key => $value) {
            $categories[$key] = $value['shortname'];
        }

        $assignments = $this->user->get_section()->get_assignments();
        foreach($assignments->get_categories() as $category) {
            foreach($category->get_assignments() as $assignment) {
                $assignment->load();
                $categories[$assignment->get_tag()] = $assignment->get_shortname();
            }
        }

        return $categories;
    }

    private $course;
    private $user;
    private $time;
}