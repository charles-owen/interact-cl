<?php
/**
 * @file
 * View class for presentation of interaction discussions
 */

namespace Interact;

/**
* View class for presentation of interaction discussions
*/
class DiscussionView extends InteractBaseView {
	/**
	 * Constructor
	 * @param \Course $course Current course
	 * @param \User $user Current user
	 * @param $interactId ID for the interaction this is a discussion on
	 */
    public function __construct(\Course $course, \User $user, $interactId) {
        parent::__construct($course, $user);

        $this->interactId = $interactId;
    }

	/**
	 * Get all discussions related to a given interaction
	 *
	 * The result of this is returned to the client to display
	 * @param $time Current time
     * @param $after Only discussion items after this will be loaded
	 * @return array of discussions, each with keys html, id
	 */
    public function get_discussion($time, $after=null) {
        $result = array();

        $discussions = new Discussions($this->course);
        $iter = $discussions->select($this->user, $this->interactId, null, $after);
        while(($current = $iter->get_current())) {
            $result[] = $this->discussion_to_send($current, $time);

            $iter->advance();
        }

        return $result;
    }

	/**
	 * Convert a discussion item into the format we send to the client
	 *
	 * Note that html is actually formed at the client.
	 * @param Discussion $discussion
	 * @param $currentTime The current time
	 * @return array with keys id, name, data, msg
	 */
    public function discussion_to_send(Discussion $discussion, $currentTime) {
        $id = $discussion->get_id();
        $msg = $discussion->get_html();
        $date = $this->display_time($discussion->get_time(), $currentTime);
        $name = $discussion->display_name($this->user);
        $libroot = $this->course->get_libroot();

        $edits = array();
        foreach($discussion->get_edits() as $edit) {
            $editor = "Student";
            if($edit['id'] == $this->user->get_id()) {
                $editor = "me";
            } else if($this->user->is_staff()) {
                $editor = \User::to_displayname($edit['name']);
            }
            $time = $this->display_time($edit['time'], $currentTime, true);
            $edits[] = array('name' => $editor, 'time' => $time);
        }


        $menu = '';
        if($this->user->is_staff() || $this->user->get_id() == $discussion->get_user_id()) {
            $staffMenu = '';
            if($this->user->is_staff()) {
                $staffMenu = <<<MENU
<li><a class="endorse" href="javascript:;"><img src="$libroot/images/interact/check16.png"></a> <a class="endorse" href="javascript:;">Endorse</a></li>
MENU;
            }
            $menu = <<<MENU
<div class="menu"><a href="javascript:;"><img src="$libroot/images/menubars.png"></a>
<ul>
<li><a class="edit" href="javascript:;"><img src="$libroot/images/pen16.png"></a> <a class="edit" href="javascript:;">Edit</a></li>
<li><a class="delete" href="javascript:;"><img src="$libroot/images/x.png"></a> <a class="delete" href="javascript:;">Delete</a></li>
$staffMenu
</ul>
</div>
MENU;
        }

        $endorse = '';
        $endorsements = $discussion->get_endorsements();
        if(count($endorsements) > 0) {
            $names = '';
            foreach($endorsements as $endorsement) {
                if(strlen($name) > 0) {
                    $name .= ", ";
                }

                $names .= $discussion->display_name_role($endorsement['name'], $endorsement['role']);
            }

            if(count($endorsements) > 1) {
                $names .= " endorse";
            } else {
                $names .= " endorses";
            }

            $endorse = <<<ENDORSE
<span class="good"><img src="$libroot/images/check.png"> $names this contribution!</span>
ENDORSE;
        }


        return array('id' => $id, 'interactid' => $discussion->get_interact_id(),
            'name' => $name, 'time' => $discussion->get_time(), 'date' => $date, 'msg' => $msg, 'edits' => $edits,
            'menu' => $menu, 'endorse' => $endorse);
    }

    private $interactId;
}