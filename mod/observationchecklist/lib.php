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

// Log lib.php loading
$logdir = $CFG->dataroot . '/temp';
if (!is_dir($logdir)) {
    mkdir($logdir, 0777, true);
}
error_log("Loading observationchecklist lib.php");

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
        case FEATURE_COMPLETION_HAS_RULES:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return true;
        case FEATURE_GRADE_OUTCOMES:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        case FEATURE_PLAGIARISM:
            return false;
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
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

    try {
        $observationchecklist->timecreated = time();
        $observationchecklist->timemodified = time();

        // Set default values for checkbox fields
        $observationchecklist->allowstudentadd = isset($observationchecklist->allowstudentadd) ? 1 : 0;
        $observationchecklist->allowstudentsubmit = isset($observationchecklist->allowstudentsubmit) ? 1 : 0;
        $observationchecklist->enableprinting = isset($observationchecklist->enableprinting) ? 1 : 0;
        $observationchecklist->completionpass = isset($observationchecklist->completionpass) ? 1 : 0;

        // Grading settings
        if (!isset($observationchecklist->grade)) {
            $observationchecklist->grade = 100;
        }
        if (!isset($observationchecklist->grademethod)) {
            $observationchecklist->grademethod = 0;
        }

        $observationchecklist->id = $DB->insert_record('observationchecklist', $observationchecklist);

        if (!$observationchecklist->id) {
            throw new moodle_exception('cannotinsertrecord', 'error');
        }

        observationchecklist_grade_item_update($observationchecklist);

        return $observationchecklist->id;
    } catch (Exception $e) {
        debugging('Error adding observationchecklist instance: ' . $e->getMessage(), DEBUG_DEVELOPER);
        throw $e;
    }
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

    try {
        $observationchecklist->timemodified = time();
        $observationchecklist->id = $observationchecklist->instance;

        // Set default values for checkbox fields
        $observationchecklist->allowstudentadd = isset($observationchecklist->allowstudentadd) ? 1 : 0;
        $observationchecklist->allowstudentsubmit = isset($observationchecklist->allowstudentsubmit) ? 1 : 0;
        $observationchecklist->enableprinting = isset($observationchecklist->enableprinting) ? 1 : 0;
        $observationchecklist->completionpass = isset($observationchecklist->completionpass) ? 1 : 0;

        $result = $DB->update_record('observationchecklist', $observationchecklist);

        if (!$result) {
            throw new moodle_exception('cannotupdaterecord', 'error');
        }

        observationchecklist_grade_item_update($observationchecklist);

        return $result;
    } catch (Exception $e) {
        debugging('Error updating observationchecklist instance: ' . $e->getMessage(), DEBUG_DEVELOPER);
        throw $e;
    }
}

/**
 * Removes an instance of the observationchecklist from the database
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function observationchecklist_delete_instance($id) {
    global $DB;

    try {
        if (!$observationchecklist = $DB->get_record('observationchecklist', array('id' => $id))) {
            return false;
        }

        // Use transactions for data integrity
        $transaction = $DB->start_delegated_transaction();

        // Delete related records in proper order
        $DB->delete_records('observationchecklist_user_items', array('checklistid' => $id));
        $DB->delete_records('observationchecklist_items', array('checklistid' => $id));
        $DB->delete_records('observationchecklist_grades', array('checklistid' => $id));
        
        // Delete grade item
        observationchecklist_grade_item_delete($observationchecklist);
        
        // Delete the instance
        $result = $DB->delete_records('observationchecklist', array('id' => $id));

        if (!$result) {
            throw new moodle_exception('cannotdeleterecord', 'error');
        }

        $transaction->allow_commit();
        return true;
    } catch (Exception $e) {
        if (isset($transaction)) {
            $transaction->rollback($e);
        }
        debugging('Error deleting observationchecklist instance: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}

/**
 * Create/update grade item for given observationchecklist
 *
 * @param stdClass $observationchecklist object with extra cmidnumber
 * @param mixed $grades optional array/object of grade(s); 'reset' means reset grades in gradebook
 * @return int 0 if ok, error code otherwise
 */
function observationchecklist_grade_item_update($observationchecklist, $grades = null) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $params = array('itemname' => $observationchecklist->name, 'idnumber' => $observationchecklist->cmidnumber ?? null);

    if ($observationchecklist->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax']  = $observationchecklist->grade;
        $params['grademin']  = 0;
    } else if ($observationchecklist->grade < 0) {
        $params['gradetype'] = GRADE_TYPE_SCALE;
        $params['scaleid']   = -$observationchecklist->grade;
    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }

    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }

    return grade_update('mod/observationchecklist', $observationchecklist->course, 'mod', 'observationchecklist', $observationchecklist->id, 0, $grades, $params);
}

