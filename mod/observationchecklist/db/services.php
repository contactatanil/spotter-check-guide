
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

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
);

$services = array(
    'observationchecklist' => array(
        'functions' => array(
            'mod_observationchecklist_add_item',
            'mod_observationchecklist_delete_item',
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
    ),
);
