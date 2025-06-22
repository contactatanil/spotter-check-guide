
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
 * External API for getting user progress
 */
class get_user_progress extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'userid' => new external_value(PARAM_INT, 'User ID')
        ]);
    }

    /**
     * Get user progress for checklist items
     * @param int $cmid Course module ID
     * @param int $userid User ID
     * @return array
     */
    public static function execute($cmid, $userid) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'userid' => $userid
        ]);

        // Get course module and context
        $cm = get_coursemodule_from_id('observationchecklist', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        
        // Check capabilities
        require_capability('mod/observationchecklist:view', $context);

        try {
            // Get user progress
            $progress = $DB->get_records('observationchecklist_user_items', [
                'checklistid' => $cm->instance,
                'userid' => $params['userid']
            ]);

            $result = [];
            foreach ($progress as $item) {
                $result[] = [
                    'itemid' => $item->itemid,
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
                'itemid' => new external_value(PARAM_INT, 'Item ID'),
                'status' => new external_value(PARAM_TEXT, 'Assessment status'),
                'notes' => new external_value(PARAM_TEXT, 'Assessment notes'),
                'dateassessed' => new external_value(PARAM_INT, 'Date assessed')
            ])
        );
    }
}
