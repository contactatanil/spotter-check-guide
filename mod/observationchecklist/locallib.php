
<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Get all checklist items for a specific checklist with optional caching
 */
function observationchecklist_get_items($checklistid, $usecache = true) {
    global $DB;
    
    if ($usecache) {
        $cache = cache::make('mod_observationchecklist', 'items');
        $items = $cache->get($checklistid);
        
        if ($items === false) {
            $items = $DB->get_records('observationchecklist_items', 
                array('checklistid' => $checklistid), 
                'sortorder ASC'
            );
            $cache->set($checklistid, $items);
        }
        return $items;
    }
    
    return $DB->get_records('observationchecklist_items', 
        array('checklistid' => $checklistid), 
        'sortorder ASC'
    );
}

/**
 * Add a new item to the checklist
 */
function observationchecklist_add_item($checklistid, $itemtext, $category, $userid) {
    global $DB;
    
    // Input validation
    if (empty($itemtext) || strlen($itemtext) > 500) {
        throw new invalid_parameter_exception('Item text must be between 1 and 500 characters');
    }
    
    if (!empty($category) && strlen($category) > 100) {
        throw new invalid_parameter_exception('Category must be less than 100 characters');
    }
    
    $transaction = $DB->start_delegated_transaction();
    
    try {
        $maxsort = $DB->get_field_sql('SELECT MAX(sortorder) FROM {observationchecklist_items} WHERE checklistid = ?', array($checklistid));
        
        $item = new stdClass();
        $item->checklistid = $checklistid;
        $item->itemtext = clean_param($itemtext, PARAM_TEXT);
        $item->category = clean_param($category, PARAM_TEXT);
        $item->userid = $userid;
        $item->sortorder = ($maxsort + 1);
        $item->timecreated = time();
        $item->timemodified = time();
        
        $itemid = $DB->insert_record('observationchecklist_items', $item);
        
        // Clear cache
        $cache = cache::make('mod_observationchecklist', 'items');
        $cache->delete($checklistid);
        
        $transaction->allow_commit();
        return $itemid;
        
    } catch (Exception $e) {
        $transaction->rollback($e);
        throw $e;
    }
}

/**
 * Delete a checklist item
 */
function observationchecklist_delete_item($itemid) {
    global $DB;
    
    $transaction = $DB->start_delegated_transaction();
    
    try {
        // Get item to clear cache later
        $item = $DB->get_record('observationchecklist_items', array('id' => $itemid));
        
        // Delete the item and all associated user progress
        $DB->delete_records('observationchecklist_user_items', array('itemid' => $itemid));
        $DB->delete_records('observationchecklist_items', array('id' => $itemid));
        
        // Clear cache if item existed
        if ($item) {
            $cache = cache::make('mod_observationchecklist', 'items');
            $cache->delete($item->checklistid);
        }
        
        $transaction->allow_commit();
        return true;
        
    } catch (Exception $e) {
        $transaction->rollback($e);
        throw $e;
    }
}

/**
 * Assess a checklist item for a student
 */
function observationchecklist_assess_item($itemid, $studentid, $status, $notes, $assessorid) {
    global $DB;
    
    // Enhanced validation
    $valid_statuses = ['satisfactory', 'not_satisfactory', 'in_progress', 'not_started'];
    if (!in_array($status, $valid_statuses)) {
        throw new invalid_parameter_exception('Invalid status');
    }
    
    if (strlen($notes) > 1000) {
        throw new invalid_parameter_exception('Notes too long (maximum 1000 characters)');
    }
    
    $transaction = $DB->start_delegated_transaction();
    
    try {
        $item = $DB->get_record('observationchecklist_items', array('id' => $itemid), '*', MUST_EXIST);
        
        $existing = $DB->get_record('observationchecklist_user_items', 
            array('itemid' => $itemid, 'userid' => $studentid)
        );
        
        if ($existing) {
            $existing->status = $status;
            $existing->assessornotes = clean_param($notes, PARAM_TEXT);
            $existing->assessorid = $assessorid;
            $existing->dateassessed = time();
            $existing->timemodified = time();
            $result = $DB->update_record('observationchecklist_user_items', $existing);
        } else {
            $record = new stdClass();
            $record->itemid = $itemid;
            $record->userid = $studentid;
            $record->checklistid = $item->checklistid;
            $record->status = $status;
            $record->assessornotes = clean_param($notes, PARAM_TEXT);
            $record->assessorid = $assessorid;
            $record->dateassessed = time();
            $record->timecreated = time();
            $record->timemodified = time();
            $result = $DB->insert_record('observationchecklist_user_items', $record);
        }
        
        $transaction->allow_commit();
        return $result;
        
    } catch (Exception $e) {
        $transaction->rollback($e);
        throw $e;
    }
}

/**
 * Get user's progress for checklist items
 */
