
<?php
// This file is part of Moodle - http://moodle.org/

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');
require_once(dirname(__FILE__).'/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID
$n  = optional_param('n', 0, PARAM_INT);  // observationchecklist instance ID

if ($id) {
    $cm         = get_coursemodule_from_id('observationchecklist', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $observationchecklist  = $DB->get_record('observationchecklist', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $observationchecklist  = $DB->get_record('observationchecklist', array('id' => $n), '*', MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $observationchecklist->course), '*', MUST_EXIST);
    $cm         = get_coursemodule_from_instance('observationchecklist', $observationchecklist->id, $course->id, false, MUST_EXIST);
} else {
    error('You must specify a course_module ID or an instance ID');
}

require_login($course, true, $cm);

$context = context_module::instance($cm->id);

// Handle AJAX requests
if (optional_param('ajax', 0, PARAM_INT)) {
    $action = required_param('action', PARAM_ALPHA);
    
    switch ($action) {
        case 'additem':
            require_capability('mod/observationchecklist:edit', $context);
            $itemtext = required_param('itemtext', PARAM_TEXT);
            $itemid = observationchecklist_add_item($observationchecklist->id, $itemtext, $USER->id);
            echo json_encode(array('success' => true, 'itemid' => $itemid));
            break;
            
        case 'updatestatus':
            $itemid = required_param('itemid', PARAM_INT);
            $checked = required_param('checked', PARAM_BOOL);
            observationchecklist_update_item_status($itemid, $USER->id, $checked);
            echo json_encode(array('success' => true));
            break;
            
        case 'deleteitem':
            require_capability('mod/observationchecklist:edit', $context);
            $itemid = required_param('itemid', PARAM_INT);
            $DB->delete_records('observationchecklist_items', array('id' => $itemid));
            $DB->delete_records('observationchecklist_user_items', array('itemid' => $itemid));
            echo json_encode(array('success' => true));
            break;
    }
    exit;
}

$PAGE->set_url('/mod/observationchecklist/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($observationchecklist->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->requires->css('/mod/observationchecklist/styles.css');
$PAGE->requires->js_call_amd('mod_observationchecklist/checklist', 'init');

// Output starts here.
echo $OUTPUT->header();

echo $OUTPUT->heading($observationchecklist->name);

if ($observationchecklist->intro) {
    echo $OUTPUT->box(format_module_intro('observationchecklist', $observationchecklist, $cm->id), 'generalbox mod_introbox', 'observationchecklistintro');
}

// Get checklist items and user progress
$items = observationchecklist_get_user_progress($observationchecklist->id, $USER->id);
$canedit = has_capability('mod/observationchecklist:edit', $context);
$canaddstudent = $observationchecklist->allowstudentadd || $canedit;

echo '<div class="checklist-container">';
echo '<div class="checklist-header">';
echo '<h3>' . get_string('checklistitems', 'mod_observationchecklist') . '</h3>';

if ($canaddstudent) {
    echo '<div class="add-item-form">';
    echo '<input type="text" id="new-item-text" placeholder="' . get_string('newitemtext', 'mod_observationchecklist') . '">';
    echo '<button id="add-item-btn" class="btn btn-primary">' . get_string('additem', 'mod_observationchecklist') . '</button>';
    echo '</div>';
}

echo '</div>';

echo '<div class="checklist-items">';
if (empty($items)) {
    echo '<p class="no-items">' . get_string('noitems', 'mod_observationchecklist') . '</p>';
} else {
    foreach ($items as $item) {
        echo '<div class="checklist-item" data-itemid="' . $item->id . '">';
        echo '<label class="checklist-label">';
        echo '<input type="checkbox" class="item-checkbox" ' . ($item->checked ? 'checked' : '') . '>';
        echo '<span class="item-text">' . format_text($item->itemtext) . '</span>';
        echo '</label>';
        if ($canedit) {
            echo '<button class="delete-item-btn btn btn-danger btn-sm" data-itemid="' . $item->id . '">';
            echo get_string('delete');
            echo '</button>';
        }
        echo '</div>';
    }
}
echo '</div>';
echo '</div>';

// JavaScript for handling interactions
echo '<script>
document.addEventListener("DOMContentLoaded", function() {
    const addBtn = document.getElementById("add-item-btn");
    const newItemInput = document.getElementById("new-item-text");
    
    if (addBtn) {
        addBtn.addEventListener("click", function() {
            const itemText = newItemInput.value.trim();
            if (itemText) {
                addItem(itemText);
            }
        });
        
        newItemInput.addEventListener("keypress", function(e) {
            if (e.key === "Enter") {
                addBtn.click();
            }
        });
    }
    
    // Handle checkbox changes
    document.querySelectorAll(".item-checkbox").forEach(function(checkbox) {
        checkbox.addEventListener("change", function() {
            const itemId = this.closest(".checklist-item").dataset.itemid;
            updateItemStatus(itemId, this.checked);
        });
    });
    
    // Handle delete buttons
    document.querySelectorAll(".delete-item-btn").forEach(function(btn) {
        btn.addEventListener("click", function() {
            const itemId = this.dataset.itemid;
            if (confirm("' . get_string('confirmdelete', 'mod_observationchecklist') . '")) {
                deleteItem(itemId);
            }
        });
    });
});

function addItem(itemText) {
    fetch(window.location.href + "&ajax=1", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "action=additem&itemtext=" + encodeURIComponent(itemText) + "&sesskey=' . sesskey() . '"
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}

function updateItemStatus(itemId, checked) {
    fetch(window.location.href + "&ajax=1", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "action=updatestatus&itemid=" + itemId + "&checked=" + (checked ? 1 : 0) + "&sesskey=' . sesskey() . '"
    });
}

function deleteItem(itemId) {
    fetch(window.location.href + "&ajax=1", {
        method: "POST",
        headers: {"Content-Type": "application/x-www-form-urlencoded"},
        body: "action=deleteitem&itemid=" + itemId + "&sesskey=' . sesskey() . '"
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        }
    });
}
</script>';

echo $OUTPUT->footer();
