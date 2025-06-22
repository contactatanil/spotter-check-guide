
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Mobile support for mod_observationchecklist.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'mod_observationchecklist' => [
        'handlers' => [
            'observationchecklist' => [
                'displaydata' => [
                    'icon' => $CFG->wwwroot . '/mod/observationchecklist/pix/icon.png',
                    'class' => '',
                ],
                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'mobile_course_view',
                'offlinefunctions' => [
                    'mobile_course_view' => [],
                ],
            ],
        ],
        'lang' => [
            ['pluginname', 'mod_observationchecklist'],
            ['modulename', 'mod_observationchecklist'],
        ],
    ],
];
