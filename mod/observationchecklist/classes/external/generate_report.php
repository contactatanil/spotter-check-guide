
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
 * External API for generating printable reports
 */
class generate_report extends external_api {

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
     * Generate a printable report for a student
     * @param int $cmid Course module ID
     * @param int $userid User ID
     * @return array
     */
    public static function execute($cmid, $userid) {
        global $DB, $OUTPUT;

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
            $observationchecklist = $DB->get_record('observationchecklist', ['id' => $cm->instance], '*', MUST_EXIST);
            $user = $DB->get_record('user', ['id' => $params['userid']], '*', MUST_EXIST);
            $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);

            // Get checklist items and progress
            $items = $DB->get_records('observationchecklist_items', 
                ['checklistid' => $cm->instance], 'position ASC');

            $progress = $DB->get_records('observationchecklist_user_items', [
                'checklistid' => $cm->instance,
                'userid' => $params['userid']
            ], '', 'itemid, status, assessornotes, dateassessed, assessorid');

            // Get assessor information
            $assessors = [];
            foreach ($progress as $item) {
                if ($item->assessorid && !isset($assessors[$item->assessorid])) {
                    $assessor = $DB->get_record('user', ['id' => $item->assessorid], 'id, firstname, lastname');
                    if ($assessor) {
                        $assessors[$item->assessorid] = fullname($assessor);
                    }
                }
            }

            // Prepare template data
            $templatedata = [
                'checklist' => $observationchecklist,
                'student' => $user,
                'course' => $course,
                'items' => [],
                'generatedon' => userdate(time()),
                'completedcount' => 0,
                'satisfactorycount' => 0,
                'notsatisfactorycount' => 0
            ];

            foreach ($items as $item) {
                $itemdata = [
                    'itemtext' => $item->itemtext,
                    'status' => 'not_started',
                    'statusclass' => 'not-started',
                    'notes' => '',
                    'dateassessed' => '',
                    'assessor' => ''
                ];

                if (isset($progress[$item->id])) {
                    $p = $progress[$item->id];
                    $itemdata['status'] = ucfirst(str_replace('_', ' ', $p->status));
                    $itemdata['statusclass'] = str_replace('_', '-', $p->status);
                    $itemdata['notes'] = $p->assessornotes;
                    $itemdata['dateassessed'] = $p->dateassessed ? userdate($p->dateassessed) : '';
                    $itemdata['assessor'] = isset($assessors[$p->assessorid]) ? $assessors[$p->assessorid] : '';
                    
                    $templatedata['completedcount']++;
                    if ($p->status === 'satisfactory') {
                        $templatedata['satisfactorycount']++;
                    } else if ($p->status === 'not_satisfactory') {
                        $templatedata['notsatisfactorycount']++;
                    }
                }

                $templatedata['items'][] = $itemdata;
            }

            $templatedata['totalitems'] = count($items);

            // Render the report template
            $html = $OUTPUT->render_from_template('mod_observationchecklist/print_report', $templatedata);

            return [
                'success' => true,
                'html' => $html
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'html' => ''
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
            'html' => new external_value(PARAM_RAW, 'Generated HTML report')
        ]);
    }
}
