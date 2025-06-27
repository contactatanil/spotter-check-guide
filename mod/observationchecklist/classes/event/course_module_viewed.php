
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_observationchecklist course module viewed event.
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_viewed extends \core\event\course_module_viewed {

    /**
     * Create instance of event.
     *
     * @param array $data
     * @return course_module_viewed
     */
    public static function create_from_course_module(\stdClass $cm, \context_module $context) {
        $data = array(
            'context' => $context,
            'objectid' => $cm->instance
        );
        return self::create($data);
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'observationchecklist';
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/observationchecklist/view.php', array('id' => $this->contextinstanceid));
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventcoursemoduleviewed', 'mod_observationchecklist');
    }

    /**
     * Get description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' viewed the observation checklist activity with " .
            "course module id '$this->contextinstanceid'.";
    }

    /**
     * Get legacy log data.
     *
     * @return array
     */
    protected function get_legacy_logdata() {
        return array($this->courseid, 'observationchecklist', 'view', 'view.php?id=' . $this->contextinstanceid,
            $this->objectid, $this->contextinstanceid);
    }
}
