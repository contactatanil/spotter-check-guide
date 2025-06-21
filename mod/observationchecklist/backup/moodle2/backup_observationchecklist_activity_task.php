
<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/observationchecklist/backup/moodle2/backup_observationchecklist_stepslib.php');

/**
 * observationchecklist backup task that provides all the settings and steps to perform one
 * complete backup of the activity
 */
class backup_observationchecklist_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // observationchecklist only has one structure step
        $this->add_step(new backup_observationchecklist_activity_structure_step('observationchecklist_structure', 'observationchecklist.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     */
    static public function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of observationchecklists
        $search = "/(".$base."\/mod\/observationchecklist\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@OBSERVATIONCHECKLISTINDEX*$2@$', $content);

        // Link to observationchecklist view by moduleid
        $search = "/(".$base."\/mod\/observationchecklist\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@OBSERVATIONCHECKLISTVIEWBYID*$2@$', $content);

        return $content;
    }
}
