
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\core_userlist_provider {

    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'observationchecklist_user_items',
            [
                'userid' => 'privacy:metadata:observationchecklist_user_items:userid',
                'checked' => 'privacy:metadata:observationchecklist_user_items:checked',
                'timecreated' => 'privacy:metadata:observationchecklist_user_items:timecreated',
                'timemodified' => 'privacy:metadata:observationchecklist_user_items:timemodified',
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
                INNER JOIN {observationchecklist_user_items} oui ON oui.checklistid = oc.id
                WHERE oui.userid = :userid";

        $params = [
            'contextlevel' => CONTEXT_MODULE,
            'modname' => 'observationchecklist',
            'userid' => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

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
                JOIN {observationchecklist} oc ON oc.id = cm.instance
                JOIN {observationchecklist_user_items} oui ON oui.checklistid = oc.id
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

            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid]);
            if (!$instanceid) {
                continue;
            }

            $sql = "SELECT oui.*, oi.itemtext
                    FROM {observationchecklist_user_items} oui
                    JOIN {observationchecklist_items} oi ON oi.id = oui.itemid
                    WHERE oui.checklistid = :checklistid AND oui.userid = :userid";

            $records = $DB->get_records_sql($sql, [
                'checklistid' => $instanceid,
                'userid' => $user->id
            ]);

            if (!empty($records)) {
                $data = [];
                foreach ($records as $record) {
                    $data[] = [
                        'item' => $record->itemtext,
                        'checked' => $record->checked ? 'Yes' : 'No',
                        'timecreated' => \core_privacy\local\request\transform::datetime($record->timecreated),
                        'timemodified' => \core_privacy\local\request\transform::datetime($record->timemodified),
                    ];
                }

                writer::with_context($context)->export_data(['Observation Checklist'], (object) $data);
            }
        }
    }

    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid]);
        if ($instanceid) {
            $DB->delete_records('observationchecklist_user_items', ['checklistid' => $instanceid]);
        }
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

            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid]);
            if ($instanceid) {
                $DB->delete_records('observationchecklist_user_items', [
                    'checklistid' => $instanceid,
                    'userid' => $userid
                ]);
            }
        }
    }

    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid]);
        if (!$instanceid) {
            return;
        }

        $userids = $userlist->get_userids();
        if (!empty($userids)) {
            list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
            $params = ['checklistid' => $instanceid] + $userparams;
            $DB->delete_records_select('observationchecklist_user_items', 
                "checklistid = :checklistid AND userid $usersql", $params);
        }
    }
}
