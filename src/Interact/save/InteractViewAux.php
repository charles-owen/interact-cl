<?php
/**
 * @file
 * ViewAux base file for Interact
 */

namespace Interact;

/**
 * ViewAux base file for Interact
 *
 * Common elements used by both InteractBaseView and InteractConsoleViewAux
 */
class InteractViewAux extends \ViewAux {
    /**
     * Constructor
     * @param Course $course Course object
     * @param User $user User who is viewing
     */
    public function __construct(\Course $course, \User $user) {
        $this->course = $course;
        $this->user = $user;
    }

    /**
     * Called when this auxiliary view is installed in a view
     * @param View $view View we are installing into
     */
    public function install(\View $view) {
        $course = $view->get_course();
        $interactJs = $course->get_sandbox() ? 'interact/interact.con.js' : 'interact/interact.min.js';

  //$interactJs = 'interact/interact.con.js';
        $view->add_js('js/ckeditor/ckeditor.js');
        $view->add_js_timestamped($interactJs);
        $view->add_css('interact/ck_interact.css');
        $view->add_css('interact/ck_interact_content.css');
        $view->js = "var INTERACT = new Interact();\n";

        // interact.css is included in course.css
    }

    /**
     * Convert a date/time to the format we display
     * @param $time Time to convert
     * @param $currentTime The current time
     * @param bool $short If true, display a shorter version
     * @return string Converted time
     */
    public function display_time($time, $currentTime, $short=false) {
        $elapsed = $currentTime - $time;

        if($elapsed < 60) {
            return "<1 min ago";
        }

        if($elapsed < 3600) {
            // Within an hour ago
            $mins = (int)($elapsed / 60);
            return "$mins min ago";
        }

        /*
         * Determine if it was today
         */
        $currentDate = date("n-j-Y", $currentTime);
        $date = date("n-j-Y", $time);
        if($currentDate === $date) {
            return "Today at " . date("g:ia", $time);
        }

        /*
         * Determine if it was for this week
         */
        $currentWeek = date("W", $currentTime);
        $week = date("W", $time);
        if($currentWeek === $week) {
            return date("D, g:ia", $time);
        }

        if($short) {
            return date("n-j-Y", $time);
        }

        return date("D, n-j-Y g:ia", $time);
    }

    protected $course;		///< Course object
    protected $user;		///< User object
}