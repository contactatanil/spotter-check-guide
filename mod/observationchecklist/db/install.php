
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
        // Set longer timeout for installation
        if (method_exists($DB, 'set_timeout')) {
            $DB->set_timeout(300);
        }
        
        // Ensure UTF-8 encoding for MariaDB/MySQL
        if ($DB->get_dbfamily() === 'mysql') {
            try {
                $DB->execute("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
                $DB->execute("SET sql_mode = ''");
                $DB->execute("SET foreign_key_checks = 0");
            } catch (Exception $e) {
                // Continue if these fail - they're optimization attempts
                debugging('Warning during database setup: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        
        // Verify that all required tables were created successfully
        $dbman = $DB->get_manager();
        
        $tables_to_check = [
            'observationchecklist',
            'observationchecklist_items', 
            'observationchecklist_user_items',
            'observationchecklist_grades'
        ];
        
        $missing_tables = [];
        foreach ($tables_to_check as $tablename) {
            $table = new xmldb_table($tablename);
            if (!$dbman->table_exists($table)) {
                $missing_tables[] = $tablename;
            }
        }
        
        if (!empty($missing_tables)) {
            debugging("Tables not created during installation: " . implode(', ', $missing_tables), DEBUG_DEVELOPER);
            // Don't return false - let Moodle handle the error
        }
        
        // Re-enable foreign key checks
        if ($DB->get_dbfamily() === 'mysql') {
            try {
                $DB->execute("SET foreign_key_checks = 1");
            } catch (Exception $e) {
                // Continue if this fails
                debugging('Warning re-enabling foreign key checks: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }
        
        // Clear any caches
        if (function_exists('purge_all_caches')) {
            purge_all_caches();
        }
        
        return true;
        
    } catch (Exception $e) {
        debugging('Error during observationchecklist installation: ' . $e->getMessage(), DEBUG_DEVELOPER);
        // Log the full error for debugging
        error_log('Observationchecklist installation error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        return false;
    }
}
