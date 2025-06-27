
<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Report page for observation checklist
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/observationchecklist/lib.php');
require_once($CFG->dirroot.'/mod/observationchecklist/locallib.php');

$id = required_param('id', PARAM_INT); // Course module ID
$reporttype = optional_param('type', 'overview', PARAM_ALPHA);
$userid = optional_param('userid', 0, PARAM_INT);
$groupid = optional_param('groupid', 0, PARAM_INT);
$format = optional_param('format', 'html', PARAM_ALPHA);

$cm = get_coursemodule_from_id('observationchecklist', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$observationchecklist = $DB->get_record('observationchecklist', array('id' => $cm->instance), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Check capabilities
require_capability('mod/observationchecklist:view', $context);

$PAGE->set_url('/mod/observationchecklist/report.php', array('id' => $cm->id, 'type' => $reporttype));
$PAGE->set_title(format_string($observationchecklist->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Navigation
$PAGE->navbar->add(get_string('reports', 'observationchecklist'));

// Export handling
if ($format !== 'html') {
    require_capability('mod/observationchecklist:export', $context);
    
    switch ($format) {
        case 'csv':
            observationchecklist_export_csv($cm, $reporttype, $userid, $groupid);
            break;
        case 'excel':
            observationchecklist_export_excel($cm, $reporttype, $userid, $groupid);
            break;
        case 'pdf':
            observationchecklist_export_pdf($cm, $reporttype, $userid, $groupid);
            break;
    }
    exit;
}

echo $OUTPUT->header();

// Report navigation
$reporttabs = array(
    'overview' => get_string('reportoverview', 'observationchecklist'),
    'grading' => get_string('reportgrading', 'observationchecklist'),
    'statistics' => get_string('reportstatistics', 'observationchecklist'),
    'attempts' => get_string('reportattempts', 'observationchecklist'),
    'progress' => get_string('reportprogress', 'observationchecklist'),
    'competency' => get_string('reportcompetency', 'observationchecklist'),
    'trainer' => get_string('reporttrainer', 'observationchecklist')
);

$tabs = array();
foreach ($reporttabs as $type => $name) {
    $tabs[] = new tabobject($type, 
        new moodle_url('/mod/observationchecklist/report.php', array('id' => $cm->id, 'type' => $type)),
        $name
    );
}

echo $OUTPUT->tabtree($tabs, $reporttype);

// Report content based on type
switch ($reporttype) {
    case 'overview':
        observationchecklist_display_overview_report($cm, $context);
        break;
    case 'grading':
        observationchecklist_display_grading_report($cm, $context, $userid, $groupid);
        break;
    case 'statistics':
        observationchecklist_display_statistics_report($cm, $context);
        break;
    case 'attempts':
        observationchecklist_display_attempts_report($cm, $context, $userid);
        break;
    case 'progress':
        observationchecklist_display_progress_report($cm, $context, $groupid);
        break;
    case 'competency':
        observationchecklist_display_competency_report($cm, $context);
        break;
    case 'trainer':
        observationchecklist_display_trainer_report($cm, $context);
        break;
    default:
        observationchecklist_display_overview_report($cm, $context);
}

echo $OUTPUT->footer();
