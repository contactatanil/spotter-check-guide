
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
use required_capability_exception;

/**
 * External API for deleting checklist items.
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_item extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'itemid' => new external_value(PARAM_INT, 'Item ID'),
        ]);
    }

    /**
     * Delete a checklist item
     * @param int $itemid
     * @return array
     */
    public static function execute($itemid) {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(), [
            'itemid' => $itemid,
        ]);

        // Get item and verify permissions.
        $item = $DB->get_record('observationchecklist_items', ['id' => $params['itemid']], '*', MUST_EXIST);
        $checklist = $DB->get_record('observationchecklist', ['id' => $item->checklistid], '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('observationchecklist', $checklist->id, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        // Check capabilities.
        require_capability('mod/observationchecklist:edit', $context);

        // Delete the item.
        require_once(__DIR__ . '/../../locallib.php');
        $success = observationchecklist_delete_item($params['itemid']);

        return [
            'success' => $success,
        ];
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
        ]);
    }
}
