
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
            // Get enrolled students
            $students = get_enrolled_users($context, 'mod/observationchecklist:submit');
            
            // Get total items count
            $totalItems = $DB->count_records('observationchecklist_items', ['checklistid' => $cm->instance]);

            $result = [];
            foreach ($students as $student) {
                // Get student progress
                $progress = $DB->get_records('observationchecklist_user_items', [
                    'checklistid' => $cm->instance,
                    'userid' => $student->id
                ]);

                $satisfactory = 0;
                $notSatisfactory = 0;
                foreach ($progress as $item) {
                    if ($item->status === 'satisfactory') {
                        $satisfactory++;
                    } else if ($item->status === 'not_satisfactory') {
                        $notSatisfactory++;
                    }
                }

                $result[] = [
                    'userid' => $student->id,
                    'fullname' => fullname($student),
                    'total' => $totalItems,
                    'completed' => count($progress),
                    'satisfactory' => $satisfactory,
                    'not_satisfactory' => $notSatisfactory
                ];
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
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'Full name'),
                    'total' => new external_value(PARAM_INT, 'Total items'),
                    'completed' => new external_value(PARAM_INT, 'Completed items'),
                    'satisfactory' => new external_value(PARAM_INT, 'Satisfactory items'),
                    'not_satisfactory' => new external_value(PARAM_INT, 'Not satisfactory items')
                ])
            )
        ]);
    }
}
