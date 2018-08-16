<?php
/**
 * @file
 * Class that represents a single discussion item in an interaction
 */

namespace CL\Interact;

use CL\Site\Site;
use CL\Course\Member;
use CL\Users\User;

/**
 * Class that represents a single discussion item in an interaction
 *
 * This class represents records in the discussion table
 */
class Discussion extends InteractContent
{

	public function __construct($row = null, $prefix = '')
	{
		parent::__construct($row, $prefix);

		if ($row !== null) {
			$this->interactId = +$row['interactid'];
		}
	}

	/**
	 * Property get magic method
	 *
	 * <b>Properties</b>
	 * Property | Type | Description
	 * -------- | ---- | -----------
	 *
	 * @param string $property Property name
	 * @return mixed
	 */
	public function __get($property)
	{
		switch ($property) {
			case 'interactId':
				return $this->interactId;

			default:
				return parent::__get($property);
		}
	}

	/**
	 * Property set magic method
	 *
	 * <b>Properties</b>
	 * Property | Type | Description
	 * -------- | ---- | -----------
	 *
	 * @param string $property Property name
	 * @param mixed $value Value to set
	 */
	public function __set($property, $value)
	{
		switch ($property) {
			case 'interactId':
				$this->interactId = $value;
				break;

			default:
				parent::__set($property, $value);
				break;
		}
	}

	/**
	 * Create client data when in summary mode.
	 * @param Site $site The Site object
	 * @param User $user The current user
	 * @return array results
	 */
	public function data(Site $site, User $user) {
		$data = parent::data($site, $user);

		$data['interactId'] = $this->interactId;
		$data['message'] = $this->message;

		return $data;
	}


//	/**
//	 * Update a discussion
//	 * @param $html New HTML content
//	 * @param array $post Data from $_POST
//	 */
//    public function update($html, array $post) {
//        $this->set_html($html);
//        $this->interactId = $post['interactid'];
//    }



    private $interactId = 0;
}