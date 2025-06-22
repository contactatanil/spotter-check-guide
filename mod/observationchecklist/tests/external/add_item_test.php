
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Unit tests for mod_observationchecklist add_item external service.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_observationchecklist\external;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * Unit tests for add_item external service.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_item_test extends \externallib_advanced_testcase {

    /**
     * Test adding a checklist item.
     */
    public function test_add_item() {
        global $DB;
        
        $this->resetAfterTest(true);

        // Create test data.
        $course = $this->getDataGenerator()->create_course();
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course->id, 'editingteacher');
        
        $checklist = $this->getDataGenerator()->create_module('observationchecklist', 
            ['course' => $course->id, 'name' => 'Test Checklist']);
        $cm = get_coursemodule_from_instance('observationchecklist', $checklist->id);

        // Set user and test permissions.
        $this->setUser($teacher);
        $context = \context_module::instance($cm->id);
        $this->assertTrue(has_capability('mod/observationchecklist:edit', $context));

        // Test adding an item.
        $result = add_item::execute($cm->id, 'Test item text');
        
        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('itemid', $result);
        
        // Verify item was created in database.
        $item = $DB->get_record('observationchecklist_items', ['id' => $result['itemid']]);
        $this->assertNotFalse($item);
        $this->assertEquals('Test item text', $item->itemtext);
        $this->assertEquals($checklist->id, $item->checklistid);
    }

    /**
     * Test adding item without permission.
     */
    public function test_add_item_no_permission() {
        $this->resetAfterTest(true);

        // Create test data.
        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student->id, $course->id, 'student');
        
        $checklist = $this->getDataGenerator()->create_module('observationchecklist', 
            ['course' => $course->id]);
        $cm = get_coursemodule_from_instance('observationchecklist', $checklist->id);

        // Set user without edit permission.
        $this->setUser($student);

        // Test should throw exception.
        $this->expectException(\required_capability_exception::class);
        add_item::execute($cm->id, 'Test item');
    }
}
