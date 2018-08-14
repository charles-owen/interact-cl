<?php
/**
 * @file
 * Base class for Interact system content: interaction and discussion items
 */

namespace CL\Interact;

use CL\Users\User;
use CL\Site\MetaData;

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
    /**
     * Constructor
     */
    public function __construct() {
    	$this->metaData = new MetaData();
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


//    /**
//     * Create a name as it is going to be displayed for users
//     * @param \User $user The user who is viewing
//     * @return string The name
//     */
//    public function display_name(\User $user) {
//        if($user->get_id() == $this->memberId) {
//            return "Me";
//        }
//
//        switch($this->userRole) {
//            case \User::INSTRUCTOR:
//                return \User::to_displayname($this->userName) . ', <span class="role">Instructor</span>';
//
//            case \USER::STAFF:
//                return \User::to_displayname($this->userName) . ', <span class="role">Staff</span>';
//        }
//
//        if($user->is_staff() || \User::role_is_staff($this->get_user_role())) {
//            return \User::to_displayname($this->get_user_name());
//        }
//
//        return "Student";
//    }
//
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

	private $messsage = '';
}