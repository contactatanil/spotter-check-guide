
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
$string['observationchecklist:edit'] = 'Edit observation checklist';
$string['observationchecklist:assess'] = 'Assess students';
$string['observationchecklist:submit'] = 'Submit to checklist';

// Form fields
$string['checklistname'] = 'Checklist name';
$string['description'] = 'Description';
$string['itemtext'] = 'Item text';
$string['category'] = 'Category';
$string['additem'] = 'Add item';
$string['deleteitem'] = 'Delete item';
$string['edititem'] = 'Edit item';

// Assessment
$string['assess'] = 'Assess';
$string['assessment'] = 'Assessment';
$string['studentassessment'] = 'Student Assessment';
$string['individualstudentassessment'] = 'Individual Student Assessment';
$string['multistudentobservation'] = 'Multi-Student Observation';
$string['selectstudent'] = 'Select Student';
$string['choosestudent'] = 'Choose a student';
$string['choosestudentmessage'] = 'Choose a student from the dropdown above to begin assessment';
$string['assessmentitems'] = 'Assessment Items';
$string['saveallassessments'] = 'Save All Assessments';
$string['assessmentstatus'] = 'Assessment Status';
$string['assessornotes'] = 'Assessor Notes';
$string['addassessmentnotes'] = 'Add your assessment notes...';
$string['maxcharacters'] = 'Maximum characters';

// Statuses
$string['notstarted'] = 'Not Started';
$string['inprogress'] = 'In Progress';
$string['satisfactory'] = 'Satisfactory';
$string['notsatisfactory'] = 'Not Satisfactory';
$string['progress'] = 'Progress';

// Messages
$string['itemadded'] = 'Item added successfully';
$string['itemdeleted'] = 'Item deleted successfully';
$string['assessmentadded'] = 'Assessment added successfully';
$string['observationsaved'] = 'Observation saved successfully';
$string['noitemsfound'] = 'No items found';
$string['nostudentsfound'] = 'No students found';

// Errors
$string['invaliditemid'] = 'Invalid item ID';
$string['invaliduserid'] = 'Invalid user ID';
$string['invalidstatus'] = 'Invalid status';
$string['notestoolong'] = 'Notes too long (maximum 1000 characters)';
$string['missingdata'] = 'Missing required data';
$string['databaseerror'] = 'Database error occurred';

// Reports
$string['generatereport'] = 'Generate Report';
$string['printreport'] = 'Print Report';
$string['completionrate'] = 'Completion Rate';
$string['totalitems'] = 'Total Items';
$string['assesseditems'] = 'Assessed Items';

// Default strings for configuration
$string['defaultstatuses'] = 'Default assessment statuses';
$string['defaultstatuses_desc'] = 'Comma-separated list of default assessment statuses available for assessors';
$string['defaultcategories'] = 'Default categories';
$string['defaultcategories_desc'] = 'Comma-separated list of default categories for organizing checklist items';

// Events
$string['eventassessmentmade'] = 'Assessment made';
$string['eventcoursemoduleviewed'] = 'Course module viewed';
$string['eventitemadded'] = 'Item added';

// Privacy
$string['privacy:metadata:observationchecklist_user_items'] = 'Information about user assessments in observation checklists';
$string['privacy:metadata:observationchecklist_user_items:userid'] = 'The ID of the user being assessed';
$string['privacy:metadata:observationchecklist_user_items:assessorid'] = 'The ID of the user making the assessment';
$string['privacy:metadata:observationchecklist_user_items:status'] = 'The assessment status';
$string['privacy:metadata:observationchecklist_user_items:assessornotes'] = 'Notes added by the assessor';
$string['privacy:metadata:observationchecklist_user_items:dateassessed'] = 'The date when the assessment was made';

// Mobile
$string['mobileapp'] = 'Mobile App';
$string['mobileappdesc'] = 'Use the mobile app to assess students on the go';
