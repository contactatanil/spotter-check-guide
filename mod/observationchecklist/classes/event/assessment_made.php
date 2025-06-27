
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_observationchecklist assessment made event.
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class assessment_made extends \core\event\base {

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'observationchecklist_user_items';
    }

    /**
     * Return localised event name.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('eventassessmentmade', 'mod_observationchecklist');
    }

    /**
     * Get description of what happened.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '$this->userid' made an assessment for user '$this->relateduserid' " .
            "in the observation checklist with course module id '$this->contextinstanceid'.";
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/observationchecklist/assess.php', 
            array('id' => $this->contextinstanceid, 'userid' => $this->relateduserid));
    }
}
