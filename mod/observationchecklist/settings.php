
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Plugin administration pages are defined here.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Default assessment statuses.
    $settings->add(new admin_setting_configtext(
        'mod_observationchecklist/defaultstatuses',
        get_string('defaultstatuses', 'mod_observationchecklist'),
        get_string('defaultstatuses_desc', 'mod_observationchecklist'),
        'not_started,in_progress,satisfactory,not_satisfactory',
        PARAM_TEXT
    ));

    // Default categories.
    $settings->add(new admin_setting_configtext(
        'mod_observationchecklist/defaultcategories',
        get_string('defaultcategories', 'mod_observationchecklist'),
        get_string('defaultcategories_desc', 'mod_observationchecklist'),
        'General,Skills,Knowledge,Attitude',
        PARAM_TEXT
    ));

    // Enable notifications.
    $settings->add(new admin_setting_configcheckbox(
        'mod_observationchecklist/enablenotifications',
        get_string('enablenotifications', 'mod_observationchecklist'),
        get_string('enablenotifications_desc', 'mod_observationchecklist'),
        1
    ));
}

