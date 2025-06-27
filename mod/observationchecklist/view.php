
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Prints a particular instance of observationchecklist
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once(__DIR__.'/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID
$n  = optional_param('n', 0, PARAM_INT);  // ... observationchecklist instance ID
$action = optional_param('action', '', PARAM_ALPHA);

if ($id) {
    $cm         = get_coursemodule_from_id('observationchecklist', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $observationchecklist  = $DB->get_record('observationchecklist', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $observationchecklist  = $DB->get_record('observationchecklist', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $observationchecklist->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('observationchecklist', $observationchecklist->id, $course->id, false, MUST_EXIST);
} else {
    throw new moodle_exception('missingidandcmid', 'mod_observationchecklist');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

// Trigger course_module_viewed event
$event = \mod_observationchecklist\event\course_module_viewed::create(array(
    'objectid' => $observationchecklist->id,
    'context' => $context
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('observationchecklist', $observationchecklist);
$event->trigger();

// Set page properties
$PAGE->set_url('/mod/observationchecklist/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($observationchecklist->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Handle form actions
if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'additem':
            if (has_capability('mod/observationchecklist:edit', $context)) {
                $itemtext = required_param('itemtext', PARAM_TEXT);
                $category = optional_param('category', 'General', PARAM_TEXT);
                
                if (!empty($itemtext)) {
                    $item = new stdClass();
                    $item->checklistid = $observationchecklist->id;
                    $item->itemtext = $itemtext;
                    $item->category = $category;
                    $item->userid = $USER->id;
                    $item->position = 0;
                    $item->sortorder = 0;
                    $item->timecreated = time();
                    $item->timemodified = time();
                    
                    $DB->insert_record('observationchecklist_items', $item);
                    
                    // Trigger item added event
                    $event = \mod_observationchecklist\event\item_added::create(array(
                        'objectid' => $item->id ?? 0,
                        'context' => $context,
                        'other' => array('checklistid' => $observationchecklist->id)
                    ));
                    $event->trigger();
                    
                    redirect($PAGE->url, get_string('itemadded', 'mod_observationchecklist'));
                }
            }
            break;
            
        case 'deleteitem':
            if (has_capability('mod/observationchecklist:edit', $context)) {
                $itemid = required_param('itemid', PARAM_INT);
                $DB->delete_records('observationchecklist_items', array('id' => $itemid));
                $DB->delete_records('observationchecklist_user_items', array('itemid' => $itemid));
                redirect($PAGE->url, get_string('itemdeleted', 'mod_observationchecklist'));
            }
            break;
    }
}

// Output starts here
echo $OUTPUT->header();

echo $OUTPUT->heading($observationchecklist->name);

if (trim(strip_tags($observationchecklist->description))) {
    echo $OUTPUT->box(format_module_intro('observationchecklist', $observationchecklist, $cm->id), 'generalbox mod_introbox', 'observationchecklistintro');
}

// Show checklist items
$items = $DB->get_records('observationchecklist_items', array('checklistid' => $observationchecklist->id), 'id ASC');

if (has_capability('mod/observationchecklist:edit', $context)) {
    echo '<div class="card mt-3">';
    echo '<div class="card-header"><h5 class="mb-0">'.get_string('addnewitem', 'mod_observationchecklist').'</h5></div>';
    echo '<div class="card-body">';
    echo '<form method="post" action="'.$PAGE->url.'">';
    echo '<input type="hidden" name="sesskey" value="'.sesskey().'" />';
    echo '<input type="hidden" name="action" value="additem" />';
    echo '<div class="mb-3">';
    echo '<label for="itemtext" class="form-label">'.get_string('itemtext', 'mod_observationchecklist').'</label>';
    echo '<textarea class="form-control" id="itemtext" name="itemtext" rows="3" required></textarea>';
    echo '</div>';
    echo '<div class="mb-3">';
    echo '<label for="category" class="form-label">'.get_string('category', 'mod_observationchecklist').'</label>';
    echo '<input type="text" class="form-control" id="category" name="category" value="General" />';
    echo '</div>';
    echo '<button type="submit" class="btn btn-primary">'.get_string('additem', 'mod_observationchecklist').'</button>';
    echo '</form>';
    echo '</div>';
    echo '</div>';
}

// Display items
if (!empty($items)) {
    echo '<div class="card mt-3">';
    echo '<div class="card-header"><h5 class="mb-0">'.get_string('assessmentitems', 'mod_observationchecklist').'</h5></div>';
    echo '<div class="card-body">';
    echo '<div class="list-group list-group-flush">';
    
    foreach ($items as $item) {
        echo '<div class="list-group-item d-flex justify-content-between align-items-start">';
        echo '<div class="me-auto">';
        echo '<div class="fw-bold">'.format_text($item->itemtext).'</div>';
        echo '<small class="text-muted">'.get_string('category', 'mod_observationchecklist').': '.format_string($item->category).'</small>';
        echo '</div>';
        
        if (has_capability('mod/observationchecklist:edit', $context)) {
            echo '<a href="'.$PAGE->url.'?action=deleteitem&itemid='.$item->id.'&sesskey='.sesskey().'" ';
            echo 'class="btn btn-sm btn-outline-danger" ';
            echo 'onclick="return confirm(\''.get_string('confirmdeleteitem', 'mod_observationchecklist').'\')">';
            echo '<i class="fa fa-trash"></i> '.get_string('delete', 'mod_observationchecklist');
            echo '</a>';
        }
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    echo '</div>';
} else {
    echo '<div class="alert alert-info mt-3">';
    echo '<i class="fa fa-info-circle"></i> ';
    echo get_string('noitemsfound', 'mod_observationchecklist');
    echo '</div>';
}

// Assessment interface for teachers
if (has_capability('mod/observationchecklist:assess', $context)) {
    $students = get_enrolled_users($context, 'mod/observationchecklist:submit');
    if (!empty($students)) {
        echo '<div class="card mt-3">';
        echo '<div class="card-header"><h5 class="mb-0">'.get_string('studentassessment', 'mod_observationchecklist').'</h5></div>';
        echo '<div class="card-body">';
        echo '<p>'.get_string('choosestudentmessage', 'mod_observationchecklist').'</p>';
        
        echo '<div class="list-group">';
        foreach ($students as $student) {
            echo '<a href="assess.php?id='.$cm->id.'&userid='.$student->id.'" class="list-group-item list-group-item-action">';
            echo '<div class="d-flex w-100 justify-content-between">';
            echo '<h6 class="mb-1">'.fullname($student).'</h6>';
            echo '<small class="text-muted">'.$student->email.'</small>';
            echo '</div>';
            echo '</a>';
        }
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
}

echo $OUTPUT->footer();
