
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * mod_observationchecklist data generator.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Observation checklist module data generator class.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_observationchecklist_generator extends testing_module_generator {

    /**
     * Create new observationchecklist module instance.
     *
     * @param array|stdClass $record
     * @param array $options
     * @return stdClass activity record with extra cmid field
     */
    public function create_instance($record = null, array $options = null) {
        $record = (object)(array)$record;

        $defaultsettings = [
            'name' => 'Test Observation Checklist',
            'intro' => 'This is a test observation checklist',
            'introformat' => FORMAT_HTML,
            'allowstudentadd' => 1,
            'allowstudentsubmit' => 1,
            'enableprinting' => 1,
        ];

        foreach ($defaultsettings as $name => $value) {
            if (!isset($record->{$name})) {
                $record->{$name} = $value;
            }
        }

        return parent::create_instance($record, (array)$options);
    }

    /**
     * Create a checklist item.
     *
     * @param array $record
     * @return stdClass
     */
    public function create_item(array $record) {
        global $DB, $USER;

        $record = (object)$record;
        
        if (!isset($record->checklistid)) {
            throw new coding_exception('checklistid is required');
        }
        
        if (!isset($record->itemtext)) {
            $record->itemtext = 'Test checklist item';
        }
        
        if (!isset($record->category)) {
            $record->category = 'General';
        }
        
        if (!isset($record->userid)) {
            $record->userid = $USER->id;
        }
        
        if (!isset($record->position)) {
            $record->position = $DB->count_records('observationchecklist_items', 
                ['checklistid' => $record->checklistid]) + 1;
        }
        
        if (!isset($record->sortorder)) {
            $record->sortorder = $record->position;
        }
        
        $record->timecreated = time();
        $record->timemodified = time();

        $record->id = $DB->insert_record('observationchecklist_items', $record);
        return $record;
    }

    /**
     * Create a user assessment.
     *
     * @param array $record
     * @return stdClass
     */
    public function create_assessment(array $record) {
        global $DB, $USER;

        $record = (object)$record;
        
        if (!isset($record->checklistid) || !isset($record->itemid) || !isset($record->userid)) {
            throw new coding_exception('checklistid, itemid and userid are required');
        }
        
        if (!isset($record->status)) {
            $record->status = 'satisfactory';
        }
        
        if (!isset($record->assessorid)) {
            $record->assessorid = $USER->id;
        }
        
        if (!isset($record->dateassessed)) {
            $record->dateassessed = time();
        }
        
        $record->timecreated = time();
        $record->timemodified = time();

        $record->id = $DB->insert_record('observationchecklist_user_items', $record);
        return $record;
    }
}
