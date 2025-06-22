
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Backup steps for mod_observationchecklist.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete observation checklist structure for backup, with file and id annotations.
 */
class backup_observationchecklist_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $observationchecklist = new backup_nested_element('observationchecklist', array('id'), array(
            'name', 'intro', 'introformat', 'description', 'allowstudentadd', 
            'allowstudentsubmit', 'enableprinting', 'timecreated', 'timemodified'));

        $items = new backup_nested_element('items');

        $item = new backup_nested_element('item', array('id'), array(
            'itemtext', 'category', 'userid', 'sortorder', 'timecreated'));

        $useritems = new backup_nested_element('useritems');

        $useritem = new backup_nested_element('useritem', array('id'), array(
            'itemid', 'userid', 'status', 'assessornotes', 'assessorid', 
            'dateassessed', 'timecreated', 'timemodified'));

        // Build the tree.
        $observationchecklist->add_child($items);
        $items->add_child($item);

        $observationchecklist->add_child($useritems);
        $useritems->add_child($useritem);

        // Define sources.
        $observationchecklist->set_source_table('observationchecklist', array('id' => backup::VAR_ACTIVITYID));

        $item->set_source_table('observationchecklist_items', array('checklistid' => backup::VAR_PARENTID));

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $useritem->set_source_table('observationchecklist_user_items', array('checklistid' => backup::VAR_PARENTID));
        }

        // Define id annotations.
        $item->annotate_ids('user', 'userid');
        $useritem->annotate_ids('user', 'userid');
        $useritem->annotate_ids('user', 'assessorid');

        // Define file annotations.
        $observationchecklist->annotate_files('mod_observationchecklist', 'intro', null);

        // Return the root element (observationchecklist), wrapped into standard activity structure.
        return $this->prepare_activity_structure($observationchecklist);
    }
}
