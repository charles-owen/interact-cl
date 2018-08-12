<?php
/**
 * @file
 * Class that provides functions to create and delete all Interact! system tables
 */

namespace Interact;

/**
 * Class that provides functions to create and delete all Interact! system tables
 *
 * It is used by the management system to create the necessary tables as needed.
 */
class InteractTables extends \TableMaker {
    /**
     * Constructor
     * @param \Course $course The Course object
     */
    public function __construct(\Course $course) {
        $this->add(new Interacts($course));
        $this->add(new Discussions($course));
        $this->add(new InterFollows($course));
        $this->add(new InterActive($course));
    }
}