function observationchecklist_get_user_progress($checklistid, $userid) {
    global $DB;
    
    $sql = "SELECT i.id, i.itemtext, i.category, 
                   COALESCE(ui.status, 'not_started') as status,
                   ui.assessornotes, ui.dateassessed, ui.assessorid,
                   u.firstname, u.lastname
            FROM {observationchecklist_items} i
            LEFT JOIN {observationchecklist_user_items} ui ON i.id = ui.itemid AND ui.userid = ?
            LEFT JOIN {user} u ON ui.assessorid = u.id
            WHERE i.checklistid = ?
            ORDER BY i.sortorder ASC";
    
    return $DB->get_records_sql($sql, array($userid, $checklistid));
}

/**
 * Get all students' progress for a checklist - Optimized to fix N+1 query problem
 */
function observationchecklist_get_all_progress($checklistid) {
    global $DB;
    
    // Single optimized query to get all progress data
    $sql = "SELECT CONCAT(u.id, '_', COALESCE(ui.itemid, 0)) as uniquekey,
                   u.id as userid, u.firstname, u.lastname, u.email,
                   ui.itemid, ui.status, ui.assessornotes, ui.dateassessed,
                   COUNT(DISTINCT ui2.id) as assessed_items,
                   SUM(CASE WHEN ui2.status = 'satisfactory' THEN 1 ELSE 0 END) as satisfactory_items,
                   SUM(CASE WHEN ui2.status = 'not_satisfactory' THEN 1 ELSE 0 END) as not_satisfactory_items,
                   (SELECT COUNT(*) FROM {observationchecklist_items} WHERE checklistid = ?) as total_items
            FROM {user} u
            INNER JOIN {role_assignments} ra ON u.id = ra.userid
            INNER JOIN {context} ctx ON ra.contextid = ctx.id
            INNER JOIN {role} r ON ra.roleid = r.id
            LEFT JOIN {observationchecklist_user_items} ui ON u.id = ui.userid AND ui.checklistid = ?
            LEFT JOIN {observationchecklist_user_items} ui2 ON u.id = ui2.userid AND ui2.checklistid = ?
            WHERE ctx.instanceid = (SELECT course FROM {observationchecklist} WHERE id = ?)
            AND r.shortname = 'student'
            AND u.deleted = 0
            GROUP BY u.id, u.firstname, u.lastname, u.email, ui.itemid, ui.status, ui.assessornotes, ui.dateassessed
            ORDER BY u.lastname, u.firstname";
    
    $records = $DB->get_records_sql($sql, array($checklistid, $checklistid, $checklistid, $checklistid));
    
    // Process results to group by user
    $progress = array();
    foreach ($records as $record) {
        if (!isset($progress[$record->userid])) {
            $progress[$record->userid] = (object) array(
                'userid' => $record->userid,
                'firstname' => $record->firstname,
                'lastname' => $record->lastname,
                'email' => $record->email,
                'assessed_items' => $record->assessed_items,
                'satisfactory_items' => $record->satisfactory_items,
                'not_satisfactory_items' => $record->not_satisfactory_items,
                'total_items' => $record->total_items,
                'completion_rate' => $record->total_items > 0 ? round(($record->assessed_items / $record->total_items) * 100, 1) : 0
            );
        }
    }
    
    return $progress;
}

/**
 * Generate a printable report for a student
 */
function observationchecklist_generate_report($checklistid, $userid) {
    global $DB, $USER;
    
    $checklist = $DB->get_record('observationchecklist', array('id' => $checklistid), '*', MUST_EXIST);
    $student = $DB->get_record('user', array('id' => $userid), '*', MUST_EXIST);
    $items = observationchecklist_get_items($checklistid);
    $progress = observationchecklist_get_user_progress($checklistid, $userid);
    
    $report = new stdClass();
    $report->checklist = $checklist;
    $report->student = $student;
    $report->items = $items;
    $report->progress = $progress;
    $report->generated_by = $USER;
    $report->generated_date = time();
    
    // Calculate summary statistics
    $total_items = count($items);
    $assessed_items = 0;
    $satisfactory_items = 0;
    $not_satisfactory_items = 0;
    
    foreach ($progress as $item) {
        if ($item->status !== 'not_started') {
            $assessed_items++;
            if ($item->status === 'satisfactory') {
                $satisfactory_items++;
            } elseif ($item->status === 'not_satisfactory') {
                $not_satisfactory_items++;
            }
        }
    }
    
    $report->statistics = (object) array(
        'total_items' => $total_items,
        'assessed_items' => $assessed_items,
        'satisfactory_items' => $satisfactory_items,
        'not_satisfactory_items' => $not_satisfactory_items,
        'completion_rate' => $total_items > 0 ? round(($assessed_items / $total_items) * 100, 1) : 0
    );
    
    return $report;
}

/**
 * Clear all caches for a checklist
 */
function observationchecklist_clear_cache($checklistid) {
    $cache = cache::make('mod_observationchecklist', 'items');
    $cache->delete($checklistid);
}
