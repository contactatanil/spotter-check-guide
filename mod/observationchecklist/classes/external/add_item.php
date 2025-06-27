
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
use invalid_parameter_exception;

/**
 * External API for adding checklist items.
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_item extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'itemtext' => new external_value(PARAM_TEXT, 'Item text'),
            'category' => new external_value(PARAM_TEXT, 'Item category', VALUE_DEFAULT, 'General'),
        ]);
    }

    /**
     * Add a new checklist item
     * @param int $cmid
     * @param string $itemtext
     * @param string $category
     * @return array
     */
    public static function execute($cmid, $itemtext, $category = 'General') {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'itemtext' => $itemtext,
            'category' => $category,
        ]);

        // Get course module and context.
        $cm = get_coursemodule_from_id('observationchecklist', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        // Check capabilities.
        require_capability('mod/observationchecklist:edit', $context);

        $checklist = $DB->get_record('observationchecklist', ['id' => $cm->instance], '*', MUST_EXIST);

        // Add the item.
        require_once(__DIR__ . '/../../locallib.php');
        $itemid = observationchecklist_add_item($checklist->id, $params['itemtext'], $params['category'], $USER->id);

        return [
            'success' => true,
            'itemid' => $itemid,
        ];
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Success status'),
            'itemid' => new external_value(PARAM_INT, 'New item ID'),
        ]);
    }
}
