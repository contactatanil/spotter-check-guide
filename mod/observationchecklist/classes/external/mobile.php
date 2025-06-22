
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/observationchecklist/locallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use context_module;

/**
 * Mobile app external functions.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile extends external_api {

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function mobile_course_view_parameters() {
        return new external_function_parameters(
            array(
                'courseid' => new external_value(PARAM_INT, 'Course id'),
                'cmid' => new external_value(PARAM_INT, 'Course module id')
            )
        );
    }

    /**
     * Get course view data for mobile app.
     *
     * @param int $courseid Course id
     * @param int $cmid Course module id
     * @return array
     */
    public static function mobile_course_view($courseid, $cmid) {
        global $DB, $USER;

        $params = self::validate_parameters(self::mobile_course_view_parameters(), array(
            'courseid' => $courseid,
            'cmid' => $cmid
        ));

        $cm = get_coursemodule_from_id('observationchecklist', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        $checklist = $DB->get_record('observationchecklist', array('id' => $cm->instance), '*', MUST_EXIST);
        $items = observationchecklist_get_items($checklist->id);
        $progress = observationchecklist_get_user_progress($checklist->id, $USER->id);

        return array(
            'checklist' => array(
                'id' => $checklist->id,
                'name' => $checklist->name,
                'description' => $checklist->description
            ),
            'items' => array_values($items),
            'progress' => array_values($progress),
            'canassess' => has_capability('mod/observationchecklist:assess', $context),
            'canedit' => has_capability('mod/observationchecklist:edit', $context),
        );
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function mobile_course_view_returns() {
        return new external_single_structure(
            array(
                'checklist' => new external_single_structure(
                    array(
                        'id' => new external_value(PARAM_INT, 'Checklist id'),
                        'name' => new external_value(PARAM_TEXT, 'Checklist name'),
                        'description' => new external_value(PARAM_RAW, 'Checklist description')
                    )
                ),
                'items' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'id' => new external_value(PARAM_INT, 'Item id'),
                            'itemtext' => new external_value(PARAM_RAW, 'Item text'),
                            'category' => new external_value(PARAM_TEXT, 'Item category'),
                        )
                    )
                ),
                'progress' => new external_multiple_structure(
                    new external_single_structure(
                        array(
                            'itemid' => new external_value(PARAM_INT, 'Item id'),
                            'status' => new external_value(PARAM_TEXT, 'Status'),
                            'assessornotes' => new external_value(PARAM_RAW, 'Assessor notes', VALUE_OPTIONAL),
                        )
                    )
                ),
                'canassess' => new external_value(PARAM_BOOL, 'Can assess'),
                'canedit' => new external_value(PARAM_BOOL, 'Can edit'),
            )
        );
    }
}

