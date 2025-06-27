
<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Post installation and migration code.
 *
 * @package    mod_observationchecklist
 * @copyright  2024 Your Name
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Function to be run after the plugin installation is complete.
 */
function xmldb_observationchecklist_install() {
    global $DB;
    
    // Set default capabilities if needed.
    $context = context_system::instance();
    
    // Any post-installation setup can go here.
    
    return true;
}
