<?php
/**
 * @file
 * Standard full Interact page
 * /cl/interact
 */

namespace CL\Interact;

use CL\Site\Site;
use CL\Course\View;
use CL\Site\System\Server;
use CL\Course\Member;

/**
 * Standard full Interact page
 *
 * /cl/interact
 */
class InteractView extends View {
	/**
	 * View constructor.
	 * @param Site $site The Site object
	 */
	public function __construct(Site $site, Server $server) {
		parent::__construct($site, []);

		$this->get = $server->get;

		$this->setTitle('Interact!');

		$this->interact = new InteractViewAux();
		$this->add_aux($this->interact);

//		$assignTag = strip_tags($properties['assigntag']);
//
//		$assignment = $this->section->get_assignment($assignTag);
//		if($assignment === null) {
//			$server->redirect($site->root . '/');
//		}
//
//		$assignment->load();
//
//		$grading = $assignment->grading;
//
//		// Get all existing grade data
//		$grades = $grading->getUserGrades($this->user);
//
//		//
//		// Construct data to send to client
//		//
//		$data = [];
//
//		// Determine if assignment is due, yet?
//		if(!$assignment->after_due($this->user, time())) {
//			$data['due'] = $assignment->get_due($this->user);
//		}
//
//		$data['grades'] = $grading->presentGrades($this->user, $grades);
//		$data['grade'] = $grading->computeGrade($this->user, $grades);
//		$data['assignment'] = [
//			'name'=> $assignment->name,
//			'shortName'=>$assignment->shortName
//		];
//
//		$this->setTitle('Grade View');
//		$this->addJS('grades');
//		$this->addStaff();
//		$this->addCLS('cl-grade-assignment', json_encode($data));
	}

	public function present() {
		$html = <<<HTML
<div class="full">
<p class="centerbox primary">Please ask assignment-specific questions on the pages for the assignment. You will
find an Interact! section at the bottom of each page. Only ask general course questions here. You can respond
to assignment-specific questions here.</p>
</div>
HTML;

		$html .= $this->interact->present(true, $this->get);
		return $html;
	}

	private $interact;
	private $get;
}