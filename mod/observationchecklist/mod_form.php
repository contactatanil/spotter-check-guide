
<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_observationchecklist_mod_form extends moodleform_mod {

    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // General settings
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        // Observation checklist specific settings
        $mform->addElement('header', 'observationsettings', get_string('observationsettings', 'mod_observationchecklist'));

        $mform->addElement('textarea', 'description', get_string('description', 'mod_observationchecklist'), 
            array('rows' => 10, 'cols' => 60));
        $mform->setType('description', PARAM_TEXT);
        $mform->addHelpButton('description', 'description', 'mod_observationchecklist');

        $mform->addElement('selectyesno', 'allowstudentadd', get_string('allowstudentadd', 'mod_observationchecklist'));
        $mform->setDefault('allowstudentadd', 1);
        $mform->addHelpButton('allowstudentadd', 'allowstudentadd', 'mod_observationchecklist');

        $mform->addElement('selectyesno', 'allowstudentsubmit', get_string('allowstudentsubmit', 'mod_observationchecklist'));
        $mform->setDefault('allowstudentsubmit', 1);
        $mform->addHelpButton('allowstudentsubmit', 'allowstudentsubmit', 'mod_observationchecklist');

        $mform->addElement('selectyesno', 'enableprinting', get_string('enableprinting', 'mod_observationchecklist'));
        $mform->setDefault('enableprinting', 1);
        $mform->addHelpButton('enableprinting', 'enableprinting', 'mod_observationchecklist');

        // Standard elements
        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }
}
