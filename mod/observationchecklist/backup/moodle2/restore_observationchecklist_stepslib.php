
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Restore steps for mod_observationchecklist.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Structure step to restore one observation checklist activity.
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

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    protected function process_observationchecklist($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the observation checklist record.
        $newitemid = $DB->insert_record('observationchecklist', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    protected function process_observationchecklist_item($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->checklistid = $this->get_new_parentid('observationchecklist');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $data->timecreated = $this->apply_date_offset($data->timecreated);

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
    }

    protected function after_execute() {
        // Add observation checklist related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_observationchecklist', 'intro', null);
    }
}
