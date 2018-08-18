<?php
/**
 * @file
 * Class that represents a single interaction
 */

/// Classes associated with the Interact! system
namespace CL\Interact;

use CL\Course\Member;
use CL\Users\User;
use CL\Course\Course;
use CL\Site\Site;

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

	/**
	 * Interaction constructor.
	 * @param array $row Database table row
	 * @param string $prefix Optional row name prefix for Interact table content.
	 */
    public function __construct(array $row = null, $prefix='') {
		parent::__construct($row, $prefix);

		if($row !== null) {
			$this->created = strtotime($row["{$prefix}created"]);
			$this->assignTag = $row["{$prefix}assigntag"];
			$this->sectionTag = $row["{$prefix}sectiontag"];
			$this->type = $row["{$prefix}type"];
			$this->pin = +$row["{$prefix}pin"] == 1;
			$this->private = +$row["{$prefix}private"] == 1;
			$this->summary = $row["{$prefix}summary"];
			$this->discussionCnt = $row["{$prefix}discussions"];
		}
    }

    /**
     * Property get magic method
     *
     * <b>Properties</b>
     * Property | Type | Description
     * -------- | ---- | -----------
     * assignTag | string | Assignment tag for this interaction
     * created | int | When interaction was created
     * discussions | array | Array of Discussion objects for this interaction
     * pin | bool | True if interaction is pinned
     * private | bool | True if interaction is private
     * type | string | Interaction type, Interaction::QUESTION or Interactin::ANNOUNCEMENT
     * sectionTag | string | Section tag for this interaction or null if none
     * summary | string | Interaction summary line
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

	        case 'discussions':
	        	return $this->discussions;

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
	 * assignTag | string | Assignment tag for this interaction
	 * created | int | When interaction was created
	 * discussions | array | Array of Discussion objects for this interaction
	 * pin | bool | True if interaction is pinned
	 * private | bool | True if interaction is private
	 * sectionTag | string | Section tag for this interaction or null if none
	 * summary | string | Interaction summary line
	 * type | string | Interaction type, Interaction::QUESTION or Interactin::ANNOUNCEMENT
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

			case 'discussions':
				$this->discussions = $value;
				$this->discussionCnt = count($this->discussions);
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

	/**
	 * Construct an attribution line for an interaction.
	 *
	 * This is an indication of the assignment and optional section like Step 1/Task 1
	 * @return string Attribution
	 */
	public function attribution(Site $site, User $user, $email=false) {
		$course = $site->course;
		$assignTag = $this->assignTag;
		if($assignTag === null || $assignTag === '') {
			return '';
		}

		$section = $course->get_section_for($user);
		$assignment = $section->get_assignment($assignTag);
		if($assignment !== null) {
			$assignment->load();
			$attr = $assignment->shortName;

			$sectionTag = $this->sectionTag;
			if($sectionTag !== null && $sectionTag !== '') {
				if($assignment instanceof \CL\Step\Step) {
					$stepSection = $assignment->get_section($sectionTag);

					if($stepSection !== null) {
						if(!$email) {
							$url = $site->server . $stepSection->url;

							$attr = '<a href="' . $url . '" target="INTERACT_STEP">' .
								$attr . "/" . $stepSection->name .
								'</a>';
						} else {
							$attr = $attr . "/" . $stepSection->name;
						}
					}
				}
			}

			return $attr;
		}

		switch($assignTag) {
			case 'general':
				return "General Course Information";

			case 'test':
				return "Test Messages";
		}

		return $assignTag;
	}

	/**
	 * Create client data when in summary mode.
	 * @param Site $site The Site object
	 * @param User $user The current user
	 * @return array results
	 */
	public function summaryData(Site $site, User $user) {
		return [
			'id'=>$this->id,
			'assign'=>$this->assignTag,
			'section'=>$this->sectionTag,
			'pin'=>$this->pin,
			'private'=>$this->private,
			'time'=>$this->time,
			'summarized'=>$this->summarize(),
			'summary'=>$this->summary,
			'discussionsCnt'=>$this->discussionCnt,
			'attribution'=>$this->attribution($site, $user),
			'closed'=>false,
			'type'=>$this->type
		];
	}

	/**
	 * Create client data when in interaction mode.
	 * @param Site $site The Site object
	 * @param User $user The current user
	 * @return array results
	 */
	public function data(Site $site, User $user) {
		$data = parent::data($site, $user);

		$discussions = [];
		foreach($this->discussions as $discussion) {
			$discussions[] = $discussion->data($site, $user);
		}

		$data1 = [
			'assign'=>$this->assignTag,
			'section'=>$this->sectionTag,
			'pin'=>$this->pin,
			'private'=>$this->private,
			'created'=>$this->created,
			'summarized'=>$this->summarize(),
			'summary'=>$this->summary,
			'discussionsCnt'=>$this->discussionCnt,
			'attribution'=>$this->attribution($site, $user),
			'closed'=>false,
			'type'=>$this->type,
			'message'=>$this->message,
			'discussions'=>$discussions
		];

		$data1['history'] = $this->historyData($user);

		// Some additional data goes to staff only
		if($user->atLeast(Member::STAFF)) {
			$data1['memberid'] = $this->user->member->id;
		}

		// Are we following?
		$interFollows = new InterFollows($site->db);
		$data1['following'] = $interFollows->getFollowing($user->member->id, $this->id);

		return array_merge($data, $data1);
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
    private $discussionCnt = 0;
    private $discussions = [];
}