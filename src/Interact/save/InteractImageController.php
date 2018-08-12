<?php
/**
 * @file
 * Controller for file submission
 */

namespace Interact;

/**
 * Controller for image uploading
 *
 * Used by interact/submitimg.php
 */
class InteractImageController extends \Controller {
    /**
     * @brief Constructor
     * @param \Course $course The Course object
     * @param \User $user The User object
     * @param $time Time of the submission as a Unix time value
     */
    public function __construct(\Course $course, \User $user, $time) {
		parent::__construct($course, $user);
		
        $this->time = $time;
    }

    /**
     * @brief Handle post for file submission
     * @param $post The $_POST array
     * @param $files The $_FILES array
     * @return string String to return to client
     */
    public function post(&$post, &$get, &$files) {
		$file = $files["upload"];
		if ($file["error"] > 0 || $file["tmp_name"] == "") {
            // Error return
			return $this->error("No supplied file ");
        }

		/*
		 * Ensure the image is acceptable
		 */
		$imgsets = array(
			'maxwidth' => 2000,          // maximum allowed width, in pixels
			'maxheight' => 2000,         // maximum allowed height, in pixels
			'minwidth' => 10,           // minimum allowed width, in pixels
			'minheight' => 10,          // minimum allowed height, in pixels
			'type' => array('bmp', 'gif', 'jpg', 'jpe', 'png')        // allowed extensions
		);

		$name = $file["name"];
		$sepext = explode('.', strtolower($name));
		$type = end($sepext);       // gets extension

		$path = $file["tmp_name"];

		list($width, $height) = getimagesize($path);     // gets image width and height

		// Checks if the file has allowed type, size, width and height (for images)
		$err = '';
		if(!in_array($type, $imgsets['type'])) {
			$err .= 'The file extension: ' . $type . ' is not an allowed extension type.';
		}

		if(isset($width) && isset($height)) {
			if($width > $imgsets['maxwidth'] || $height > $imgsets['maxheight']) {
				$err .= '\\n Width x Height = '. $width .' x '. $height .' \\n The maximum Width x Height must be: '. $imgsets['maxwidth']. ' x '. $imgsets['maxheight'];
			}

			if($width < $imgsets['minwidth'] || $height < $imgsets['minheight']) {
				$err .= '\\n Width x Height = '. $width .' x '. $height .'\\n The minimum Width x Height must be: '. $imgsets['minwidth']. ' x '. $imgsets['minheight'];
			}
		}

		if($err !== '') {
			unlink($path);
			return $this->error($err);
		}

		// Create a random name
		$randomName = "i" . mt_rand(1, mt_getrandmax());
		$filesystem = new \FileSystem\FileSystem($this->course);
		$id = $filesystem->write_file($this->user, "interact", "public", $randomName,
			$path, $file['type'], $this->time);

		$CKEditorFuncNum = $get['CKEditorFuncNum'];
		$url = $this->course->get_libroot() . '/interact/image.php?i=' . $id . '&n=' . $randomName;
		$message =  'Successfully uploaded: \\n- Image Width x Height: '. $width. ' x '. $height;
		$re = "window.parent.CKEDITOR.tools.callFunction($CKEditorFuncNum, '$url', '$message')";
		return "<script>$re;</script>";

    }
	
	private function error($msg) {
		$re = 'alert("' . $msg . '")';
		return "<script>$re;</script>";
	}
	
    private $time;
}