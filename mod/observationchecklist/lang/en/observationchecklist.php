
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

// Form elements.
$string['checklistname'] = 'Checklist name';
$string['checklistname_help'] = 'Enter a name for this observation checklist.';
$string['description'] = 'Description';
$string['description_help'] = 'Enter a description for this observation checklist.';

// Settings.
$string['settings'] = 'Settings';
$string['allowstudentadd'] = 'Allow students to add items';
$string['allowstudentadd_help'] = 'If enabled, students can add their own items to the checklist.';
$string['allowstudentsubmit'] = 'Allow students to submit';
$string['allowstudentsubmit_help'] = 'If enabled, students can submit their completed checklists.';
$string['enableprinting'] = 'Enable printing';
$string['enableprinting_help'] = 'If enabled, users can print their checklists.';

// Interface.
$string['additem'] = 'Add item';
$string['newitem'] = 'New item';
$string['deleteitem'] = 'Delete item';
$string['itemtext'] = 'Item text';
$string['category'] = 'Category';
$string['position'] = 'Position';
$string['status'] = 'Status';
$string['assessornotes'] = 'Assessor notes';
$string['dateassessed'] = 'Date assessed';

// Statuses.
$string['not_started'] = 'Not started';
$string['in_progress'] = 'In progress';
$string['satisfactory'] = 'Satisfactory';
$string['not_satisfactory'] = 'Not satisfactory';

// Events.
$string['eventitemadded'] = 'Checklist item added';
$string['eventassessmentmade'] = 'Assessment made';
$string['eventcoursemoduleviewed'] = 'Course module viewed';

// Capabilities.
$string['observationchecklist:addinstance'] = 'Add a new observation checklist';
$string['observationchecklist:view'] = 'View observation checklist';
$string['observationchecklist:edit'] = 'Edit observation checklist';
$string['observationchecklist:assess'] = 'Assess students';
$string['observationchecklist:submit'] = 'Submit checklist';
$string['observationchecklist:viewreports'] = 'View reports';

// Admin settings.
$string['defaultstatuses'] = 'Default statuses';
$string['defaultstatuses_desc'] = 'Comma-separated list of default assessment statuses.';
$string['defaultcategories'] = 'Default categories';
$string['defaultcategories_desc'] = 'Comma-separated list of default item categories.';
$string['enablenotifications'] = 'Enable notifications';
$string['enablenotifications_desc'] = 'Send notifications when assessments are made.';

// Privacy.
$string['privacy:metadata:observationchecklist_items'] = 'Information about checklist items created by users.';
$string['privacy:metadata:observationchecklist_items:userid'] = 'The ID of the user who created the item.';
$string['privacy:metadata:observationchecklist_items:itemtext'] = 'The text content of the checklist item.';
$string['privacy:metadata:observationchecklist_items:timecreated'] = 'The time when the item was created.';
$string['privacy:metadata:observationchecklist_items:timemodified'] = 'The time when the item was last modified.';

$string['privacy:metadata:observationchecklist_user_items'] = 'Information about user assessments and progress.';
$string['privacy:metadata:observationchecklist_user_items:userid'] = 'The ID of the user being assessed.';
$string['privacy:metadata:observationchecklist_user_items:status'] = 'The assessment status for the item.';
$string['privacy:metadata:observationchecklist_user_items:assessornotes'] = 'Notes provided by the assessor.';
$string['privacy:metadata:observationchecklist_user_items:assessorid'] = 'The ID of the user who made the assessment.';
$string['privacy:metadata:observationchecklist_user_items:dateassessed'] = 'The date when the assessment was made.';
$string['privacy:metadata:observationchecklist_user_items:timecreated'] = 'The time when the assessment record was created.';
$string['privacy:metadata:observationchecklist_user_items:timemodified'] = 'The time when the assessment record was last modified.';

$string['privacy:path:items'] = 'Checklist items';
$string['privacy:path:assessments'] = 'Assessments';

// View interface.
$string['noitems'] = 'No items have been added to this checklist yet.';
$string['addnewitem'] = 'Add new item';
$string['assessmentinterface'] = 'Assessment Interface';
$string['studentprogress'] = 'Student Progress';
$string['printchecklist'] = 'Print Checklist';

// Assessment interface.
$string['selectstudent'] = 'Select student';
$string['nostudents'] = 'No students enrolled in this course.';
$string['saveassessment'] = 'Save assessment';
$string['assessmentsaved'] = 'Assessment saved successfully.';

// Errors.
$string['erroraddingitem'] = 'Error adding item to checklist.';
$string['errordeletingitem'] = 'Error deleting item from checklist.';
$string['errorsavingassessment'] = 'Error saving assessment.';
$string['itemnotfound'] = 'Checklist item not found.';
$string['studentnotfound'] = 'Student not found.';
