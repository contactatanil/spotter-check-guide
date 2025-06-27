
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

// Trigger course_module_viewed event.
$event = \mod_observationchecklist\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot($cm->modname, $moduleinstance);
$event->trigger();

// Check capabilities
$canview = has_capability('mod/observationchecklist:view', $modulecontext);
$canedit = has_capability('mod/observationchecklist:edit', $modulecontext);
$canassess = has_capability('mod/observationchecklist:assess', $modulecontext);
$cansubmit = has_capability('mod/observationchecklist:submit', $modulecontext);

$PAGE->set_url('/mod/observationchecklist/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Add JavaScript for dynamic functionality
$PAGE->requires->js_call_amd('mod_observationchecklist/checklist_manager', 'init', array($cm->id));

echo $OUTPUT->header();

// Display intro
if ($moduleinstance->intro) {
    echo $OUTPUT->box(format_module_intro('observationchecklist', $moduleinstance, $cm->id), 'generalbox mod_introbox', 'observationchecklistintro');
}

echo '<div class="observationchecklist-container">';

// Get checklist items
$items = $DB->get_records('observationchecklist_items', array('checklistid' => $moduleinstance->id), 'position ASC');

if ($canedit) {
    // Show add item form
    echo '<div class="card mb-3">';
    echo '<div class="card-header"><h5>' . get_string('addnewitem', 'observationchecklist') . '</h5></div>';
    echo '<div class="card-body">';
    echo '<form id="additemform" class="mb-3">';
    echo '<div class="form-group mb-2">';
    echo '<input type="text" class="form-control" id="itemtext" placeholder="' . get_string('itemtext', 'observationchecklist') . '" required>';
    echo '</div>';
    echo '<div class="form-group mb-2">';
    echo '<input type="text" class="form-control" id="category" placeholder="' . get_string('category', 'observationchecklist') . '" value="General">';
    echo '</div>';
    echo '<button type="submit" class="btn btn-primary">' . get_string('additem', 'observationchecklist') . '</button>';
    echo '</form>';
    echo '</div>';
    echo '</div>';
}

// Display checklist items
if (empty($items)) {
    echo '<div class="alert alert-info">' . get_string('noitemsfound', 'observationchecklist') . '</div>';
} else {
    echo '<div class="card">';
    echo '<div class="card-header"><h5>' . get_string('assessmentitems', 'observationchecklist') . '</h5></div>';
    echo '<div class="card-body">';
    
    // Group items by category
    $categories = array();
    foreach ($items as $item) {
        if (!isset($categories[$item->category])) {
            $categories[$item->category] = array();
        }
        $categories[$item->category][] = $item;
    }
    
    foreach ($categories as $category => $categoryitems) {
        echo '<h6 class="mt-3">' . format_string($category) . '</h6>';
        echo '<div class="list-group">';
        
        foreach ($categoryitems as $item) {
            echo '<div class="list-group-item" data-itemid="' . $item->id . '">';
            echo '<div class="d-flex justify-content-between align-items-start">';
            echo '<div class="flex-grow-1">';
            echo '<p class="mb-1">' . format_text($item->itemtext) . '</p>';
            echo '</div>';
            
            if ($canedit) {
                echo '<div class="ms-2">';
                echo '<button class="btn btn-sm btn-outline-danger delete-item" data-itemid="' . $item->id . '">';
                echo get_string('delete', 'observationchecklist');
                echo '</button>';
                echo '</div>';
            }
            
            echo '</div>';
            echo '</div>';
        }
        
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
}

// If user can assess, show student selector
if ($canassess) {
    echo '<div class="card mt-3">';
    echo '<div class="card-header"><h5>' . get_string('studentassessment', 'observationchecklist') . '</h5></div>';
    echo '<div class="card-body">';
    echo '<p>' . get_string('choosestudentmessage', 'observationchecklist') . '</p>';
    
    // Get enrolled students
    $context = context_course::instance($course->id);
    $students = get_enrolled_users($context, 'mod/observationchecklist:submit', 0, 'u.id, u.firstname, u.lastname');
    
    if (!empty($students)) {
        echo '<div class="form-group">';
        echo '<select class="form-control" id="studentselect">';
        echo '<option value="">Select a student...</option>';
        foreach ($students as $student) {
            echo '<option value="' . $student->id . '">' . fullname($student) . '</option>';
        }
        echo '</select>';
        echo '</div>';
        echo '<button class="btn btn-primary mt-2" id="loadstudentprogress">Load Student Progress</button>';
    } else {
        echo '<div class="alert alert-info">No students enrolled with submission capability.</div>';
    }
    
    echo '</div>';
    echo '</div>';
    
    // Container for student progress
    echo '<div id="studentprogress" class="mt-3" style="display: none;"></div>';
}

echo '</div>';

echo $OUTPUT->footer();
