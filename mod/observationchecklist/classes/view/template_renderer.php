
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\view;

defined('MOODLE_INTERNAL') || die();

use mod_observationchecklist\data\template_data_provider;

/**
 * Template renderer for observation checklist
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_renderer {
    
    /** @var template_data_provider Data provider */
    private $dataprovider;
    
    /** @var object Output renderer */
    private $output;
    
    /**
     * Constructor
     */
    public function __construct($dataprovider, $output) {
        $this->dataprovider = $dataprovider;
        $this->output = $output;
    }
    
    /**
     * Render appropriate template based on user capabilities
     */
    public function render_for_user($canassess, $canedit, $cansubmit) {
        // Get base template context
        $templatecontext = $this->dataprovider->get_base_context($canassess, $canedit, $cansubmit);
        
        // Add statistics
        $templatecontext['stats'] = $this->dataprovider->get_statistics(
            $templatecontext['items'], 
            $templatecontext['progress']
        );
        
        if ($canassess) {
            return $this->render_assessor_interface($templatecontext);
        } else if ($cansubmit) {
            return $this->render_student_progress($templatecontext);
        } else {
            return $this->render_overview($templatecontext);
        }
    }
    
    /**
     * Render assessor interface
     */
    private function render_assessor_interface($templatecontext) {
        $templatecontext = $this->dataprovider->get_assessor_context($templatecontext);
        return $this->output->render_from_template('mod_observationchecklist/assessment_interface', $templatecontext);
    }
    
    /**
     * Render student progress interface
     */
    private function render_student_progress($templatecontext) {
        $templatecontext = $this->dataprovider->get_student_context(
            $templatecontext, 
            $templatecontext['items'], 
            $templatecontext['progress']
        );
        return $this->output->render_from_template('mod_observationchecklist/student_progress', $templatecontext);
    }
    
    /**
     * Render overview interface
     */
    private function render_overview($templatecontext) {
        return $this->output->render_from_template('mod_observationchecklist/checklist_overview', $templatecontext);
    }
}
