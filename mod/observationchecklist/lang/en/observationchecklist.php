
<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Plugin strings are defined here.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Observation Checklist';
$string['modulename'] = 'Observation Checklist';
$string['modulenameplural'] = 'Observation Checklists';
$string['pluginadministration'] = 'Observation Checklist administration';

// Capabilities
$string['observationchecklist:addinstance'] = 'Add a new observation checklist';
$string['observationchecklist:view'] = 'View observation checklist';
$string['observationchecklist:edit'] = 'Edit observation checklist items';
$string['observationchecklist:assess'] = 'Assess observation checklist items';
$string['observationchecklist:submit'] = 'Submit observation checklist';

// Form elements
$string['name'] = 'Name';
$string['description'] = 'Description';
$string['itemtext'] = 'Item text';
$string['category'] = 'Category';
$string['additem'] = 'Add item';
$string['addnewitem'] = 'Add new item';
$string['assessmentitems'] = 'Assessment items';
$string['studentassessment'] = 'Student assessment';
$string['choosestudentmessage'] = 'Choose a student to assess:';

// Status messages
$string['itemadded'] = 'Item added successfully';
$string['itemdeleted'] = 'Item deleted successfully';
$string['assessmentadded'] = 'Assessment added successfully';
$string['submissioncomplete'] = 'Checklist submitted successfully';
$string['submissionfailed'] = 'Failed to submit checklist';
$string['confirmsubmission'] = 'Are you sure you want to submit this checklist?';

// Assessment statuses
$string['satisfactory'] = 'Satisfactory';
$string['not_satisfactory'] = 'Not Satisfactory';
$string['in_progress'] = 'In Progress';
$string['not_started'] = 'Not Started';

// General
$string['delete'] = 'Delete';
$string['confirmdelete'] = 'Are you sure you want to delete this item?';
$string['confirmdeleteitem'] = 'Are you sure you want to delete this item?';
$string['noitemsfound'] = 'No items found in this checklist.';
$string['assess'] = 'Assess';
$string['evidence'] = 'Evidence';
$string['notes'] = 'Notes';
$string['dateassessed'] = 'Date assessed';
$string['assessedby'] = 'Assessed by';

// Privacy
$string['privacy:metadata:observationchecklist_user_items'] = 'Information about user assessments for checklist items.';
$string['privacy:metadata:observationchecklist_user_items:userid'] = 'The ID of the user being assessed.';
$string['privacy:metadata:observationchecklist_user_items:status'] = 'The assessment status for the item.';
$string['privacy:metadata:observationchecklist_user_items:assessornotes'] = 'Notes provided by the assessor.';
$string['privacy:metadata:observationchecklist_user_items:dateassessed'] = 'The date when the assessment was made.';

// Events
$string['eventcoursemoduleviewed'] = 'Course module viewed';
$string['eventitemadded'] = 'Checklist item added';
$string['eventassessmentmade'] = 'Assessment made';
