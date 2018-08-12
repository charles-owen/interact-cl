<?php
/**
 * @file
 * Controller class for the Interact Comet connection.
 */

namespace Interact;

/**
 * Controller class for the Interact Comet connection.
 *
 * Clients poll this connection for new interactions and
 * status of discussions. The response is delayed when
 * no status change is detected.
 */
class InteractPollingController extends \Controller {
    const POLLING_PERIOD = 3;   ///< We test for status every five seconds
    const POLLING_CNT = 4;      ///< Number of times we'll try polling before an automatic response
    const MAXIMUM_EDIT_TIME = 1800; ///< Maximum seconds we'll consider something as edited (30 minutes)

    /**
     * Constructor
     * @param \Course $course Course object
     * @param \User $user User object
     * @param $time Current time
     * @param \Email $email An email object. If null, default is used.
     */
    public function __construct(\Course $course, \User $user, $time) {
        parent::__construct($course, $user);
        $this->time = $time;

    }

    /**
     * Handle $_POST array and a post command
     * @param array $post
     * @return string|void
     */
    public function post($post) {
        $data = json_decode($post['json'], true);

        $mostrecent = strip_tags($data['mostrecent']);
        $mostrecentdiscuss = strip_tags($data['mostrecentdiscuss']);
        $cnt = strip_tags($data['cnt']);
        $categories = null;
        $sectionTag = null;

        if(isset($data['categories'])) {
            $categories = $data['categories'];
        }

        if(isset($data['section']) && strlen($data['section']) > 0) {
            $sectionTag = strip_tags($data['section']);
        }

        $query = isset($data['query']) ? strip_tags($data['query']) : null;

        $interacts = new \Interact\Interacts($this->course);

        $result = ['ok'=>true, 'cnt'=>$cnt];
        $any = false;           // Anything added to $result for return?

        for($i=0; $i<self::POLLING_CNT;  $i++) {
            /*
             * Determine if there are any new interactions the user should see
             */
            $interactions = array();

            $interacts->select($this->user, $categories, $sectionTag, null, $mostrecent, $query);
            while(($current = $interacts->get_current()) !== null) {
                $interactions[] = $current->to_array($this->user);
                $interacts->advance();
            }

            if(count($interactions) > 0) {
                $result['interactions'] = $interactions;
                $any = true;
            }


            $selected = strip_tags($data['selected']);
            if($selected !== null) {
                /*
                 * Has the editing changed?
                 */
                $wasEditing = $data['editing'];
                $interactive = new \Interact\InterActive($this->course);
                $editing = $interactive->query($selected, time() - self::MAXIMUM_EDIT_TIME * 1000);

                // Anonymize the data for non-staff
                if(!$this->user->is_staff()) {
                    for($j=0; $j<count($editing);  $j++) {
                        if($this->user->id != $editing[$j]['userid'] &&
                            !\User::role_is_staff($editing[$j]['userrole'])) {
                            $editing[$j]['username'] = 'Student';
                            $editing[$j]['userid'] = 0;
                        }
                    }
                }

                // Compare them. We only send the list if it changes
                $equal = true;
                if(count($editing) == count($wasEditing)) {
                    for($j=0; $j<count($editing); $j++) {
                        $e = $editing[$j];
                        $w = $wasEditing[$j];
                        if($e['interactid'] != $w['interactid'] ||
                            $e['userid'] != $w['userid']) {
                            $equal = false;
                            break;
                        }
                    }
                } else {
                    $equal = false;
                }

                if(!$equal) {
                    $result['editing'] = $editing;
                    $any = true;
                }

                /*
                 * Are there any new discussion items?
                 */
                $discussionView = new DiscussionView($this->course, $this->user, $selected);
                $discussions = $discussionView->get_discussion($this->time, $mostrecentdiscuss);
                if(count($discussions) > 0) {
                    $result['discussions'] = $discussions;
                    $any = true;
                }
            }




            if($any) {
                return json_encode($result);
            }

            sleep(self::POLLING_PERIOD);
        }


        return json_encode($result);
    }

    private $time;
}