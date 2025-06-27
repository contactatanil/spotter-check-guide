
<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Library of interface functions and constants for module observationchecklist
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know
 */
function observationchecklist_supports($feature) {
    switch($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the observationchecklist into the database
 */
function observationchecklist_add_instance(stdClass $observationchecklist, mod_observationchecklist_mod_form $mform = null) {
    global $DB;

    $observationchecklist->timecreated = time();
    $observationchecklist->timemodified = time();

    if (isset($observationchecklist->introeditor)) {
        $observationchecklist->introformat = $observationchecklist->introeditor['format'];
        $observationchecklist->intro = $observationchecklist->introeditor['text'];
        unset($observationchecklist->introeditor);
    }

    $observationchecklist->id = $DB->insert_record('observationchecklist', $observationchecklist);

    return $observationchecklist->id;
}

/**
 * Updates an instance of the observationchecklist in the database
 */
function observationchecklist_update_instance(stdClass $observationchecklist, mod_observationchecklist_mod_form $mform = null) {
    global $DB;

    $observationchecklist->timemodified = time();
    $observationchecklist->id = $observationchecklist->instance;

    if (isset($observationchecklist->introeditor)) {
        $observationchecklist->introformat = $observationchecklist->introeditor['format'];
        $observationchecklist->intro = $observationchecklist->introeditor['text'];
        unset($observationchecklist->introeditor);
    }

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

    $DB->delete_records('observationchecklist_items', array('checklistid' => $id));
    $DB->delete_records('observationchecklist_user_items', array('checklistid' => $id));
    $DB->delete_records('observationchecklist', array('id' => $id));

    return true;
}
