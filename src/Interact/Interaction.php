<?php
/**
 * @file
 * Class that represents a single interaction
 */

/// Classes associated with the Interact! system
namespace CL\Interact;

use CL\Users\User;

/**
 * Class that represents a single interaction
 *
 * This class represents records in the interact table
 *
 * @cond
 * @property string assignTag
 * @property string sectionTag
 * @property string summary
 * @property boolean pin
 * @property boolean private
 * @property string type
 * @endcond
 */
class Interaction extends InteractContent {
    /// User question expecting an answer
    const QUESTION = 'Q';

    /// Announcement
    const ANNOUNCEMENT = 'A';

    /// Maximum length for message summaries in characters
    const SummarizeMax = 1000;

//	/**
//	 * Constructor
//	 * @param User $user User who created the interaction
//	 * @param string $assignTag Assignment/Category tag
//	 * @param string $sectionTag Optional section tag
//	 * @param string $type Type of interaction (constants above)
//	 * @param boolean $pin Pinned interaction
//	 * @param boolean $private Private interaction
//	 * @param int $time Time the interaction was created
//	 */
//    public function __construct(User $user=null, $assignTag, $sectionTag=null,
//                                $type, $pin, $private, $time=0) {
//        parent::__construct($user, $time);
//
//        $this->assignTag = $assignTag;
//        $this->sectionTag = $sectionTag;
//        $this->type = $type;
//        $this->pin = $pin;
//        $this->private = $private;
//    }

    public function __construct() {
		parent::__construct();
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
	        case 'assignTag':
	        	return $this->assignTag;

	        case 'sectionTag':
	        	return $this->sectionTag;

	        case 'summary':
	        	return $this->summary;

	        case 'pin':
	        	return $this->pin;

	        case 'private':
	        	return $this->private;

	        case 'type':
	        	return $this->type;

	        case 'created':
	        	return $this->created;

            default:
                return parent::__get($property);
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
			case 'assignTag':
				$this->assignTag = $value;
				break;

			case 'sectionTag':
				$this->sectionTag = $value;
				break;

			case 'summary':
				$this->summary = $value;
				break;

			case 'type':
				$this->type = $value;
				break;

			case 'pin':
				$this->pin = $value;
				break;

			case 'private':
				$this->private = $value;
				break;

			case 'created':
				$this->created = $value;
				break;

			default:
				parent::__set($property, $value);
				break;
		}
	}


	/**
	 * Update an interaction with new data
	 * @param string $summary New summary text
	 * @param string $html New HTML content
	 * @param array $post array with key 'type' and optional keys 'pin' and 'post'
	 */
    public function update($summary, $html, array $post) {
        $this->summary = $summary;
        $this->html = $html;
        $this->pin = isset($post['pin']);
        $this->private = isset($post['private']);
        $this->type = $post['type'] === 'Announcement' ? Interaction::Announcement : Interaction::Question;
    }

//	/**
//	 * Set the interaction summary text
//	 * @param $summary New text
//	 */
//    public function set_summary($summary) {
//        $this->summary = $summary;
//    }
//
//	/**
//	 * Get the interaction summary text
//	 * @return string Interaction summary
//	 */
//    public function get_summary() {
//        return $this->summary;
//    }
//
//	/**
//	 * Set the assignment and section tags
//	 * @param $tag New assignment tag
//	 */
//	public function set_assign_section($assignTag, $sectionTag) {
//		$this->assignTag = $assignTag;
//		$this->sectionTag = $sectionTag;
//	}
//
//	/**
//	 * Get the assignment/category tag
//	 * @return string Assignment tag
//	 */
//    public function get_assign_tag() {
//        return $this->assignTag;
//    }
//
//	/**
//	 * Get the section tag or null if none
//	 * @return string|null Section tag
//	 */
//    public function get_section_tag() {
//        return $this->sectionTag;
//    }
//
//	/**
//	 * Get the interaction type (Annoucement or Question)
//	 * @return Type constant
//	 */
//    public function get_type() {
//        return $this->type;
//    }
//
//	/**
//	 * Is this interaction pinned?
//	 * @return bool True if pinned
//	 */
//    public function is_pin() {
//        return $this->pin;
//    }
//
//	/**
//	 * Is this interaction private
//	 * @return bool True if private
//	 */
//    public function is_private() {
//        return $this->private;
//    }
//
//	/**
//	 * Set the interaction ID
//	 * @param int $id New interaction ID
//	 */
//    public function set_id($id) {
//        $this->id = $id;
//    }
//
//	/**
//	 * Get the interaction ID
//	 * @return int Interaction ID
//	 */
//    public function get_id() {
//        return $this->id;
//    }

	/**
	 * Get a summarized version of the interaction HTML
	 * @return string HTML/truncated
	 */
    public function summarize() {
        $summary = strip_tags($this->html);

        // Since the data from the clients is UTF8, just truncating
        // the string can lead to a broken multibyte character at
        // the end, which breaks JSON encoding. This ensures that
        // that cannot happen.
        return utf8_encode(substr(utf8_decode($summary), 0, self::SummarizeMax));
    }

