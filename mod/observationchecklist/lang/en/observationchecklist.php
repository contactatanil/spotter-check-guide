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
$string['observationchecklistname'] = 'Observation checklist name';
$string['observationchecklistname_help'] = 'This is the content of the help tooltip associated with the name field.';
$string['name'] = 'Name';
$string['description'] = 'Description';
$string['itemtext'] = 'Item text';
$string['category'] = 'Category';
$string['additem'] = 'Add item';
$string['addnewitem'] = 'Add new item';
$string['assessmentitems'] = 'Assessment items';
$string['studentassessment'] = 'Student assessment';
$string['choosestudentmessage'] = 'Choose a student to assess:';

// Settings
$string['settings'] = 'Settings';
$string['allowstudentadd'] = 'Allow students to add items';
$string['allowstudentadd_help'] = 'If enabled, students can add their own items to the checklist.';
$string['allowstudentsubmit'] = 'Allow student submissions';
$string['allowstudentsubmit_help'] = 'If enabled, students can submit their checklist for assessment.';
$string['enableprinting'] = 'Enable printing';
$string['enableprinting_help'] = 'If enabled, users can print the checklist.';

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

// Reports
$string['reports'] = 'Reports';
$string['reportoverview'] = 'Overview';
$string['reportgrading'] = 'Grading';
$string['reportstatistics'] = 'Statistics';
$string['reportattempts'] = 'Attempts';
$string['reportprogress'] = 'Progress';
$string['reportcompetency'] = 'Competency';
$string['reporttrainer'] = 'Trainer';
$string['reportlibrary'] = 'Report Library';

// Report content
$string['totalitems'] = 'Total items';
$string['totalusers'] = 'Total users';
$string['usersstarted'] = 'Users started';
$string['completionrate'] = 'Completion rate';
$string['itemsassessed'] = 'Items assessed';
$string['lastactivity'] = 'Last activity';
$string['assessmentactivity'] = 'Assessment activity';
$string['categorystatistics'] = 'Category statistics';
$string['itemstatistics'] = 'Item statistics';
$string['progressreport'] = 'Progress report';
$string['competencyanalysis'] = 'Competency analysis';
$string['traineractivity'] = 'Trainer activity';
$string['assessmentsmade'] = 'Assessments made';
$string['satisfactorygiven'] = 'Satisfactory given';
$string['notsatisfactorygiven'] = 'Not satisfactory given';
$string['firstassessment'] = 'First assessment';
$string['lastassessment'] = 'Last assessment';
$string['dailyactivity'] = 'Daily activity';

// Export
$string['export'] = 'Export';
$string['exportcsv'] = 'Export CSV';
$string['exportexcel'] = 'Export Excel';
$string['exportpdf'] = 'Export PDF';
$string['exportcomplete'] = 'Export completed successfully';

// Capabilities
$string['observationchecklist:export'] = 'Export observation checklist reports';
$string['observationchecklist:viewreports'] = 'View observation checklist reports';

// Filters
$string['filterbyuser'] = 'Filter by user';
$string['filterbygroup'] = 'Filter by group';
$string['selectuser'] = 'Select user';
$string['selectgroup'] = 'Select group';
$string['allgroups'] = 'All groups';
$string['nodata'] = 'No data available';

// Chart labels
$string['completionchart'] = 'Completion chart';
$string['statusdistribution'] = 'Status distribution';
$string['progresschart'] = 'Progress chart';
$string['activitychart'] = 'Activity chart';

// Additional strings for proper functionality
$string['noobservationchecklists'] = 'No observation checklists found in this course.';
$string['itemcount'] = 'Item count';
$string['totalattempts'] = 'Total attempts';
$string['assessor'] = 'Assessor';
