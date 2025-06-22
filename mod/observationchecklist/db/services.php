
<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'mod_observationchecklist_mobile_course_view' => array(
        'classname' => 'mod_observationchecklist\external\mobile',
        'methodname' => 'mobile_course_view',
        'description' => 'Get observation checklist data for mobile app',
        'type' => 'read',
        'ajax' => true,
        'loginrequired' => true,
    ),
);

$services = array(
    'Observation Checklist Mobile Service' => array(
        'functions' => array(
            'mod_observationchecklist_mobile_course_view'
        ),
        'restrictedusers' => 0,
        'enabled' => 1,
    ),
);

