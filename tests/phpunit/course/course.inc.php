<?php
/** @file 
 * This file contains the information that specifies a course
 *
 * This file must return a function that accepts the variable $course
 */

use \CL\Course\Section;

return function(\CL\Course\Course $course) {

	$course->define("cse999", 	// Course account name
		"CSE999", 				// Course name
		"Course Software Development"
		);

	/*
	 * Add the sections 
	 */
	$course->add_section('FS18', '799', Section::Online);
	$course->add_section('SS19', '899', Section::Online);

	$course->gradedispute = '<a href="mailto:ta@msu.edu">Some TA</a>';
};

