
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
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);
$context = context_module::instance($cm->id);

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

// Add Bootstrap CSS
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css'));
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'));

echo $OUTPUT->header();

// Check user role
$canassess = has_capability('mod/observationchecklist:assess', $context);
$canedit = has_capability('mod/observationchecklist:edit', $context);
$cansubmit = has_capability('mod/observationchecklist:submit', $context) && $observationchecklist->allowstudentsubmit;

echo '<div class="container-fluid">';
echo '<div class="row">';

// Header
echo '<div class="col-12 mb-4">';
echo '<div class="card">';
echo '<div class="card-header bg-primary text-white">';
echo '<h2 class="mb-0">' . format_string($observationchecklist->name) . '</h2>';
echo '</div>';
echo '<div class="card-body">';
if ($observationchecklist->intro) {
    echo format_module_intro('observationchecklist', $observationchecklist, $cm->id);
}
echo '</div>';
echo '</div>';
echo '</div>';

// Get checklist items and user progress
$items = observationchecklist_get_items($observationchecklist->id);
$progress = observationchecklist_get_user_progress($observationchecklist->id, $USER->id);

// Role-based tabs
echo '<div class="col-12">';
echo '<ul class="nav nav-tabs" id="mainTabs" role="tablist">';
echo '<li class="nav-item" role="presentation">';
echo '<button class="nav-link active" id="overview-tab" data-bs-toggle="tab" data-bs-target="#overview" type="button" role="tab">Overview</button>';
echo '</li>';

if ($canassess) {
    echo '<li class="nav-item" role="presentation">';
    echo '<button class="nav-link" id="assess-tab" data-bs-toggle="tab" data-bs-target="#assess" type="button" role="tab">Assessment</button>';
    echo '</li>';
}

if ($cansubmit) {
    echo '<li class="nav-item" role="presentation">';
    echo '<button class="nav-link" id="student-tab" data-bs-toggle="tab" data-bs-target="#student" type="button" role="tab">My Progress</button>';
    echo '</li>';
}

echo '<li class="nav-item" role="presentation">';
echo '<button class="nav-link" id="reports-tab" data-bs-toggle="tab" data-bs-target="#reports" type="button" role="tab">Reports</button>';
echo '</li>';
echo '</ul>';

echo '<div class="tab-content" id="mainTabsContent">';

// Overview Tab
echo '<div class="tab-pane fade show active" id="overview" role="tabpanel">';
echo '<div class="row mt-3">';

// Progress cards
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

$progressPercentage = $totalItems > 0 ? ($completedItems / $totalItems) * 100 : 0;

echo '<div class="col-md-3">';
echo '<div class="card text-center border-success">';
echo '<div class="card-body">';
echo '<i class="fa fa-check-circle fa-2x text-success mb-2"></i>';
echo '<h4 class="text-success">' . $satisfactoryItems . '</h4>';
echo '<p class="card-text">Satisfactory</p>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<div class="card text-center border-danger">';
echo '<div class="card-body">';
echo '<i class="fa fa-times-circle fa-2x text-danger mb-2"></i>';
echo '<h4 class="text-danger">' . $notSatisfactoryItems . '</h4>';
echo '<p class="card-text">Not Satisfactory</p>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<div class="card text-center border-primary">';
echo '<div class="card-body">';
echo '<i class="fa fa-list fa-2x text-primary mb-2"></i>';
echo '<h4 class="text-primary">' . $totalItems . '</h4>';
echo '<p class="card-text">Total Items</p>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<div class="card text-center border-info">';
echo '<div class="card-body">';
echo '<i class="fa fa-chart-line fa-2x text-info mb-2"></i>';
echo '<h4 class="text-info">' . round($progressPercentage) . '%</h4>';
echo '<p class="card-text">Progress</p>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>'; // End progress cards row

// Items list
echo '<div class="row mt-4">';
echo '<div class="col-12">';
echo '<div class="card">';
echo '<div class="card-header d-flex justify-content-between align-items-center">';
echo '<h5 class="mb-0">Checklist Items</h5>';
if ($canedit) {
    echo '<button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addItemModal">Add Item</button>';
}
echo '</div>';
echo '<div class="card-body">';

