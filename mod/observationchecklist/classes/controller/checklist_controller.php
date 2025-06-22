
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\controller;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/observationchecklist/locallib.php');

use mod_observationchecklist\event\item_added;
use mod_observationchecklist\event\assessment_made;
use mod_observationchecklist\message\notification_manager;

/**
 * Controller for handling checklist actions
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checklist_controller {
    
    /** @var object Course module record */
    private $cm;
    
    /** @var object Course record */
    private $course;
    
    /** @var object Checklist instance */
    private $checklist;
    
    /** @var object Context */
    private $context;
    
    /** @var object PAGE */
    private $page;
    
    /**
     * Constructor
     */
    public function __construct($cm, $course, $checklist, $context, $page) {
        $this->cm = $cm;
        $this->course = $course;
        $this->checklist = $checklist;
        $this->context = $context;
        $this->page = $page;
    }
    
    /**
     * Handle form actions
     */
    public function handle_action($action, $itemid = 0) {
        global $USER;
        
        if (!$action || !confirm_sesskey()) {
            return false;
        }
        
        switch ($action) {
            case 'additem':
                return $this->handle_add_item();
                
            case 'assess':
                return $this->handle_assess_item($itemid);
                
            case 'deleteitem':
                return $this->handle_delete_item($itemid);
                
            default:
                return false;
        }
    }
    
    /**
     * Handle multi-student actions
     */
    public function handle_multi_student_action($action, $studentids = []) {
        global $USER;
        
        if (!$action || !confirm_sesskey()) {
            return false;
        }
        
        switch ($action) {
            case 'save_multi_observations':
                return $this->handle_save_multi_observations();
                
            default:
                return false;
        }
    }
    
    /**
     * Handle save multiple observations
     */
    private function handle_save_multi_observations() {
        global $DB;
        
        require_capability('mod/observationchecklist:assess', $this->context);
        
        $observations = required_param('observations', PARAM_RAW);
        $observations = json_decode($observations, true);
        
        if (!$observations || !is_array($observations)) {
            return false;
        }
        
        $success_count = 0;
        
        foreach ($observations as $observation) {
            if (isset($observation['studentId'], $observation['itemId'], $observation['status'])) {
                $itemid = (int)$observation['itemId'];
                $studentid = (int)$observation['studentId'];
                $status = clean_param($observation['status'], PARAM_ALPHA);
                $notes = isset($observation['notes']) ? clean_param($observation['notes'], PARAM_TEXT) : '';
                
                if (observationchecklist_assess_item($itemid, $studentid, $status, $notes, $GLOBALS['USER']->id)) {
                    $success_count++;
                    
                    // Trigger event for each assessment
                    $event = assessment_made::create(array(
                        'objectid' => $itemid,
                        'context' => $this->context,
                        'relateduserid' => $studentid,
                        'other' => array(
                            'status' => $status,
                            'checklistid' => $this->checklist->id,
                            'multi_student' => true
                        )
                    ));
                    $event->trigger();
                }
            }
        }
        
        if ($success_count > 0) {
            redirect($this->page->url, get_string('multiobservationssaved', 'mod_observationchecklist', $success_count));
        } else {
            redirect($this->page->url, get_string('noobservationssaved', 'mod_observationchecklist'), null, \core\output\notification::NOTIFY_ERROR);
        }
    }
    
    /**
     * Handle add item action
     */
    private function handle_add_item() {
        global $DB;
        
        require_capability('mod/observationchecklist:edit', $this->context);
        
        $itemtext = required_param('itemtext', PARAM_TEXT);
        $category = optional_param('category', 'General', PARAM_TEXT);
        
        $itemid = observationchecklist_add_item($this->checklist->id, $itemtext, $category, $GLOBALS['USER']->id);
        
        // Trigger event.
        $event = item_added::create(array(
            'objectid' => $itemid,
            'context' => $this->context,
            'other' => array(
                'checklistid' => $this->checklist->id,
                'itemtext' => $itemtext
            )
        ));
        $event->trigger();
        
        redirect($this->page->url, get_string('itemadded', 'mod_observationchecklist'));
    }
    
    /**
     * Handle assess item action
     */
    private function handle_assess_item($itemid) {
        global $DB;
        
        require_capability('mod/observationchecklist:assess', $this->context);
        
        $studentid = required_param('studentid', PARAM_INT);
        $status = required_param('status', PARAM_ALPHA);
        $notes = optional_param('notes', '', PARAM_TEXT);
        
        observationchecklist_assess_item($itemid, $studentid, $status, $notes, $GLOBALS['USER']->id);
        
        // Trigger event.
        $event = assessment_made::create(array(
            'objectid' => $itemid,
            'context' => $this->context,
            'relateduserid' => $studentid,
            'other' => array(
                'status' => $status,
                'checklistid' => $this->checklist->id
            )
        ));
        $event->trigger();
        
        // Send notification.
        $student = $DB->get_record('user', array('id' => $studentid));
        $item = $DB->get_record('observationchecklist_items', array('id' => $itemid));
        notification_manager::send_assessment_notification($student, $this->checklist, $item, $status);
        
        redirect($this->page->url, get_string('assessmentadded', 'mod_observationchecklist'));
    }
    
    /**
     * Handle delete item action
     */
    private function handle_delete_item($itemid) {
        require_capability('mod/observationchecklist:edit', $this->context);
        
        observationchecklist_delete_item($itemid);
        redirect($this->page->url, get_string('itemdeleted', 'mod_observationchecklist'));
    }
}
