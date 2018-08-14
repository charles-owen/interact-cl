<?php
/**
 * @file
 * Abstract base class is the base for derived Interact views
 */

namespace Interact;

/**
 * Basic Interact
 *
 * This class maintains elements of the views in the Interact system that
 * are common. Actual instantiated object will be InteractView in the global
 * namespace.
 */
class InteractBaseView extends InteractViewAux {


    /**
     * Called when this auxilliary view is installed in a view
     * @param View $view View we are installing into
     */
    public function install(\View $view) {
        parent::install($view);



        $js = <<<HTML
INTERACT.set_roots('$libroot', '$ajaxroot');

HTML;

        if($this->user->is_guest()) {
            $js .= <<<HTML
INTERACT.guest = true;

HTML;
        }

        $js .= $this->interact->js($this->user);

        $catarray = '';
        foreach($this->categories as $category) {
            if(strlen($catarray) > 0) {
                $catarray .= ',';
            }
            $catarray .= "'" . $category . "'";
        }
        $js .= "INTERACT.set_categories([$catarray]);\n";

        if($this->sectionTag !== null) {
            $js .= "INTERACT.set_section('$this->sectionTag');\n";
        }

        $view->js = $js;
    }




	/**
	 * Handle a request from the AJAX script
	 * @param $request $_GET
	 * @param $time Current time
	 * @return string JSON result as sent
	 */
	public function request($request, $time) {
		if(!isset($request['cmd'])) {
			return $this->error("Invalid request");
		}

		if(isset($request['categories'])) {
			$this->categories = $request['categories'];
			if(count($this->categories) == 0) {
				$this->chooser = true;
				$this->categories_set();
			} else {
				$this->chooser = false;
			}
		}

		if(isset($request['section']) && strlen($request['section']) > 0) {
			$this->sectionTag = strip_tags($request['section']);
		}

		switch($request['cmd']) {
			case 'ui':
                return $this->present_ui($request, $time);

            case 'fetch':
                return $this->fetch_interactions($request, $time);

			case 'question-form':
                return $this->present_question_form($request);

			case 'question':
				return $this->present_question($request, $time);
		}

		return $this->error('Invalid request');
	}



	/**
	 * Present the basic HTML form - just the banner
	 * @return string HTML for the basic form
	 */
	public function present($open = false, $get = null) {
		$libroot = $this->course->get_libroot();

		$interacts = new \Interact\Interacts($this->course);
		$counts = $interacts->counts($this->user->get_section(), $this->categories, $this->sectionTag);
		$questions = $counts[\Interact\Interaction::Question];
		$announcements = $counts[\Interact\Interaction::Announcement];

		if($questions == 1) {
			$questions .= " question";
		} else {
			$questions .= " questions";
		}

		if($announcements == 1) {
			$announcements .= " announcement";
		} else {
			$announcements .= " announcements";
		}

		$immediateId = '';
		if($get !== null && isset($get['i'])) {
			$immediateId = 'INTERACT.immediateId = ' . strip_tags($get['i']) . "\n";
		}

		// This ends the "content" div
		$html = '</div>';
		if($open) {
			$html .= <<<HTML
<script>
INTERACT.immediateOpen = true;
$immediateId</script>
<div class="interact" id="interact">
<h2 class="banner"><span class="message"></span></h2>
<div class="interact-body"></div></div>
HTML;
		} else {
			$html .= <<<HTML
<div class="interact" id="interact">
<h2 class="banner"><button class="start"><img src="$libroot/images/interact/logo16.png" width="16" height="16"> Interact!</button>
<span class="info">$questions, $announcements</span>
<span class="message"></span></h2>
</div>
HTML;
		}

		// Restart the "content" div
		$html .= '<div class="content">';

		return $html;
	}


	private function error($msg) {
		return json_encode(array('ok' => false, 'msg' => $msg));
	}

