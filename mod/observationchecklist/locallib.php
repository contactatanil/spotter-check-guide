<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Internal library of functions for module observationchecklist
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Get all items for a checklist
 *
 * @param int $checklistid
 * @return array
 */
function observationchecklist_get_items($checklistid) {
    global $DB;
    
    return $DB->get_records('observationchecklist_items', 
        array('checklistid' => $checklistid), 
        'id ASC'
    );
}

/**
 * Get user progress for a checklist
 *
 * @param int $checklistid
 * @param int $userid
 * @return array
 */
function observationchecklist_get_user_progress($checklistid, $userid) {
    global $DB;
    
    return $DB->get_records('observationchecklist_user_items', 
        array('checklistid' => $checklistid, 'userid' => $userid), 
        '', 'itemid, status, assessornotes, dateassessed'
    );
}

/**
 * Add a new item to a checklist
 *
 * @param int $checklistid
 * @param string $itemtext
 * @param string $category
 * @param int $userid
 * @return int item id
 */
function observationchecklist_add_item($checklistid, $itemtext, $category, $userid) {
    global $DB;
    
    $item = new stdClass();
    $item->checklistid = $checklistid;
    $item->itemtext = clean_param($itemtext, PARAM_TEXT);
    $item->category = clean_param($category, PARAM_TEXT);
    $item->userid = $userid;
    $item->position = 0;
    $item->sortorder = 0;
    $item->timecreated = time();
    $item->timemodified = time();
    
    return $DB->insert_record('observationchecklist_items', $item);
}

/**
 * Delete an item from a checklist
 *
 * @param int $itemid
 * @return bool
 */
function observationchecklist_delete_item($itemid) {
    global $DB;
    
    // Delete user progress for this item
    $DB->delete_records('observationchecklist_user_items', array('itemid' => $itemid));
    
    // Delete the item
    return $DB->delete_records('observationchecklist_items', array('id' => $itemid));
}

/**
 * Assess an item for a user
 *
 * @param int $itemid
 * @param int $userid
 * @param string $status
 * @param string $notes
 * @param int $assessorid
 * @return bool
 */
function observationchecklist_assess_item($itemid, $userid, $status, $notes, $assessorid) {
    global $DB;
    
    $item = $DB->get_record('observationchecklist_items', array('id' => $itemid), '*', MUST_EXIST);
    
    $existing = $DB->get_record('observationchecklist_user_items', 
        array('itemid' => $itemid, 'userid' => $userid));
    
    if ($existing) {
        $existing->status = $status;
        $existing->assessornotes = clean_param($notes, PARAM_TEXT);
        $existing->assessorid = $assessorid;
        $existing->dateassessed = time();
        $existing->timemodified = time();
        return $DB->update_record('observationchecklist_user_items', $existing);
    } else {
        $record = new stdClass();
        $record->checklistid = $item->checklistid;
        $record->itemid = $itemid;
        $record->userid = $userid;
        $record->status = $status;
        $record->assessornotes = clean_param($notes, PARAM_TEXT);
        $record->assessorid = $assessorid;
        $record->dateassessed = time();
        $record->timecreated = time();
        $record->timemodified = time();
        return $DB->insert_record('observationchecklist_user_items', $record);
    }
}

/**
 * Display overview report
 */
function observationchecklist_display_overview_report($cm, $context) {
    global $OUTPUT;
    
    $reportmanager = new \mod_observationchecklist\report\report_manager();
    $data = $reportmanager::get_overview_data($cm->id);
    
    echo html_writer::start_div('overview-report');
    echo html_writer::tag('h3', get_string('reportoverview', 'observationchecklist'));
    
    // Statistics cards
    echo html_writer::start_div('row');
    
    $cards = [
        ['title' => get_string('totalitems', 'observationchecklist'), 'value' => $data['total_items'], 'icon' => 'fa-list'],
        ['title' => get_string('totalusers', 'observationchecklist'), 'value' => $data['total_users'], 'icon' => 'fa-users'],
        ['title' => get_string('usersstarted', 'observationchecklist'), 'value' => $data['users_started'], 'icon' => 'fa-play'],
        ['title' => get_string('completionrate', 'observationchecklist'), 'value' => $data['completion_rate'] . '%', 'icon' => 'fa-chart-pie']
    ];
    
    foreach ($cards as $card) {
        echo html_writer::start_div('col-md-3');
        echo html_writer::start_div('card text-center');
        echo html_writer::start_div('card-body');
        echo html_writer::tag('i', '', ['class' => 'fa ' . $card['icon'] . ' fa-3x text-primary']);
        echo html_writer::tag('h4', $card['value'], ['class' => 'card-title mt-2']);
        echo html_writer::tag('p', $card['title'], ['class' => 'card-text']);
        echo html_writer::end_div();
        echo html_writer::end_div();
        echo html_writer::end_div();
    }
    
    echo html_writer::end_div();
    
    // Export buttons
    echo observationchecklist_render_export_buttons($cm->id, 'overview');
    
    echo html_writer::end_div();
}

