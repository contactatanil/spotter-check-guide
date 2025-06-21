
<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

/**
 * Define all the backup steps that will be used by the backup_observationchecklist_activity_task
 */
class backup_observationchecklist_activity_structure_step extends backup_activity_structure_step {

    protected function define_structure() {

        // To know if we are including userinfo
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated
        $observationchecklist = new backup_nested_element('observationchecklist', array('id'), array(
            'name', 'intro', 'introformat', 'description', 'allowstudentadd',
            'timecreated', 'timemodified'));

        $items = new backup_nested_element('items');

        $item = new backup_nested_element('item', array('id'), array(
            'itemtext', 'userid', 'sortorder', 'timecreated'));

        $useritems = new backup_nested_element('useritems');

        $useritem = new backup_nested_element('useritem', array('id'), array(
            'itemid', 'userid', 'checked', 'timecreated', 'timemodified'));

        // Build the tree
        $observationchecklist->add_child($items);
        $items->add_child($item);

        $observationchecklist->add_child($useritems);
        $useritems->add_child($useritem);

        // Define sources
        $observationchecklist->set_source_table('observationchecklist', array('id' => backup::VAR_ACTIVITYID));

        $item->set_source_table('observationchecklist_items', array('checklistid' => backup::VAR_PARENTID));

        // All the rest of elements only happen if we are including user info
        if ($userinfo) {
            $useritem->set_source_table('observationchecklist_user_items', array('checklistid' => backup::VAR_PARENTID));
        }

        // Define id annotations
        $item->annotate_ids('user', 'userid');
        $useritem->annotate_ids('user', 'userid');

        // Define file annotations
        $observationchecklist->annotate_files('mod_observationchecklist', 'intro', null);

        // Return the root element (observationchecklist), wrapped into standard activity structure
        return $this->prepare_activity_structure($observationchecklist);
    }
}