	/**
	 * Present the basic user interface for the page
	 *
	 * Called by $this->request
	 * @param $request $_REQUEST array
	 * @param $time The current time
	 * @return string json for the page
	 */
	private function present_ui($request, $time) {
		$html = "";

		$libroot = $this->course->get_libroot();

		$welcome = <<<HTML
<p class="welcome">Welcome to the Interact! System. Press the Ask a Question button to
ask a question about this assignment or section. Choose previously asked questions on the
left to view them in more detail and add to the discussion.</p>
HTML;

		/*
		 * The category chooser only appears if we have not selected any
		 * assignment for this instance.
		 */
		$chooser = $this->chooser ? $this->categories_menu() : '';

		$button = '';
		if(!$this->user->is_guest()) {
			$button = <<<HTML
<button class="ask" type="button"><img src="$libroot/images/interact/logo16.png" width="16" height="16"> &iquest;Ask a Question?</button>
HTML;
		}

		$html .= <<<HTML
<form class="search">
<h2 class="banner">$chooser{$button} Interact!
<span class="message"></span><span class="search"><input type="text" placeholder="Search..." name="search"><button type="submit">Search</button></span>
</h2>
</form>
<div class="interact-body">
<div class="interactions"></div>
<div class="interaction">
$welcome
</div>
</div>
</div>
HTML;

		/*
		 * The list of interactions
		 */
		$interactions = array();

		$interacts = new \Interact\Interacts($this->course);
		$interacts->select($this->user, $this->categories, $this->sectionTag, null);
		while(($current = $interacts->get_current()) !== null) {
			$interactions[] = $current->to_array($this->user);
			$interacts->advance();
		}

		$json = json_encode(['ok' => true,
            'html' => $html,
            'interactions'=>$interactions,
			'welcome' => $welcome,
            'more' => count($interactions) >= $interacts->limit]);

		if($json === false)
        {
            return $this->error(json_last_error_msg());
        }

		return $json;
	}


    /**
     * Handle a request for interactions.
     *
     * Called by $this->request on the fetch command.
     * Used to get more interactions and for searches.
     * @param $request $_GET array
     * @param $time The current time
     * @return string json for the page
     */
    private function fetch_interactions($request, $time) {
        $html = "";

        $oldest = isset($request['oldest']) ? strip_tags($request['oldest']) : null;
        $query = isset($request['query']) ? strip_tags($request['query']) : null;
        $interactions = array();

        $interacts = new \Interact\Interacts($this->course);
        $interacts->select($this->user, $this->categories, $this->sectionTag, $oldest, null, $query);
        while(($current = $interacts->get_current()) !== null) {
            $interactions[] = $current->to_array($this->user);
            $interacts->advance();
        }

        $json = json_encode(['ok' => true,
            'html' => $html,
            'interactions'=>$interactions,
            'more' => count($interactions) >= $interacts->limit]);

        if($json === false) {
            return $this->error(json_last_error_msg());
        }

        return $json;

    }



    /**
	 * Create the course categories chooser menu
	 */
	private function categories_menu() {
		$libroot = $this->course->get_libroot();

		$chooser = <<<HTML
<div class="menu"><a class="categories" href="javascript:;"><img alt="Categories Menu" src="$libroot/images/interact/catmenu.png"></a>
<ul>
<li id="all_cat"><span class="menuicon"/><a class="category" href="javascript:;">Choose all</a></li>
<li><span class="menusep" /><span class="menusep" /><span class="menusep" /></li>
HTML;

		foreach($this->categoryNames as $tag => $name) {
			$check = $this->categoryActives[$tag] ?
				'<a class="category menuicon" href="javascript:;"><img src="' . $libroot .
				'/images/interact/check16.png"></a>' :
				'<span class="menuicon"/>';

			$chooser .= <<<HTML
<li id="$tag">$check<a class="category" href="javascript:;">$name</a> <a class="only" href="javascript:;">(only)</a> </li>
HTML;
		}

		$chooser .= <<<HTML
</ul>
</div>
HTML;

		return $chooser;
	}