/**
 * Display grading report
 */
function observationchecklist_display_grading_report($cm, $context, $userid = 0, $groupid = 0) {
    global $OUTPUT;
    
    $reportmanager = new \mod_observationchecklist\report\report_manager();
    $data = $reportmanager::get_grading_data($cm->id, $userid, $groupid);
    
    echo html_writer::start_div('grading-report');
    echo html_writer::tag('h3', get_string('reportgrading', 'observationchecklist'));
    
    // Filters
    echo observationchecklist_render_filters($cm->id, $userid, $groupid);
    
    // Data table
    $table = new html_table();
    $table->head = [
        get_string('student'),
        get_string('email'),
        get_string('itemsassessed', 'observationchecklist'),
        get_string('satisfactory', 'observationchecklist'),
        get_string('not_satisfactory', 'observationchecklist'),
        get_string('lastactivity')
    ];
    
    foreach ($data as $row) {
        $table->data[] = [
            fullname($row),
            $row->email,
            $row->items_assessed,
            $row->satisfactory,
            $row->not_satisfactory,
            $row->last_activity ? userdate($row->last_activity) : '-'
        ];
    }
    
    echo html_writer::table($table);
    
    // Export buttons
    echo observationchecklist_render_export_buttons($cm->id, 'grading');
    
    echo html_writer::end_div();
}

/**
 * Display statistics report
 */
function observationchecklist_display_statistics_report($cm, $context) {
    global $OUTPUT;
    
    $reportmanager = new \mod_observationchecklist\report\report_manager();
    $data = $reportmanager::get_statistics_data($cm->id);
    
    echo html_writer::start_div('statistics-report');
    echo html_writer::tag('h3', get_string('reportstatistics', 'observationchecklist'));
    
    // Item statistics
    echo html_writer::tag('h4', get_string('itemstatistics', 'observationchecklist'));
    $table = new html_table();
    $table->head = [
        get_string('itemtext', 'observationchecklist'),
        get_string('category', 'observationchecklist'),
        get_string('totalattempts', 'observationchecklist'),
        get_string('satisfactory', 'observationchecklist'),
        get_string('not_satisfactory', 'observationchecklist'),
        get_string('in_progress', 'observationchecklist')
    ];
    
    foreach ($data['item_statistics'] as $item) {
        $table->data[] = [
            $item->itemtext,
            $item->category,
            $item->total_attempts,
            $item->satisfactory,
            $item->not_satisfactory,
            $item->in_progress
        ];
    }
    
    echo html_writer::table($table);
    
    // Category statistics
    if (!empty($data['category_statistics'])) {
        echo html_writer::tag('h4', get_string('categorystatistics', 'observationchecklist'));
        $table = new html_table();
        $table->head = [
            get_string('category', 'observationchecklist'),
            get_string('itemcount', 'observationchecklist'),
            get_string('totalattempts', 'observationchecklist'),
            get_string('satisfactory', 'observationchecklist'),
            get_string('not_satisfactory', 'observationchecklist')
        ];
        
        foreach ($data['category_statistics'] as $category) {
            $table->data[] = [
                $category->category,
                $category->item_count,
                $category->total_attempts,
                $category->satisfactory,
                $category->not_satisfactory
            ];
        }
        
        echo html_writer::table($table);
    }
    
    echo html_writer::end_div();
}

/**
 * Display attempts report
 */
function observationchecklist_display_attempts_report($cm, $context, $userid = 0) {
    global $OUTPUT;
    
    $reportmanager = new \mod_observationchecklist\report\report_manager();
    $data = $reportmanager::get_attempts_data($cm->id, $userid);
    
    echo html_writer::start_div('attempts-report');
    echo html_writer::tag('h3', get_string('reportattempts', 'observationchecklist'));
    
    $table = new html_table();
    $table->head = [
        get_string('student'),
        get_string('itemtext', 'observationchecklist'),
        get_string('category', 'observationchecklist'),
        get_string('status'),
        get_string('notes', 'observationchecklist'),
        get_string('dateassessed', 'observationchecklist'),
        get_string('assessedby', 'observationchecklist')
    ];
    
    foreach ($data as $attempt) {
        $table->data[] = [
            fullname($attempt),
            $attempt->itemtext,
            $attempt->category,
            ucfirst(str_replace('_', ' ', $attempt->status)),
            $attempt->assessornotes,
            userdate($attempt->dateassessed),
            $attempt->assessor_firstname . ' ' . $attempt->assessor_lastname
        ];
    }
    
    echo html_writer::table($table);
    
    echo html_writer::end_div();
}

/**
 * Display progress report
 */
