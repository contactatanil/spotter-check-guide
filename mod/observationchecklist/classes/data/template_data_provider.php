
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\data;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/mod/observationchecklist/locallib.php');

/**
 * Data provider for templates
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class template_data_provider {
    
    /** @var object Checklist instance */
    private $checklist;
    
    /** @var object Course */
    private $course;
    
    /** @var object Course module */
    private $cm;
    
    /** @var object Context */
    private $context;
    
    /** @var object PAGE */
    private $page;
    
    /**
     * Constructor
     */
    public function __construct($checklist, $course, $cm, $context, $page) {
        $this->checklist = $checklist;
        $this->course = $course;
        $this->cm = $cm;
        $this->context = $context;
        $this->page = $page;
    }
    
    /**
     * Get base template context
     */
    public function get_base_context($canassess, $canedit, $cansubmit) {
        $items = observationchecklist_get_items($this->checklist->id);
        $progress = observationchecklist_get_user_progress($this->checklist->id, $GLOBALS['USER']->id);
        
        return [
            'checklist' => $this->checklist,
            'course' => $this->course,
            'cm' => $this->cm,
            'items' => array_values($items),
            'progress' => $progress,
            'canassess' => $canassess,
            'canedit' => $canedit,
            'cansubmit' => $cansubmit,
            'sesskey' => sesskey(),
            'actionurl' => $this->page->url->out(false)
        ];
    }
    
    /**
     * Get statistics data
     */
    public function get_statistics($items, $progress) {
        $totalItems = count($items);
        $completedItems = 0;
        $satisfactoryItems = 0;
        $notSatisfactoryItems = 0;

        foreach ($progress as $item) {
            if ($item->status == 'satisfactory' || $item->status == 'not_satisfactory') {
                $completedItems++;
                if ($item->status == 'satisfactory') {
                    $satisfactoryItems++;
                } else {
                    $notSatisfactoryItems++;
                }
            }
        }

        return [
            'total_items' => $totalItems,
            'completed_items' => $completedItems,
            'satisfactory_items' => $satisfactoryItems,
            'not_satisfactory_items' => $notSatisfactoryItems,
            'progress_percentage' => $totalItems > 0 ? round(($completedItems / $totalItems) * 100) : 0
        ];
    }
    
    /**
     * Get assessor template context
     */
    public function get_assessor_context($templatecontext) {
        $students = get_enrolled_users($this->context, 'mod/observationchecklist:submit');
        $templatecontext['students'] = array_values($students);
        
        return $templatecontext;
    }
    
    /**
     * Get student progress context
     */
    public function get_student_context($templatecontext, $items, $progress) {
        global $DB;
        
        $progressitems = [];
        foreach ($items as $item) {
            $itemProgress = isset($progress[$item->id]) ? $progress[$item->id] : null;
            $status = $itemProgress ? $itemProgress->status : 'not_started';
            
            $progressitems[] = [
                'item_id' => $item->id,
                'item_text' => format_text($item->itemtext),
                'category' => $item->category,
                'status' => $status,
                'status_text' => get_string($status, 'mod_observationchecklist'),
                'status_color' => $status == 'satisfactory' ? 'success' : 
                                ($status == 'not_satisfactory' ? 'danger' : 
                                ($status == 'in_progress' ? 'warning' : 'secondary')),
                'status_icon' => $status == 'satisfactory' ? 'fa-check-circle' : 
                               ($status == 'not_satisfactory' ? 'fa-times-circle' : 
                               ($status == 'in_progress' ? 'fa-clock' : 'fa-circle')),
                'has_feedback' => $itemProgress && !empty($itemProgress->assessornotes),
                'assessor_notes' => $itemProgress ? $itemProgress->assessornotes : '',
                'assessor_name' => $itemProgress && $itemProgress->assessorid ? 
                                 fullname($DB->get_record('user', ['id' => $itemProgress->assessorid])) : '',
                'date_assessed' => $itemProgress && $itemProgress->dateassessed ? 
                                 userdate($itemProgress->dateassessed) : '',
                'can_submit_evidence' => $this->checklist->allowstudentsubmit
            ];
        }
        
        $stats = $this->get_statistics($items, $progress);
        
        $templatecontext['items'] = $progressitems;
        $templatecontext['completed_items'] = $stats['completed_items'];
        $templatecontext['total_items'] = $stats['total_items'];
        $templatecontext['success_rate'] = $stats['total_items'] > 0 ? 
            round(($stats['satisfactory_items'] / $stats['total_items']) * 100) : 0;
        $templatecontext['pending_items'] = $stats['total_items'] - $stats['completed_items'];
        
        return $templatecontext;
    }
}
