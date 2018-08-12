<?php
/**
 * Created by PhpStorm.
 * User: charl
 * Date: 9/3/2017
 * Time: 1:59 PM
 */

namespace Interact;


class InteractConsoleController extends \Controller {

    public function __construct(\Course $course, \User $user, $time) {
        parent::__construct($course, $user);

    }

    public function post($post)
    {
        if (!$this->user->at_least(\User::GRADER)) {
            return $this->error("Not authorized");
        }

        if (!isset($post['cmd'])) {
            return $this->error("Misconfigured");
        }

        switch ($post['cmd']) {
            case 'set':
                return $this->set($post);
                break;

            default:
                return $this->error("Misconfigured");
        }
    }

    private function set($post) {
        if(!isset($post['user'])) {
            // This is for the current user
            $preferences = $this->user->get_preferences();
            $preferences->set(Interact::RECEIVE_MAIL, $post['checked'] === "true" ? true : false);
            $preferences->write();

            return json_encode(['ok'=>true, 'id'=>$this->user->id, 'set'=>$preferences->get(Interact::RECEIVE_MAIL)]);
        }

        if (!$this->user->at_least(\User::STAFF)) {
            return $this->error("Not authorized");
        }

        $users = new \Users($this->course);
        $staffUser = $users->get_user($post['user']);
        if($staffUser === null) {
            return $this->error("User does not exist");
        }

        $preferences = $staffUser->get_preferences();
        $preferences->set(Interact::RECEIVE_MAIL, $post['checked'] === "true" ? true : false);
        $preferences->write();

        return json_encode(['ok'=>true, 'id'=>$staffUser->id, 'set'=>$preferences->get(Interact::RECEIVE_MAIL)]);

    }

    private function error($msg) {
        return json_encode(['ok'=>false, 'msg'=>$msg]);
    }
}