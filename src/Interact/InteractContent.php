<?php
/**
 * @file
 * Base class for Interact system content: interaction and discussion items
 */

namespace CL\Interact;

use CL\Users\User;
use CL\Site\MetaData;
use CL\Course\Member;
use CL\Site\Site;

/**
 * Base class for Interact system content: interaction and discussion items
 *
 * This class implements a structure for saving content in the table as
 * JSON with an edit history. It also supports these common elements of interaction
 * content: html, user id, user name, user role, time
 *
 * @cond
 * @property string message
 * @property User user
 * @endcond
 */
abstract class InteractContent {
	/// Maximum length for message summaries in characters
	const SummarizeMax = 1000;

	/**
     * Constructor
     */
    public function __construct($row = null, $prefix='') {
    	if($row !== null) {
		    $user = new User($row, 'user_');
		    $member = new Member($row, 'member_');
		    $user->member = $member;
		    $this->user = $user;
		    if(isset($row["{$prefix}message"])) {
		        $message = $row["{$prefix}message"];

		        // Data from the database may or may not be UTF-8 encoded.
                // This tests if it is. If it is not, we encode it.
		        if(utf8_encode(utf8_decode($message)) !== $message) {
                    $message = utf8_encode($message);
                }
			    $this->message = $message;
		    }

		    $this->time = strtotime($row["{$prefix}time"]);
		    $this->id = $row["{$prefix}id"];

		    if($row["{$prefix}metadata"] !== null) {
			    $this->metaData = new MetaData(null, $row["{$prefix}metadata"]);
		    } else {
			    $this->metaData = new MetaData();
		    }
	    } else {
		    $this->metaData = new MetaData();
	    }
    }

	/**
	 * Property get magic method
	 *
	 * <b>Properties</b>
	 * Property | Type | Description
	 * -------- | ---- | -----------
	 *
	 * @param string $property Property name
	 * @return mixed
	 */
	public function __get($property) {
		switch($property) {
			case 'message':
				return $this->message;

			case 'user':
				return $this->user;

			case 'time':
				return $this->time;

			case 'meta':
			case 'metaData':
				return $this->metaData;

			case 'id':
				return $this->id;

			default:
				$trace = debug_backtrace();
				trigger_error(
					'Undefined property ' . $property .
					' in ' . $trace[0]['file'] .
					' on line ' . $trace[0]['line'],
					E_USER_NOTICE);
				return null;
		}
	}

	/**
	 * Property set magic method
	 *
	 * <b>Properties</b>
	 * Property | Type | Description
	 * -------- | ---- | -----------
	 *
	 * @param string $property Property name
	 * @param mixed $value Value to set
	 */
	public function __set($property, $value) {
		switch($property) {
			case 'message':
				$this->message = $value;
				break;

			case 'user':
				$this->user = $value;
				break;

			case 'time':
				$this->time = $value;
				break;

			case 'id':
				$this->id = $value;
				break;

			default:
				$trace = debug_backtrace();
				trigger_error(
					'Undefined property ' . $property .
					' in ' . $trace[0]['file'] .
					' on line ' . $trace[0]['line'],
					E_USER_NOTICE);
				break;
		}
	}


	/**
	 * Get a summarized version of the message
	 * @return string HTML/truncated
	 */
	public function summarize() {
		$summary = strip_tags(utf8_decode($this->message));

		// Since the data from the clients is UTF8, just truncating
		// the string can lead to a broken multibyte character at
		// the end, which breaks JSON encoding. This ensures that
		// that cannot happen.
		return utf8_encode(substr($summary, 0, self::SummarizeMax));
	}


	/**
	 * Create client data when in summary mode.
	 * @param Site $site The Site object
	 * @param User $user The current user
	 * @return array results
	 */
	public function data(Site $site, User $user) {
		$data = [
			'id'=>$this->id,
			'time'=>$this->time,
			'by'=>$this->displayName($user),
			'byRole'=>$this->user->role
		];

		return $data;
	}


    /**
     * Create a name as it is going to be displayed for users
     * @param User $user The user who is viewing
     * @return string The name
     */
    public function displayName(User $user) {
	    //
	    // Only staff see all "by" indications
	    //
	    $by = $this->user->displayName;
	    if($this->user->id == $user->id) {
		    $by = 'Me';
	    }

	    if(!$user->atLeast(Member::STAFF) && !$this->user->atLeast(Member::STAFF)) {
		    // This was created by other than staff and the user is not staff
		    if($this->user->id == $user->id) {
			    $by = 'Me';
		    } else {
			    $by = 'Student';
		    }
	    }

        return $by;
    }

	/**
	 * Create a name as it is going to be displayed for users
	 *
	 * This version is useful for when a complete User object does
	 * not exist for the other user (attributions and history)
	 * @param User $user The user who is viewing
	 * @param int $userId User ID for the other user (to determine "me")
	 * @param string $role Other user's role.
	 * @param string $displayName Other user's name
	 * @return string The name to dislay.
	 */
	public function displayName2(User $user, $userId, $role, $displayName) {
		//
		// Only staff see all "by" indications
		//
		$by = $displayName;
		if($userId == $user->id) {
			$by = 'Me';
		}

		if(!$user->atLeast(Member::STAFF) && !$this->user->roleAtLeast($role, Member::STAFF)) {
			// This was created by other than staff and the user is not staff
			if($this->user->id == $user->id) {
				$by = 'Me';
			} else {
				$by = 'Student';
			}
		}

		return $by;
	}

	/**
	 * Generate an array of history data suitable for sending to a client.
	 * @param User $user
	 * @return array of arrays with keys 'op', 'time', 'by', and 'role'
	 */
	protected function historyData(User $user) {
		$history = $this->meta->get('public', Interact::HISTORY, []);
		$sendHistory = [];

		foreach($history as $item) {
			$by = $this->displayName2($user, $item['user'], $item['role'], $item['name']);
			$sendHistory[] = [
				'op'=>$item['op'],
				'time'=>$item['time'],
				'by'=>$by,
				'role'=>$item['role']
			];
		}

		return $sendHistory;
	}

	private $id = 0;        // Interaction or discussion ID
    private $user = null;   // User for this message
    private $time = 0;      // Content timestamp
	private $metaData;

	private $message = '';  // Interaction or discussion message body
}