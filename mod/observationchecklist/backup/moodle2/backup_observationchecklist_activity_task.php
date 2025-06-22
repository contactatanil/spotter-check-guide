
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Backup activity task for mod_observationchecklist.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->dirroot . '/mod/observationchecklist/backup/moodle2/backup_observationchecklist_stepslib.php');

/**
 * Observation checklist backup task that provides all the settings and steps to perform one complete backup of the activity.
 */
class backup_observationchecklist_activity_task extends backup_activity_task {

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
        // The observation checklist only has one structure step.
        $this->add_step(new backup_observationchecklist_activity_structure_step('observationchecklist_structure', 'observationchecklist.xml'));
    }

    /**
     * Code the transformations to perform in the activity in order to get transportable (encoded) links.
     *
     * @param string $content
     * @return string
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of observation checklists.
        $search = "/(".$base."\/mod\/observationchecklist\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@OBSERVATIONCHECKLISTINDEX*$2@$', $content);

        // Link to observation checklist view by moduleid.
        $search = "/(".$base."\/mod\/observationchecklist\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@OBSERVATIONCHECKLISTVIEWBYID*$2@$', $content);

        return $content;
    }
}
