
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * The main mod_observationchecklist configuration form.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_observationchecklist_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('observationchecklistname', 'observationchecklist'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'observationchecklistname', 'observationchecklist');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Settings fieldset
        $mform->addElement('header', 'observationchecklistsettings', get_string('settings', 'observationchecklist'));

        // Allow students to add items
        $mform->addElement('advcheckbox', 'allowstudentadd', get_string('allowstudentadd', 'observationchecklist'));
        $mform->addHelpButton('allowstudentadd', 'allowstudentadd', 'observationchecklist');
        $mform->setDefault('allowstudentadd', 1);

        // Allow student submissions
        $mform->addElement('advcheckbox', 'allowstudentsubmit', get_string('allowstudentsubmit', 'observationchecklist'));
        $mform->addHelpButton('allowstudentsubmit', 'allowstudentsubmit', 'observationchecklist');
        $mform->setDefault('allowstudentsubmit', 1);

        // Enable printing
        $mform->addElement('advcheckbox', 'enableprinting', get_string('enableprinting', 'observationchecklist'));
        $mform->addHelpButton('enableprinting', 'enableprinting', 'observationchecklist');
        $mform->setDefault('enableprinting', 1);

        // Grade settings
        $this->standard_grading_coursemodule_elements();

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    /**
     * Perform minimal validation on the settings form
     * @param array $data
     * @param array $files
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
