
<?php
// This file is part of Moodle - http://moodle.org/

namespace mod_observationchecklist\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem implementation for mod_observationchecklist.
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\null_provider {

    /**
     * Get the language string identifier with the component's language
     * file to explain why this plugin stores no data.
     *
     * @return  string
     */
    public static function get_reason() : string {
        return 'privacy:metadata';
    }
}
