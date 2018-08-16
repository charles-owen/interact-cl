<?php
/** @file
 * Course Assigment declarations
 */

use CL\Course\Assignments;

return function(Assignments $assignments) {
	//
	// Calendar events
	//
	$assignments->add_calendar("Meet and Greet", "9/5/2018", "startup.php");
	$assignments->add_calendar("Exam 1", "10/16/2018", "exam1");
	$assignments->add_calendar("Exam 1", "10/17/2018", "exam1");
	$assignments->add_calendar("Exam 2", "12/12/2018", "exam2");
	$assignments->add_calendar("Exam 2", "12/13/2018", "exam2");
	$assignments->add_calendar("University Holiday", "9/4/2018");
	$assignments->add_calendar("University Holiday", "11/23/2018");
	$assignments->add_calendar("University Holiday", "11/24/2018");

	$assignments->add_calendar("Project 1 Sprint 1", "10/3/2018", "project1");
	$assignments->add_calendar("Project 1 Sprint 2", "10/6/2018", "project1");
	$assignments->add_calendar("Project 1 Sprint 4", "10/13/2018", "project1");
	$assignments->add_calendar("Project 2 Info Session", "11/13/2018", "project2");


	/*
	 * Course grading categories and assignments
	 */

	$designs = $assignments->add_category("designs", "Design Assignments", 15);
	$steps = $assignments->add_category("steps", "Step Assignments", 30);
	$projects = $assignments->add_category("projects", "Projects", 35);
	$exams = $assignments->add_category("exams", "Exams", 20);

	$step1 = $steps->add_assignment('step1', 'Step 1');
	$step1->set_release('8/28/2018 1:00am', '9/6/2018 11:55pm');
	$step1->submissions->add_text('text', 'Text');

//	//
//	// Design 1
//	//
//	$design = $designs->add_step("design1", "Design 1");
//	$design->set_release('8/29/2017 1:00am', '9/05/2017 11:55pm');
//
//	//
//	// Design 2
//	//
//	$design = $designs->add_step("design2", "Design 2");
//	$design->set_release('8/29/2017 1:00am', '9/11/2017 11:55pm');
//	$design->set_reviews_due('9/15/2017 11:55pm');
//	$design->set_solving('design2solving');
//
//	//
//	// Design 3
//	//
//	$design = $designs->add_step("design3", "Design 3");
//	$design->set_due('9/18/2017 11:55pm');
//	$design->set_reviews_due('9/22/2017 11:55pm');
//	$design->set_release('9/5/2017 1:00am');
//	$design->set_solving('design3solving');
//
//	//
//	// Design 4
//	//
//	$design = $designs->add_step("design4", "Design 4");
//	$design->set_due('9/25/2017 11:55pm');
//	$design->set_reviews_due('9/29/2017 11:55pm');
//	$design->set_release('-14 days');
//	$design->set_solving('design4solving');
//
//	//
//	// Design 5
//	//
//	$design = $designs->add_step("design5", "Design 5");
//	$design->set_due('10/3/2017 11:55pm', true);
//	$design->set_reviews_due('10/6/2017 11:55pm');
//	$design->set_release('-14 days');
//	$design->set_solving('design5solving');
//
//	//
//	// Design 6
//	//
//	$design = $designs->add_step("design6", "Design 6");
//	$design->set_due('10/23/2017 11:55pm', true);
//	$design->set_release('-18 days');
//	$design->set_reviews_due('10/26/2017 11:55pm');
//	$design->set_solving('design6solving');
//
//	//
//	// Design 7
//	//
//	$design = $designs->add_step("design7", "Design 7");
//	$design->set_due('10/27/2017 11:55pm');
//	$design->set_release('-14 days');
//	$design->set_solving('design7solving');
//
//	//
//	// Design 8
//	//
//	$design = $designs->add_step("design8", "Design 8");
//	$design->set_due('11/28/2017 11:55pm', true);
//	$design->set_release('-21 days');
//
//	//
//	// Design 9
//	//
//	$design = $designs->add_step("design9", "Design 9");
//	$design->set_due('12/6/2017 11:55pm');
//	$design->set_release('-14 days');
//
//	//
//	// Step 1
//	//
//	$step = $steps->add_step("step1", "Step 1");
//	$step->set_solving('step1solving');
//
//	//
//	// Step 2
//	//
//	$step = $steps->add_step("step2", "Step 2");
//	$step->set_release('8/29/2017 1:00am', '9/15/2017 11:55pm', true);
//	$step->set_solving('step2solving');
//
//	//
//	// Step 3
//	//
//	$step = $steps->add_step("step3", "Step 3");
//	$step->set_release('-20 days', '9/22/2017 11:55pm', true);
//	$step->set_solving('step3solving');
//
//	//
//	// Step 4
//	//
//	$step = $steps->add_step("step4", "Step 4");
//	$step->set_due('9/29/2017 11:55pm', true);
//	$step->set_release('-18 days');
//	$step->set_solving('step4solving');
//
//	//
//	// Step 5
//	//
//	$step = $steps->add_step("step5", "Step 5");
//	$step->set_due('11/1/2017 11:55pm', true);
//	$step->set_release('-21 days');
//	$step->set_solving('step5solving');
//
//	//
//	// Step 6
//	//
//	$step = $steps->add_step("step6", "Step 6");
//	$step->set_due('11/11/2017 11:55pm', true);
//	$step->set_release('-21 days');
//	$step->set_solving('step6solving');
//
//	//
//	// Step 7
//	//
//	$step = $steps->add_step("step7", "Step 7");
//	$step->set_due('12/9/2017 11:55pm', true);
//	$step->set_release('-21 days');
//	$step->set_solving('step7solving');

	//
	// Project 1
	//
	$project = $projects->add_assignment("project1", "Project 1", 40);
//	$project->set_due('10/21/2017 11:55pm', true);
//	$project->set_release('-30 days');
//
//	//
//	// Project 2
//	//
//	$project = $projects->add_assignment("project2a", "Project 2 Part 1", 15);
//	$project->set_due('11/20/2017 11:55pm', true);
//	$project->set_release('-30 days');
//
//	$project = $projects->add_assignment("project2", "Project 2 Part 2", 45);
//	$project->set_due('12/05/2017 11:55pm', true);
//	$project->set_release('-30 days');
//
//	$exams->add_assignment("exam1", "Exam 1", 50);
//	$exams->add_assignment("exam2", "Exam 2", 50);
};
