
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Internal library of functions for module observationchecklist
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get all items for a checklist
 *
 * @param int $checklistid
 * @return array
 */
function observationchecklist_get_items($checklistid) {
    global $DB;
    
    return $DB->get_records('observationchecklist_items', 
        array('checklistid' => $checklistid), 
        'id ASC'
    );
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
    
    return $DB->get_records('observationchecklist_user_items', 
        array('checklistid' => $checklistid, 'userid' => $userid), 
        '', 'itemid, status, assessornotes, dateassessed'
    );
}

/**
 * Add a new item to a checklist
 *
 * @param int $checklistid
 * @param string $itemtext
 * @param string $category
 * @param int $userid
 * @return int item id
 */
function observationchecklist_add_item($checklistid, $itemtext, $category, $userid) {
    global $DB;
    
    $item = new stdClass();
    $item->checklistid = $checklistid;
    $item->itemtext = clean_param($itemtext, PARAM_TEXT);
    $item->category = clean_param($category, PARAM_TEXT);
    $item->userid = $userid;
    $item->position = 0;
    $item->sortorder = 0;
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
    
    // Delete user progress for this item
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
function observationchecklist_assess_item($itemid, $userid, $status, $notes, $assessorid) {
    global $DB;
    
    $item = $DB->get_record('observationchecklist_items', array('id' => $itemid), '*', MUST_EXIST);
    
    $existing = $DB->get_record('observationchecklist_user_items', 
        array('itemid' => $itemid, 'userid' => $userid));
    
    if ($existing) {
        $existing->status = $status;
        $existing->assessornotes = clean_param($notes, PARAM_TEXT);
        $existing->assessorid = $assessorid;
        $existing->dateassessed = time();
        $existing->timemodified = time();
        return $DB->update_record('observationchecklist_user_items', $existing);
    } else {
        $record = new stdClass();
        $record->checklistid = $item->checklistid;
        $record->itemid = $itemid;
        $record->userid = $userid;
        $record->status = $status;
        $record->assessornotes = clean_param($notes, PARAM_TEXT);
        $record->assessorid = $assessorid;
        $record->dateassessed = time();
        $record->timecreated = time();
        $record->timemodified = time();
        return $DB->insert_record('observationchecklist_user_items', $record);
    }
}
