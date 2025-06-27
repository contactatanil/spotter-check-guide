
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
 * External API for exporting reports
 */
class export_report extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'reporttype' => new external_value(PARAM_ALPHA, 'Report type'),
            'format' => new external_value(PARAM_ALPHA, 'Export format'),
            'userid' => new external_value(PARAM_INT, 'User ID filter', VALUE_OPTIONAL, 0),
            'groupid' => new external_value(PARAM_INT, 'Group ID filter', VALUE_OPTIONAL, 0)
        ]);
    }

    /**
     * Export report data
     * @param int $cmid Course module ID
     * @param string $reporttype Report type
     * @param string $format Export format
     * @param int $userid User ID filter
     * @param int $groupid Group ID filter
     * @return array
     */
    public static function execute($cmid, $reporttype, $format, $userid = 0, $groupid = 0) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'reporttype' => $reporttype,
            'format' => $format,
            'userid' => $userid,
            'groupid' => $groupid
        ]);

        // Get course module and context
        $cm = get_coursemodule_from_id('observationchecklist', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        
        // Check capabilities
        require_capability('mod/observationchecklist:export', $context);

        try {
            $reportmanager = new \mod_observationchecklist\report\report_manager();
            $exportmanager = new \mod_observationchecklist\report\export_manager();
            
            // Get report data
            switch ($params['reporttype']) {
                case 'grading':
                    $data = $reportmanager::get_grading_data($params['cmid'], $params['userid'], $params['groupid']);
                    break;
                case 'attempts':
                    $data = $reportmanager::get_attempts_data($params['cmid'], $params['userid']);
                    break;
                case 'statistics':
                    $data = $reportmanager::get_statistics_data($params['cmid']);
                    break;
                default:
                    $data = $reportmanager::get_overview_data($params['cmid']);
            }
            
            $filename = 'observationchecklist_' . $params['reporttype'] . '_' . date('Y-m-d');
            
            // Export based on format
            switch ($params['format']) {
                case 'csv':
                    $exportmanager::export_csv($cm, $params['reporttype'], $data, $filename);
                    break;
                case 'excel':
                    $exportmanager::export_excel($cm, $params['reporttype'], $data, $filename);
                    break;
                case 'pdf':
                    $exportmanager::export_pdf($cm, $params['reporttype'], $data, $filename);
                    break;
            }

            return [
                'success' => true,
                'message' => get_string('exportcomplete', 'observationchecklist')
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
