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
	    $this->categoriesSet($view->section);

		//
	    // Does the file interact.php exist in the courses directory?
	    //
	    $configFile = $view->site->root . "/course/interact.php";
	    $config  = @include $configFile;
	    if($config !== false && is_callable($config)) {
		    $config($this->interact);
	    }

	    $view->addJS('interact');

//        $view->add_js('js/ckeditor/ckeditor.js');
//        $view->add_js_timestamped($interactJs);
//        $view->add_css('interact/ck_interact.css');
//        $view->add_css('interact/ck_interact_content.css');
//        $view->js = "var INTERACT = new Interact();\n";

    }

    public function present($open=false, $get) {
    	$data = [
    		'open'=>$open,
		    'categories'=>$this->categoriesData,
		    'section'=>$this->sectionTag
	    ];

    	$json = htmlspecialchars(json_encode($data), ENT_NOQUOTES);

    	$html = <<<HTML
</div>
<div class="cl-interact" style="display:none">$json</div>
<div class="content">
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
			foreach($this->categories as $key => $value) {
				$this->categoriesData[] = ['tag'=>$key, 'name'=>$value];
			}
		}

	}

	private $interact;

	private $categories;            // Categories we will look at or null if none provided, meaning use a chooser
	private $sectionTag;            // Optional section tag
	private $categoriesData = [];   // Categories data send to the client
}