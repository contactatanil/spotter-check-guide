
<?php
// This file is part of Moodle - http://moodle.org/

/**
 * observationchecklist external functions and service definitions.
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'mod_observationchecklist_add_item' => array(
        'classname'   => 'mod_observationchecklist\external\add_item',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Add a new checklist item',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/observationchecklist:edit',
    ),
    'mod_observationchecklist_delete_item' => array(
        'classname'   => 'mod_observationchecklist\external\delete_item',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Delete a checklist item',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/observationchecklist:edit',
    ),
    'mod_observationchecklist_assess_item' => array(
        'classname'   => 'mod_observationchecklist\external\assess_item',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Assess a checklist item',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities' => 'mod/observationchecklist:assess',
    ),
    'mod_observationchecklist_get_user_progress' => array(
        'classname'   => 'mod_observationchecklist\external\get_user_progress',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Get user progress for a checklist',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'mod/observationchecklist:view',
    ),
    'mod_observationchecklist_generate_report' => array(
        'classname'   => 'mod_observationchecklist\external\generate_report',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Generate a printable report',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'mod/observationchecklist:viewreports',
    ),
    'mod_observationchecklist_export_report' => array(
        'classname'   => 'mod_observationchecklist\external\export_report',
        'methodname'  => 'execute',
        'classpath'   => '',
        'description' => 'Export report data',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities' => 'mod/observationchecklist:export',
    ),
);

$services = array(
    'Observation Checklist Services' => array(
        'functions' => array(
            'mod_observationchecklist_add_item',
            'mod_observationchecklist_delete_item',
            'mod_observationchecklist_assess_item',
            'mod_observationchecklist_get_user_progress',
            'mod_observationchecklist_generate_report',
            'mod_observationchecklist_export_report'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
    )
);
