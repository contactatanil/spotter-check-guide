
<?php
// This file is part of Moodle - http://moodle.org/

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

// Include the new classes
require_once($CFG->dirroot.'/mod/observationchecklist/classes/controller/checklist_controller.php');
require_once($CFG->dirroot.'/mod/observationchecklist/classes/data/template_data_provider.php');
require_once($CFG->dirroot.'/mod/observationchecklist/classes/view/template_renderer.php');

use mod_observationchecklist\controller\checklist_controller;
use mod_observationchecklist\data\template_data_provider;
use mod_observationchecklist\view\template_renderer;

$id = optional_param('id', 0, PARAM_INT); // Course_module ID
$n  = optional_param('n', 0, PARAM_INT);  // observationchecklist instance ID
$action = optional_param('action', '', PARAM_ALPHA);
$itemid = optional_param('itemid', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('observationchecklist', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $observationchecklist = $DB->get_record('observationchecklist', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $observationchecklist = $DB->get_record('observationchecklist', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $observationchecklist->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('observationchecklist', $observationchecklist->id, $course->id, false, MUST_EXIST);
} else {
    print_error('missingidandcmid', 'mod_observationchecklist');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Trigger module viewed event.
$event = \mod_observationchecklist\event\course_module_viewed::create(array(
    'objectid' => $observationchecklist->id,
    'context' => $context
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('observationchecklist', $observationchecklist);
$event->trigger();

// Initialize controller and handle actions
$controller = new checklist_controller($cm, $course, $observationchecklist, $context, $PAGE);
$controller->handle_action($action, $itemid);

$PAGE->set_url('/mod/observationchecklist/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($observationchecklist->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Check user capabilities
$canassess = has_capability('mod/observationchecklist:assess', $context);
$canedit = has_capability('mod/observationchecklist:edit', $context);
$cansubmit = has_capability('mod/observationchecklist:submit', $context) && $observationchecklist->allowstudentsubmit;

// Initialize AMD modules based on user capabilities
if ($canassess || $canedit) {
    $PAGE->requires->js_call_amd('mod_observationchecklist/checklist_manager', 'init', array($cm->id));
    $PAGE->requires->js_call_amd('mod_observationchecklist/progress_tracker', 'init', array($cm->id));
}

echo $OUTPUT->header();

// Initialize data provider and renderer
$dataprovider = new template_data_provider($observationchecklist, $course, $cm, $context, $PAGE);
$renderer = new template_renderer($dataprovider, $OUTPUT);

// Render the appropriate interface
echo $renderer->render_for_user($canassess, $canedit, $cansubmit);

// Add custom CSS
$PAGE->requires->css('/mod/observationchecklist/styles.css');

echo $OUTPUT->footer();
