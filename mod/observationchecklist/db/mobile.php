
<?php
// This file is part of Moodle - http://moodle.org/

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
                'offlinefunctions' => [],
            ],
        ],
        'lang' => [
            ['pluginname', 'mod_observationchecklist'],
            ['checklistitems', 'mod_observationchecklist'],
        ],
    ],
];