//	/**
//	 * Set the number of discussions about this interaction
//	 * @param $r Number of discussions
//	 */
//    public function set_discussions($r) {
//        $this->discussions = $r;
//    }
//
//	/**
//	 * Get the number of discussions about this interaction
//	 * @return int Number of discussions
//	 */
//    public function get_discussions() {
//        return $this->discussions;
//    }



//	protected function write_xml_root(\XMLWriter $xml) {
//		parent::write_xml_root($xml);
//
//		if ($this->closed) {
//			$xml->writeAttribute('closed', 'yes');
//		}
//	}
//
//	protected function xml_root($xml) {
//		parent::xml_root($xml);
//
//		$c = $xml->getAttribute('closed');
//		$this->closed = $c === 'yes';
//	}
//
//	/**
//	 * Is discussion close for this iteraction.
//	 * @return bool True if discussion is closed
//	 */
//	public function is_closed() {
//		return $this->closed;
//	}
//
//	public function set_closed($closed = true) {
//		$this->closed = $closed;
//	}
//
//    /**
//     * Construct an attribution line for this interaction
//     * @param \User $user A user this is displaying for
//     * @param $email True if attribute is for email
//     * @return string Attribution
//     */
//    public function attribution(\User $user, $email=false) {
//        $assignTag = $this->assignTag;
//        if($assignTag === null || $assignTag === '') {
//            return '';
//        }
//
//        $assignment = $user->get_assignment($assignTag);
//        if($assignment !== null) {
//            $assignment->load();
//            $attr = $assignment->get_shortname();
//
//            $sectionTag = $this->sectionTag;
//
//            if($sectionTag !== null && $sectionTag !== '') {
//                if($assignment instanceof \Step\Step) {
//                    $section = $assignment->get_section($sectionTag);
//                    if($section !== null) {
//                        if(!$email) {
//                            $url = $this->course->get_libroot() . '/step/stepsection.php?step=' .
//                                $assignment->get_tag() . '&section=' . $sectionTag;
//
//                            $attr = '<a href="' . $url . '" target="INTERACT_STEP">' .
//                                $attr . "/" . $assignment->get_section($sectionTag)->get_name() .
//                                '</a>';
//                        } else {
//                            $attr = $attr . "/" . $assignment->get_section($sectionTag)->get_name();
//                        }
//                    }
//                }
//            }
//
//            return $attr;
//        }
//
//        switch($assignTag) {
//            case 'general':
//                return "General Course Information";
//
//            case 'test':
//                return "Test Messages";
//        }
//
//        return $assignTag;
//    }
//
//    /**
//     * Convert the Interaction an array that we can then convert to JSON
//     * @param \User $user User we are displaying this interaction for
//     * @return array
//     */
//    public function to_array(\User $user) {
//        return [
//            'id'=>$this->id,
//            'time'=>$this->time,
//            'type'=>$this->type,
//            'pin'=>$this->pin,
//            'private'=>$this->private,
//            'summary'=>$this->summary,
//            'discussions'=>$this->discussions,
//            'closed'=>$this->closed,
//            'attribution'=>$this->attribution($user),
//            'summarized'=> $this->summarize()
//        ];
//    }

    private $assignTag = null;
    private $sectionTag = null;
    private $created = 0;

    private $type;
    private $pin;
    private $private;
    private $summary = '';
    private $numDiscussions = 0;
	private $closed = false;
}