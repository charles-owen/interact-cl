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
 * @property int id
 * @property int time
 * @property int created
 * @endcond
 */
class Interaction extends InteractContent {

	/// type: User question expecting an answer
    const QUESTION = 'Q';

    /// type: Announcement
    const ANNOUNCEMENT = 'A';

    //
	// The interaction states
	//
    const PENDING = 'P';    ///< Requiring an answer
    const ANSWERED = 'A';   ///< Has been answered
    const RESOLVED = 'R';   ///< Problem has been resolved
    const CLOSED = 'C';     ///< Interaction is closed for further discussion

	/**
	 * Interaction constructor.
	 * @param array $row Database table row
	 * @param string $prefix Optional row name prefix for Interact table content.
	 */
    public function __construct(array $row = null, $prefix='') {
		parent::__construct($row, $prefix);

		if($row !== null) {
			$this->created = strtotime($row["{$prefix}created"]);
			$this->time = strtotime($row["{$prefix}time"]);
			$this->assignTag = $row["{$prefix}assigntag"];
			$this->sectionTag = $row["{$prefix}sectiontag"];
			$this->type = $row["{$prefix}type"];
			$this->pin = +$row["{$prefix}pin"] == 1;
			$this->private = +$row["{$prefix}private"] == 1;
			$this->summary = $row["{$prefix}summary"];
			if(!empty($row["{$prefix}discussions"])) {
				$this->discussionCnt = $row["{$prefix}discussions"];
			} else {
				$this->discussionCnt = null;
			}

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
		$data = parent::data($site, $user);

		$data1 = [
			'created'=>$this->created,
			'assign'=>$this->assignTag,
			'section'=>$this->sectionTag,
			'pin'=>$this->pin,
			'private'=>$this->private,
			'summarized'=>$this->summarize(),
			'summary'=>$this->summary,
			'discussionsCnt'=>$this->discussionCnt,
			'attribution'=>$this->attribution($site, $user),
			'closed'=>false,
			'type'=>$this->type
		];

		if($this->type === self::QUESTION) {
			$data1['state'] = $this->meta->get('public', Interact::INTERACTION_STATE, self::RESOLVED);
			$escalated = $this->meta->get('public', Interact::ESCALATED, null);
			if($escalated !== null) {
				$data1['escalated'] = $escalated;
			}
		}

		return array_merge($data, $data1);
	}

	/**
	 * Create client data when in interaction mode.
	 * @param Site $site The Site object
	 * @param User $user The current user
	 * @return array results
	 */
	public function data(Site $site, User $user) {
		$data = $this->summaryData($site, $user);

		$discussions = [];
		foreach($this->discussions as $discussion) {
			$discussions[] = $discussion->data($site, $user);
		}

		$data1 = [
			'message'=>$this->message,
			'discussions'=>$discussions,
			'history'=>$this->historyData($user)
		];

		// Some additional data goes to staff only
		if($user->atLeast(Member::STAFF)) {
			$data1['memberid'] = $this->user->member->id;
		}

		// Are we following?
		$interFollows = new InterFollows($site->db);
		$data1['following'] = $interFollows->getFollowing($user->member->id, $this->id);

		return array_merge($data, $data1);
	}

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