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
	 * @param Server $server The Server object
	 */
	public function __construct(Site $site, Server $server) {
		parent::__construct($site, []);

		$this->setTitle('Interact!');

		$this->interact = new InteractViewAux();
		$this->add_aux($this->interact);
		//$this->addCLS('cl-interact-', json_encode(['before'=>$this->before, 'after'=>$this->after]));

	}

	/**
	 * Present Interact and the page contents.
	 * @return string HTML
	 */
	public function present() {
		return $this->interact->present(true, 'cl-interact-page');

//		$html = <<<HTML
//<div class="full">
//<p class="centerbox primary">Please ask assignment-specific questions on the pages for the assignment. You will
//find an Interact! section at the bottom of each page. Only ask general course questions here. You can respond
//to assignment-specific questions here.</p>
//</div>
//HTML;
//
//		$html .= $this->exitContent() .  . $this->enterContent();
//		return $html;
	}

	private $interact;
}