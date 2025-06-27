
<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Internal library of functions for module observationchecklist
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Add a new item to a checklist
 *
 * @param int $checklistid
 * @param string $itemtext
 * @param string $category
 * @param int $userid
 * @return int
 */
function observationchecklist_add_item($checklistid, $itemtext, $category = 'General', $userid = null) {
    global $DB, $USER;
    
    if ($userid === null) {
        $userid = $USER->id;
    }
    
    // Get the next position
    $maxposition = $DB->get_field('observationchecklist_items', 'MAX(position)', array('checklistid' => $checklistid));
    $position = $maxposition ? $maxposition + 1 : 1;
    
    $item = new stdClass();
    $item->checklistid = $checklistid;
    $item->itemtext = $itemtext;
    $item->category = $category;
    $item->userid = $userid;
    $item->position = $position;
    $item->timecreated = time();
    $item->timemodified = time();
    
    return $DB->insert_record('observationchecklist_items', $item);
}

/**
 * Delete an item from a checklist
 *
 * @param int $itemid
 * @return bool
 */
function observationchecklist_delete_item($itemid) {
    global $DB;
    
    // Delete user progress records for this item
    $DB->delete_records('observationchecklist_user_items', array('itemid' => $itemid));
    
    // Delete the item
    return $DB->delete_records('observationchecklist_items', array('id' => $itemid));
}

/**
 * Assess an item for a user
 *
 * @param int $itemid
 * @param int $userid
 * @param string $status
 * @param string $notes
 * @param int $assessorid
 * @return bool
 */
function observationchecklist_assess_item($itemid, $userid, $status, $notes = '', $assessorid = null) {
    global $DB, $USER;
    
    if ($assessorid === null) {
        $assessorid = $USER->id;
    }
    
    $item = $DB->get_record('observationchecklist_items', array('id' => $itemid), '*', MUST_EXIST);
    
    // Check if assessment already exists
    $existing = $DB->get_record('observationchecklist_user_items', array('itemid' => $itemid, 'userid' => $userid));
    
    if ($existing) {
        // Update existing assessment
        $existing->status = $status;
        $existing->assessornotes = $notes;
        $existing->assessorid = $assessorid;
        $existing->dateassessed = time();
        $existing->timemodified = time();
        
        return $DB->update_record('observationchecklist_user_items', $existing);
    } else {
        // Create new assessment
        $assessment = new stdClass();
        $assessment->checklistid = $item->checklistid;
        $assessment->itemid = $itemid;
        $assessment->userid = $userid;
        $assessment->status = $status;
        $assessment->assessornotes = $notes;
        $assessment->assessorid = $assessorid;
        $assessment->dateassessed = time();
        $assessment->timecreated = time();
        $assessment->timemodified = time();
        
        return $DB->insert_record('observationchecklist_user_items', $assessment);
    }
}

/**
 * Get user progress for a checklist
 *
 * @param int $checklistid
 * @param int $userid
 * @return array
 */
function observationchecklist_get_user_progress($checklistid, $userid) {
    global $DB;
    
    $sql = "SELECT i.*, ui.status, ui.assessornotes, ui.dateassessed, ui.assessorid
            FROM {observationchecklist_items} i
            LEFT JOIN {observationchecklist_user_items} ui ON i.id = ui.itemid AND ui.userid = ?
            WHERE i.checklistid = ?
            ORDER BY i.position ASC";
    
    return $DB->get_records_sql($sql, array($userid, $checklistid));
}

/**
 * Get all users progress for a checklist
 *
 * @param int $checklistid
 * @return array
 */
function observationchecklist_get_all_progress($checklistid) {
    global $DB;
    
    $sql = "SELECT u.id as userid, u.firstname, u.lastname, 
                   COUNT(ui.id) as assessed_items,
                   COUNT(i.id) as total_items,
                   ROUND(COUNT(ui.id) * 100.0 / COUNT(i.id), 2) as completion_percentage
            FROM {user} u
            CROSS JOIN {observationchecklist_items} i
            LEFT JOIN {observationchecklist_user_items} ui ON i.id = ui.itemid AND u.id = ui.userid
            WHERE i.checklistid = ?
            GROUP BY u.id, u.firstname, u.lastname
            ORDER BY u.lastname, u.firstname";
    
    return $DB->get_records_sql($sql, array($checklistid));
}
