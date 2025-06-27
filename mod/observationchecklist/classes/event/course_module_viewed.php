
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
     * Init method.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'r';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'observationchecklist';
    }

    public static function get_objectid_mapping() {
        return array('db' => 'observationchecklist', 'restore' => 'observationchecklist');
    }
}
