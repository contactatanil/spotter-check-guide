
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/mod/observationchecklist/locallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_module;

/**
 * External API for saving multiple observations
 */
class save_multi_observations extends external_api {

    /**
     * Returns description of method parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'observations' => new external_multiple_structure(
                new external_single_structure([
                    'studentId' => new external_value(PARAM_INT, 'Student ID'),
                    'itemId' => new external_value(PARAM_INT, 'Item ID'),
                    'status' => new external_value(PARAM_ALPHA, 'Assessment status'),
                    'notes' => new external_value(PARAM_TEXT, 'Assessment notes', VALUE_OPTIONAL, ''),
                ])
            )
        ]);
    }

    /**
     * Save multiple observations
     */
    public static function execute($cmid, $observations) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'observations' => $observations
        ]);

        $cm = get_coursemodule_from_id('observationchecklist', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        
        self::validate_context($context);
        require_capability('mod/observationchecklist:assess', $context);

        $success_count = 0;
        $errors = [];

        foreach ($params['observations'] as $observation) {
            try {
                $result = observationchecklist_assess_item(
                    $observation['itemId'],
                    $observation['studentId'],
                    $observation['status'],
                    $observation['notes'],
                    $USER->id
                );
                
                if ($result) {
                    $success_count++;
                    
                    // Trigger assessment event
                    $event = \mod_observationchecklist\event\assessment_made::create([
                        'objectid' => $observation['itemId'],
                        'context' => $context,
                        'relateduserid' => $observation['studentId'],
                        'other' => [
                            'status' => $observation['status'],
                            'checklistid' => $cm->instance,
                            'multi_student' => true
                        ]
                    ]);
                    $event->trigger();
                }
            } catch (Exception $e) {
                $errors[] = $e->getMessage();
            }
        }

        return [
            'success' => $success_count > 0,
            'saved_count' => $success_count,
            'total_count' => count($params['observations']),
            'errors' => $errors,
            'message' => get_string('multiobservationssaved', 'mod_observationchecklist', $success_count)
        ];
    }

    /**
     * Returns description of method result value
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the operation was successful'),
            'saved_count' => new external_value(PARAM_INT, 'Number of observations saved'),
            'total_count' => new external_value(PARAM_INT, 'Total number of observations attempted'),
            'errors' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Error message'), 'List of errors', VALUE_OPTIONAL
            ),
            'message' => new external_value(PARAM_TEXT, 'Success message')
        ]);
    }
}
