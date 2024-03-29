<?php
/**
 * @file
 * Automatic question answerer.
 */

namespace CL\Interact;

use CL\Site\Site;
use CL\Users\User;
use CL\Course\Members;
use CL\Users\Users;

/**
 * Automatic question answerer.
 *
 * Reads a configuration file in course/autoanswer.php if available.
 * Given a string, this class tries to automatically answer a given
 * user question based on regular expression analysis of questions.
 *
 * @property string answerer User id for autoanswer user or null if none
 * @property Closure xinteractionHook Function to be called when a new interaction arrives
 */
class Answerer {
	/// User preferences key for autoanswer options.
	///
	/// Used to keep track of if a user has seem some topic so they
	/// don't have to see it again
    const PREFERENCES_KEY = "autoanswer";

    /**
     * Answerer constructor.
     * @param Site $site The Site object
     * @param User $user The user we are answering for
     */
    public function __construct(Site $site, User $user) {
        $this->site = $site;
        $this->user = $user;

        //
        // Load the autoanswer file and execute it
        //
	    $configFile = $site->rootDir . '/' . $site->config . '/autoanswer.php';
        $config  = @include $configFile;
        if($config !== false && is_callable($config)) {
            $config($this);
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
		    case "answerer":
			    $this->answerer = $value;
			    break;

            case 'interactionHook':
                $this->interactionHook = $value;
                break;

            case 'discussionHook':
                $this->discussionHook = $value;
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
     * Post a new discussion onto an interaction
     * @param CL\Site\System\Server $server A valid Server object
     * @param int $time The time to stamp the response with
     * @param CL\Interact\Interaction $interaction Interaction we are responding to
     * @param string $text Text for the response
     */
    public function PostDiscussion($server, $time, $interaction, $text) {
        if($this->answerer === null) {
            return;
        }

        $site = $this->site;
        $user = $this->user;

        // Find the answering user
        $users = new Users($site->db);
        $answerUser = $users->getByUser($this->answerer);
        if($answerUser !== null) {

            // Find an associated membership for that user
            $members = new Members($site->db);
            $answerMember = $members->getBySection($answerUser->id,
                $user->member->semester, $user->member->sectionId);
            if($answerMember !== null) {
                // We found the membership, post the answer
                $answerUser->member = $answerMember;

                $discussion = new Discussion();
                $discussion->interactId = $interaction->id;
                $discussion->user = $answerUser;
                $discussion->time = $time;
                $discussion->message = $text;

                $discussions = new Discussions($site->db);
                $discussions->add($discussion);

                // Interaction time is set to the time of the discussion item
                $interaction->time = $time;
                $interaction->meta->set("public", Interact::INTERACTION_STATE, Interaction::ANSWERED);

                $interacts = new Interacts($site->db);
                $interacts->update($interaction);

                $email = new InteractEmail($site, $user, $server->email);
                $email->newDiscussion($interaction, $discussion);
            }
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
		    case "answerer":
			    return $this->answerer;

		    case "user":
			    return $this->user;

		    case "site":
			    return $this->site;

            case 'interactionHook':
                return $this->interactionHook;
                break;

            case 'discussionHook':
                return $this->discussionHook;
                break;

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
     * Lookup an answer to a question.
     * @param string $question Question to look up
     * @param string $assign Optional assignment tag this must be an answer for
     * @param string $section Optional section tag this must an answer for
     * @return array|null Array with these keys:
     *     file => The filename
     *     matches => The matches array from preg_match
     */
    public function lookup($question, $assign=null, $section=null) {
        $status = $this->user->member->meta->get(self::PREFERENCES_KEY);

        foreach($this->answers as $answer) {
            // Does an assignment matter here?
            if($answer['assign'] !== null) {
                // Assignment is required
                if($assign === null) {
                    continue;
                }

                // We accept an exact match for the assignment name
                // or a regular expression
                if($answer['assign'] !== $assign && !preg_match($answer['assign'], $assign)) {
                    continue;
                }
            }

            // Does the section matter here?
            if($answer['section'] !== null) {
                // Section is required
                if($section === null) {
                    continue;
                }

                // We accept an exact match for the section name
                // or a regular expression
                if($answer['section'] !== $section && !preg_match($answer['section'], $section)) {
                    continue;
                }
            }

            $matches = [];
            if(preg_match($answer['regex'], $question, $matches)) {
                $file = $answer['file'];

                // We have a match. Is there a repeat limit for this error match?
                if($answer['repeat'] !== null) {
                    $cnt = isset($status[$file]) ? $status[$file] : 0;

                    // Have see seen this enough?
                    if($cnt >= $answer['repeat']) {
                        continue;
                    }

                    $status[$file] = $cnt + 1;
	                $this->user->member->meta->setCategory(self::PREFERENCES_KEY, $status);
					$members = new Members($this->site->db);
					$members->writeMetaData($this->user->member);
                }

                $text = $this->retrieve_text($file, $matches);

                if($answer['prefix'] !== null) {
                    $text = $answer['prefix'] . $text;
                }

                return ['file'=>$answer['file'],
                        'matches'=>$matches,
                        'text'=>$text];
            }
        }

        return null;
    }

	/**
	 * Retrieve the text for a question
	 * @param string $file File relative to course root directory
	 * @param array $matches Matches from preg_match
	 * @return bool|false|null|string|string[]
	 */
    private function retrieve_text($file, $matches) {
        $filename = $this->site->rootDir . '/' . $file;
        if(substr($filename, -4) !== ".php") {
            $filename .= ".php";
        }

        $text = file_get_contents($filename);

        // Remove any of the page header content
        $head = '/^.*\$view\->header\(\); *\?>/s';
        $text = preg_replace($head, "", $text);

        // Remove trailing body tag
        $body = '#</body>.*$#s';
        $text = preg_replace($body, "", $text);

        // Remove page tail content
        $tail = '#<\?php *echo *\$view\->(tail|footer)\(\);.*$#s';
        $text = preg_replace($tail, "", $text);

        $GLOBALS['matches'] = $matches;

        ob_start();
        eval('global $site; global $matches; ?>' . $text);
        $text = ob_get_clean();

        return $text;
    }

    /**
     * Indicate an answer mapping.
     * @param string $regex Regular expression to match
     * @param string $file File relative to site root directory
     * @param string $assign Optional assignment - only responds for that assignment
     * @param string $section Optional assignment section - only responds for that section
     * @param int|null $repeat Optional maximum number of times to repeat this answer
     * @param string $prefix Optional prefix to add to the front of answer
     *
     * If optional arguments are not supplied or are supplied as null,
     * the option is not applied.
     */
    public function answer($regex, $file, $assign=null, $section=null, $repeat=null, $prefix=null) {
        $answer = ['regex'=>$regex,
            'file'=>$file,
            'assign'=>$assign,
            'section'=>$section,
            'repeat'=>$repeat,
            'prefix'=>$prefix];

        $this->answers[] = $answer;
    }


    /// The collection of possible answers
    private $answers = [];

    private $site;
    private $user;

    /// User id for the user who is answering the questions
    /// Usually this is a fake user.
    private $answerer = null;

    private $interactionHook = null;
    private $discussionHook = null;
}
