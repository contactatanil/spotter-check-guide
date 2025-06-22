
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
 * External API for adding checklist items
 */
class add_item extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'itemtext' => new external_value(PARAM_TEXT, 'Item text')
        ]);
    }

    /**
     * Add a new checklist item
     * @param int $cmid Course module ID
     * @param string $itemtext Item text
     * @return array
     */
    public static function execute($cmid, $itemtext) {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'itemtext' => $itemtext
        ]);

        // Get course module and context
        $cm = get_coursemodule_from_id('observationchecklist', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        
        // Check capabilities
        require_capability('mod/observationchecklist:edit', $context);

        try {
            // Insert new item
            $item = new \stdClass();
            $item->checklistid = $cm->instance;
            $item->itemtext = clean_param($params['itemtext'], PARAM_TEXT);
            $item->userid = $USER->id;
            $item->sortorder = $DB->count_records('observationchecklist_items', ['checklistid' => $cm->instance]) + 1;
            $item->position = $item->sortorder;
            $item->timecreated = time();
            $item->timemodified = time();

            $itemid = $DB->insert_record('observationchecklist_items', $item);

            return [
                'success' => true,
                'itemid' => $itemid,
                'message' => get_string('itemadded', 'mod_observationchecklist')
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
            'itemid' => new external_value(PARAM_INT, 'New item ID', VALUE_OPTIONAL),
            'message' => new external_value(PARAM_TEXT, 'Response message')
        ]);
    }
}
