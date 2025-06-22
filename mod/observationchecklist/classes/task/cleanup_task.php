
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Scheduled task for observation checklist cleanup.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_task extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task.
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanuptask', 'mod_observationchecklist');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        // Clean up orphaned user items (items where the related checklist item was deleted).
        $sql = "DELETE FROM {observationchecklist_user_items} 
                WHERE itemid NOT IN (SELECT id FROM {observationchecklist_items})";
        $DB->execute($sql);

        // Log the cleanup.
        mtrace('Observation checklist cleanup completed.');
    }
}

