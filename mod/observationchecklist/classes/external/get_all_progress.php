
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_module;

/**
 * External API for getting all students progress
 */
class get_all_progress extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID')
        ]);
    }

    /**
     * Get all students progress for a checklist
     * @param int $cmid Course module ID
     * @return array
     */
    public static function execute($cmid) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid
        ]);

        // Get course module and context
        $cm = get_coursemodule_from_id('observationchecklist', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        
        // Check capabilities
        require_capability('mod/observationchecklist:view', $context);

        try {
            // Get all progress for this checklist
            $sql = "SELECT ui.*, u.firstname, u.lastname, i.itemtext
                    FROM {observationchecklist_user_items} ui
                    JOIN {user} u ON u.id = ui.userid
                    JOIN {observationchecklist_items} i ON i.id = ui.itemid
                    WHERE ui.checklistid = ?
                    ORDER BY u.lastname, u.firstname, i.sortorder";
            
            $progress = $DB->get_records_sql($sql, [$cm->instance]);

            $result = [];
            foreach ($progress as $item) {
                $result[] = [
                    'userid' => $item->userid,
                    'userfullname' => $item->firstname . ' ' . $item->lastname,
                    'itemid' => $item->itemid,
                    'itemtext' => $item->itemtext,
                    'status' => $item->status,
                    'notes' => $item->assessornotes,
                    'dateassessed' => $item->dateassessed
                ];
            }

            return $result;

        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Returns description of method result value
     * @return external_multiple_structure
     */
    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'userid' => new external_value(PARAM_INT, 'User ID'),
                'userfullname' => new external_value(PARAM_TEXT, 'User full name'),
                'itemid' => new external_value(PARAM_INT, 'Item ID'),
                'itemtext' => new external_value(PARAM_TEXT, 'Item text'),
                'status' => new external_value(PARAM_TEXT, 'Assessment status'),
                'notes' => new external_value(PARAM_TEXT, 'Assessment notes'),
                'dateassessed' => new external_value(PARAM_INT, 'Date assessed')
            ])
        );
    }
}
