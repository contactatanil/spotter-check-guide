
<?php
// This file is part of Moodle - http://moodle.org/

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

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
    
    $saved = 0;
    foreach ($assessments as $itemid => $status) {
        if (!empty($status) && $status !== 'not_observed') {
            $itemid = (int)$itemid;
            $assessornotes = isset($notes[$itemid]) ? $notes[$itemid] : '';
            
            try {
                observationchecklist_assess_item($itemid, $userid, $status, $assessornotes, $USER->id);
                $saved++;
            } catch (Exception $e) {
                // Continue with other assessments
            }
        }
    }
    
    if ($saved > 0) {
        redirect($PAGE->url, get_string('observationsaved', 'mod_observationchecklist'), null, \core\output\notification::NOTIFY_SUCCESS);
    }
}

$PAGE->set_url('/mod/observationchecklist/assess.php', array('id' => $cm->id, 'userid' => $userid));
$PAGE->set_title(get_string('studentassessment', 'mod_observationchecklist'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('studentassessment', 'mod_observationchecklist') . ': ' . fullname($student));

// Get items and current progress
$items = observationchecklist_get_items($observationchecklist->id);
$progress = observationchecklist_get_user_progress($observationchecklist->id, $userid);

if (!empty($items)) {
    echo '<form method="post" action="' . $PAGE->url . '">';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
    
    $currentcategory = '';
    foreach ($items as $item) {
        if ($item->category != $currentcategory) {
            if ($currentcategory != '') {
                echo '</div></div>'; // Close previous category card
            }
            echo '<div class="card mt-3">';
            echo '<div class="card-header"><h5>' . format_string($item->category) . '</h5></div>';
            echo '<div class="card-body">';
            $currentcategory = $item->category;
        }
        
        $currentstatus = isset($progress[$item->id]) ? $progress[$item->id]->status : 'not_started';
        $currentnotes = isset($progress[$item->id]) ? $progress[$item->id]->assessornotes : '';
        
        echo '<div class="mb-4 border-bottom pb-3">';
        echo '<div class="mb-2"><strong>' . format_text($item->itemtext) . '</strong></div>';
        
        // Status selection
        echo '<div class="mb-2">';
        echo '<label class="form-label">' . get_string('status', 'mod_observationchecklist') . '</label><br>';
        
        $statuses = array(
            'not_observed' => get_string('notobserved', 'mod_observationchecklist'),
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
        echo '<div class="mb-2">';
        echo '<label for="notes_' . $item->id . '" class="form-label">' . get_string('assessornotes', 'mod_observationchecklist') . '</label>';
        echo '<textarea class="form-control" id="notes_' . $item->id . '" name="notes[' . $item->id . ']" rows="2">';
        echo s($currentnotes);
        echo '</textarea>';
        echo '</div>';
        
        echo '</div>';
    }
    
    if ($currentcategory != '') {
        echo '</div></div>'; // Close last category card
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
    echo '<a href="view.php?id=' . $cm->id . '" class="btn btn-secondary">' . get_string('back', 'mod_observationchecklist') . '</a>';
}

echo $OUTPUT->footer();
