
<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 */
function observationchecklist_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        default:
            return null;
    }
}

/**
 * Adds an instance of the observationchecklist to the database
 */
function observationchecklist_add_instance($observationchecklist, $mform = null) {
    global $DB;
    
    $observationchecklist->timecreated = time();
    $observationchecklist->timemodified = time();
    
    $id = $DB->insert_record('observationchecklist', $observationchecklist);
    
    return $id;
}

/**
 * Updates an instance of the observationchecklist in the database
 */
function observationchecklist_update_instance($observationchecklist, $mform = null) {
    global $DB;
    
    $observationchecklist->timemodified = time();
    $observationchecklist->id = $observationchecklist->instance;
    
    return $DB->update_record('observationchecklist', $observationchecklist);
}

/**
 * Removes an instance of the observationchecklist from the database
 */
function observationchecklist_delete_instance($id) {
    global $DB;
    
    if (!$observationchecklist = $DB->get_record('observationchecklist', array('id' => $id))) {
        return false;
    }
    
    // Delete all related checklist items
    $DB->delete_records('observationchecklist_items', array('checklistid' => $id));
    $DB->delete_records('observationchecklist_user_items', array('checklistid' => $id));
    
    // Delete the main record
    $DB->delete_records('observationchecklist', array('id' => $id));
    
    return true;
}
