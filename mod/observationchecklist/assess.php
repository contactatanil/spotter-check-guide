
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Assessment interface for observationchecklist
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

$id = required_param('id', PARAM_INT); // Course module ID
$userid = required_param('userid', PARAM_INT); // Student ID

$cm = get_coursemodule_from_id('observationchecklist', $id, 0, false, MUST_EXIST);
$course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
$observationchecklist = $DB->get_record('observationchecklist', array('id' => $cm->instance), '*', MUST_EXIST);
$student = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

require_capability('mod/observationchecklist:assess', $context);

// Handle form submission
if (data_submitted() && confirm_sesskey()) {
    $assessments = optional_param_array('assessment', array(), PARAM_RAW);
    $notes = optional_param_array('notes', array(), PARAM_TEXT);
    
    foreach ($assessments as $itemid => $status) {
        if (!empty($status)) {
            $itemid = (int)$itemid;
            $assessornotes = isset($notes[$itemid]) ? $notes[$itemid] : '';
            
            // Check if assessment already exists
            $existing = $DB->get_record('observationchecklist_user_items', 
                array('itemid' => $itemid, 'userid' => $userid));
            
            if ($existing) {
                $existing->status = $status;
                $existing->assessornotes = $assessornotes;
                $existing->assessorid = $USER->id;
                $existing->dateassessed = time();
                $existing->timemodified = time();
                $DB->update_record('observationchecklist_user_items', $existing);
            } else {
                $record = new stdClass();
                $record->checklistid = $observationchecklist->id;
                $record->itemid = $itemid;
                $record->userid = $userid;
                $record->status = $status;
                $record->assessornotes = $assessornotes;
                $record->assessorid = $USER->id;
                $record->dateassessed = time();
                $record->timecreated = time();
                $record->timemodified = time();
                $DB->insert_record('observationchecklist_user_items', $record);
            }
        }
    }
    
    redirect($PAGE->url, get_string('observationsaved', 'mod_observationchecklist'));
}

$PAGE->set_url('/mod/observationchecklist/assess.php', array('id' => $cm->id, 'userid' => $userid));
$PAGE->set_title(get_string('studentassessment', 'mod_observationchecklist'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('studentassessment', 'mod_observationchecklist') . ': ' . fullname($student));

// Get items and current progress
$items = $DB->get_records('observationchecklist_items', array('checklistid' => $observationchecklist->id), 'id ASC');
$progress = $DB->get_records('observationchecklist_user_items', 
    array('checklistid' => $observationchecklist->id, 'userid' => $userid), '', 'itemid, status, assessornotes');

if (!empty($items)) {
    echo '<form method="post" action="' . $PAGE->url . '">';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
    
    foreach ($items as $item) {
        $currentstatus = isset($progress[$item->id]) ? $progress[$item->id]->status : 'not_started';
        $currentnotes = isset($progress[$item->id]) ? $progress[$item->id]->assessornotes : '';
        
        echo '<div class="card mb-3">';
        echo '<div class="card-body">';
        echo '<h6 class="card-title">' . format_text($item->itemtext) . '</h6>';
        
        // Status selection
        echo '<div class="mb-3">';
        echo '<label class="form-label">' . get_string('status', 'mod_observationchecklist') . '</label><br>';
        
        $statuses = array(
            'not_started' => get_string('notstarted', 'mod_observationchecklist'),
            'in_progress' => get_string('inprogress', 'mod_observationchecklist'),
            'satisfactory' => get_string('satisfactory', 'mod_observationchecklist'),
            'not_satisfactory' => get_string('notsatisfactory', 'mod_observationchecklist')
        );
        
        foreach ($statuses as $value => $label) {
            $checked = ($value == $currentstatus) ? 'checked' : '';
            echo '<div class="form-check form-check-inline">';
            echo '<input class="form-check-input" type="radio" name="assessment[' . $item->id . ']" ';
            echo 'id="status_' . $item->id . '_' . $value . '" value="' . $value . '" ' . $checked . '>';
            echo '<label class="form-check-label" for="status_' . $item->id . '_' . $value . '">' . $label . '</label>';
            echo '</div>';
        }
        echo '</div>';
        
        // Notes
        echo '<div class="mb-3">';
        echo '<label for="notes_' . $item->id . '" class="form-label">' . get_string('assessornotes', 'mod_observationchecklist') . '</label>';
        echo '<textarea class="form-control" id="notes_' . $item->id . '" name="notes[' . $item->id . ']" rows="2">';
        echo s($currentnotes);
        echo '</textarea>';
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
    
    echo '<div class="mt-3">';
    echo '<button type="submit" class="btn btn-primary me-2">' . get_string('save', 'mod_observationchecklist') . '</button>';
    echo '<a href="view.php?id=' . $cm->id . '" class="btn btn-secondary">' . get_string('back', 'mod_observationchecklist') . '</a>';
    echo '</div>';
    
    echo '</form>';
} else {
    echo '<div class="alert alert-info">';
    echo get_string('noitemsfound', 'mod_observationchecklist');
    echo '</div>';
}

echo $OUTPUT->footer();
