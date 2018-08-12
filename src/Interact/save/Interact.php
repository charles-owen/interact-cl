<?php
/**
 * @file
 * Interact file for constants and any custom configuration
 */

namespace Interact;


class Interact {
    /// Preferences key for determining if staff member receives Interact email
    const RECEIVE_MAIL = "interact-receive-mail";

    /**
     * Add a canned text item to Interact
     * @param $title Item title
     * @param $text Item text
     */
    public function add_canned($title, $text){
        $this->canned[] = ['title' => $title, 'text' => $text];
    }

    /**
     * Generate JavasScript necessary for this configuration
     */
    public function js(\User $user) {
        $js = '';

        if($user->is_staff()) {
            foreach($this->canned as $canned) {
                $title = $canned['title'];
                $text = $canned['text'];
                $js .= 'INTERACT.add_canned("' .
                    addslashes($title) . '", "' . addslashes($text) . '");' . "\n";
            }
        }

        return $js;
    }

    /**
     * Convert a date/time to the format we display
     * @param $time Time to convert
     * @param $currentTime The current time
     * @param bool $short If true, display a shorter version
     * @return string Converted time
     */
    public static function display_time($time, $currentTime, $short=false) {
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

    private $canned = [];
}