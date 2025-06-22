
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\message;

defined('MOODLE_INTERNAL') || die();

/**
 * Notification manager for observation checklist.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class notification_manager {

    /**
     * Send assessment notification to student.
     *
     * @param object $student Student user object
     * @param object $checklist Checklist object
     * @param object $item Item object
     * @param string $status Assessment status
     */
    public static function send_assessment_notification($student, $checklist, $item, $status) {
        global $USER;

        if (!get_config('mod_observationchecklist', 'enablenotifications')) {
            return;
        }

        $message = new \core\message\message();
        $message->component = 'mod_observationchecklist';
        $message->name = 'assessment';
        $message->userfrom = $USER;
        $message->userto = $student;
        $message->subject = get_string('assessmentnotificationsubject', 'mod_observationchecklist', $checklist->name);
        $message->fullmessage = get_string('assessmentnotificationbody', 'mod_observationchecklist', array(
            'checklistname' => $checklist->name,
            'itemtext' => $item->itemtext,
            'status' => get_string($status, 'mod_observationchecklist')
        ));
        $message->fullmessageformat = FORMAT_PLAIN;
        $message->fullmessagehtml = '';
        $message->smallmessage = get_string('assessmentnotificationsmall', 'mod_observationchecklist');
        $message->notification = 1;

        message_send($message);
    }
}

