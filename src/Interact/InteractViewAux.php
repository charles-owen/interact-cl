<?php
/**
 * @file
 * ViewAux base file for Interact
 */

namespace CL\Interact;

use CL\Site\ViewAux;
use CL\Site\View;
use CL\Course\Section;


/**
 * ViewAux base file for Interact
 *
 * Common elements used by both InteractBaseView and InteractConsoleViewAux
 */
class InteractViewAux extends ViewAux {
	/**
	 * Constructor
	 * @param array $categories Array of categories or null to allow all categories.
	 * @param null $sectionTag Optional section for an assignment/category
	 */
	public function __construct(array $categories=null, $sectionTag=null) {
		$this->interact = new Interact();

		$this->categories = $categories;
		$this->sectionTag = $sectionTag;
	}

    /**
     * Called when this auxiliary view is installed in a view
     * @param View $view View we are installing into
     */
    public function install(View $view) {
    	$this->user = $view->user;
	    $this->categoriesSet($view->section);

	    if($view->site->installed('grades') &&
	        $view->user->staff) {
	    	$this->gradingLink = $view->site->grades->gradingLink;
	    }

		//
	    // Does the file interact.php exist in the config directory?
	    //
	    $configFile = $view->site->rootDir . '/' . $view->site->config . "/interact.php";
	    $config  = @include $configFile;
	    if($config !== false && is_callable($config)) {
		    $config($this->interact);
	    }

	    $view->addJS('interact');
    }

	/**
	 * Present Interact on a page.
	 *
	 * This creates the Interact! div and fills it with information that Interact needs.
	 * @param bool $open If true, open Interact immediately.
	 * @param string $id ID to use for the DIV, default is cl-interact
	 * @return string HTML
	 */
    public function present($open=false, $id='cl-interact') {
    	$data = [
    		'open'=>$open,
		    'categories'=>$this->categoriesData,
		    'section'=>$this->sectionTag,
		    'interact'=>$this->interact->data($this->user)
	    ];

		if($this->gradingLink !== null) {
			$data['gradingLink'] = $this->gradingLink;
		}

    	$json = htmlspecialchars(json_encode($data), ENT_NOQUOTES);

    	$html = <<<HTML
<div id="$id" style="display:none">$json</div>
HTML;

    	return $html;
    }

	/**
	 * Set the value of
	 */
	protected function categoriesSet(Section $section) {
		$this->categoriesData = [];

		if($this->categories === null) {
			/*
			 * Collect up all possible categories based on the
			 * the assignment names
			 */
			$categoryNames = [];

			$assignments = $section->assignments;
			foreach($assignments->categories as $category) {
				foreach($category->assignments as $assignment) {
					$categoryNames[$assignment->tag] = $assignment->name;
				}
			}

			// Natural sort them.
			natcasesort($categoryNames);

			/*
			 * And put them into the categories data
			 * General categories go first...
			 */
			foreach(Interact::standard_categories() as $key => $value) {
				$this->categoriesData[] = ['tag'=>$key, 'name'=>$value];
			}

			foreach($categoryNames as $key => $value) {
				$this->categoriesData[] = ['tag'=>$key, 'name'=>$value];
			}

		} else {
			foreach($this->categories as $category) {
				$assignment = $section->get_assignment($category);
				$this->categoriesData[] = ['tag'=>$assignment->tag, 'name'=>$assignment->name];
			}
		}

	}

	private $interact;

	private $user = null;           // User we are displaying for
	private $categories;            // Categories we will look at or null if none provided, meaning use a chooser
	private $sectionTag;            // Optional section tag
	private $categoriesData = [];   // Categories data send to the client
	private $gradingLink = null;    // Any provided grading link?
}