
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Restore activity task for mod_observationchecklist.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/observationchecklist/backup/moodle2/restore_observationchecklist_stepslib.php');

/**
 * Observation checklist restore task that provides all the settings and steps to perform one complete restore of the activity.
 */
class restore_observationchecklist_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have.
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have.
     */
    protected function define_my_steps() {
        // Observation checklist only has one structure step.
        $this->add_step(new restore_observationchecklist_activity_structure_step('observationchecklist_structure', 'observationchecklist.xml'));
    }

    /**
     * Define the contents in the activity that must be processed by the link decoder.
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('observationchecklist', array('intro'), 'observationchecklist');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging to the activity to be executed by the link decoder.
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('OBSERVATIONCHECKLISTVIEWBYID', '/mod/observationchecklist/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('OBSERVATIONCHECKLISTINDEX', '/mod/observationchecklist/index.php?id=$1', 'course');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied by the restore_logs_processor when restoring observation checklist logs.
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('observationchecklist', 'add', 'view.php?id={course_module}', '{observationchecklist}');
        $rules[] = new restore_log_rule('observationchecklist', 'update', 'view.php?id={course_module}', '{observationchecklist}');
        $rules[] = new restore_log_rule('observationchecklist', 'view', 'view.php?id={course_module}', '{observationchecklist}');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied by the restore_logs_processor when restoring course logs.
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('observationchecklist', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