if (empty($items)) {
    echo '<div class="text-center py-4">';
    echo '<p class="text-muted">No items have been added to this checklist yet.</p>';
    echo '</div>';
} else {
    foreach ($items as $item) {
        $itemProgress = isset($progress[$item->id]) ? $progress[$item->id] : null;
        $status = $itemProgress ? $itemProgress->status : 'not_started';
        
        echo '<div class="card mb-2">';
        echo '<div class="card-body">';
        echo '<div class="row align-items-center">';
        echo '<div class="col-md-8">';
        echo '<h6 class="mb-1">' . format_text($item->itemtext) . '</h6>';
        echo '<small class="text-muted">Category: ' . $item->category . '</small>';
        echo '</div>';
        echo '<div class="col-md-4 text-end">';
        
        switch ($status) {
            case 'satisfactory':
                echo '<span class="badge bg-success">Satisfactory</span>';
                break;
            case 'not_satisfactory':
                echo '<span class="badge bg-danger">Not Satisfactory</span>';
                break;
            case 'in_progress':
                echo '<span class="badge bg-warning">In Progress</span>';
                break;
            default:
                echo '<span class="badge bg-secondary">Not Started</span>';
        }
        
        if ($canedit) {
            echo ' <button class="btn btn-danger btn-sm ms-2" onclick="deleteItem(' . $item->id . ')">Delete</button>';
        }
        echo '</div>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }
}

echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>'; // End overview tab

// Assessment Tab (for assessors)
if ($canassess) {
    echo '<div class="tab-pane fade" id="assess" role="tabpanel">';
    echo '<div class="mt-3">';
    
    // Student selector
    $students = get_enrolled_users($context, 'mod/observationchecklist:submit');
    
    echo '<div class="card mb-4">';
    echo '<div class="card-header">';
    echo '<h5 class="mb-0">Select Student for Assessment</h5>';
    echo '</div>';
    echo '<div class="card-body">';
    echo '<select class="form-select" id="studentSelector" onchange="loadStudentProgress()">';
    echo '<option value="">Select a student...</option>';
    foreach ($students as $student) {
        echo '<option value="' . $student->id . '">' . fullname($student) . '</option>';
    }
    echo '</select>';
    echo '</div>';
    echo '</div>';
    
    echo '<div id="assessmentArea" style="display: none;">';
    // Assessment area will be populated via AJAX
    echo '</div>';
    
    echo '</div>';
    echo '</div>';
}

// Student Tab
if ($cansubmit) {
    echo '<div class="tab-pane fade" id="student" role="tabpanel">';
    echo '<div class="mt-3">';
    
    echo '<div class="alert alert-info">';
    echo '<strong>Note:</strong> Only assessors can mark items as complete. You can view your progress and submit evidence here.';
    echo '</div>';
    
    foreach ($items as $item) {
        $itemProgress = isset($progress[$item->id]) ? $progress[$item->id] : null;
        $status = $itemProgress ? $itemProgress->status : 'not_started';
        
        echo '<div class="card mb-3">';
        echo '<div class="card-header">';
        echo '<h6 class="mb-0">' . format_text($item->itemtext) . '</h6>';
        echo '</div>';
        echo '<div class="card-body">';
        echo '<p class="text-muted mb-2">Category: ' . $item->category . '</p>';
        
        switch ($status) {
            case 'satisfactory':
                echo '<div class="alert alert-success">✓ Marked as Satisfactory</div>';
                if ($itemProgress->assessornotes) {
                    echo '<div class="alert alert-light"><strong>Assessor Notes:</strong> ' . $itemProgress->assessornotes . '</div>';
                }
                break;
            case 'not_satisfactory':
                echo '<div class="alert alert-danger">✗ Marked as Not Satisfactory</div>';
                if ($itemProgress->assessornotes) {
                    echo '<div class="alert alert-light"><strong>Assessor Notes:</strong> ' . $itemProgress->assessornotes . '</div>';
                }
                break;
            case 'in_progress':
                echo '<div class="alert alert-warning">⏳ Awaiting Assessment</div>';
                break;
            default:
                echo '<div class="alert alert-secondary">📋 Ready for Evidence Submission</div>';
        }
        
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
}

// Reports Tab
echo '<div class="tab-pane fade" id="reports" role="tabpanel">';
echo '<div class="mt-3">';
echo '<div class="card">';
echo '<div class="card-header d-flex justify-content-between align-items-center">';
echo '<h5 class="mb-0">Assessment Report</h5>';
if ($observationchecklist->enableprinting) {
    echo '<button class="btn btn-primary" onclick="printReport()">Print Report</button>';
}
echo '</div>';
echo '<div class="card-body" id="reportContent">';

// Report content
echo '<div class="text-center mb-4">';
echo '<h4>Workplace Observation Report</h4>';
echo '<p class="text-muted">Assessment Summary and Progress Report</p>';
echo '</div>';

echo '<div class="row mb-4">';
echo '<div class="col-md-6">';
echo '<strong>Student:</strong> ' . fullname($USER) . '<br>';
echo '<strong>Course:</strong> ' . $course->fullname . '<br>';
echo '<strong>Report Generated:</strong> ' . date('Y-m-d H:i:s');
echo '</div>';
echo '<div class="col-md-6">';
echo '<strong>Total Items:</strong> ' . $totalItems . '<br>';
echo '<strong>Completed:</strong> ' . $completedItems . '<br>';
echo '<strong>Success Rate:</strong> ' . round($progressPercentage) . '%';
echo '</div>';
echo '</div>';

// Detailed items
foreach ($items as $item) {
    $itemProgress = isset($progress[$item->id]) ? $progress[$item->id] : null;
    $status = $itemProgress ? $itemProgress->status : 'not_started';
    
    echo '<div class="border rounded p-3 mb-3">';
    echo '<div class="row">';
    echo '<div class="col-md-8">';
    echo '<h6>' . format_text($item->itemtext) . '</h6>';
    echo '<small class="text-muted">Category: ' . $item->category . '</small>';
    echo '</div>';
    echo '<div class="col-md-4 text-end">';
    
    switch ($status) {
        case 'satisfactory':
            echo '<span class="badge bg-success">SATISFACTORY</span>';
            break;
        case 'not_satisfactory':
            echo '<span class="badge bg-danger">NOT SATISFACTORY</span>';
            break;
        case 'in_progress':
            echo '<span class="badge bg-warning">IN PROGRESS</span>';
            break;
        default:
            echo '<span class="badge bg-secondary">NOT STARTED</span>';
    }
    
    echo '</div>';
    echo '</div>';
    
    if ($itemProgress && $itemProgress->assessornotes) {
        echo '<div class="mt-2 p-2 bg-light rounded">';
        echo '<strong>Assessor Notes:</strong> ' . $itemProgress->assessornotes;
        if ($itemProgress->dateassessed) {
            echo '<br><small class="text-muted">Assessed on: ' . date('Y-m-d H:i', $itemProgress->dateassessed) . '</small>';
        }
        echo '</div>';
    }
    
    echo '</div>';
}

echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';

echo '</div>'; // End tab content
echo '</div>'; // End col-12
echo '</div>'; // End row
echo '</div>'; // End container

// Add Item Modal
if ($canedit) {
    echo '
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="post" action="' . $PAGE->url . '">
                    <div class="modal-header">
                        <h5 class="modal-title">Add New Item</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="itemtext" class="form-label">Item Description</label>
                            <textarea class="form-control" id="itemtext" name="itemtext" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" class="form-control" id="category" name="category" value="General">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <input type="hidden" name="action" value="additem">
                        <input type="hidden" name="sesskey" value="' . sesskey() . '">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Add Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>';
}

// JavaScript for interactivity
echo '
<script>
function deleteItem(itemId) {
    if (confirm("Are you sure you want to delete this item?")) {
        window.location.href = "' . $PAGE->url . '&action=deleteitem&itemid=" + itemId + "&sesskey=' . sesskey() . '";
    }
}

function printReport() {
    var printContent = document.getElementById("reportContent");
    var originalContent = document.body.innerHTML;
    document.body.innerHTML = printContent.innerHTML;
    window.print();
    document.body.innerHTML = originalContent;
    location.reload();
}

function loadStudentProgress() {
    var studentId = document.getElementById("studentSelector").value;
    if (studentId) {
        // AJAX call to load student progress for assessment
        document.getElementById("assessmentArea").style.display = "block";
        // Implementation would load student-specific assessment interface
    } else {
        document.getElementById("assessmentArea").style.display = "none";
    }
}
</script>';

echo $OUTPUT->footer();
