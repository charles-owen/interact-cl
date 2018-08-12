<?php
/**
 * @file
 * @brief Class that represents a single interaction
 */

namespace Interact;

/**
 * @brief Class that represents a single discussion item in an interaction
 *
 * This class represents records in the discussion table
 */
class Discussion extends InteractContent {
	/**
	 * @brief Constructor
	 * @param \Course $course Current course
	 * @param \User $user Current user
	 * @param int $interactId Interact ID for this discussion
	 * @param int|Time $time Time the discussion was created
	 */
    public function __construct(\Course $course, \User $user=null, $interactId=0, $time=0) {
        parent::__construct($course, $user, $time);

        $this->interactId = $interactId;
    }

	/**
	 * Update a discussion
	 * @param $html New HTML content
	 * @param array $post Data from $_POST
	 */
    public function update($html, array $post) {
        $this->set_html($html);
        $this->interactId = $post['interactid'];
    }


    /**
     * @brief Get the interaction ID for this discussion
     * @return mixed Interaction ID for this discussion
     */
    public function get_interact_id() {
        return $this->interactId;
    }

	/**
	 * Set this discussion ID
	 * @param $id New ID
	 */
    public function set_id($id) {
        $this->id = $id;
    }

	/**
	 * The discussion ID
	 * @return int Discussion ID
	 */
    public function get_id() {
        return $this->id;
    }

    private $id = 0;
    private $interactId;
}