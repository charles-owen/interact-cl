<?php
/**
 * @file
 * Handle polling requests to the Interact system
 */

namespace CL\Interact;

use CL\Site\Api\JsonAPI;
use CL\Site\Site;
use CL\Site\System\Server;
use CL\Course\Member;

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
	 * @throws \CL\Tables\TableException
	 */
	public function poll(Site $site, Server $server, array $post, JsonAPI $json, $time) {
		$interacts = new Interacts($site->db);
		$user = $site->users->user;

		//
		// Discover any new summaries that are now available
		//
		if(isset($post['summaries'])) {
			$data = $interacts->summariesData($site, $user, $post['summaries']);
			if(count($data) > 0) {
				$json->addData('interact-summaries', 0, $data);
			}
		}

		// Are we editing?
		if(isset($post['active'])) {
			// Refresh the interactive record
			$interactives = new InterActives($site->db);
			$interactives->set($user, $post['instance'], $post['active'], $time);
		}

		//
		// Discover any changes to the current interaction that are not available.
		// This will detect any edits or new interactions.
		//
		if(isset($post['interaction'])) {
			$query = $post['interaction'];

			// Get this interaction
			$interaction = $interacts->get($query['id'], false);
			if($interaction !== null && $interaction->time > $query['after']) {
				$authorized = true;
				if(!$user->atLeast(Member::STAFF)) {
					if($interaction->private && $interaction->user->id != $user->id) {
						$authorized = false;
					}
				}

				if($authorized) {
					// Get the discussions
					$discussions = new Discussions($site->db);
					$interaction->discussions = $discussions->getFor($query['id']);

					$json->addData('interaction', $interaction->id, $interaction->data($site, $user));
				}
			}

			$interactives = new InterActives($site->db);


			// Anyone editing?
			$editing = $interactives->get($query['id'], $time);
			$data = [];
			foreach($editing as $editor) {
				if($editor['memberid'] == $user->member->id) {
					// We only care about others
					continue;
				}

				if($user->staff) {
					// Staff sees all
					$data[] = [
						'name'=>$editor['username'],
						'role'=>$editor['userrole']
					];
				} else {
					$role = $editor['userrole'] !== Member::ADMIN ? $editor['userrole'] : Member::INSTRUCTOR;
					$roles = $user->getRoles();
					$priority = isset($roles[$role]) ? $roles[$role]['priority'] : 0;
					$name = $priority >= $roles[Member::STAFF]['priority'] ? $editor['username'] : null;

					$data[] = [
						'name'=>$name,
						'role'=>$role
					];
				}

			}

			if(count($data) > 0) {
				$json->addData('interactives', $interaction->id, $data);
			}
		}


	}
}