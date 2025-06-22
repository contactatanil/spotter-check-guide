
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
            'userid' => new external_value(PARAM_INT, 'User ID', VALUE_OPTIONAL, 0)
        ]);
    }

    /**
     * Get user progress for checklist items
     * @param int $cmid Course module ID
     * @param int $userid User ID (0 for current user)
     * @return array
     */
    public static function execute($cmid, $userid = 0) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'userid' => $userid
        ]);

        // Get course module and context
        $cm = get_coursemodule_from_id('observationchecklist', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        
        // Check capabilities
        require_capability('mod/observationchecklist:view', $context);

        // Use current user if no userid specified
        if (!$params['userid']) {
            $params['userid'] = $USER->id;
        }

        try {
            // Get all checklist items
            $items = $DB->get_records('observationchecklist_items', 
                ['checklistid' => $cm->instance], 'sortorder ASC');

            // Get user progress
            $progress = $DB->get_records('observationchecklist_user_items', [
                'checklistid' => $cm->instance,
                'userid' => $params['userid']
            ], '', 'itemid, status, assessornotes, dateassessed');

            $result = [];
            foreach ($items as $item) {
                $itemProgress = [
                    'itemid' => $item->id,
                    'itemtext' => $item->itemtext,
                    'status' => 'not_started',
                    'notes' => '',
                    'dateassessed' => 0
                ];

                if (isset($progress[$item->id])) {
                    $itemProgress['status'] = $progress[$item->id]->status;
                    $itemProgress['notes'] = $progress[$item->id]->assessornotes;
                    $itemProgress['dateassessed'] = $progress[$item->id]->dateassessed;
                }

                $result[] = $itemProgress;
            }

            return [
                'success' => true,
                'data' => $result
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ];
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'message' => new external_value(PARAM_TEXT, 'Response message', VALUE_OPTIONAL),
            'data' => new external_multiple_structure(
                new external_single_structure([
                    'itemid' => new external_value(PARAM_INT, 'Item ID'),
                    'itemtext' => new external_value(PARAM_TEXT, 'Item text'),
                    'status' => new external_value(PARAM_ALPHA, 'Assessment status'),
                    'notes' => new external_value(PARAM_TEXT, 'Assessment notes'),
                    'dateassessed' => new external_value(PARAM_INT, 'Date assessed')
                ])
            )
        ]);
    }
}
