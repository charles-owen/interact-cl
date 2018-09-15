<?php
/**
 * @file
 * Interact class for constants, common functions, and any custom configuration
 */

namespace CL\Interact;

use CL\Users\User;

/**
 * Interact class for constants, common functions, and any custom configuration
 */
class Interact {
	/// Member MetaData category
	const INTERACT_CATEGORY = 'interact';

    /// MetaData key for determining if staff member receives Interact email
    const RECEIVE_MAIL = "email";

	/// MetaData key for determining if staff member receives Interact escalations
	const RECEIVE_ESCALATION = 'escalate';

    /// MetaData key for interaction or discussion history
	const HISTORY = 'history';

	/// MetaData category for discussion endorsements
	const ENDORSEMENTS = 'endorse';

	/// MetaData key for Interaction state
	const INTERACTION_STATE = 'state';

	/// MetaData key for Interaction escalation role
	const ESCALATED = 'escalated';

    /**
     * Add a canned text item to Interact
     * @param string $title Item title
     * @param string $text Item text
     */
    public function add_canned($title, $text){
        $this->canned[] = ['title' => $title, 'text' => $text];
    }

	/**
	 * Get the standard Interact! system categories.
	 *
	 * These categories are in addition to the regular assignment tags.
	 *
	 * @return array Array with tag as key and name as the value
	 */
	public static function standard_categories() {
		return array('general' => 'General Course');
	}

	/**
	 * Sanitize an HTML post.
	 *
	 * Uses HTML purifier to ensure not script tags or other dangerous content.
	 * @param string $str HTML to sanitize
	 * @return string HTML
	 */
	public static function sanitize($str) {
		// Only allow certain tags (no scripts for example)
		$str = strip_tags($str, '<code><span><div><label><a><br><p><b><i><del><strike><u><img><video><audio><iframe><param><blockquote><mark><cite><small><ul><ol><li><hr><dl><dt><dd><sup><sub><big><pre><code><figure><figcaption><strong><em><table><tr><td><th><tbody><thead><tfoot><h1><h2><h3><h4><h5><h6>');

		// HTML purifier cleans possible attribute-based exploits from the HTML
		$config = \HTMLPurifier_Config::createDefault();
		$config->set('Cache.DefinitionImpl', null);
		$purifier = new \HTMLPurifier($config);
		return $purifier->purify($str);
	}


	public function data(User $user) {
		$data = [];
		if($user->staff && count($this->canned) !== 0) {
			$data['canned'] = $this->canned;
		}
		return $data;
	}

//    /**
//     * Generate JavasScript necessary for this configuration
//     */
//    public function js(User $user) {
//        $js = '';
//
//        if($user->staff) {
//            foreach($this->canned as $canned) {
//                $title = $canned['title'];
//                $text = $canned['text'];
//                $js .= 'INTERACT.add_canned("' .
//                    addslashes($title) . '", "' . addslashes($text) . '");' . "\n";
//            }
//        }
//
//        return $js;
//    }

    /**
     * Convert a date/time to the format we display
     * @param int $time Time to convert
     * @param int $currentTime The current time
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