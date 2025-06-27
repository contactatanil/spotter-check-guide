
<?php
// This file is part of Moodle - http://moodle.org/

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

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

// Handle actions
if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'additem':
            $itemtext = required_param('itemtext', PARAM_TEXT);
            $category = optional_param('category', 'General', PARAM_TEXT);
            
            if (has_capability('mod/observationchecklist:edit', $context)) {
                try {
                    observationchecklist_add_item($observationchecklist->id, $itemtext, $category, $USER->id);
                    redirect($PAGE->url, get_string('itemadded', 'mod_observationchecklist'), null, \core\output\notification::NOTIFY_SUCCESS);
                } catch (Exception $e) {
                    redirect($PAGE->url, get_string('databaseerror', 'mod_observationchecklist'), null, \core\output\notification::NOTIFY_ERROR);
                }
            }
            break;
            
        case 'deleteitem':
            if (has_capability('mod/observationchecklist:edit', $context) && $itemid) {
                try {
                    observationchecklist_delete_item($itemid);
                    redirect($PAGE->url, get_string('itemdeleted', 'mod_observationchecklist'), null, \core\output\notification::NOTIFY_SUCCESS);
                } catch (Exception $e) {
                    redirect($PAGE->url, get_string('databaseerror', 'mod_observationchecklist'), null, \core\output\notification::NOTIFY_ERROR);
                }
            }
            break;
    }
}

// Trigger module viewed event.
$event = \mod_observationchecklist\event\course_module_viewed::create(array(
    'objectid' => $observationchecklist->id,
    'context' => $context
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('observationchecklist', $observationchecklist);
$event->trigger();

$PAGE->set_url('/mod/observationchecklist/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($observationchecklist->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Check user capabilities
$canassess = has_capability('mod/observationchecklist:assess', $context);
$canedit = has_capability('mod/observationchecklist:edit', $context);
$cansubmit = has_capability('mod/observationchecklist:submit', $context);

echo $OUTPUT->header();

// Display the checklist name and description
echo $OUTPUT->heading($observationchecklist->name);

if (!empty($observationchecklist->description)) {
    echo $OUTPUT->box(format_text($observationchecklist->description, $observationchecklist->descriptionformat), 'generalbox', 'intro');
}

// Get checklist items
$items = observationchecklist_get_items($observationchecklist->id);

if ($canedit) {
    // Add item form
    echo '<div class="card mt-3">';
    echo '<div class="card-header">';
    echo '<h4>' . get_string('addnewitem', 'mod_observationchecklist') . '</h4>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<form method="post" action="' . $PAGE->url . '">';
    echo '<input type="hidden" name="sesskey" value="' . sesskey() . '" />';
    echo '<input type="hidden" name="action" value="additem" />';
    echo '<div class="form-group mb-3">';
    echo '<label for="itemtext">' . get_string('itemtext', 'mod_observationchecklist') . '</label>';
    echo '<textarea class="form-control" id="itemtext" name="itemtext" rows="3" required></textarea>';
    echo '</div>';
    echo '<div class="form-group mb-3">';
    echo '<label for="category">' . get_string('category', 'mod_observationchecklist') . '</label>';
    echo '<input type="text" class="form-control" id="category" name="category" value="General" />';
    echo '</div>';
    echo '<button type="submit" class="btn btn-primary">' . get_string('additem', 'mod_observationchecklist') . '</button>';
    echo '</form>';
    echo '</div>';
    echo '</div>';
}

// Display items
if (!empty($items)) {
    echo '<div class="card mt-3">';
    echo '<div class="card-header">';
    echo '<h4>' . get_string('assessmentitems', 'mod_observationchecklist') . '</h4>';
    echo '</div>';
    echo '<div class="card-body">';
    
    $currentcategory = '';
    foreach ($items as $item) {
        if ($item->category != $currentcategory) {
            if ($currentcategory != '') {
                echo '</div>'; // Close previous category
            }
            echo '<h5 class="mt-3">' . format_string($item->category) . '</h5>';
            echo '<div class="list-group">';
            $currentcategory = $item->category;
        }
        
        echo '<div class="list-group-item d-flex justify-content-between align-items-start">';
        echo '<div class="me-auto">';
        echo '<div class="fw-bold">' . format_text($item->itemtext) . '</div>';
        echo '<small class="text-muted">Added ' . userdate($item->timecreated) . '</small>';
        echo '</div>';
        
        if ($canedit) {
            echo '<a href="' . $PAGE->url . '?action=deleteitem&itemid=' . $item->id . '&sesskey=' . sesskey() . '" ';
            echo 'class="btn btn-sm btn-outline-danger" ';
            echo 'onclick="return confirm(\'' . get_string('confirmdeleteitem', 'mod_observationchecklist') . '\')">';
            echo get_string('delete', 'mod_observationchecklist');
            echo '</a>';
        }
        echo '</div>';
    }
    
    if ($currentcategory != '') {
        echo '</div>'; // Close last category
    }
    
    echo '</div>';
    echo '</div>';
} else {
    echo '<div class="alert alert-info mt-3">';
    echo get_string('noitemsfound', 'mod_observationchecklist');
    echo '</div>';
}

// Assessment interface for teachers
if ($canassess) {
    $students = get_enrolled_users($context, 'mod/observationchecklist:submit');
    if (!empty($students)) {
        echo '<div class="card mt-3">';
        echo '<div class="card-header">';
        echo '<h4>' . get_string('studentassessment', 'mod_observationchecklist') . '</h4>';
        echo '</div>';
        echo '<div class="card-body">';
        echo '<p>' . get_string('choosestudentmessage', 'mod_observationchecklist') . '</p>';
        
        echo '<div class="list-group">';
        foreach ($students as $student) {
            echo '<a href="assess.php?id=' . $cm->id . '&userid=' . $student->id . '" class="list-group-item list-group-item-action">';
            echo '<div class="d-flex w-100 justify-content-between">';
            echo '<h6 class="mb-1">' . fullname($student) . '</h6>';
            
            // Get progress for this student
            $progress = observationchecklist_get_user_progress($observationchecklist->id, $student->id);
            $totalitems = count($items);
            $assesseditems = count($progress);
            
            echo '<small>' . $assesseditems . '/' . $totalitems . ' ' . get_string('assesseditems', 'mod_observationchecklist') . '</small>';
            echo '</div>';
            echo '</a>';
        }
        echo '</div>';
        
        echo '</div>';
        echo '</div>';
    }
}

// Navigation buttons
if ($canassess) {
    echo '<div class="mt-3">';
    echo '<a href="multi_student.php?id=' . $cm->id . '" class="btn btn-secondary me-2">';
    echo get_string('multistudentobservation', 'mod_observationchecklist');
    echo '</a>';
    echo '</div>';
}

echo $OUTPUT->footer();
