
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\report;

defined('MOODLE_INTERNAL') || die();

/**
 * Report manager class for observation checklist
 */
class report_manager {

    /**
     * Generate overview report data
     */
    public static function get_overview_data($cmid) {
        global $DB;
        
        $cm = get_coursemodule_from_id('observationchecklist', $cmid);
        
        // Get total items
        $totalitems = $DB->count_records('observationchecklist_items', ['checklistid' => $cm->instance]);
        
        // Get enrolled users
        $context = \context_module::instance($cm->id);
        $enrolledusers = get_enrolled_users($context, 'mod/observationchecklist:submit');
        $totalusers = count($enrolledusers);
        
        // Get completion statistics
        $sql = "SELECT 
                    COUNT(DISTINCT ui.userid) as users_started,
                    SUM(CASE WHEN ui.status = 'satisfactory' THEN 1 ELSE 0 END) as satisfactory_count,
                    SUM(CASE WHEN ui.status = 'not_satisfactory' THEN 1 ELSE 0 END) as not_satisfactory_count,
                    COUNT(ui.id) as total_assessments
                FROM {observationchecklist_user_items} ui
                WHERE ui.checklistid = ?";
        
        $stats = $DB->get_record_sql($sql, [$cm->instance]);
        
        return [
            'total_items' => $totalitems,
            'total_users' => $totalusers,
            'users_started' => $stats->users_started ?? 0,
            'satisfactory_count' => $stats->satisfactory_count ?? 0,
            'not_satisfactory_count' => $stats->not_satisfactory_count ?? 0,
            'total_assessments' => $stats->total_assessments ?? 0,
            'completion_rate' => $totalusers > 0 ? round(($stats->users_started / $totalusers) * 100, 2) : 0
        ];
    }