	/**
	 * Present a question in the right side are of the window
	 * @param $request Any $_REQUEST
	 * @param $time Current time
	 * @return string JSON with the keys: 'ok', 'html', 'id', 'discussion'
	 */
	private function present_question($request, $time) {
		if(!isset($request['id'])) {
			return $this->error("Invalid question id");
		}

		$id = $request['id'];

        /* Indicate the user is not currently editing an interaction */
        $interactive = new InterActive($this->course);
        $interactive->delete($this->user);

		/*
		 * Get the interaction and convert to HTML
		 */
		$interacts = new \Interact\Interacts($this->course);
		$interaction = $interacts->get($id);
		if($interaction === null) {
			return $this->error("Invalid question id");
		}

		$html = '<div class="interact-present" id="ip' . $id . '">';
		$html .= $this->interaction_to_right_html($interaction, $time);
		$html .= '</div>';

		/*
		 * Get the discussions and convert to HTML
		 */
		$discussionView = new \Interact\DiscussionView($this->course, $this->user, $id);
		$discussion = $discussionView->get_discussion($time);

		/*
		 * Return the result
		 */
		return json_encode(array('ok' => true, 'html' => $html,
				'id' => $interaction->get_id(),
				'closed' => $interaction->is_closed(),
				'interaction' => $interaction->get_html(),
				'discussion' => $discussion)
		);
	}

	/**
	 * Present the form for entering/editing a question
	 * @param $request $_REQUEST
	 * @return string HTML for the form
	 */
	private function present_question_form($request) {
		$html = '';

		$edit = isset($request['id']);
		$interaction = null;

		$questionChecked = ' checked';
		$announceChecked = '';
		$pinChecked = '';
		$privateChecked = '';
		$summary = '';
		$question = '';
		$selectedCategory = "general";

		if($edit) {
			$id = strip_tags($request['id']);
			$interacts = new \Interact\Interacts($this->course);
			$interaction = $interacts->get($id);
			if($interaction === null) {
				return $this->error("Unable to edit!");
			}

			if(!$this->user->is_staff() && $interaction->get_user_id() !== $this->user->get_id()) {
				return $this->error("You do not have permission to edit!");
			}

			$questionChecked = $interaction->get_type() === \Interact\Interaction::Question ? ' checked' : '';
			$announceChecked = $interaction->get_type() === \Interact\Interaction::Announcement ? ' checked' : '';
			$pinChecked = $interaction->is_pin() ? ' checked' : '';
			$privateChecked = $interaction->is_private() ? ' checked' : '';
			$summary = $interaction->get_summary();
			$question = $interaction->get_html();
			$selectedCategory = $interaction->get_assign_tag();

			$html .= <<<HTML
<div class="interact-content">
<h3 class="interaction-heading">Edit Interaction</h3>
<form class="question-form" action="post">
<input type="hidden" name="cmd" value="edit">
<input type="hidden" name="id" value="$id">

HTML;
			$select = '';
		} else {
			$html .= <<<HTML
<div class="interact-content">
<h3 class="interaction-heading">New Interaction</h3>
<form class="question-form" action="post">
<input type="hidden" name="cmd" value="post">
HTML;

			if($this->sectionTag !== null) {
				$html .= <<<HTML
<input type="hidden" name="section" value="$this->sectionTag">
HTML;
			}
		}

		$staffOptions = '';
		$select = '';
		if($this->user->is_staff()) {
			$staffOptions = <<<HTML
<input type="checkbox" name="sendall"> Email to all users (staff only)<br />
&nbsp;
HTML;

			// Removed: <input type="checkbox" name="forcefollow"> Force follow all users (staff only)

			/*
			 * Is there one category selected?
			 */
			if(count($this->categories) == 1 && current($this->categories) != 'all') {
				$select = '<input type="hidden" name="category" value="' . current($this->categories) . '">';
			} else {
				$select = " <label for=\"interact_category\">Category: </label><select id=\"interact_category\" name=\"category\">";
				foreach($this->categoryNames as $key => $name) {
					if(!$this->categoryActives[$key]) {
						continue;
					}

					$sel = ($key === $selectedCategory) ? " selected" : "";
					$select .= "<option value=\"$key\"$sel>$name</option>";
				}
				$select .= "</select>";
			}
		} else {
			if(count($this->categories) == 1 && current($this->categories) != 'all') {
				$select = '<input type="hidden" name="category" value="' . current($this->categories) . '">';
			}
		}

		$html .= <<<HTML
<p class="question-type"><input type="radio" name="type" value="Question"$questionChecked> Question<br />
<input type="radio" name="type" value="Announcement"$announceChecked> Announcement</p>
<p class="question-type"><input type="checkbox" name="pin"$pinChecked> Pin<br />
<input type="checkbox" name="private"$privateChecked> Private (only staff can see)</p>
<p class="question-type">$staffOptions</p>
<p><label for="summary">Question Summary: </label>
<input class="summary" type="text" name="summary" id="summary"
  placeholder="A short summary of your question" value="$summary"></p>
<p>Question:<br /><textarea name="question" id="interact_question">$question</textarea>
</p>
<p><input type="submit" value="Post"> <input type="button" value="Cancel"> $select</p>
</form>
</div>
HTML;

		return json_encode(array('ok' => true, 'html' => $html, 'edit' => $edit));
	}

