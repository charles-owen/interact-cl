<?php
/**
 * @file
 * Handle polling requests to the Interact system
 */

namespace CL\Interact;

use CL\Site\Api\JsonAPI;
use CL\Site\Site;
use CL\Site\System\Server;

/**
 * Handle polling requests to the Interact system
 */
class InteractPolling {

	/**
	 * Handle a polling request
	 * @param Site $site The Site object
	 * @param Server $server The Server object
	 * @param array $post Post parameters, just the 'interact' parts
	 * @param JsonAPI $json
	 * @param int $time
	 */
	public function poll(Site $site, Server $server, array $post, JsonAPI $json, $time) {
		$interacts = new Interacts($site->db);

		if(isset($post['summaries'])) {
			$data = $interacts->summariesData($site, $site->users->user, $post['summaries']);
			if(count($data) > 0) {
				$json->addData('interact-summaries', 0, $data);
			}
		}
	}
}