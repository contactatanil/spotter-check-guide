
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy Subsystem implementation for mod_observationchecklist.
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $items): collection {
        $items->add_database_table(
            'observationchecklist_items',
            [
                'userid' => 'privacy:metadata:observationchecklist_items:userid',
                'itemtext' => 'privacy:metadata:observationchecklist_items:itemtext',
                'timecreated' => 'privacy:metadata:observationchecklist_items:timecreated',
                'timemodified' => 'privacy:metadata:observationchecklist_items:timemodified',
            ],
            'privacy:metadata:observationchecklist_items'
        );

        $items->add_database_table(
            'observationchecklist_user_items',
            [
                'userid' => 'privacy:metadata:observationchecklist_user_items:userid',
                'status' => 'privacy:metadata:observationchecklist_user_items:status',
                'assessornotes' => 'privacy:metadata:observationchecklist_user_items:assessornotes',
                'assessorid' => 'privacy:metadata:observationchecklist_user_items:assessorid',
                'dateassessed' => 'privacy:metadata:observationchecklist_user_items:dateassessed',
                'timecreated' => 'privacy:metadata:observationchecklist_user_items:timecreated',
                'timemodified' => 'privacy:metadata:observationchecklist_user_items:timemodified',
            ],
            'privacy:metadata:observationchecklist_user_items'
        );

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        // Contexts where user has created items.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {observationchecklist} oc ON oc.id = cm.instance
            INNER JOIN {observationchecklist_items} oci ON oci.checklistid = oc.id
                 WHERE oci.userid = :userid";

        $params = [
            'modname' => 'observationchecklist',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        // Contexts where user has been assessed.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {observationchecklist} oc ON oc.id = cm.instance
            INNER JOIN {observationchecklist_user_items} ocui ON ocui.checklistid = oc.id
                 WHERE ocui.userid = :userid OR ocui.assessorid = :assessorid";

        $params = [
            'modname' => 'observationchecklist',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
            'assessorid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        // Fetch all users who have created items.
        $sql = "SELECT oci.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {observationchecklist} oc ON oc.id = cm.instance
                  JOIN {observationchecklist_items} oci ON oci.checklistid = oc.id
                 WHERE cm.id = :cmid";

        $params = [
            'cmid' => $context->instanceid,
            'modname' => 'observationchecklist',
        ];

        $userlist->add_from_sql('userid', $sql, $params);

        // Fetch all users who have been assessed or are assessors.
        $sql = "SELECT ocui.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {observationchecklist} oc ON oc.id = cm.instance
                  JOIN {observationchecklist_user_items} ocui ON ocui.checklistid = oc.id
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT ocui.assessorid as userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {observationchecklist} oc ON oc.id = cm.instance
                  JOIN {observationchecklist_user_items} ocui ON ocui.checklistid = oc.id
                 WHERE cm.id = :cmid AND ocui.assessorid IS NOT NULL";

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        // Export items created by the user.
        $sql = "SELECT oci.*, oc.name as checklistname
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid
            INNER JOIN {observationchecklist} oc ON oc.id = cm.instance
            INNER JOIN {observationchecklist_items} oci ON oci.checklistid = oc.id
                 WHERE c.id {$contextsql} AND oci.userid = :userid
              ORDER BY c.id";

        $params = $contextparams;
        $params['userid'] = $user->id;

        $items = $DB->get_recordset_sql($sql, $params);
        foreach ($items as $item) {
            $context = \context::instance_by_id($item->contextid);
            $data = [
                'itemtext' => $item->itemtext,
                'category' => $item->category,
                'timecreated' => transform::datetime($item->timecreated),
                'timemodified' => transform::datetime($item->timemodified),
            ];
            writer::with_context($context)->export_data([get_string('privacy:path:items', 'mod_observationchecklist')], (object) $data);
        }
        $items->close();

        // Export assessments.
        $sql = "SELECT ocui.*, oc.name as checklistname, oci.itemtext
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid
            INNER JOIN {observationchecklist} oc ON oc.id = cm.instance
            INNER JOIN {observationchecklist_user_items} ocui ON ocui.checklistid = oc.id
            INNER JOIN {observationchecklist_items} oci ON oci.id = ocui.itemid
                 WHERE c.id {$contextsql} AND (ocui.userid = :userid OR ocui.assessorid = :assessorid)
              ORDER BY c.id";

        $params = $contextparams;
        $params['userid'] = $user->id;
        $params['assessorid'] = $user->id;

        $assessments = $DB->get_recordset_sql($sql, $params);
        foreach ($assessments as $assessment) {
            $context = \context::instance_by_id($assessment->contextid);
            $data = [
                'itemtext' => $assessment->itemtext,
                'status' => $assessment->status,
                'assessornotes' => $assessment->assessornotes,
                'dateassessed' => transform::datetime($assessment->dateassessed),
                'timecreated' => transform::datetime($assessment->timecreated),
                'timemodified' => transform::datetime($assessment->timemodified),
            ];
            writer::with_context($context)->export_data([get_string('privacy:path:assessments', 'mod_observationchecklist')], (object) $data);
        }
        $assessments->close();
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        if ($cm = get_coursemodule_from_id('observationchecklist', $context->instanceid)) {
            $DB->delete_records('observationchecklist_items', ['checklistid' => $cm->instance]);
            $DB->delete_records('observationchecklist_user_items', ['checklistid' => $cm->instance]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if (!$context instanceof \context_module) {
                continue;
            }
            if ($cm = get_coursemodule_from_id('observationchecklist', $context->instanceid)) {
                $DB->delete_records('observationchecklist_items', ['checklistid' => $cm->instance, 'userid' => $userid]);
                $DB->delete_records('observationchecklist_user_items', ['checklistid' => $cm->instance, 'userid' => $userid]);
                
                // Also delete where user is the assessor.
                $DB->delete_records('observationchecklist_user_items', ['checklistid' => $cm->instance, 'assessorid' => $userid]);
            }
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('observationchecklist', $context->instanceid);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        if (empty($userids)) {
            return;
        }

        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $params = array_merge(['checklistid' => $cm->instance], $userparams);

        $DB->delete_records_select('observationchecklist_items', "checklistid = :checklistid AND userid {$usersql}", $params);
        $DB->delete_records_select('observationchecklist_user_items', "checklistid = :checklistid AND userid {$usersql}", $params);
        $DB->delete_records_select('observationchecklist_user_items', "checklistid = :checklistid AND assessorid {$usersql}", $params);
    }
}
