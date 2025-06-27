
<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Observation Checklist';
$string['modulenameplural'] = 'Observation Checklists';
$string['pluginname'] = 'Observation Checklist';
$string['pluginadministration'] = 'Observation Checklist administration';

// Capabilities
$string['observationchecklist:addinstance'] = 'Add a new observation checklist';
$string['observationchecklist:view'] = 'View observation checklist';
$string['observationchecklist:edit'] = 'Edit observation checklist items';
$string['observationchecklist:assess'] = 'Assess students';
$string['observationchecklist:submit'] = 'Submit observations';
$string['observationchecklist:viewreports'] = 'View reports';

// Form elements
$string['checklistname'] = 'Checklist name';
$string['checklistname_help'] = 'The name of this observation checklist';
$string['description'] = 'Description';
$string['description_help'] = 'A description of this observation checklist';
$string['settings'] = 'Settings';

// Settings
$string['allowstudentadd'] = 'Allow students to add items';
$string['allowstudentadd_help'] = 'Allow students to add their own items to the checklist';
$string['allowstudentsubmit'] = 'Allow student submission';
$string['allowstudentsubmit_help'] = 'Allow students to submit their own progress';
$string['enableprinting'] = 'Enable printing';
$string['enableprinting_help'] = 'Enable printing of progress reports';

// Items
$string['addnewitem'] = 'Add new item';
$string['additem'] = 'Add item';
$string['itemtext'] = 'Item text';
$string['category'] = 'Category';
$string['assessmentitems'] = 'Assessment items';
$string['itemadded'] = 'Item added successfully';
$string['itemdeleted'] = 'Item deleted successfully';
$string['confirmdeleteitem'] = 'Are you sure you want to delete this item?';
$string['delete'] = 'Delete';
$string['noitemsfound'] = 'No items found. Add some items to get started.';
$string['noobservationchecklists'] = 'No observation checklists found in this course.';

// Assessment
$string['studentassessment'] = 'Student Assessment';
$string['choosestudentmessage'] = 'Choose a student to assess:';
$string['status'] = 'Status';
$string['assessornotes'] = 'Assessor Notes';
$string['notstarted'] = 'Not Started';
$string['inprogress'] = 'In Progress';
$string['satisfactory'] = 'Satisfactory';
$string['notsatisfactory'] = 'Not Satisfactory';
$string['save'] = 'Save';
$string['back'] = 'Back';
$string['observationsaved'] = 'Observations saved successfully';
$string['assessmentadded'] = 'Assessment added successfully';

// Multi-student
$string['multiobservationssaved'] = '{$a} observations saved successfully';
$string['noobservationssaved'] = 'No observations were saved';

// Events
$string['eventcoursemoduleviewed'] = 'Course module viewed';
$string['eventitemadded'] = 'Item added';
$string['eventassessmentmade'] = 'Assessment made';

// Privacy
$string['privacy:metadata:observationchecklist_user_items'] = 'Information about user progress on checklist items';
$string['privacy:metadata:observationchecklist_user_items:userid'] = 'The ID of the user';
$string['privacy:metadata:observationchecklist_user_items:status'] = 'The status of the assessment';
$string['privacy:metadata:observationchecklist_user_items:assessornotes'] = 'Notes from the assessor';
$string['privacy:metadata:observationchecklist_user_items:dateassessed'] = 'The date of assessment';
