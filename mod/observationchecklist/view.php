
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
require_once(__DIR__.'/locallib.php');

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

$event = \mod_observationchecklist\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot($cm->modname, $moduleinstance);
$event->trigger();

$PAGE->set_url('/mod/observationchecklist/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();

// Conditions to show the intro can change to look for own settings or whatever.
if ($moduleinstance->intro) {
    echo $OUTPUT->box(format_module_intro('observationchecklist', $moduleinstance, $cm->id), 'generalbox mod_introbox', 'observationchecklistintro');
}

// Get checklist items
$items = $DB->get_records('observationchecklist_items', array('checklistid' => $moduleinstance->id), 'position ASC');

// Check capabilities
$canassess = has_capability('mod/observationchecklist:assess', $modulecontext);
$canedit = has_capability('mod/observationchecklist:edit', $modulecontext);
$canview = has_capability('mod/observationchecklist:view', $modulecontext);

if ($canview) {
    echo '<div class="observationchecklist-container">';
    
    if ($canedit) {
        echo '<div class="btn-group mb-3">';
        echo '<button type="button" class="btn btn-primary" id="add-item-btn">' . get_string('additem', 'observationchecklist') . '</button>';
        echo '<a href="report.php?id=' . $cm->id . '" class="btn btn-secondary">' . get_string('reports', 'observationchecklist') . '</a>';
        echo '</div>';
    }
    
    if (empty($items)) {
        echo '<div class="alert alert-info">' . get_string('noitemsfound', 'observationchecklist') . '</div>';
    } else {
        echo '<div class="checklist-items">';
        foreach ($items as $item) {
            echo '<div class="card mb-2" data-itemid="' . $item->id . '">';
            echo '<div class="card-body">';
            echo '<h5 class="card-title">' . format_text($item->itemtext) . '</h5>';
            echo '<p class="card-text"><small class="text-muted">' . get_string('category', 'observationchecklist') . ': ' . $item->category . '</small></p>';
            
            if ($canedit) {
                echo '<button type="button" class="btn btn-sm btn-danger delete-item" data-itemid="' . $item->id . '">' . get_string('delete', 'observationchecklist') . '</button>';
            }
            
            if ($canassess) {
                echo '<button type="button" class="btn btn-sm btn-success assess-item" data-itemid="' . $item->id . '">' . get_string('assess', 'observationchecklist') . '</button>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        echo '</div>';
    }
    
    echo '</div>';
}

// Include JavaScript for AJAX functionality
$PAGE->requires->js_call_amd('mod_observationchecklist/checklist_manager', 'init', array($cm->id));

echo $OUTPUT->footer();
