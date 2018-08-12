<?php
/** View class for images in Interact messages
 * @file 
 */
 
namespace Interact;

/** View class for images in Interact messages */
class InteractImageView extends \View {

	/**
	 * InteractImageView constructor.
	 * @param \Course $course Course object
	 * @param \User $user User object
	 */
	public function __construct(\Course $course, \User $user) {
		parent::__construct($course, $user);
	}
	
	/** @brief Present the image
	 * @returns Image or null on failure */
	public function present_image($get) {
		if(!isset($get['i']) || !isset($get["n"])) {
			return null;
		}

		// Guest's cannot access Interact images
		if($this->user->is_guest()) {
			return null;
		}

		$id = strip_tags($get["i"]);
		$name = strip_tags($get["n"]);

		$filesystem = new \FileSystem\FileSystem($this->course);
		$file = $filesystem->get_file($id);
		if($file === false) {
			return null;
		}

		if($name !== $file['name'] ||
			$file['assigntag'] !== 'interact' ||
			$file['tag'] !== 'public') {
			return null;
		}

		header( "Content-Type: " . $file['type']);
		return $file['binary'];
	}
	

}
