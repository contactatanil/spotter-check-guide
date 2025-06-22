
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_module;

/**
 * External API for deleting checklist items
 */
class delete_item extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'itemid' => new external_value(PARAM_INT, 'Item ID to delete')
        ]);
    }

    /**
     * Delete a checklist item
     * @param int $cmid Course module ID
     * @param int $itemid Item ID
     * @return array
     */
    public static function execute($cmid, $itemid) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'itemid' => $itemid
        ]);

        // Get course module and context
        $cm = get_coursemodule_from_id('observationchecklist', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        
        // Check capabilities
        require_capability('mod/observationchecklist:edit', $context);

        try {
            // Get item to verify it belongs to this checklist
            $item = $DB->get_record('observationchecklist_items', [
                'id' => $params['itemid'],
                'checklistid' => $cm->instance
            ], '*', MUST_EXIST);

            // Delete related user progress
            $DB->delete_records('observationchecklist_user_items', ['itemid' => $params['itemid']]);
            
            // Delete the item
            $DB->delete_records('observationchecklist_items', ['id' => $params['itemid']]);

            return [
                'success' => true,
                'message' => get_string('itemdeleted', 'mod_observationchecklist')
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
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
            'message' => new external_value(PARAM_TEXT, 'Response message')
        ]);
    }
}
