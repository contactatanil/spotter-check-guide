
<?php
defined('MOODLE_INTERNAL') || die();

function observationchecklist_get_items($checklistid) {
    global $DB;
    
    return $DB->get_records('observationchecklist_items', 
        array('checklistid' => $checklistid), 
        'position ASC, id ASC'
    );
}

function observationchecklist_add_item($checklistid, $itemtext, $category = 'General', $userid) {
    global $DB;
    
    if (empty($itemtext)) {
        throw new invalid_parameter_exception('Item text cannot be empty');
    }
    
    $maxposition = $DB->get_field_sql(
        'SELECT MAX(position) FROM {observationchecklist_items} WHERE checklistid = ?', 
        array($checklistid)
    );
    
    $item = new stdClass();
    $item->checklistid = $checklistid;
    $item->itemtext = clean_param($itemtext, PARAM_TEXT);
    $item->category = clean_param($category, PARAM_TEXT);
    $item->userid = $userid;
    $item->position = ($maxposition ? $maxposition + 1 : 1);
    $item->sortorder = $item->position;
    $item->timecreated = time();
    $item->timemodified = time();
    
    return $DB->insert_record('observationchecklist_items', $item);
}

function observationchecklist_delete_item($itemid) {
    global $DB;
    
    $DB->delete_records('observationchecklist_user_items', array('itemid' => $itemid));
    return $DB->delete_records('observationchecklist_items', array('id' => $itemid));
}

function observationchecklist_assess_item($itemid, $studentid, $status, $notes, $assessorid) {
    global $DB;
    
    $valid_statuses = array('satisfactory', 'not_satisfactory', 'in_progress', 'not_started');
    if (!in_array($status, $valid_statuses)) {
        throw new invalid_parameter_exception('Invalid status');
    }
    
    $item = $DB->get_record('observationchecklist_items', array('id' => $itemid), '*', MUST_EXIST);
    
    $existing = $DB->get_record('observationchecklist_user_items', 
        array('itemid' => $itemid, 'userid' => $studentid)
    );
    
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
        $record->userid = $studentid;
        $record->status = $status;
        $record->assessornotes = clean_param($notes, PARAM_TEXT);
        $record->assessorid = $assessorid;
        $record->dateassessed = time();
        $record->timecreated = time();
        $record->timemodified = time();
        return $DB->insert_record('observationchecklist_user_items', $record);
    }
}

function observationchecklist_get_user_progress($checklistid, $userid) {
    global $DB;
    
    $sql = "SELECT ui.itemid, ui.status, ui.assessornotes, ui.dateassessed, ui.assessorid
            FROM {observationchecklist_user_items} ui
            WHERE ui.checklistid = ? AND ui.userid = ?";
    
    $progress = $DB->get_records_sql($sql, array($checklistid, $userid));
    
    $result = array();
    foreach ($progress as $item) {
        $result[$item->itemid] = $item;
    }
    
    return $result;
}
