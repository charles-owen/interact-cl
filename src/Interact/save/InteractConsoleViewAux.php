<?php
/**
 * @file
 * ViewAux for Interact console plugin
 */

namespace Interact;

/**
 * ViewAux for Interact console plugin
 */
class InteractConsoleViewAux extends InteractViewAux {
    /**
     * Constructor
     * @param Course $course Course object
     * @param User $user User who is viewing
     */
    public function __construct(\Course $course, \User $user) {
        parent::__construct($course, $user);
    }

    public function install(\View $view) {
        parent::install($view);

        $view->ready = <<<JS
new InteractConsole(".interact-console");
JS;

    }

    public function present() {
        //
        // Presentation for this user
        //
        $receiving = $this->user->get_preferences()->get(Interact::RECEIVE_MAIL, false);
        $checked = $receiving ? " checked" : "";

        $html = <<<HTML
<div class="interact-console">
<h2>Interact <span class="msg"></span></h2>
<p><input class="user" type="checkbox"$checked> Receive email on new Interacts.</p>
HTML;

        //
        // Presentation of all staff
        // Only TA's and instructors see this list
        //
        if($this->user->at_least(\User::STAFF)) {
            $html .= '<div class="staff-email"><h3>Other staff</h3>';
            $users = new \Users($this->course);

            // Copy staff into an array so we don't query many times
            $staff = [];
            foreach($users->get_users(null, "name", true) as $staffUser) {
                $staff[] = $staffUser;
            }

            foreach([\User::ADMIN, \User::INSTRUCTOR, \User::STAFF, \User::GRADER] as $role) {
                $first = ' class="first"';

                foreach($staff as $staffUser) {
                    // Do not display if they do not have an email address
                    if(trim($staffUser->email) === '') {
                        continue;
                    }

                    if($staffUser->role !== $role) {
                        continue;
                    }

                    $receiving = $staffUser->get_preferences()->get(Interact::RECEIVE_MAIL, false);
                    $checked = $receiving ? " checked" : "";
                    $id = $staffUser->id;
                    if($id === $this->user->id) {
                        continue;
                    }

                    $html .= <<<HTML
    <p$first><input class="staff-user" value="$id" type="checkbox"$checked> $staffUser->displayname</p>
HTML;

                    $first = '';
                }
            }


            $html .= "</div>";
        }

        $html .= <<<HTML
</div>
HTML;

        return $html;
    }
}