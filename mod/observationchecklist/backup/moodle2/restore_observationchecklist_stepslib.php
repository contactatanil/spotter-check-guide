
<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Define all the restore steps that will be used by the restore_observationchecklist_activity_task
 *
 * @package   mod_observationchecklist
 * @copyright 2024 Your Name
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure step to restore one observationchecklist activity
 */
class restore_observationchecklist_activity_structure_step extends restore_activity_structure_step {

    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $paths[] = new restore_path_element('observationchecklist', '/activity/observationchecklist');
        $paths[] = new restore_path_element('observationchecklist_item', '/activity/observationchecklist/items/item');
        if ($userinfo) {
            $paths[] = new restore_path_element('observationchecklist_useritem', '/activity/observationchecklist/useritems/useritem');
        }

        // Return the paths wrapped into standard activity structure
        return $this->prepare_activity_structure($paths);
    }

    protected function process_observationchecklist($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the observationchecklist record
        $newitemid = $DB->insert_record('observationchecklist', $data);
        // Immediately after inserting "activity" record, call this
        $this->apply_activity_instance($newitemid);
    }

    protected function process_observationchecklist_item($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->checklistid = $this->get_new_parentid('observationchecklist');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('observationchecklist_items', $data);
        $this->set_mapping('observationchecklist_item', $oldid, $newitemid);
    }

    protected function process_observationchecklist_useritem($data) {
        global $DB;

        $data = (object)$data;

        $data->checklistid = $this->get_new_parentid('observationchecklist');
        $data->itemid = $this->get_mappingid('observationchecklist_item', $data->itemid);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->assessorid = $this->get_mappingid('user', $data->assessorid);
        $data->dateassessed = $this->apply_date_offset($data->dateassessed);
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        $newitemid = $DB->insert_record('observationchecklist_user_items', $data);
        // No need to save this mapping as far as nothing depend on it
        // (child paths, file areas nor links decoder)
    }

    protected function after_execute() {
        // Add observationchecklist related files, no need to match by itemname (just internally handled context)
        $this->add_related_files('mod_observationchecklist', 'intro', null);
        $this->add_related_files('mod_observationchecklist', 'description', null);
    }
}
