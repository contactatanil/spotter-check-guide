
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * The main observationchecklist configuration form
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
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

        // Adding the "general" fieldset, where all the common settings are shown
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field
        $mform->addElement('text', 'name', get_string('checklistname', 'mod_observationchecklist'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'checklistname', 'mod_observationchecklist');

        // Adding the standard "intro" and "introformat" fields
        $this->standard_intro_elements();

        // Adding the description field
        $mform->addElement('editor', 'description_editor', get_string('description', 'mod_observationchecklist'), 
                          null, array('maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this->context));
        $mform->setType('description_editor', PARAM_RAW);
        $mform->addHelpButton('description_editor', 'description', 'mod_observationchecklist');

        // Add standard elements, common to all modules
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules
        $this->add_action_buttons();
    }

    /**
     * Allows modules to modify the data returned by form get_data()
     * This method is also called in the bulk activity completion form
     *
     * @param stdClass $data passed by reference
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        
        // Process description editor
        if (!empty($data->description_editor)) {
            $data->descriptionformat = $data->description_editor['format'];
            $data->description = $data->description_editor['text'];
        }
    }

    /**
     * Prepares the form before data are set
     *
     * @param  array $defaultvalues
     * @return void
     */
    public function data_preprocessing(&$defaultvalues) {
        if ($this->current->instance) {
            $draftitemid = file_get_submitted_draft_itemid('description_editor');
            $defaultvalues['description_editor']['text'] = file_prepare_draft_area(
                $draftitemid,
                $this->context->id,
                'mod_observationchecklist',
                'description',
                0,
                array('subdirs' => true),
                $defaultvalues['description']
            );
            $defaultvalues['description_editor']['format'] = $defaultvalues['descriptionformat'];
            $defaultvalues['description_editor']['itemid'] = $draftitemid;
        }
    }
}
