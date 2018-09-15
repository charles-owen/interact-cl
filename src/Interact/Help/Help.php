<?php
/**
 * @file
 * Example of a PHP view class
 */

namespace CL\Interact\Help;


use CL\Site\Site;

/**
 * Example of a PHP view class
 */
class Help extends \CL\Course\View {
	/**
	 * Help constructor.
	 * @param Site $site Site object
	 */
	public function __construct(Site $site) {
		parent::__construct($site);

		$this->setTitle('Interact! Help');
	}


	/**
	 * Present the section selector
	 * @return string HTML
	 */
	public function present() {
		$img = $this->site->root . '/vendor/cl/interact/img';

		$html = <<<HTML
<figure class="right noshadow"><img src="$img/logo350.png" alt="Interact Logo"></figure>	
<figure><img src="$img/example.png" alt="Example Interact screen"></figure>	
<p>The Interact! system provides a tool for course communications including announcements and questions.</p>

<h2>Question Status</h2>
<p>The Interact! system supports questions and announcements. Annoucements always appear with a white 
background. Questions appear with different background colors depending on the question state. The 
question states are:</p>

<h3>Pending</h3>
<figure class="right"><img src="$img/pending-example.png" alt="Pending"></figure>
<p>A pending question either has just been posted or has had a new discussion added by a student. This 
color indicates that the question is awaiting a response from course staff.</p>
<p>Email is sent to the course staff currently monitoring Interact! when any Interaction becomes
pending.</p>

<h3>Answered</h3>
<figure class="right"><img src="$img/answered-example.png" alt="Answered"></figure>
<p>Once a question has been answered by course staff, it appears with the answered color.
The answered color only appears when the answer is by a member of the course staff. If a student
answers a question for another student, which is perfectly okay, the color remains pending, since
staff will want to read this answer to ensure it is correct, at which time they may answer the 
question themselves or indicate that it has been resolved.</p>

<h3>Resolved or Closed</h3>
<figure class="right"><img src="$img/resolved-example.png" alt="Resolved"></figure>
<p>Course staff and the user who posted the original Interaction will have a Resolved menu 
option in the Interaction and Discussion menus. Selecting this option indicates that the 
question has been resolved. Course staff may select this option when they are confident the 
question has been answered and students can select this option if they have resolved the 
problem themselves or the answers so far resolved it sufficiently.</p>
<p>A resolved question can be opened back up at any time by posting a new discussion item.</p>
<p>Course staff can also close an interaction. Closed interactions allow no further 
discussion. Both closed and resolved interactions appear in the summary with a white background.</p>

HTML;


		if($this->user->staff) {
			$html .= <<<HTML
<h2>Staff Features</h2>			
<p>Staff members can escalate a question to TA's or the Instructor.</p>
HTML;

		}

		return $html;
	}

}