    /**
     * Generate grading report data
     */
    public static function get_grading_data($cmid, $userid = 0, $groupid = 0) {
        global $DB;
        
        $cm = get_coursemodule_from_id('observationchecklist', $cmid);
        $context = \context_module::instance($cm->id);
        
        $params = [$cm->instance];
        $userfilter = '';
        
        if ($userid > 0) {
            $userfilter = ' AND u.id = ?';
            $params[] = $userid;
        }
        
        if ($groupid > 0) {
            $userfilter .= ' AND u.id IN (SELECT userid FROM {groups_members} WHERE groupid = ?)';
            $params[] = $groupid;
        }
        
        $sql = "SELECT u.id, u.firstname, u.lastname, u.email,
                       COUNT(ui.id) as items_assessed,
                       SUM(CASE WHEN ui.status = 'satisfactory' THEN 1 ELSE 0 END) as satisfactory,
                       SUM(CASE WHEN ui.status = 'not_satisfactory' THEN 1 ELSE 0 END) as not_satisfactory,
                       MAX(ui.timemodified) as last_activity
                FROM {user} u
                JOIN {user_enrolments} ue ON ue.userid = u.id
                JOIN {enrol} e ON e.id = ue.enrolid
                LEFT JOIN {observationchecklist_user_items} ui ON ui.userid = u.id AND ui.checklistid = ?
                WHERE e.courseid = ? AND u.deleted = 0 $userfilter
                GROUP BY u.id, u.firstname, u.lastname, u.email
                ORDER BY u.lastname, u.firstname";
        
        $params[] = $cm->course;
        
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Generate statistics report data
     */
    public static function get_statistics_data($cmid) {
        global $DB;
        
        $cm = get_coursemodule_from_id('observationchecklist', $cmid);
        
        // Item completion statistics
        $sql = "SELECT i.id, i.itemtext, i.category,
                       COUNT(ui.id) as total_attempts,
                       SUM(CASE WHEN ui.status = 'satisfactory' THEN 1 ELSE 0 END) as satisfactory,
                       SUM(CASE WHEN ui.status = 'not_satisfactory' THEN 1 ELSE 0 END) as not_satisfactory,
                       SUM(CASE WHEN ui.status = 'in_progress' THEN 1 ELSE 0 END) as in_progress
                FROM {observationchecklist_items} i
                LEFT JOIN {observationchecklist_user_items} ui ON ui.itemid = i.id
                WHERE i.checklistid = ?
                GROUP BY i.id, i.itemtext, i.category
                ORDER BY i.position";
        
        $itemstats = $DB->get_records_sql($sql, [$cm->instance]);
        
        // Category statistics
        $sql = "SELECT i.category,
                       COUNT(DISTINCT i.id) as item_count,
                       COUNT(ui.id) as total_attempts,
                       SUM(CASE WHEN ui.status = 'satisfactory' THEN 1 ELSE 0 END) as satisfactory,
                       SUM(CASE WHEN ui.status = 'not_satisfactory' THEN 1 ELSE 0 END) as not_satisfactory
                FROM {observationchecklist_items} i
                LEFT JOIN {observationchecklist_user_items} ui ON ui.itemid = i.id
                WHERE i.checklistid = ?
                GROUP BY i.category
                ORDER BY i.category";
        
        $categorystats = $DB->get_records_sql($sql, [$cm->instance]);
        
        return [
            'item_statistics' => $itemstats,
            'category_statistics' => $categorystats
        ];
    }

    /**
     * Generate attempts report data
     */
    public static function get_attempts_data($cmid, $userid = 0) {
        global $DB;
        
        $cm = get_coursemodule_from_id('observationchecklist', $cmid);
        
        $params = [$cm->instance];
        $userfilter = '';
        
        if ($userid > 0) {
            $userfilter = ' AND ui.userid = ?';
            $params[] = $userid;
        }
        
        $sql = "SELECT ui.id, u.firstname, u.lastname, i.itemtext, i.category,
                       ui.status, ui.assessornotes, ui.dateassessed, ui.timemodified,
                       assessor.firstname as assessor_firstname, assessor.lastname as assessor_lastname
                FROM {observationchecklist_user_items} ui
                JOIN {user} u ON u.id = ui.userid
                JOIN {observationchecklist_items} i ON i.id = ui.itemid
                LEFT JOIN {user} assessor ON assessor.id = ui.assessorid
                WHERE ui.checklistid = ? $userfilter
                ORDER BY ui.timemodified DESC";
        
        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Generate progress report data
     */
    public static function get_progress_data($cmid, $groupid = 0) {
        global $DB;
        
        $cm = get_coursemodule_from_id('observationchecklist', $cmid);
        $context = \context_module::instance($cm->id);
        
        $enrolledusers = get_enrolled_users($context, 'mod/observationchecklist:submit', $groupid);
        $totalitems = $DB->count_records('observationchecklist_items', ['checklistid' => $cm->instance]);
        
        $progressdata = [];
        
        foreach ($enrolledusers as $user) {
            $sql = "SELECT COUNT(*) as completed,
                           SUM(CASE WHEN status = 'satisfactory' THEN 1 ELSE 0 END) as satisfactory,
                           SUM(CASE WHEN status = 'not_satisfactory' THEN 1 ELSE 0 END) as not_satisfactory,
                           SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress
                    FROM {observationchecklist_user_items}
                    WHERE checklistid = ? AND userid = ?";
            
            $progress = $DB->get_record_sql($sql, [$cm->instance, $user->id]);
            
            $progressdata[] = [
                'user' => $user,
                'completed' => $progress->completed ?? 0,
                'satisfactory' => $progress->satisfactory ?? 0,
                'not_satisfactory' => $progress->not_satisfactory ?? 0,
                'in_progress' => $progress->in_progress ?? 0,
                'total_items' => $totalitems,
                'completion_percentage' => $totalitems > 0 ? round(($progress->completed / $totalitems) * 100, 2) : 0
            ];
        }
        
        return $progressdata;
    }

    /**
     * Generate trainer report data
     */
    public static function get_trainer_data($cmid) {
        global $DB;
        
        $cm = get_coursemodule_from_id('observationchecklist', $cmid);
        
        // Assessor activity
        $sql = "SELECT assessor.id, assessor.firstname, assessor.lastname,
                       COUNT(ui.id) as assessments_made,
                       SUM(CASE WHEN ui.status = 'satisfactory' THEN 1 ELSE 0 END) as satisfactory_given,
                       SUM(CASE WHEN ui.status = 'not_satisfactory' THEN 1 ELSE 0 END) as not_satisfactory_given,
                       MIN(ui.dateassessed) as first_assessment,
                       MAX(ui.dateassessed) as last_assessment
                FROM {observationchecklist_user_items} ui
                JOIN {user} assessor ON assessor.id = ui.assessorid
                WHERE ui.checklistid = ? AND ui.assessorid IS NOT NULL
                GROUP BY assessor.id, assessor.firstname, assessor.lastname
                ORDER BY assessments_made DESC";
        
        $assessordata = $DB->get_records_sql($sql, [$cm->instance]);
        
        // Daily assessment activity
        $sql = "SELECT DATE(FROM_UNIXTIME(dateassessed)) as assessment_date,
                       COUNT(*) as assessments_count
                FROM {observationchecklist_user_items}
                WHERE checklistid = ? AND dateassessed IS NOT NULL
                GROUP BY DATE(FROM_UNIXTIME(dateassessed))
                ORDER BY assessment_date DESC
                LIMIT 30";
        
        $dailyactivity = $DB->get_records_sql($sql, [$cm->instance]);
        
        return [
            'assessor_data' => $assessordata,
            'daily_activity' => $dailyactivity
        ];
    }
}
