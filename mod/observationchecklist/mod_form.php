
<?php
// This file is part of Moodle - http://moodle.org/

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

class mod_observationchecklist_mod_form extends moodleform_mod {

    public function definition() {
        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('checklistname', 'mod_observationchecklist'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'checklistname', 'mod_observationchecklist');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Add custom fields
        $mform->addElement('textarea', 'description', get_string('description', 'mod_observationchecklist'), 'wrap="virtual" rows="5" cols="50"');
        $mform->setType('description', PARAM_TEXT);
        $mform->addHelpButton('description', 'description', 'mod_observationchecklist');

        // Adding the rest of mod_observationchecklist settings, spreading all them into this fieldset.
        $mform->addElement('header', 'observationchecklistfieldset', get_string('observationchecklistfieldset', 'mod_observationchecklist'));

        $mform->addElement('selectyesno', 'allowstudentadd', get_string('allowstudentadd', 'mod_observationchecklist'));
        $mform->setDefault('allowstudentadd', 1);
        $mform->addHelpButton('allowstudentadd', 'allowstudentadd', 'mod_observationchecklist');

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        return $errors;
    }
}
