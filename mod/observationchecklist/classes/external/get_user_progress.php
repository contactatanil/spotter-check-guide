
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
 * External API for getting user progress.
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_user_progress extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'userid' => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    /**
     * Get user progress for a checklist
     * @param int $cmid Course module ID
     * @param int $userid User ID
     * @return array
     */
    public static function execute($cmid, $userid) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'userid' => $userid,
        ]);

        // Get course module and context
        $cm = get_coursemodule_from_id('observationchecklist', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        
        // Check capabilities
        require_capability('mod/observationchecklist:view', $context);

        // Get user progress
        require_once(__DIR__ . '/../../locallib.php');
        $progress = observationchecklist_get_user_progress($cm->instance, $params['userid']);
        
        $result = [];
        foreach ($progress as $item) {
            $result[] = [
                'itemid' => $item->id,
                'itemtext' => $item->itemtext,
                'category' => $item->category,
                'status' => $item->status ?: 'not_started',
                'assessornotes' => $item->assessornotes ?: '',
                'dateassessed' => $item->dateassessed ?: 0,
                'assessorid' => $item->assessorid ?: 0,
            ];
        }

        return $result;
    }

    /**
     * Returns description of method result value
     * @return external_multiple_structure
     */
    public static function execute_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'itemid' => new external_value(PARAM_INT, 'Item ID'),
                'itemtext' => new external_value(PARAM_TEXT, 'Item text'),
                'category' => new external_value(PARAM_TEXT, 'Item category'),
                'status' => new external_value(PARAM_TEXT, 'Assessment status'),
                'assessornotes' => new external_value(PARAM_TEXT, 'Assessor notes'),
                'dateassessed' => new external_value(PARAM_INT, 'Date assessed'),
                'assessorid' => new external_value(PARAM_INT, 'Assessor ID'),
            ])
        );
    }
}
