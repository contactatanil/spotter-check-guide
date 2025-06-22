
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
 * External API for saving multiple student observations
 */
class save_multi_observations extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'observations' => new external_multiple_structure(
                new external_single_structure([
                    'itemid' => new external_value(PARAM_INT, 'Item ID'),
                    'userid' => new external_value(PARAM_INT, 'User ID'),
                    'status' => new external_value(PARAM_ALPHA, 'Assessment status'),
                    'notes' => new external_value(PARAM_TEXT, 'Assessment notes', VALUE_OPTIONAL, '')
                ])
            )
        ]);
    }

    /**
     * Save multiple student observations
     * @param int $cmid Course module ID
     * @param array $observations Array of observations
     * @return array
     */
    public static function execute($cmid, $observations) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'observations' => $observations
        ]);

        // Get course module and context
        $cm = get_coursemodule_from_id('observationchecklist', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        
        // Check capabilities
        require_capability('mod/observationchecklist:assess', $context);

        $transaction = $DB->start_delegated_transaction();
        
        try {
            $saved = 0;
            foreach ($params['observations'] as $obs) {
                // Validate status
                $valid_statuses = ['satisfactory', 'not_satisfactory', 'in_progress', 'not_started'];
                if (!in_array($obs['status'], $valid_statuses)) {
                    continue;
                }

                // Check if assessment already exists
                $existing = $DB->get_record('observationchecklist_user_items', [
                    'checklistid' => $cm->instance,
                    'itemid' => $obs['itemid'],
                    'userid' => $obs['userid']
                ]);

                $assessment = new \stdClass();
                $assessment->checklistid = $cm->instance;
                $assessment->itemid = $obs['itemid'];
                $assessment->userid = $obs['userid'];
                $assessment->status = $obs['status'];
                $assessment->assessornotes = clean_param($obs['notes'], PARAM_TEXT);
                $assessment->assessorid = $USER->id;
                $assessment->dateassessed = time();
                $assessment->timemodified = time();

                if ($existing) {
                    $assessment->id = $existing->id;
                    $assessment->timecreated = $existing->timecreated;
                    $DB->update_record('observationchecklist_user_items', $assessment);
                } else {
                    $assessment->timecreated = time();
                    $DB->insert_record('observationchecklist_user_items', $assessment);
                }
                $saved++;
            }

            $transaction->allow_commit();

            return [
                'success' => true,
                'saved' => $saved,
                'message' => get_string('observationsaved', 'mod_observationchecklist')
            ];

        } catch (\Exception $e) {
            $transaction->rollback($e);
            return [
                'success' => false,
                'saved' => 0,
                'message' => 'Error saving observations: ' . $e->getMessage()
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
            'saved' => new external_value(PARAM_INT, 'Number of observations saved'),
            'message' => new external_value(PARAM_TEXT, 'Response message')
        ]);
    }
}
