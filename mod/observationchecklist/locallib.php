
<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Get all checklist items for a specific checklist
 */
function observationchecklist_get_items($checklistid) {
    global $DB;
    
    return $DB->get_records('observationchecklist_items', 
        array('checklistid' => $checklistid), 
        'sortorder ASC'
    );
}

/**
 * Add a new item to the checklist
 */
function observationchecklist_add_item($checklistid, $itemtext, $category, $userid) {
    global $DB;
    
    $maxsort = $DB->get_field_sql('SELECT MAX(sortorder) FROM {observationchecklist_items} WHERE checklistid = ?', array($checklistid));
    
    $item = new stdClass();
    $item->checklistid = $checklistid;
    $item->itemtext = $itemtext;
    $item->category = $category;
    $item->userid = $userid;
    $item->sortorder = ($maxsort + 1);
    $item->timecreated = time();
    
    return $DB->insert_record('observationchecklist_items', $item);
}

/**
 * Delete a checklist item
 */
function observationchecklist_delete_item($itemid) {
    global $DB;
    
    // Delete the item and all associated user progress
    $DB->delete_records('observationchecklist_user_items', array('itemid' => $itemid));
    $DB->delete_records('observationchecklist_items', array('id' => $itemid));
    
    return true;
}

/**
 * Assess a checklist item for a student
 */
function observationchecklist_assess_item($itemid, $studentid, $status, $notes, $assessorid) {
    global $DB;
    
    $item = $DB->get_record('observationchecklist_items', array('id' => $itemid), '*', MUST_EXIST);
    
    $existing = $DB->get_record('observationchecklist_user_items', 
        array('itemid' => $itemid, 'userid' => $studentid)
    );
    
    if ($existing) {
        $existing->status = $status;
        $existing->assessornotes = $notes;
        $existing->assessorid = $assessorid;
        $existing->dateassessed = time();
        $existing->timemodified = time();
        return $DB->update_record('observationchecklist_user_items', $existing);
    } else {
        $record = new stdClass();
        $record->itemid = $itemid;
        $record->userid = $studentid;
        $record->checklistid = $item->checklistid;
        $record->status = $status;
        $record->assessornotes = $notes;
        $record->assessorid = $assessorid;
        $record->dateassessed = time();
        $record->timecreated = time();
        $record->timemodified = time();
        return $DB->insert_record('observationchecklist_user_items', $record);
    }
}

/**
 * Get user's progress for checklist items
 */
function observationchecklist_get_user_progress($checklistid, $userid) {
    global $DB;
    
    $sql = "SELECT i.id, i.itemtext, i.category, 
                   COALESCE(ui.status, 'not_started') as status,
                   ui.assessornotes, ui.dateassessed, ui.assessorid
            FROM {observationchecklist_items} i
            LEFT JOIN {observationchecklist_user_items} ui ON i.id = ui.itemid AND ui.userid = ?
            WHERE i.checklistid = ?
            ORDER BY i.sortorder ASC";
    
    return $DB->get_records_sql($sql, array($userid, $checklistid));
}

/**
 * Get all students' progress for a checklist
 */
function observationchecklist_get_all_progress($checklistid) {
    global $DB;
    
    $sql = "SELECT u.id as userid, u.firstname, u.lastname, u.email,
                   COUNT(ui.id) as assessed_items,
                   SUM(CASE WHEN ui.status = 'satisfactory' THEN 1 ELSE 0 END) as satisfactory_items,
                   SUM(CASE WHEN ui.status = 'not_satisfactory' THEN 1 ELSE 0 END) as not_satisfactory_items
            FROM {user} u
            LEFT JOIN {observationchecklist_user_items} ui ON u.id = ui.userid AND ui.checklistid = ?
            WHERE u.id IN (
                SELECT DISTINCT userid 
                FROM {role_assignments} ra
                JOIN {context} ctx ON ra.contextid = ctx.id
                WHERE ctx.instanceid = (SELECT course FROM {observationchecklist} WHERE id = ?)
                AND ra.roleid IN (SELECT id FROM {role} WHERE shortname = 'student')
            )
            GROUP BY u.id, u.firstname, u.lastname, u.email
            ORDER BY u.lastname, u.firstname";
    
    return $DB->get_records_sql($sql, array($checklistid, $checklistid));
}

/**
 * Generate a printable report for a student
 */
function observationchecklist_generate_report($checklistid, $userid) {
    global $DB, $USER;
    
    $checklist = $DB->get_record('observationchecklist', array('id' => $checklistid), '*', MUST_EXIST);
    $student = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
    $items = observationchecklist_get_items($checklistid);
    $progress = observationchecklist_get_user_progress($checklistid, $userid);
    
    $report = new stdClass();
    $report->checklist = $checklist;
    $report->student = $student;
    $report->items = $items;
    $report->progress = $progress;
    $report->generated_by = $USER;
    $report->generated_date = time();
    
    return $report;
}
