
<?php
// This file is part of Moodle - http://moodle.org/

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once($CFG->dirroot.'/mod/observationchecklist/classes/controller/checklist_controller.php');
require_once($CFG->dirroot.'/mod/observationchecklist/classes/data/template_data_provider.php');

use mod_observationchecklist\controller\checklist_controller;
use mod_observationchecklist\data\template_data_provider;

$id = optional_param('id', 0, PARAM_INT); // Course_module ID
$action = optional_param('action', '', PARAM_ALPHA);
$studentids = optional_param_array('studentids', [], PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('observationchecklist', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $observationchecklist = $DB->get_record('observationchecklist', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    print_error('missingidandcmid', 'mod_observationchecklist');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Check assessor capability
require_capability('mod/observationchecklist:assess', $context);

// Handle multi-student assessment actions
$controller = new checklist_controller($cm, $course, $observationchecklist, $context, $PAGE);
if ($action && confirm_sesskey()) {
    $controller->handle_multi_student_action($action, $studentids);
}

$PAGE->set_url('/mod/observationchecklist/multi_student.php', array('id' => $cm->id));
$PAGE->set_title(format_string($observationchecklist->name) . ' - ' . get_string('multistudentobservation', 'mod_observationchecklist'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Add JavaScript module
$PAGE->requires->js_call_amd('mod_observationchecklist/multi_student_observer', 'init', array($cm->id));

echo $OUTPUT->header();

// Get enrolled students
$students = get_enrolled_users($context, 'mod/observationchecklist:submit');
$items = observationchecklist_get_items($observationchecklist->id);

// Prepare template context
$templatecontext = [
    'checklist' => $observationchecklist,
    'students' => array_values($students),
    'items' => array_values($items),
    'cmid' => $cm->id,
    'sesskey' => sesskey(),
    'actionurl' => new moodle_url('/mod/observationchecklist/multi_student.php', ['id' => $cm->id])
];

echo $OUTPUT->render_from_template('mod_observationchecklist/multi_student_observation', $templatecontext);

echo $OUTPUT->footer();
