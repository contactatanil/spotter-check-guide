
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
    global $DB, $CFG;
    
    try {
        // Basic database setup
        if ($DB->get_dbfamily() === 'mysql') {
            $DB->execute("SET sql_mode = 'TRADITIONAL'");
        }
        
        // Verify that required tables were created
        $dbman = $DB->get_manager();
        
        $required_tables = [
            'observationchecklist',
            'observationchecklist_items', 
            'observationchecklist_user_items',
            'observationchecklist_grades'
        ];
        
        foreach ($required_tables as $tablename) {
            $table = new xmldb_table($tablename);
            if (!$dbman->table_exists($table)) {
                debugging("Table {$tablename} was not created during installation", DEBUG_DEVELOPER);
            }
        }
        
        return true;
        
    } catch (Exception $e) {
        debugging('Error during observationchecklist installation: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}
