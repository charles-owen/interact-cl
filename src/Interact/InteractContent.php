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
			    $this->message = $row["{$prefix}message"];
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
		$summary = strip_tags($this->message);

		// Since the data from the clients is UTF8, just truncating
		// the string can lead to a broken multibyte character at
		// the end, which breaks JSON encoding. This ensures that
		// that cannot happen.
		return utf8_encode(substr(utf8_decode($summary), 0, self::SummarizeMax));
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

//	/**
//	 * Display a name and role
//	 * @param $name Name to display
//	 * @param $role The user role
//	 * @return string HTML to display
//	 */
//    public function display_name_role($name, $role) {
//        switch($role) {
//			case \User::ADMIN:
//			case \User::INSTRUCTOR:
//                return \User::to_displayname($name) . ', <span class="role">Instructor</span>';
//
//            case \User::STAFF:
//			case \User::GRADER:
//                return \User::to_displayname($name) . ', <span class="role">Staff</span>';
//        }
//
//        return \User::to_displayname($name) . ', <span class="role">Student</span>';
//    }
//
//	/**
//	 * Set the content HTML
//	 * @param $html Set the HTML for the content
//	 */
//    public function set_html($html) {
//        $this->html = $html;
//    }
//
//	/**
//	 * Get the content HTML
//	 * @return string HTML for the content
//	 */
//    public function get_html() {
//        return $this->html;
//    }
//
//	/**
//	 * Get the user's role
//	 * @return string User role
//	 */
//    public function get_user_role() {
//        return $this->userRole;
//    }
//
//	/**
//	 * Set the user for this content
//	 * @param $id User ID
//	 * @param $name Name
//	 * @param $role Role
//	 */
//    public function set_user($id, $name, $role) {
//        $this->memberId = $id;
//        $this->userName = $name;
//        $this->userRole = $role;
//    }
//
//	/**
//	 * Get the content timestamp
//	 * @return Content time
//	 */
//    public function get_time() {
//        return $this->time;
//    }
//
//	/**
//	 * Convert this content to XML
//	 * @return string XML
//	 */
//    public function to_xml() {
//        $xml = new \XMLWriter();
//        $xml->openMemory();
//        $xml->startDocument('1.0', 'UTF-8');
//        $xml->startElement('interaction');
//
//		$this->write_xml_root($xml);
//        $this->write_xml_html($xml);
//        $this->write_xml_edits($xml);
//        $this->write_xml_endorsements($xml);
//
//        $xml->endElement();
//        $xml->endDocument();
//
//        return $xml->outputMemory(TRUE);
//    }
//
//	protected function write_xml_root(\XMLWriter $xml) {
//	}
//
//    protected function write_xml_html(\XMLWriter $xml) {
//        $xml->startElement('html');
//        $xml->startCdata();
//        $xml->text($this->html);
//        $xml->endCdata();
//        $xml->endElement();
//    }
//
//
//    private function write_xml_edits(\XMLWriter $xml) {
//        foreach($this->edits as $edit) {
//            $xml->startElement('edit');
//            $xml->writeAttribute('time', date("Y-m-d H:i:s", $edit['time']));
//            $xml->writeAttribute('id', $edit['id']);
//            $xml->writeAttribute('name', $edit['name']);
//            $xml->writeAttribute('role', $edit['role']);
//            $xml->endElement();
//        }
//    }
//
//    private function write_xml_endorsements(\XMLWriter $xml) {
//        foreach($this->endorsements as $endorsement) {
//            $xml->startElement('endorse');
//            $xml->writeAttribute('time', date("Y-m-d H:i:s", $endorsement['time']));
//            $xml->writeAttribute('id', $endorsement['id']);
//            $xml->writeAttribute('name', $endorsement['name']);
//            $xml->writeAttribute('role', $endorsement['role']);
//            $xml->endElement();
//        }
//    }
//
//    /** Read the content from an XML string
//     * $xmlstr The XML string */
//    public function from_xml($xmlstr) {
//        $this->html = '';
//        $this->edits = array();
//        $this->endorsements = array();
//
//        $xml = new \DOMDocument('1.0', 'utf-8');
//        $xml->loadXML($xmlstr);
//
//        $root = $xml->documentElement;
//		$this->xml_root($root);
//
//        // Loop over any children
//        foreach($root->childNodes as $child) {
//            switch($child->nodeName) {
//                case 'html':
//                    $this->xml_html($child);
//                    break;
//
//                case 'edit':
//                    $this->xml_edit($child);
//                    break;
//
//                case 'endorse':
//                    $this->xml_endorse($child);
//                    break;
//            }
//        }
//
//    }
//
//	protected function xml_root($xml) {
//	}
//
//    private function xml_html($xml) {
//        foreach($xml->childNodes as $child) {
//            if($child->nodeType === XML_CDATA_SECTION_NODE) {
//                $this->html = $child->data;
//                return;
//            }
//        }
//
//    }
//
//    private function xml_edit($xml) {
//        $this->edits[] = array('time' => strtotime($xml->getAttribute('time')),
//            'id' => $xml->getAttribute('id'),
//            'name' => $xml->getAttribute('name'),
//            'role' => $xml->getAttribute('role'));
//    }
//
//    private function xml_endorse($xml) {
//        $id = $xml->getAttribute('id');
//
//        $this->endorsements[$id] = array('time' => strtotime($xml->getAttribute('time')),
//            'id' => $xml->getAttribute('id'),
//            'name' => $xml->getAttribute('name'),
//            'role' => $xml->getAttribute('role'));
//    }
//
//    /**
//     * Add an indication that this interaction has been edited
//     * @param $time The time of the edit
//     * @param \User $user Editing user
//     */
//    public function add_edit($time, \User $user) {
//        $this->edits[] = array('time' => $time,
//            'id' => $user->get_id(), 'name' => $user->get_name(), 'role' => $user->get_role());
//    }
//
//    /**
//     * Get all edits for this interaction
//     * @return array of arrays, each with keys: 'time', 'id', 'name', 'role'
//     *
//     * The id is for the user (editor)
//     */
//    public function get_edits() {
//        return $this->edits;
//    }
//
//    /**
//     * Toggle the endorsement status for a user.
//     * @param $time The time of the endorsement
//     * @param \User $user Editing user
//     */
//    public function toggle_endorsement($time, \User $user) {
//        $userId = $user->get_id();
//
//        if(isset($this->endorsements[$userId])) {
//            unset($this->endorsements[$userId]);
//        } else {
//            $this->endorsements[$userId] = array('time' => $time,
//                'id' => $userId, 'name' => $user->get_name(), 'role' => $user->get_role());
//        }
//    }
//
//    /**
//     * Get any endorsements
//     */
//    public function get_endorsements() {
//        return $this->endorsements;
//    }

	private $id = 0;        // Interaction or discussion ID
    private $user = null;   // User for this message
    private $time = 0;      // Content timestamp
	private $metaData;

	private $message = '';  // Interaction or discussion message body
}