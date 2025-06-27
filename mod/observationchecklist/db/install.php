
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
    
    // Create installation log
    $logfile = $CFG->dataroot . '/observationchecklist_install.log';
    $log = fopen($logfile, 'a');
    
    function write_log($log, $message) {
        $timestamp = date('Y-m-d H:i:s');
        fwrite($log, "[$timestamp] $message\n");
        debugging($message, DEBUG_DEVELOPER);
    }
    
    write_log($log, "=== OBSERVATIONCHECKLIST INSTALLATION START ===");
    write_log($log, "Moodle version: " . $CFG->version);
    write_log($log, "Database type: " . $DB->get_dbfamily());
    write_log($log, "Database version: " . $DB->get_server_info()['version']);
    
    try {
        // Check database connection
        write_log($log, "Testing database connection...");
        $test_query = $DB->get_record_sql("SELECT 1 as test");
        if ($test_query) {
            write_log($log, "Database connection: OK");
        } else {
            write_log($log, "Database connection: FAILED");
            fclose($log);
            return false;
        }
        
        // Check database family specific settings
        if ($DB->get_dbfamily() === 'mysql') {
            write_log($log, "Setting MySQL mode...");
            try {
                $DB->execute("SET sql_mode = 'TRADITIONAL'");
                write_log($log, "MySQL mode set successfully");
            } catch (Exception $e) {
                write_log($log, "MySQL mode setting failed: " . $e->getMessage());
            }
            
            // Check MySQL/MariaDB version
            $version_info = $DB->get_server_info();
            write_log($log, "MySQL/MariaDB version: " . $version_info['version']);
            write_log($log, "MySQL/MariaDB description: " . $version_info['description']);
        }
        
        // Verify that required tables were created
        write_log($log, "Checking if tables were created...");
        $dbman = $DB->get_manager();
        
        $required_tables = [
            'observationchecklist',
            'observationchecklist_items', 
            'observationchecklist_user_items',
            'observationchecklist_grades'
        ];
        
        $tables_ok = true;
        foreach ($required_tables as $tablename) {
            write_log($log, "Checking table: $tablename");
            $table = new xmldb_table($tablename);
            if ($dbman->table_exists($table)) {
                write_log($log, "Table $tablename: EXISTS");
                
                // Check table structure
                try {
                    $columns = $DB->get_columns($tablename);
                    write_log($log, "Table $tablename has " . count($columns) . " columns");
                    foreach ($columns as $column) {
                        write_log($log, "  - Column: {$column->name} ({$column->type})");
                    }
                } catch (Exception $e) {
                    write_log($log, "Error checking table structure for $tablename: " . $e->getMessage());
                }
            } else {
                write_log($log, "Table $tablename: MISSING");
                $tables_ok = false;
            }
        }
        
        if (!$tables_ok) {
            write_log($log, "Some tables are missing - this might be normal during initial install");
        }
        
        // Test basic database operations
        write_log($log, "Testing basic database operations...");
        
        // Test if we can create a simple test table
        try {
            $test_sql = "CREATE TABLE test_observationchecklist_install (id INT PRIMARY KEY, test_field VARCHAR(255))";
            $DB->execute($test_sql);
            write_log($log, "Test table creation: SUCCESS");
            
            // Clean up test table
            $DB->execute("DROP TABLE test_observationchecklist_install");
            write_log($log, "Test table cleanup: SUCCESS");
        } catch (Exception $e) {
            write_log($log, "Test table operations FAILED: " . $e->getMessage());
        }
        
        // Check permissions
        write_log($log, "Checking database permissions...");
        try {
            // Try to get current user and privileges
            if ($DB->get_dbfamily() === 'mysql') {
                $user_info = $DB->get_record_sql("SELECT USER() as current_user, DATABASE() as current_db");
                write_log($log, "Current MySQL user: " . $user_info->current_user);
                write_log($log, "Current database: " . $user_info->current_db);
                
                // Check some basic privileges
                try {
                    $privileges = $DB->get_records_sql("SHOW GRANTS");
                    write_log($log, "User has " . count($privileges) . " privilege grants");
                } catch (Exception $e) {
                    write_log($log, "Could not check privileges: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            write_log($log, "Permission check failed: " . $e->getMessage());
        }
        
        write_log($log, "=== OBSERVATIONCHECKLIST INSTALLATION END - SUCCESS ===");
        fclose($log);
        return true;
        
    } catch (Exception $e) {
        write_log($log, "CRITICAL ERROR during installation: " . $e->getMessage());
        write_log($log, "Error trace: " . $e->getTraceAsString());
        write_log($log, "=== OBSERVATIONCHECKLIST INSTALLATION END - FAILED ===");
        fclose($log);
        debugging('Error during observationchecklist installation: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    }
}
