
<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'mod_observationchecklist_add_item' => array(
        'classname'   => 'mod_observationchecklist\\external\\add_item',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Add a new item to the checklist',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/observationchecklist:edit',
    ),

    'mod_observationchecklist_assess_item' => array(
        'classname'   => 'mod_observationchecklist\\external\\assess_item',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Assess a checklist item for a student',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/observationchecklist:assess',
    ),

    'mod_observationchecklist_delete_item' => array(
        'classname'   => 'mod_observationchecklist\\external\\delete_item',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Delete a checklist item',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/observationchecklist:edit',
    ),

    'mod_observationchecklist_get_user_progress' => array(
        'classname'   => 'mod_observationchecklist\\external\\get_user_progress',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get user progress for checklist items',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'mod/observationchecklist:view',
    ),

    'mod_observationchecklist_get_all_progress' => array(
        'classname'   => 'mod_observationchecklist\\external\\get_all_progress',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get all students progress for a checklist',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'mod/observationchecklist:view',
    ),

    'mod_observationchecklist_generate_report' => array(
        'classname'   => 'mod_observationchecklist\\external\\generate_report',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Generate a printable report for a student',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'mod/observationchecklist:view',
    ),

    'mod_observationchecklist_save_multi_observations' => array(
        'classname'   => 'mod_observationchecklist\external\save_multi_observations',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Save multiple student observations',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/observationchecklist:assess',
    ),
);
