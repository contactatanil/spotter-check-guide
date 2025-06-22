
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Behat step definitions for mod_observationchecklist.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode;

/**
 * Behat step definitions for the observation checklist activity.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_observationchecklist extends behat_base {

    /**
     * Adds a checklist item to the observation checklist.
     *
     * @Given /^I add a checklist item "([^"]*)" to the observation checklist$/
     * @param string $itemtext
     */
    public function i_add_a_checklist_item_to_the_observation_checklist($itemtext) {
        $this->execute('behat_forms::i_set_the_field_to', ['Add new item', $itemtext]);
        $this->execute('behat_general::i_click_on', ['Add item', 'button']);
    }

    /**
     * Assesses a checklist item for a student.
     *
     * @Given /^I assess the item "([^"]*)" as "([^"]*)" for student "([^"]*)"$/
     * @param string $itemtext
     * @param string $status
     * @param string $student
     */
    public function i_assess_the_item_as_for_student($itemtext, $status, $student) {
        $xpath = "//tr[contains(., '$student')]//td[contains(., '$itemtext')]//select";
        $this->execute('behat_forms::i_set_the_field_with_xpath_to', [$xpath, $status]);
    }
}
