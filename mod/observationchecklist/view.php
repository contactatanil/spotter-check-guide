
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

// Trigger module viewed event.
$event = \mod_observationchecklist\event\course_module_viewed::create(array(
    'objectid' => $observationchecklist->id,
    'context' => $context
));
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('observationchecklist', $observationchecklist);
$event->trigger();

// Handle form submissions
if ($action && confirm_sesskey()) {
    switch ($action) {
        case 'additem':
            require_capability('mod/observationchecklist:edit', $context);
            $itemtext = required_param('itemtext', PARAM_TEXT);
            $category = optional_param('category', 'General', PARAM_TEXT);
            observationchecklist_add_item($observationchecklist->id, $itemtext, $category, $USER->id);
            redirect($PAGE->url, get_string('itemadded', 'mod_observationchecklist'));
            break;
            
        case 'assess':
            require_capability('mod/observationchecklist:assess', $context);
            $studentid = required_param('studentid', PARAM_INT);
            $status = required_param('status', PARAM_ALPHA);
            $notes = optional_param('notes', '', PARAM_TEXT);
            observationchecklist_assess_item($itemid, $studentid, $status, $notes, $USER->id);
            redirect($PAGE->url, get_string('assessmentadded', 'mod_observationchecklist'));
            break;
            
        case 'deleteitem':
            require_capability('mod/observationchecklist:edit', $context);
            observationchecklist_delete_item($itemid);
            redirect($PAGE->url, get_string('itemdeleted', 'mod_observationchecklist'));
            break;
    }
}

$PAGE->set_url('/mod/observationchecklist/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($observationchecklist->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

// Check user capabilities
$canassess = has_capability('mod/observationchecklist:assess', $context);
$canedit = has_capability('mod/observationchecklist:edit', $context);
$cansubmit = has_capability('mod/observationchecklist:submit', $context) && $observationchecklist->allowstudentsubmit;

echo $OUTPUT->header();

// Get data for templates
$items = observationchecklist_get_items($observationchecklist->id);
$progress = observationchecklist_get_user_progress($observationchecklist->id, $USER->id);

// Prepare template data
$templatecontext = [
    'checklist' => $observationchecklist,
    'course' => $course,
    'cm' => $cm,
    'items' => array_values($items),
    'progress' => $progress,
    'canassess' => $canassess,
    'canedit' => $canedit,
    'cansubmit' => $cansubmit,
    'sesskey' => sesskey(),
    'actionurl' => $PAGE->url->out(false)
];

// Calculate statistics
$totalItems = count($items);
$completedItems = 0;
$satisfactoryItems = 0;
$notSatisfactoryItems = 0;

foreach ($progress as $item) {
    if ($item->status == 'satisfactory' || $item->status == 'not_satisfactory') {
        $completedItems++;
        if ($item->status == 'satisfactory') {
            $satisfactoryItems++;
        } else {
            $notSatisfactoryItems++;
        }
    }
}

$templatecontext['stats'] = [
    'total_items' => $totalItems,
    'completed_items' => $completedItems,
    'satisfactory_items' => $satisfactoryItems,
    'not_satisfactory_items' => $notSatisfactoryItems,
    'progress_percentage' => $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : 0
];

// Render appropriate template based on user role
if ($canassess) {
    // Get students for assessment interface
    $students = get_enrolled_users($context, 'mod/observationchecklist:submit');
    $templatecontext['students'] = array_values($students);
    
    echo $OUTPUT->render_from_template('mod_observationchecklist/assessment_interface', $templatecontext);
} else if ($cansubmit) {
    // Prepare student progress data
    $progressitems = [];
    foreach ($items as $item) {
        $itemProgress = isset($progress[$item->id]) ? $progress[$item->id] : null;
        $status = $itemProgress ? $itemProgress->status : 'not_started';
        
        $progressitems[] = [
            'item_id' => $item->id,
            'item_text' => format_text($item->itemtext),
            'category' => $item->category,
            'status' => $status,
            'status_text' => get_string($status, 'mod_observationchecklist'),
            'status_color' => $status == 'satisfactory' ? 'success' : 
                            ($status == 'not_satisfactory' ? 'danger' : 
                            ($status == 'in_progress' ? 'warning' : 'secondary')),
            'status_icon' => $status == 'satisfactory' ? 'fa-check-circle' : 
                           ($status == 'not_satisfactory' ? 'fa-times-circle' : 
                           ($status == 'in_progress' ? 'fa-clock' : 'fa-circle')),
            'has_feedback' => $itemProgress && !empty($itemProgress->assessornotes),
            'assessor_notes' => $itemProgress ? $itemProgress->assessornotes : '',
            'assessor_name' => $itemProgress && $itemProgress->assessorid ? 
                             fullname($DB->get_record('user', ['id' => $itemProgress->assessorid])) : '',
            'date_assessed' => $itemProgress && $itemProgress->dateassessed ? 
                             userdate($itemProgress->dateassessed) : '',
            'can_submit_evidence' => $observationchecklist->allowstudentsubmit
        ];
    }
    
    $templatecontext['items'] = $progressitems;
    $templatecontext['completed_items'] = $completedItems;
    $templatecontext['total_items'] = $totalItems;
    $templatecontext['success_rate'] = $totalItems > 0 ? round(($satisfactoryItems / $totalItems) * 100) : 0;
    $templatecontext['pending_items'] = $totalItems - $completedItems;
    
    echo $OUTPUT->render_from_template('mod_observationchecklist/student_progress', $templatecontext);
} else {
    // Default overview for all users
    echo $OUTPUT->render_from_template('mod_observationchecklist/checklist_overview', $templatecontext);
}

// Add custom CSS and JS
$PAGE->requires->css('/mod/observationchecklist/styles.css');

echo $OUTPUT->footer();
