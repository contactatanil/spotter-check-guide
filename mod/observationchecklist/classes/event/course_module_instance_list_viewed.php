
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\event;

defined('MOODLE_INTERNAL') || die();

/**
 * The mod_observationchecklist course module instance list viewed event.
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_instance_list_viewed extends \core\event\course_module_instance_list_viewed {

    /**
     * Create instance of event.
     *
     * @since Moodle 2.7
     *
     * @param \context_course $context
     * @return course_module_instance_list_viewed
     */
    public static function create_from_course(\context_course $context) {
        $data = array(
            'context' => $context,
        );
        $event = \mod_observationchecklist\event\course_module_instance_list_viewed::create($data);
        return $event;
    }

    /**
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
    }

    /**
     * Get URL related to the action.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/mod/observationchecklist/index.php', array('id' => $this->courseid));
    }

    /**
     * Return the legacy event log data.
     *
     * @return array|null
     */
    protected function get_legacy_logdata() {
        return array($this->courseid, 'observationchecklist', 'view all', 'index.php?id=' . $this->courseid, '');
    }

    public static function get_objectid_mapping() {
        return false;
    }
}