	/**
	 * Construct an attribution line for an interaction
	 * @param \Interact\Interaction $interaction The interaction we are interested in
	 * @return string Attribution
	 */
	public function attribution(\Interact\Interaction $interaction, $email=false) {
		$assignTag = $interaction->get_assign_tag();
		if($assignTag === null || $assignTag === '') {
			return '';
		}

		$assignment = $this->user->get_assignment($assignTag);
		if($assignment !== null) {
			$assignment->load();
			$attr = $assignment->get_shortname();

			$sectionTag = $interaction->get_section_tag();

			if($sectionTag !== null && $sectionTag !== '') {
				if($assignment instanceof \Step\Step) {
					$section = $assignment->get_section($sectionTag);
					if($section !== null) {
						if(!$email) {
							$url = $this->course->get_libroot() . '/step/stepsection.php?step=' .
								$assignment->get_tag() . '&section=' . $sectionTag;

							$attr = '<a href="' . $url . '" target="INTERACT_STEP">' .
								$attr . "/" . $assignment->get_section($sectionTag)->get_name() .
								'</a>';
						} else {
							$attr = $attr . "/" . $assignment->get_section($sectionTag)->get_name();
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
	 * Create the display HTML for the right side Interaction display.
     *
     * This does leave the actual message text empty so it can be filled
     * in on the client side.
	 * @param \Interact\Interaction $current Interaction to display
	 * @param $currentTime Current time
	 * @return string HTML for the right side up to, but not including, the disucssions
	 */
	public function interaction_to_right_html(\Interact\Interaction $current, $currentTime) {
		$html = '';
		$libroot = $this->course->get_libroot();
		$id = $current->get_id();

		// Is this user following?
		$interFollows = new \Interact\InterFollows($this->course);
		$following = $interFollows->get_following($this->user->get_id(), $id) === \Interact\InterFollows::FOLLOWING;

		$summary = $current->get_summary();
		$date = $this->display_time($current->get_time(), $currentTime);
		$name = $current->display_name($this->user);
		$attr = $this->attribution($current);
		$menu = '';

		$pin = $current->is_pin() ?
			'<img src="' . $libroot . '/images/interact/pin25.png" alt="Pinned" width="17" height="25">' : '';
		$type = $current->get_type();
		$announce = '';
		$followImg = $this->follow_image($following);

		if($type === \Interact\Interaction::Announcement) {
            $announce = '<img src="' . $libroot . '/images/interact/megaphone25.png" alt="Announcement" width="29" height="26">';
            $quest = $current->is_private() ? "Private announcement by" : "Announcement by";

        } else {
            $quest = $current->is_private() ? "Private question by" : "Question by";
        }

		/*
		 * Staff and owner can edit
		 */
		if($this->user->is_staff() || $this->user->get_id() == $current->get_user_id()) {
			$menu = <<<HTML
<div class="menu"><a href="javascript:;"><img src="$libroot/images/menubars.png"></a>
<ul>
    <li><a class="edit" href="javascript:;"><img src="$libroot/images/pen16.png"></a> <a class="edit" href="javascript:;">Edit</a></li>
    <li><a class="delete" href="javascript:;"><img src="$libroot/images/x.png"></a> <a class="delete" href="javascript:;">Delete</a></li>
HTML;

			if($this->user->is_staff()) {

//				$menu .= <<<HTML
//<li><a class="allfollow" href="javascript:;"><img src="$libroot/images/interact/logo16.png"></a> <a class="allfollow" href="javascript:;">Force follow all users</a></li>
//HTML;

				//
				// Optionally add the Grading page link
				//
				$assignTag = $current->get_assign_tag();
				if($assignTag === null || $assignTag === '') {
					return '';
				}

				$assignment = $this->user->get_assignment($assignTag);
				if($assignment !== null) {
					$gradingUrl = $this->course->get_libroot() . '/grading/assignmentgrader.php?tag=' .
						$assignment->get_tag() . '&id=' . $current->get_user_id();
					$menu .= <<<HTML
<li><a class="grading" href="$gradingUrl" target="INTERACT_GRADING"><img src="$libroot/images/interact/grading.png"></a> <a class="grading" href="$gradingUrl" target="INTERACT_GRADING">Grading page</a></li>
HTML;
				}

				if(!$current->is_closed()) {
					$menu .= <<<HTML
<li><a class="close" href="javascript:;"><img src="$libroot/images/interact/close.png"></a> <a class="close" href="javascript:;">Close discussion</a></li>
HTML;
				}

			}

			$menu .= <<<HTML
</ul>
</div>
HTML;
		}

		$html .= <<<HTML
<div class="interact-content">
<h3 class="interaction-heading">$menu<span>$date<br>
<button class="follow">$followImg</button> @$id</span>{$pin}{$announce}$summary</h3>
<p class="link">$quest $name / $attr</p>
<div class="content"></div>
HTML;

		foreach($current->get_edits() as $edit) {
			$name = "Student";
			if($edit['id'] == $this->user->get_id()) {
				$name = "me";
			} else if($this->user->is_staff()) {
				$name = \User::to_displayname($edit['name']);
			}
			$time = $this->display_time($edit['time'], $currentTime, true);
			$html .= <<<HTML
<p class="edits">Edited by $name $time</p>
HTML;
		}

		$html .= <<<HTML
</div>
HTML;

		return $html;
	}

	/**
	 * Get appropriate img tag for the following image
	 * @param bool $following True if user is following
	 */
	public function follow_image($following) {
		if($this->user->is_guest()) {
			return '';
		}

		$libroot = $this->course->get_libroot();
		if($following) {
			$followImg = <<<HTML
<img src="$libroot/images/interact/following.png" alt="Following" height="16" width="92">
HTML;
		} else {
			$followImg = <<<HTML
<img src="$libroot/images/interact/follow.png" alt="Not Following" height="16" width="92">
HTML;
		}

		return $followImg;
	}

	private function display_name(\Interact\Interaction $current) {
		if($this->user->is_staff() || User::role_is_staff($current->get_user_role())) {
			return \User::to_displayname($current->get_user_name());
		}

		return "Student";
	}

}