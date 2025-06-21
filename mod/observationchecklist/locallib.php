
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
function observationchecklist_add_item($checklistid, $itemtext, $userid) {
    global $DB;
    
    $maxsort = $DB->get_field_sql('SELECT MAX(sortorder) FROM {observationchecklist_items} WHERE checklistid = ?', array($checklistid));
    
    $item = new stdClass();
    $item->checklistid = $checklistid;
    $item->itemtext = $itemtext;
    $item->userid = $userid;
    $item->sortorder = ($maxsort + 1);
    $item->timecreated = time();
    
    return $DB->insert_record('observationchecklist_items', $item);
}

/**
 * Update item completion status
 */
function observationchecklist_update_item_status($itemid, $userid, $checked) {
    global $DB;
    
    $existing = $DB->get_record('observationchecklist_user_items', 
        array('itemid' => $itemid, 'userid' => $userid)
    );
    
    if ($existing) {
        $existing->checked = $checked ? 1 : 0;
        $existing->timemodified = time();
        return $DB->update_record('observationchecklist_user_items', $existing);
    } else {
        $record = new stdClass();
        $record->itemid = $itemid;
        $record->userid = $userid;
        $record->checklistid = $DB->get_field('observationchecklist_items', 'checklistid', array('id' => $itemid));
        $record->checked = $checked ? 1 : 0;
        $record->timecreated = time();
        $record->timemodified = time();
        return $DB->insert_record('observationchecklist_user_items', $record);
    }
}

/**
 * Get user's completion status for checklist items
 */
function observationchecklist_get_user_progress($checklistid, $userid) {
    global $DB;
    
    $sql = "SELECT i.id, i.itemtext, COALESCE(ui.checked, 0) as checked
            FROM {observationchecklist_items} i
            LEFT JOIN {observationchecklist_user_items} ui ON i.id = ui.itemid AND ui.userid = ?
            WHERE i.checklistid = ?
            ORDER BY i.sortorder ASC";
    
    return $DB->get_records_sql($sql, array($userid, $checklistid));
}
