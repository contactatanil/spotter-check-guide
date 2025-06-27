
<?php
// This file is part of Moodle - http://moodle.org/

/**
 * The main observationchecklist configuration form
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form
 */
class mod_observationchecklist_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are showed
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('observationchecklistname', 'observationchecklist'), array('size'=>'64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'observationchecklistname', 'observationchecklist');

        // Adding the standard "intro" and "introformat" fields
        $this->standard_intro_elements();

        // Add custom settings
        $mform->addElement('header', 'observationchecklistsettings', get_string('settings'));

        $mform->addElement('selectyesno', 'allowstudentadd', get_string('allowstudentadd', 'observationchecklist'));
        $mform->setDefault('allowstudentadd', 1);
        $mform->addHelpButton('allowstudentadd', 'allowstudentadd', 'observationchecklist');

        $mform->addElement('selectyesno', 'allowstudentsubmit', get_string('allowstudentsubmit', 'observationchecklist'));
        $mform->setDefault('allowstudentsubmit', 1);
        $mform->addHelpButton('allowstudentsubmit', 'allowstudentsubmit', 'observationchecklist');

        $mform->addElement('selectyesno', 'enableprinting', get_string('enableprinting', 'observationchecklist'));
        $mform->setDefault('enableprinting', 1);
        $mform->addHelpButton('enableprinting', 'enableprinting', 'observationchecklist');

        // Add standard elements, common to all modules
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules
        $this->add_action_buttons();
    }
}
