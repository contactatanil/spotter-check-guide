
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
 * Saves a new instance of the observationchecklist into the database
 *
 * @param stdClass $observationchecklist Submitted data from the form
 * @param mod_observationchecklist_mod_form $mform The form instance
 * @return int The id of the newly inserted observationchecklist record
 */
function observationchecklist_add_instance(stdClass $observationchecklist, mod_observationchecklist_mod_form $mform = null) {
    global $DB;

    $observationchecklist->timecreated = time();
    $observationchecklist->timemodified = time();

    // Process the description editor content
    if (isset($observationchecklist->description_editor)) {
        $observationchecklist->descriptionformat = $observationchecklist->description_editor['format'];
        $observationchecklist->description = $observationchecklist->description_editor['text'];
        unset($observationchecklist->description_editor);
    }

    $observationchecklist->id = $DB->insert_record('observationchecklist', $observationchecklist);

    // Process files in description
    if (!empty($observationchecklist->description)) {
        $cmid = $observationchecklist->coursemodule;
        $context = context_module::instance($cmid);
        $observationchecklist->description = file_save_draft_area_files(
            $observationchecklist->description,
            $context->id,
            'mod_observationchecklist',
            'description',
            0,
            array('subdirs' => true),
            $observationchecklist->description
        );
        $DB->update_record('observationchecklist', $observationchecklist);
    }

    return $observationchecklist->id;
}

/**
 * Updates an instance of the observationchecklist in the database
 *
 * @param stdClass $observationchecklist An object from the form
 * @param mod_observationchecklist_mod_form $mform The form instance
 * @return boolean Success/Fail
 */
function observationchecklist_update_instance(stdClass $observationchecklist, mod_observationchecklist_mod_form $mform = null) {
    global $DB;

    $observationchecklist->timemodified = time();
    $observationchecklist->id = $observationchecklist->instance;

    // Process the description editor content
    if (isset($observationchecklist->description_editor)) {
        $observationchecklist->descriptionformat = $observationchecklist->description_editor['format'];
        $observationchecklist->description = $observationchecklist->description_editor['text'];
        unset($observationchecklist->description_editor);
    }

    // Process files in description
    if (!empty($observationchecklist->description)) {
        $cmid = $observationchecklist->coursemodule;
        $context = context_module::instance($cmid);
        $observationchecklist->description = file_save_draft_area_files(
            $observationchecklist->description,
            $context->id,
            'mod_observationchecklist',
            'description',
            0,
            array('subdirs' => true),
            $observationchecklist->description
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

    if (! $observationchecklist = $DB->get_record('observationchecklist', array('id' => $id))) {
        return false;
    }

    // Delete any dependent records
    $DB->delete_records('observationchecklist_items', array('checklistid' => $id));
    $DB->delete_records('observationchecklist_user_items', array('checklistid' => $id));

    // Delete the main record
    $DB->delete_records('observationchecklist', array('id' => $id));

    return true;
}

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return array of [(string)filearea] => (string)description
 */
function observationchecklist_get_file_areas($course, $cm, $context) {
    return array(
        'description' => get_string('description', 'mod_observationchecklist'),
    );
}

/**
 * Serves the files from the observationchecklist file areas
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the observationchecklist's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function observationchecklist_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options = array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    if ($filearea !== 'description') {
        send_file_not_found();
    }

    $relativepath = implode('/', $args);
    $fullpath = rtrim("/$context->id/mod_observationchecklist/$filearea/$relativepath", '/');

    $fs = get_file_storage();
    $file = $fs->get_file_by_hash(sha1($fullpath));

    if (!$file || $file->is_directory()) {
        send_file_not_found();
    }

    send_stored_file($file, 86400, 0, $forcedownload, $options);
}
