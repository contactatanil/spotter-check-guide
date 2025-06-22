
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

namespace mod_observationchecklist\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use context_module;
use invalid_parameter_exception;

/**
 * External API for assessing checklist items
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assess_item extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'cmid' => new external_value(PARAM_INT, 'Course module ID'),
            'itemid' => new external_value(PARAM_INT, 'Item ID'),
            'userid' => new external_value(PARAM_INT, 'User ID being assessed'),
            'status' => new external_value(PARAM_ALPHA, 'Assessment status'),
            'notes' => new external_value(PARAM_TEXT, 'Assessment notes', VALUE_OPTIONAL, '')
        ]);
    }

    /**
     * Assess a checklist item for a user
     * @param int $cmid Course module ID
     * @param int $itemid Item ID
     * @param int $userid User ID
     * @param string $status Assessment status
     * @param string $notes Assessment notes
     * @return array
     */
    public static function execute($cmid, $itemid, $userid, $status, $notes = '') {
        global $DB, $USER;

        $params = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'itemid' => $itemid,
            'userid' => $userid,
            'status' => $status,
            'notes' => $notes
        ]);

        // Get course module and context.
        $cm = get_coursemodule_from_id('observationchecklist', $params['cmid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        
        // Validate context and check capabilities.
        self::validate_context($context);
        require_capability('mod/observ ationchecklist:assess', $context);

        // Validate that the item belongs to this checklist.
        $item = $DB->get_record('observationchecklist_items', [
            'id' => $params['itemid'],
            'checklistid' => $cm->instance
        ]);
        
        if (!$item) {
            throw new invalid_parameter_exception('Invalid item ID for this checklist');
        }

        // Validate that the user is enrolled in the course.
        $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
        $coursecontext = \context_course::instance($course->id);
        if (!is_enrolled($coursecontext, $params['userid'])) {
            throw new invalid_parameter_exception('User is not enrolled in this course');
        }

        // Validate status.
        $valid_statuses = ['satisfactory', 'not_satisfactory', 'in_progress', 'not_started'];
        if (!in_array($params['status'], $valid_statuses)) {
            throw new invalid_parameter_exception('Invalid status provided');
        }

        try {
            // Check if assessment already exists.
            $existing = $DB->get_record('observationchecklist_user_items', [
                'checklistid' => $cm->instance,
                'itemid' => $params['itemid'],
                'userid' => $params['userid']
            ]);

            $assessment = new \stdClass();
            $assessment->checklistid = $cm->instance;
            $assessment->itemid = $params['itemid'];
            $assessment->userid = $params['userid'];
            $assessment->status = $params['status'];
            $assessment->assessornotes = clean_param($params['notes'], PARAM_TEXT);
            $assessment->assessorid = $USER->id;
            $assessment->dateassessed = time();
            $assessment->timemodified = time();

            if ($existing) {
                // Update existing assessment.
                $assessment->id = $existing->id;
                $assessment->timecreated = $existing->timecreated;
                $DB->update_record('observationchecklist_user_items', $assessment);
            } else {
                // Create new assessment.
                $assessment->timecreated = time();
                $DB->insert_record('observationchecklist_user_items', $assessment);
            }

            // Trigger event.
            $event = \mod_observationchecklist\event\assessment_made::create([
                'objectid' => $params['itemid'],
                'context' => $context,
                'relateduserid' => $params['userid'],
                'other' => [
                    'status' => $params['status'],
                    'notes' => $params['notes']
                ]
            ]);
            $event->trigger();

            return [
                'success' => true,
                'message' => get_string('assessmentadded', 'mod_observationchecklist')
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
