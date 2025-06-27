
<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Plugin administration pages are defined here.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('mod_observationchecklist/pluginname',
        new lang_string('pluginname', 'mod_observationchecklist'), ''));

    $settings->add(new admin_setting_configcheckbox('mod_observationchecklist/defaultallowstudentadd',
        new lang_string('allowstudentadd', 'mod_observationchecklist'),
        new lang_string('allowstudentadd_help', 'mod_observationchecklist'), 1));

    $settings->add(new admin_setting_configcheckbox('mod_observationchecklist/defaultallowstudentsubmit',
        new lang_string('allowstudentsubmit', 'mod_observationchecklist'),
        new lang_string('allowstudentsubmit_help', 'mod_observationchecklist'), 1));

    $settings->add(new admin_setting_configcheckbox('mod_observationchecklist/defaultenableprinting',
        new lang_string('enableprinting', 'mod_observationchecklist'),
        new lang_string('enableprinting_help', 'mod_observationchecklist'), 1));
}
