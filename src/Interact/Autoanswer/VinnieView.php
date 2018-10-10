<?php
/**
 * @file
 * Autoanswer testing page
 * /cl/vinnie
 */

namespace CL\Interact\Autoanswer;

use CL\Site\Site;
use CL\Course\View;
use CL\Site\System\Server;
use CL\Course\Member;

/**
 *
 * 2. Create a route to the page. Example:
 *
 * @code
 * $router->addRoute(['example', 'vue'], function(Site $site, Server $server, array $params, array $properties, $time) {
 *   $view = new ExampleVueView($site, $server);
 *   return $view->vue();
 * });
 * @endcode
 *
 * This should display a blank page at the location
 *
 * 3. Create a .vue file to display. See ExampleVue.vue.
 * This can start with just a simple template if necessary.
 *
 * 4. Add to the object factory site.ready function code to
 * instantiate the page:
 *
 * @code
 * 	site.ready(() => {
 *    PageVue.create('div.cl-example-vue', 'Example Vue', ExampleVue);
 *  });
 * @endcode
 *
 * If  menu bar is needed:
 *
 * @code
 * 	site.ready(() => {
 *    PageVue.create('div.cl-example-vue', 'Example Vue', ExampleVue, PageNav);
 *  });
 * @endcode
 *
 * The imports are:
 * @code
 * import {PageVue} from 'site-cl/js/Vue/PageVue';
 * import PageNav from 'site-cl/js/Vue/PageNav.vue';
 * @endcode
 */
class VinnieView extends View {
	/**
	 * ExampleVueView constructor.
	 * @param Site $site The Site object
	 * @param Server $server The Server object
	 */
	public function __construct(Site $site, Server $server) {
		parent::__construct($site, ['at-least'=>Member::STUDENT]);

		$this->addJS('interact');
		$data = [];
		$this->addCLS('cl-vinnie-vue', json_encode($data));
	}
}