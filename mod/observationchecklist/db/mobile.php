
<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$addons = array(
    'mod_observationchecklist' => array(
        'handlers' => array(
            'observationchecklist' => array(
                'displaydata' => array(
                    'icon' => $CFG->wwwroot . '/mod/observationchecklist/pix/icon.png',
                    'class' => '',
                ),
                'delegate' => 'CoreCourseModuleDelegate',
                'method' => 'mobile_course_view',
                'offlinefunctions' => array(
                    'mobile_course_view' => array(),
                ),
            )
        ),
        'lang' => array(
            array('pluginname', 'mod_observationchecklist'),
            array('checklistitems', 'mod_observationchecklist'),
            array('progress', 'mod_observationchecklist'),
        ),
    )
);

