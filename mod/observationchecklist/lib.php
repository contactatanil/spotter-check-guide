
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
 *
 * @param stdClass $observationchecklist An object from the form
 * @param mod_observationchecklist_mod_form $mform The form
 * @return int The id of the newly inserted observationchecklist record
 */
function observationchecklist_add_instance(stdClass $observationchecklist, mod_observationchecklist_mod_form $mform = null) {
    global $DB;

    $observationchecklist->timecreated = time();
    $observationchecklist->timemodified = time();

    // Set default values for new fields if not provided
    if (!isset($observationchecklist->allowstudentadd)) {
        $observationchecklist->allowstudentadd = 1;
    }
    if (!isset($observationchecklist->allowstudentsubmit)) {
        $observationchecklist->allowstudentsubmit = 1;
    }
    if (!isset($observationchecklist->enableprinting)) {
        $observationchecklist->enableprinting = 1;
    }

    $observationchecklist->id = $DB->insert_record('observationchecklist', $observationchecklist);

    // Process intro files if we have the form
    if ($mform) {
        $cmid = $observationchecklist->coursemodule;
        $context = context_module::instance($cmid);
        $observationchecklist->intro = file_save_draft_area_files(
            $observationchecklist->intro,
            $context->id,
            'mod_observationchecklist',
            'intro',
            0,
            array('subdirs' => true),
            $observationchecklist->intro
        );
        $DB->update_record('observationchecklist', $observationchecklist);
    }

    return $observationchecklist->id;
}

/**
 * Updates an instance of the observationchecklist in the database
 *
 * @param stdClass $observationchecklist An object from the form
 * @param mod_observationchecklist_mod_form $mform The form
 * @return boolean Success/Fail
 */
function observationchecklist_update_instance(stdClass $observationchecklist, mod_observationchecklist_mod_form $mform = null) {
    global $DB;

    $observationchecklist->timemodified = time();
    $observationchecklist->id = $observationchecklist->instance;

    // Process intro files if we have the form
    if ($mform) {
        $cmid = $observationchecklist->coursemodule;
        $context = context_module::instance($cmid);
        $observationchecklist->intro = file_save_draft_area_files(
            $observationchecklist->intro,
            $context->id,
            'mod_observationchecklist',
            'intro',
            0,
            array('subdirs' => true),
            $observationchecklist->intro
        );
    }

    return $DB->update_record('observationchecklist', $observationchecklist);
}

/**
 * Removes an instance of the observationchecklist from the database
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function observationchecklist_delete_instance($id) {
    global $DB;

    if (!$observationchecklist = $DB->get_record('observationchecklist', array('id' => $id))) {
        return false;
    }

    // Delete related records
    $DB->delete_records('observationchecklist_items', array('checklistid' => $id));
    $DB->delete_records('observationchecklist_user_items', array('checklistid' => $id));
    
    // Delete the instance
    $DB->delete_records('observationchecklist', array('id' => $id));

    return true;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * @param stdClass $course The course record
 * @param stdClass $user The user record
 * @param stdClass $mod The course module info
 * @param stdClass $observationchecklist The observationchecklist instance
 * @return stdClass|null
 */
function observationchecklist_user_outline($course, $user, $mod, $observationchecklist) {
    global $DB;

    $result = new stdClass();
    
    // Get user's progress
    $progress = $DB->get_records('observationchecklist_user_items', array(
        'checklistid' => $observationchecklist->id,
        'userid' => $user->id
    ));

    if (!empty($progress)) {
        $result->info = count($progress) . ' items assessed';
        $lastassessment = $DB->get_field_sql('SELECT MAX(dateassessed) FROM {observationchecklist_user_items} 
                                            WHERE checklistid = ? AND userid = ?', 
                                            array($observationchecklist->id, $user->id));
        if ($lastassessment) {
            $result->time = $lastassessment;
        }
    }

    return $result;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course The current course record
 * @param stdClass $user The record of the user we are generating report for
 * @param stdClass $mod Course module info
 * @param stdClass $observationchecklist The module instance record
 * @return void
 */
function observationchecklist_user_complete($course, $user, $mod, $observationchecklist) {
    global $DB;

    $progress = $DB->get_records('observationchecklist_user_items', array(
        'checklistid' => $observationchecklist->id,
        'userid' => $user->id
    ));

    if (!empty($progress)) {
        echo get_string('totalitems', 'observationchecklist') . ': ' . count($progress) . '<br/>';
        
        $satisfactory = 0;
        $notsatisfactory = 0;
        foreach ($progress as $item) {
            if ($item->status == 'satisfactory') {
                $satisfactory++;
            } else if ($item->status == 'not_satisfactory') {
                $notsatisfactory++;
            }
        }
        
        echo get_string('satisfactory', 'observationchecklist') . ': ' . $satisfactory . '<br/>';
        echo get_string('not_satisfactory', 'observationchecklist') . ': ' . $notsatisfactory . '<br/>';
    } else {
        echo get_string('nodata', 'observationchecklist');
    }
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in observationchecklist activities and print it out.
 *
 * @param stdClass $course The course record
 * @param bool $viewfullnames Should we display full names
 * @param int $timestart The start time to check for activity
 * @return boolean True if anything was printed, otherwise false
 */
function observationchecklist_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * @param array $activities sequentially indexed array of objects with added 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items to $activities and increases $index
 */
function observationchecklist_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
    // Implementation if needed
}

/**
 * Prints single activity item prepared by {@link observationchecklist_get_recent_mod_activity()}
 *
 * @param stdClass $activity activity record with added 'cmid' property
 * @param int $courseid the id of the course we produce the report for
 * @param bool $detail print detailed report
 * @param array $modnames as returned by {@link get_module_types_names()}
 * @param bool $viewfullnames display users' full names
 * @return void
 */
function observationchecklist_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
    // Implementation if needed
}

/**
 * Function to be run periodically according to the moodle cron
 *
 * @return boolean
 */
function observationchecklist_cron () {
    return true;
}

/**
 * Returns all other caps used in the module
 *
 * @example return array('moodle/site:accessallgroups');
 * @return array
 */
function observationchecklist_get_extra_capabilities() {
    return array();
}

// File API

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function observationchecklist_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for observationchecklist file areas
 *
 * @package mod_observationchecklist
 * @category files
 *
 * @param file_browser $browser
 * @param array $areas
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @param string $filearea
 * @param int $itemid
 * @param string $filepath
 * @param string $filename
 * @return file_info instance or null if not found
 */
function observationchecklist_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the observationchecklist file areas
 *
 * @package mod_observationchecklist
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the observationchecklist's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function observationchecklist_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}
