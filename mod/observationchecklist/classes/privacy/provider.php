
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'observationchecklist_items',
            [
                'userid' => 'privacy:metadata:observationchecklist_items:userid',
                'itemtext' => 'privacy:metadata:observationchecklist_items:itemtext',
                'timecreated' => 'privacy:metadata:observationchecklist_items:timecreated',
            ],
            'privacy:metadata:observationchecklist_items'
        );

        $collection->add_database_table(
            'observationchecklist_user_items',
            [
                'userid' => 'privacy:metadata:observationchecklist_user_items:userid',
                'status' => 'privacy:metadata:observationchecklist_user_items:status',
                'assessornotes' => 'privacy:metadata:observationchecklist_user_items:assessornotes',
                'dateassessed' => 'privacy:metadata:observationchecklist_user_items:dateassessed',
            ],
            'privacy:metadata:observationchecklist_user_items'
        );

        return $collection;
    }

    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT c.id
                FROM {context} c
                INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
                INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                INNER JOIN {observationchecklist} oc ON oc.id = cm.instance
                WHERE EXISTS (
                    SELECT 1 FROM {observationchecklist_items} oci WHERE oci.checklistid = oc.id AND oci.userid = :userid1
                ) OR EXISTS (
                    SELECT 1 FROM {observationchecklist_user_items} ocui WHERE ocui.checklistid = oc.id AND ocui.userid = :userid2
                )";

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'observationchecklist',
            'userid1' => $userid,
            'userid2' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $params = [
            'cmid' => $context->instanceid,
            'modname' => 'observationchecklist',
        ];

        $sql = "SELECT oci.userid
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                JOIN {observationchecklist} oc ON oc.id = cm.instance
                JOIN {observationchecklist_items} oci ON oci.checklistid = oc.id
                WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT ocui.userid
                FROM {course_modules} cm
                JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                JOIN {observationchecklist} oc ON oc.id = cm.instance
                JOIN {observationchecklist_user_items} ocui ON ocui.checklistid = oc.id
                WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, $params);
    }

    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $data = helper::get_context_data($context, $user);

            // Export user's checklist items
            $sql = "SELECT oci.*
                    FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module AND m.name = 'observationchecklist'
                    JOIN {observationchecklist} oc ON oc.id = cm.instance
                    JOIN {observationchecklist_items} oci ON oci.checklistid = oc.id
                    WHERE cm.id = ? AND oci.userid = ?";

            $items = $DB->get_records_sql($sql, [$context->instanceid, $user->id]);
            if ($items) {
                $data->items = $items;
            }

            // Export user's progress
            $sql = "SELECT ocui.*
                    FROM {course_modules} cm
                    JOIN {modules} m ON m.id = cm.module AND m.name = 'observationchecklist'
                    JOIN {observationchecklist} oc ON oc.id = cm.instance
                    JOIN {observationchecklist_user_items} ocui ON ocui.checklistid = oc.id
                    WHERE cm.id = ? AND ocui.userid = ?";

            $progress = $DB->get_records_sql($sql, [$context->instanceid, $user->id]);
            if ($progress) {
                $data->progress = $progress;
            }

            writer::with_context($context)->export_data([], $data);
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('observationchecklist', $context->instanceid);
        if (!$cm) {
            return;
        }

        $DB->delete_records('observationchecklist_user_items', ['checklistid' => $cm->instance]);
        $DB->delete_records('observationchecklist_items', ['checklistid' => $cm->instance]);
    }

    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cm = get_coursemodule_from_id('observationchecklist', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $DB->delete_records('observationchecklist_user_items', ['checklistid' => $cm->instance, 'userid' => $userid]);
            $DB->delete_records('observationchecklist_items', ['checklistid' => $cm->instance, 'userid' => $userid]);
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('observationchecklist', $context->instanceid);
        if (!$cm) {
            return;
        }

        $userids = $userlist->get_userids();
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $params = ['checklistid' => $cm->instance] + $userparams;

        $DB->delete_records_select('observationchecklist_user_items', "checklistid = :checklistid AND userid $usersql", $params);
        $DB->delete_records_select('observationchecklist_items', "checklistid = :checklistid AND userid $usersql", $params);
    }
}
