
<?php
// This file is part of Moodle - http://moodle.org/

$string['pluginname'] = 'Observation Checklist';
$string['modulename'] = 'Observation Checklist';
$string['modulenameplural'] = 'Observation Checklists';
$string['pluginadministration'] = 'Observation Checklist administration';

// Form strings
$string['name'] = 'Name';
$string['description'] = 'Description';
$string['description_help'] = 'Enter a description for this observation checklist.';
$string['allowstudentadd'] = 'Allow students to add items';
$string['allowstudentadd_help'] = 'If enabled, students can add their own items to the checklist.';
$string['allowstudentsubmit'] = 'Allow student submissions';
$string['allowstudentsubmit_help'] = 'If enabled, students can submit evidence for assessment.';
$string['enableprinting'] = 'Enable printing';
$string['enableprinting_help'] = 'If enabled, users can print observation reports.';

// View strings
$string['observationsettings'] = 'Observation Settings';
$string['noobservationchecklists'] = 'No observation checklists';
$string['checklistitems'] = 'Checklist Items';
$string['newitemtext'] = 'Enter new item text';
$string['additem'] = 'Add Item';
$string['noitems'] = 'No items have been added to this checklist yet.';
$string['confirmdelete'] = 'Are you sure you want to delete this item?';

// Status strings
$string['not_started'] = 'Not Started';
$string['in_progress'] = 'In Progress';
$string['satisfactory'] = 'Satisfactory';
$string['not_satisfactory'] = 'Not Satisfactory';

// Action strings
$string['itemadded'] = 'Item added successfully';
$string['itemdeleted'] = 'Item deleted successfully';
$string['assessmentadded'] = 'Assessment added successfully';

// Capabilities
$string['observationchecklist:addinstance'] = 'Add a new observation checklist';
$string['observationchecklist:view'] = 'View observation checklist';
$string['observationchecklist:edit'] = 'Edit observation checklist';
$string['observationchecklist:assess'] = 'Assess student observations';
$string['observationchecklist:submit'] = 'Submit evidence for assessment';
$string['observationchecklist:viewreports'] = 'View observation reports';

// Privacy
$string['privacy:metadata:observationchecklist_items'] = 'Information about checklist items created by users.';
$string['privacy:metadata:observationchecklist_items:userid'] = 'The ID of the user who created the item.';
$string['privacy:metadata:observationchecklist_items:itemtext'] = 'The text of the checklist item.';
$string['privacy:metadata:observationchecklist_items:timecreated'] = 'The time when the item was created.';

$string['privacy:metadata:observationchecklist_user_items'] = 'Information about user progress on checklist items.';
$string['privacy:metadata:observationchecklist_user_items:userid'] = 'The ID of the user.';
$string['privacy:metadata:observationchecklist_user_items:status'] = 'The completion status of the item.';
$string['privacy:metadata:observationchecklist_user_items:assessornotes'] = 'Notes added by the assessor.';
$string['privacy:metadata:observationchecklist_user_items:dateassessed'] = 'The date when the item was assessed.';
