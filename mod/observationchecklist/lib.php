
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, false if not, null if doesn't know or string for the module purpose.
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
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

/**
 * Adds an instance of the observationchecklist to the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param stdClass $moduleinstance An object from the form.
 * @param mod_observationchecklist_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function observationchecklist_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();
    $moduleinstance->timemodified = time();

    $moduleinstance->id = $DB->insert_record('observationchecklist', $moduleinstance);

    observationchecklist_grade_item_update($moduleinstance);

    return $moduleinstance->id;
}

/**
 * Updates an instance of the observationchecklist in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param stdClass $moduleinstance An object from the form in mod_form.php.
 * @param mod_observationchecklist_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function observationchecklist_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    $result = $DB->update_record('observationchecklist', $moduleinstance);

    observationchecklist_grade_item_update($moduleinstance);

    return $result;
}

/**
 * Removes an instance of the observationchecklist from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function observationchecklist_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('observationchecklist', array('id' => $id));
    if (!$exists) {
        return false;
    }

    // Delete all related checklist items.
    $DB->delete_records('observationchecklist_items', array('checklistid' => $id));
    $DB->delete_records('observationchecklist_user_items', array('checklistid' => $id));

    // Delete the main record.
    $DB->delete_records('observationchecklist', array('id' => $id));

    observationchecklist_grade_item_delete($exists);

    return true;
}

/**
 * Is a given scale used by the instance of observationchecklist?
 *
 * This function returns if a scale is being used by one observationchecklist
 * if it has support for grading and scales.
 *
 * @param int $moduleinstanceid ID of an instance of this module.
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by the given observationchecklist instance.
 */
function observationchecklist_scale_used($moduleinstanceid, $scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('observationchecklist', array('id' => $moduleinstanceid, 'grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Checks if scale is being used by any instance of observationchecklist.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid ID of the scale.
 * @return bool True if the scale is used by any observationchecklist instance.
 */
function observationchecklist_scale_used_anywhere($scaleid) {
    global $DB;

    if ($scaleid && $DB->record_exists('observationchecklist', array('grade' => -$scaleid))) {
        return true;
    } else {
        return false;
    }
}

/**
 * Creates or updates grade item for the given observationchecklist instance.
 *
 * Needed by {@see grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param bool $reset Reset grades in the gradebook.
 * @return void.
 */
function observationchecklist_grade_item_update($moduleinstance, $reset = false) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    $item = array();
    $item['itemname'] = clean_param($moduleinstance->name, PARAM_NOTAGS);
    $item['gradetype'] = GRADE_TYPE_NONE;

    grade_update('mod/observationchecklist', $moduleinstance->course, 'mod', 'observationchecklist',
                 $moduleinstance->id, 0, null, $item);
}

/**
 * Delete grade item for given observationchecklist instance.
 *
 * @param stdClass $moduleinstance Instance object.
 * @return grade_item.
 */
function observationchecklist_grade_item_delete($moduleinstance) {
    global $CFG;
    require_once($CFG->libdir.'/gradelib.php');

    return grade_update('mod/observationchecklist', $moduleinstance->course, 'mod', 'observationchecklist',
                        $moduleinstance->id, 0, null, array('deleted' => 1));
}

/**
 * Update observationchecklist grades in the gradebook.
 *
 * Needed by {@see grade_update_mod_grades()}.
 *
 * @param stdClass $moduleinstance Instance object with extra cmidnumber and modname property.
 * @param int $userid Update grade of specific user only, 0 means all participants.
 */
function observationchecklist_update_grades($moduleinstance, $userid = 0) {
    global $CFG, $DB;
    require_once($CFG->libdir.'/gradelib.php');

    // Populate array of grade objects indexed by userid.
    $grades = array();
    grade_update('mod/observationchecklist', $moduleinstance->course, 'mod', 'observationchecklist', $moduleinstance->id, 0, $grades);
}

/**
 * Returns the lists of all browsable file areas within the given module context.
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@see file_browser::get_file_info_context_module()}.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return string[]
 */
function observationchecklist_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * File browsing support for observationchecklist file areas.
 *
 * @package     mod_observationchecklist
 * @category    files
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
 * @return file_info Instance or null if not found.
 */
function observationchecklist_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the observationchecklist file areas.
 *
 * @package     mod_observationchecklist
 * @category    files
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param stdClass $context The observationchecklist's context.
 * @param string $filearea The name of the file area.
 * @param array $args Extra arguments (itemid, path).
 * @param bool $forcedownload Whether or not force download.
 * @param array $options Additional options affecting the file serving.
 */
function observationchecklist_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options = array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    send_file_not_found();
}

/**
 * Extends the global navigation tree by adding observationchecklist nodes if there is relevant content.
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $navref An object representing the navigation node.
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function observationchecklist_extend_navigation($navref, $course, $module, $cm) {
}

/**
 * Extends the settings navigation with the observationchecklist settings.
 *
 * This function is called when the context for the page is a observationchecklist module.
 * This is not called by AJAX so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav {@see settings_navigation}
 * @param navigation_node $observationchecklistnode {@see navigation_node}
 */
function observationchecklist_extend_settings_navigation($settingsnav, $observationchecklistnode = null) {
}
