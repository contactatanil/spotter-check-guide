
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Privacy Subsystem implementation for mod_observationchecklist.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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

/**
 * Implementation of the privacy subsystem plugin provider for the observation checklist activity module.
 *
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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

        // Contexts where user has created items or has assessments.
        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {observationchecklist} o ON o.id = cm.instance
             LEFT JOIN {observationchecklist_items} oi ON oi.checklistid = o.id
             LEFT JOIN {observationchecklist_user_items} oui ON oui.checklistid = o.id
                 WHERE oi.userid = :userid1 OR oui.userid = :userid2 OR oui.assessorid = :userid3";

        $params = [
            'modname' => 'observationchecklist',
            'contextlevel' => CONTEXT_MODULE,
            'userid1' => $userid,
            'userid2' => $userid,
            'userid3' => $userid,
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

        // Fetch all users who have data.
        $sql = "SELECT oi.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {observationchecklist} o ON o.id = cm.instance
                  JOIN {observationchecklist_items} oi ON oi.checklistid = o.id
                 WHERE cm.id = :cmid";

        $params = [
            'cmid' => $context->instanceid,
            'modname' => 'observationchecklist',
        ];

        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT oui.userid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {observationchecklist} o ON o.id = cm.instance
                  JOIN {observationchecklist_user_items} oui ON oui.checklistid = o.id
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, $params);

        $sql = "SELECT oui.assessorid
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module AND m.name = :modname
                  JOIN {observationchecklist} o ON o.id = cm.instance
                  JOIN {observationchecklist_user_items} oui ON oui.checklistid = o.id
                 WHERE cm.id = :cmid AND oui.assessorid IS NOT NULL";

        $userlist->add_from_sql('assessorid', $sql, $params);
    }

    /**
     * Export personal data for the given approved_contextlist.
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

        $sql = "SELECT cm.id AS cmid, o.name, o.intro, o.introformat
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {observationchecklist} o ON o.id = cm.instance
                 WHERE c.id {$contextsql}";

        $params = ['modname' => 'observationchecklist'] + $contextparams;

        $activities = $DB->get_recordset_sql($sql, $params);
        foreach ($activities as $activity) {
            $context = \context_module::instance($activity->cmid);
            $data = helper::get_context_data($context, $user);
            helper::export_context_files($context, $user);
            writer::with_context($context)->export_data([], $data);
        }
        $activities->close();
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
            $DB->delete_records('observationchecklist_user_items', ['checklistid' => $cm->instance]);
            $DB->delete_records('observationchecklist_items', ['checklistid' => $cm->instance]);
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
                $DB->delete_records('observationchecklist_user_items', 
                    ['checklistid' => $cm->instance, 'userid' => $userid]);
                $DB->delete_records('observationchecklist_items', 
                    ['checklistid' => $cm->instance, 'userid' => $userid]);
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
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $select = "checklistid = :checklistid AND userid {$usersql}";
        $params = ['checklistid' => $cm->instance] + $userparams;
        $DB->delete_records_select('observationchecklist_user_items', $select, $params);
        $DB->delete_records_select('observationchecklist_items', $select, $params);
    }
}