function observationchecklist_display_progress_report($cm, $context, $groupid = 0) {
    global $OUTPUT;
    
    $reportmanager = new \mod_observationchecklist\report\report_manager();
    $data = $reportmanager::get_progress_data($cm->id, $groupid);
    
    echo html_writer::start_div('progress-report');
    echo html_writer::tag('h3', get_string('reportprogress', 'observationchecklist'));
    
    foreach ($data as $userdata) {
        echo html_writer::start_div('user-progress mb-3');
        echo html_writer::tag('h5', fullname($userdata['user']));
        
        echo html_writer::start_div('progress mb-2');
        $percentage = $userdata['completion_percentage'];
        echo html_writer::div('', 'progress-bar bg-primary', [
            'style' => "width: {$percentage}%",
            'role' => 'progressbar'
        ]);
        echo html_writer::end_div();
        
        echo html_writer::tag('small', 
            "{$userdata['completed']}/{$userdata['total_items']} items completed ({$percentage}%)"
        );
        
        echo html_writer::end_div();
    }
    
    echo html_writer::end_div();
}

/**
 * Display competency report
 */
function observationchecklist_display_competency_report($cm, $context) {
    echo html_writer::tag('h3', get_string('reportcompetency', 'observationchecklist'));
    echo html_writer::tag('p', 'Competency analysis coming soon...');
}

/**
 * Display trainer report
 */
function observationchecklist_display_trainer_report($cm, $context) {
    global $OUTPUT;
    
    $reportmanager = new \mod_observationchecklist\report\report_manager();
    $data = $reportmanager::get_trainer_data($cm->id);
    
    echo html_writer::start_div('trainer-report');
    echo html_writer::tag('h3', get_string('reporttrainer', 'observationchecklist'));
    
    // Assessor activity
    echo html_writer::tag('h4', get_string('traineractivity', 'observationchecklist'));
    $table = new html_table();
    $table->head = [
        get_string('assessor', 'observationchecklist'),
        get_string('assessmentsmade', 'observationchecklist'),
        get_string('satisfactorygiven', 'observationchecklist'),
        get_string('notsatisfactorygiven', 'observationchecklist'),
        get_string('firstassessment', 'observationchecklist'),
        get_string('lastassessment', 'observationchecklist')
    ];
    
    foreach ($data['assessor_data'] as $assessor) {
        $table->data[] = [
            fullname($assessor),
            $assessor->assessments_made,
            $assessor->satisfactory_given,
            $assessor->not_satisfactory_given,
            userdate($assessor->first_assessment),
            userdate($assessor->last_assessment)
        ];
    }
    
    echo html_writer::table($table);
    
    echo html_writer::end_div();
}

/**
 * Render export buttons
 */
function observationchecklist_render_export_buttons($cmid, $reporttype) {
    $buttons = html_writer::start_div('export-buttons mt-3');
    $buttons .= html_writer::tag('h5', get_string('export', 'observationchecklist'));
    
    $formats = ['csv', 'excel', 'pdf'];
    foreach ($formats as $format) {
        $url = new moodle_url('/mod/observationchecklist/report.php', [
            'id' => $cmid,
            'type' => $reporttype,
            'format' => $format
        ]);
        $buttons .= html_writer::link($url, 
            get_string('export' . $format, 'observationchecklist'),
            ['class' => 'btn btn-secondary mr-2']
        );
    }
    
    $buttons .= html_writer::end_div();
    return $buttons;
}

/**
 * Render report filters
 */
function observationchecklist_render_filters($cmid, $userid = 0, $groupid = 0) {
    global $DB;
    
    $filters = html_writer::start_div('report-filters mb-3');
    $filters .= html_writer::start_tag('form', ['method' => 'get']);
    $filters .= html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $cmid]);
    
    // User filter
    $filters .= html_writer::start_div('form-group d-inline-block mr-3');
    $filters .= html_writer::tag('label', get_string('filterbyuser', 'observationchecklist'));
    $filters .= html_writer::select([], 'userid', $userid, 
        get_string('selectuser', 'observationchecklist'), 
        ['class' => 'form-control']
    );
    $filters .= html_writer::end_div();
    
    // Group filter
    $filters .= html_writer::start_div('form-group d-inline-block mr-3');
    $filters .= html_writer::tag('label', get_string('filterbygroup', 'observationchecklist'));
    $filters .= html_writer::select([], 'groupid', $groupid, 
        get_string('allgroups', 'observationchecklist'), 
        ['class' => 'form-control']
    );
    $filters .= html_writer::end_div();
    
    $filters .= html_writer::empty_tag('input', [
        'type' => 'submit', 
        'value' => get_string('filter'),
        'class' => 'btn btn-primary'
    ]);
    
    $filters .= html_writer::end_tag('form');
    $filters .= html_writer::end_div();
    
    return $filters;
}
