<?php
/**
 * @file
 * Plugin class for the Interact! Subsystem
 */

namespace CL\Interact;

use CL\Console\ConsoleView;
use CL\Site\Site;
use CL\Site\System\Server;
use CL\Course\AssignmentCategory;
use CL\Course\Assignment;
use CL\Site\Router;
use CL\Course\Section;

/**
 * Plugin class for the Interact! Subsystem
 */
class InteractPlugin extends \CL\Site\Plugin {
	/**
	 * A tag that represents this plugin
	 * @return string A tag like 'course', 'users', etc.
	 */
	public function tag() {return 'interact';}

	/**
	 * Return an array of tags indicating what plugins this one is dependent on.
	 * @return array of tags this plugin is dependent on
	 */
	public function depends() {return ['course'];}

	/**
	 * Install the plugin
	 * @param Site $site The Site configuration object
	 */
	public function install(Site $site) {
		$this->site = $site;
	}


	/**
	 * AssignmentCategory and Assignment are extended with
	 * the grading components.
	 * @param Site $site The site configuration component
	 */
	public function ensureTables(Site $site) {
//		$maker = new InteractTables($site->db);
//		$maker->create(false);
	}

	/**
	 * Amend existing object
	 * The Router is amended with routes for the login page
	 * and for the user API.
	 * @param $object Object to amend.
	 */
	public function amend($object) {
		if($object instanceof Router) {
			$router = $object;

			$router->addRoute(['interact', '*'], function(Site $site, Server $server, array $params, array $properties, $time) {
				$view = new InteractView($site, $server, $time);
				return $view->vue();
			});

			$router->addRoute(['api', 'interact', '*'], function(Site $site, Server $server, array $params, array $properties, $time) {
				$resource = new InteractApi();
				return $resource->apiDispatch($site, $server, $params, $properties, $time);
			});
		} else if($object instanceof \CL\Course\CourseHomeView) {
			$object->extend('interact_button', function($view, $args) {
				$root = $this->site->root;
				return <<<HTML
<p class="control"><a href="$root/cl/interact" title="Interact! System">
<img src="$root/vendor/cl/interact/img/linkbutton.png" width="100" height="25" alt="Interact! System"></a></p>
HTML;
			});
		} else if($object instanceof \CL\Course\Assignment) {
			$object->extend('use_interact', function(\CL\Course\Assignment $assignment, $args) {
				$assignment->addProperty('interact', 'true');
			});
		} else if($object instanceof \CL\Course\AssignmentView) {
			if($object->assignment->hasProperty('interact')) {
				$sectionTag = ($object instanceof \CL\Step\StepSectionView) ?
					$object->sectionTag : null;

				$viewAux = new InteractViewAux([$object->tag], $sectionTag);
				$object->add_aux($viewAux);
				$object->beforeFooter = $viewAux->present();

				$object->extend('interact_link', function(\CL\Course\AssignmentView $view, $args) {
					$root = $view->root;
					return <<<HTML
<p class="cl-interact-link"><a href="#cl-interact"><img src="$root/vendor/cl/interact/img/link.png" width="82" height="16" alt="Interact!"> for this assignment</a></p>
HTML;
				});
			}


		} else if($object instanceof ConsoleView) {
			$object->addJS('interact');
		}
	}

	private $site;
}