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
use CL\Course\Member;
use CL\Site\Extendible;
use CL\Site\Api\JsonAPI;

/**
 * Plugin class for the Interact! Subsystem
 */
class InteractPlugin extends \CL\Site\Plugin implements \CL\Site\IExtension {
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
		$site->install('interact', $this);
		$this->site = $site;
	}


	/**
	 * AssignmentCategory and Assignment are extended with
	 * the grading components.
	 * @param Site $site The site configuration component
	 */
	public function ensureTables(Site $site) {
		$maker = new InteractTables($site->db);
		$maker->create(false);
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

			$router->addPolling(function(Site $site, Server $server, $post, JsonAPI $json, $time) {
				if(isset($post['interact'])) {
					$polling = new InteractPolling();
					$polling->poll($site, $server, $post['interact'], $json, $time);
				}
			});

			$router->addRoute(['help', 'interact'], function(Site $site, Server $server, array $params, array $properties, $time) {
				$view = new Help\Help($site);
				return $view->whole();
			});

			$router->addRoute(['vinnie'], function(Site $site, Server $server, array $params, array $properties, $time) {
				$view = new Autoanswer\VinnieView($site, $server);
				return $view->vue();
			});

		} else if($object instanceof \CL\Course\CourseHomeView) {
			$object->extend('interact_button', function($view, $args) {
				if(!$view->user->atLeast(Member::STUDENT)) {
					return '';
				}

				$root = $this->site->root;
				return <<<HTML
<p class="cl-home-control"><a href="$root/cl/interact" title="Interact! System">
<img src="$root/vendor/cl/interact/img/linkbutton.png" width="100" height="25" alt="Interact! System"></a></p>
HTML;
			});
		} else if($object instanceof \CL\Course\Assignment) {
			$object->extend('use_interact', $this);
		} else if($object instanceof \CL\Course\AssignmentView) {
			if($object->assignment->hasProperty('interact')) {
				if($object->user->atLeast(Member::STUDENT)) {
					$sectionTag = ($object instanceof \CL\Step\StepSectionView) ?
						$object->sectionTag : null;

					$viewAux = new InteractViewAux([$object->tag], $sectionTag);
					$object->add_aux($viewAux);
					$object->beforeFooter = $viewAux->present();
				}

				$object->extend('interact_link', function(\CL\Course\AssignmentView $view, $args) {
					if(!$view->user->atLeast(Member::STUDENT)) {
						return;
					}

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

	/**
	 * @param Extendible $extendible Extendible that is calling this function
	 * @param string $name Name of the function
	 * @param array $arguments Arguments to the function
	 * @return mixed
	 */
	public function extension(Extendible $extendible, $name, array $arguments) {
		if($name !== 'use_interact') {
			return;
		}

		$assignment = $extendible;
		$assignment->addProperty('interact', 'true');
	}

	private $site;
}