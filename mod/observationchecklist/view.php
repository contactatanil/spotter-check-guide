
<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Prints an instance of mod_observationchecklist.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$o = optional_param('o', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('observationchecklist', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('observationchecklist', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('observationchecklist', array('id' => $o), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('observationchecklist', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

$modulecontext = context_module::instance($cm->id);

$PAGE->set_url('/mod/observationchecklist/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

if ($moduleinstance->intro) {
    echo $OUTPUT->box(format_module_intro('observationchecklist', $moduleinstance, $cm->id), 'generalbox mod_introbox', 'observationchecklistintro');
}

echo '<div class="observationchecklist-container">';
echo '<p>Observation Checklist is installed and working!</p>';
echo '</div>';

echo $OUTPUT->footer();