/**
 * Delete grade item for given observationchecklist
 *
 * @param stdClass $observationchecklist object
 * @return int
 */
function observationchecklist_grade_item_delete($observationchecklist) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/observationchecklist', $observationchecklist->course, 'mod', 'observationchecklist', $observationchecklist->id, 0, null, array('deleted' => 1));
}

/**
 * Update grades in the gradebook
 *
 * @param stdClass $observationchecklist
 * @param int $userid specific user only, 0 means all
 * @param bool $nullifnone
 */
function observationchecklist_update_grades($observationchecklist, $userid = 0, $nullifnone = true) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    if ($grades = observationchecklist_get_user_grades($observationchecklist, $userid)) {
        observationchecklist_grade_item_update($observationchecklist, $grades);
    } else if ($userid && $nullifnone) {
        $grade = new stdClass();
        $grade->userid = $userid;
        $grade->rawgrade = null;
        observationchecklist_grade_item_update($observationchecklist, $grade);
    } else {
        observationchecklist_grade_item_update($observationchecklist);
    }
}

/**
 * Get user grades for observationchecklist
 *
 * @param stdClass $observationchecklist
 * @param int $userid
 * @return array
 */
function observationchecklist_get_user_grades($observationchecklist, $userid = 0) {
    global $DB;

    $where = "checklistid = ?";
    $params = array($observationchecklist->id);

    if ($userid) {
        $where .= " AND userid = ?";
        $params[] = $userid;
    }

    try {
        $grades = $DB->get_records_select('observationchecklist_grades', $where, $params);
        
        $return = array();
        foreach ($grades as $grade) {
            $return[$grade->userid] = $grade;
        }

        return $return;
    } catch (Exception $e) {
        debugging('Error getting user grades: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return array();
    }
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
 * Implementation of the function for printing the form elements that control
 * whether the course reset functionality affects the observationchecklist.
 *
 * @param $mform form passed by reference
 */
function observationchecklist_reset_course_form_definition(&$mform) {
    $mform->addElement('header', 'observationchecklistheader', get_string('modulenameplural', 'observationchecklist'));
    $mform->addElement('advcheckbox', 'reset_observationchecklist_all', get_string('deleteallsubmissions', 'observationchecklist'));
}

/**
 * Course reset form defaults.
 * @param object $course
 * @return array
 */
function observationchecklist_reset_course_form_defaults($course) {
    return array('reset_observationchecklist_all' => 1);
}

/**
 * Removes all grades from gradebook
 *
 * @param int $courseid
 * @param string optional type
 */
function observationchecklist_reset_gradebook($courseid, $type='') {
    global $DB;

    $sql = "SELECT o.*, cm.idnumber as cmidnumber, o.course as courseid
            FROM {observationchecklist} o, {course_modules} cm, {modules} m
            WHERE m.name='observationchecklist' AND m.id=cm.module AND cm.instance=o.id AND o.course=?";

    if ($observationchecklists = $DB->get_records_sql($sql, array($courseid))) {
        foreach ($observationchecklists as $observationchecklist) {
            observationchecklist_grade_item_update($observationchecklist, 'reset');
        }
    }
}

/**
 * Actual implementation of the reset course functionality, delete all the
 * observationchecklist responses for course $data->courseid.
 *
 * @param stdClass $data the data submitted from the reset course.
 * @return array status array
 */
function observationchecklist_reset_userdata($data) {
    global $DB;

    $componentstr = get_string('modulenameplural', 'observationchecklist');
    $status = array();

    if (!empty($data->reset_observationchecklist_all)) {
        $observationchecklistssql = "SELECT o.id FROM {observationchecklist} o WHERE o.course=?";
        $params = array($data->courseid);

        $DB->delete_records_select('observationchecklist_user_items', "checklistid IN ($observationchecklistssql)", $params);
        $DB->delete_records_select('observationchecklist_grades', "checklistid IN ($observationchecklistssql)", $params);

        $status[] = array('component' => $componentstr, 'item' => get_string('deleteallsubmissions', 'observationchecklist'), 'error' => false);

        // Remove all grades from gradebook
        if (empty($data->reset_gradebook_grades)) {
            observationchecklist_reset_gradebook($data->courseid);
        }
    }

    return $status;
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
    send_file_not_found();
}
