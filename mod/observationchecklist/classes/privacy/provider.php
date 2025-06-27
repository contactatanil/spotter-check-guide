
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\privacy;

defined('MOODLE_INTERNAL') || die();

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
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
     * @param collection $collection a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $collection): collection {
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

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {observationchecklist_user_items} oui ON oui.checklistid = cm.instance
                 WHERE oui.userid = :userid";

        $params = [
            'modname' => 'observationchecklist',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
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

        $params = [
            'cmid' => $context->instanceid,
        ];

        $sql = "SELECT oui.userid
                  FROM {course_modules} cm
                  JOIN {observationchecklist_user_items} oui ON oui.checklistid = cm.instance
                 WHERE cm.id = :cmid";

        $userlist->add_from_sql('userid', $sql, $params);
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

        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cm = get_coursemodule_from_id('observationchecklist', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $sql = "SELECT oui.*, oci.itemtext
                      FROM {observationchecklist_user_items} oui
                      JOIN {observationchecklist_items} oci ON oci.id = oui.itemid
                     WHERE oui.checklistid = :checklistid AND oui.userid = :userid";

            $params = [
                'checklistid' => $cm->instance,
                'userid' => $user->id,
            ];

            $records = $DB->get_records_sql($sql, $params);

            if ($records) {
                $data = [];
                foreach ($records as $record) {
                    $data[] = [
                        'item' => $record->itemtext,
                        'status' => $record->status,
                        'notes' => $record->assessornotes,
                        'date_assessed' => $record->dateassessed ? transform::datetime($record->dateassessed) : null,
                    ];
                }

                writer::with_context($context)->export_data([], (object) [
                    'assessments' => $data,
                ]);
            }
        }
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

        $cm = get_coursemodule_from_id('observationchecklist', $context->instanceid);
        if (!$cm) {
            return;
        }

        $DB->delete_records('observationchecklist_user_items', ['checklistid' => $cm->instance]);
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
            if ($context->contextlevel != CONTEXT_MODULE) {
                continue;
            }

            $cm = get_coursemodule_from_id('observationchecklist', $context->instanceid);
            if (!$cm) {
                continue;
            }

            $DB->delete_records('observationchecklist_user_items', [
                'checklistid' => $cm->instance,
                'userid' => $userid,
            ]);
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
        if (!empty($userids)) {
            list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $select = "checklistid = :checklistid AND userid {$usersql}";
            $params = ['checklistid' => $cm->instance] + $userparams;
            $DB->delete_records_select('observationchecklist_user_items', $select, $params);
        }
    }
}
