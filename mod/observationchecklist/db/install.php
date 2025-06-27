
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
    
    // Create installation log with more detailed path
    $logdir = $CFG->dataroot . '/temp';
    if (!is_dir($logdir)) {
        mkdir($logdir, 0777, true);
    }
    $logfile = $logdir . '/observationchecklist_install_' . date('Y-m-d_H-i-s') . '.log';
    $log = fopen($logfile, 'w');
    
    function write_log($log, $message) {
        $timestamp = date('Y-m-d H:i:s');
        $memory = memory_get_usage(true);
        $formatted = "[$timestamp] [MEM: " . round($memory/1024/1024, 2) . "MB] $message";
        fwrite($log, $formatted . "\n");
        error_log($formatted);
        debugging($message, DEBUG_DEVELOPER);
        flush();
    }
    
    write_log($log, "=== OBSERVATIONCHECKLIST INSTALLATION START ===");
    write_log($log, "Log file: $logfile");
    write_log($log, "PHP Version: " . phpversion());
    write_log($log, "Moodle version: " . $CFG->version);
    write_log($log, "Moodle release: " . $CFG->release);
    write_log($log, "Database type: " . $DB->get_dbfamily());
    
    try {
        // Step 1: Test basic functionality
        write_log($log, "STEP 1: Testing basic PHP functionality...");
        $test_array = array('test' => 'value');
        write_log($log, "PHP array test: " . json_encode($test_array));
        
        // Step 2: Test database connection
        write_log($log, "STEP 2: Testing database connection...");
        $start_time = microtime(true);
        $test_query = $DB->get_record_sql("SELECT 1 as test");
        $end_time = microtime(true);
        $query_time = round(($end_time - $start_time) * 1000, 2);
        
        if ($test_query) {
            write_log($log, "Database connection: OK (Query time: {$query_time}ms)");
        } else {
            write_log($log, "Database connection: FAILED");
            fclose($log);
            return false;
        }
        
        // Step 3: Get database info
        write_log($log, "STEP 3: Getting database information...");
        try {
            $db_info = $DB->get_server_info();
            foreach ($db_info as $key => $value) {
                write_log($log, "DB Info - $key: $value");
            }
        } catch (Exception $e) {
            write_log($log, "Could not get DB info: " . $e->getMessage());
        }
        
        // Step 4: Test table manager
        write_log($log, "STEP 4: Testing database manager...");
        $dbman = $DB->get_manager();
        if ($dbman) {
            write_log($log, "Database manager: OK");
        } else {
            write_log($log, "Database manager: FAILED");
            fclose($log);
            return false;
        }
        
        // Step 5: Check if tables exist (they shouldn't yet)
        write_log($log, "STEP 5: Checking table existence before creation...");
        $required_tables = [
            'observationchecklist',
            'observationchecklist_items', 
            'observationchecklist_user_items',
            'observationchecklist_grades'
        ];
        
        foreach ($required_tables as $tablename) {
            try {
                $table = new xmldb_table($tablename);
                $exists = $dbman->table_exists($table);
                write_log($log, "Table $tablename exists: " . ($exists ? 'YES' : 'NO'));
            } catch (Exception $e) {
                write_log($log, "Error checking table $tablename: " . $e->getMessage());
            }
        }
        
        // Step 6: Test creating a simple table
        write_log($log, "STEP 6: Testing table creation...");
        try {
            $test_table_name = 'test_observationchecklist_' . time();
            write_log($log, "Creating test table: $test_table_name");
            
            $create_sql = "CREATE TABLE {$test_table_name} (
                id INT PRIMARY KEY AUTO_INCREMENT,
                test_field VARCHAR(255),
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            
            $DB->execute($create_sql);
            write_log($log, "Test table created successfully");
            
            // Insert test data
            $insert_sql = "INSERT INTO {$test_table_name} (test_field) VALUES (?)";
            $DB->execute($insert_sql, array('test_value'));
            write_log($log, "Test data inserted successfully");
            
            // Read test data
            $read_sql = "SELECT * FROM {$test_table_name}";
            $results = $DB->get_records_sql($read_sql);
            write_log($log, "Test data read: " . count($results) . " records");
            
            // Clean up test table
            $drop_sql = "DROP TABLE {$test_table_name}";
            $DB->execute($drop_sql);
            write_log($log, "Test table cleaned up successfully");
            
        } catch (Exception $e) {
            write_log($log, "Test table operations FAILED: " . $e->getMessage());
            write_log($log, "Exception trace: " . $e->getTraceAsString());
        }
        
        // Step 7: Check permissions and user info
        write_log($log, "STEP 7: Checking database permissions...");
        try {
            if ($DB->get_dbfamily() === 'mysql') {
                $user_info = $DB->get_record_sql("SELECT USER() as current_user, DATABASE() as current_db");
                write_log($log, "Current MySQL user: " . $user_info->current_user);
                write_log($log, "Current database: " . $user_info->current_db);
                
                // Check connection settings
                $charset_info = $DB->get_record_sql("SELECT @@character_set_database as charset, @@collation_database as collation");
                write_log($log, "Database charset: " . $charset_info->charset);
                write_log($log, "Database collation: " . $charset_info->collation);
            }
        } catch (Exception $e) {
            write_log($log, "Permission/user check failed: " . $e->getMessage());
        }
        
        // Step 8: Check Moodle configuration
        write_log($log, "STEP 8: Checking Moodle configuration...");
        write_log($log, "CFG->dbtype: " . $CFG->dbtype);
        write_log($log, "CFG->dbhost: " . $CFG->dbhost);
        write_log($log, "CFG->dbname: " . $CFG->dbname);
        write_log($log, "CFG->dbuser: " . $CFG->dbuser);
        write_log($log, "CFG->prefix: " . $CFG->prefix);
        
        // Step 9: Final success
        write_log($log, "STEP 9: Installation function completed successfully");
        write_log($log, "=== OBSERVATIONCHECKLIST INSTALLATION END - SUCCESS ===");
        fclose($log);
        
        // Also log to Moodle's standard log
        error_log("observationchecklist install completed successfully - check log: $logfile");
        
        return true;
        
    } catch (Exception $e) {
        write_log($log, "CRITICAL ERROR during installation: " . $e->getMessage());
        write_log($log, "Error file: " . $e->getFile());
        write_log($log, "Error line: " . $e->getLine());
        write_log($log, "Error trace: " . $e->getTraceAsString());
        write_log($log, "=== OBSERVATIONCHECKLIST INSTALLATION END - FAILED ===");
        fclose($log);
        
        // Log to standard error log as well
        error_log("observationchecklist install FAILED - check log: $logfile - Error: " . $e->getMessage());
        
        debugging('Error during observationchecklist installation: ' . $e->getMessage(), DEBUG_DEVELOPER);
        return false;
    } catch (Error $e) {
        write_log($log, "FATAL ERROR during installation: " . $e->getMessage());
        write_log($log, "Error file: " . $e->getFile());
        write_log($log, "Error line: " . $e->getLine());
        write_log($log, "Error trace: " . $e->getTraceAsString());
        write_log($log, "=== OBSERVATIONCHECKLIST INSTALLATION END - FATAL ===");
        fclose($log);
        
        error_log("observationchecklist install FATAL ERROR - check log: $logfile - Error: " . $e->getMessage());
        return false;
    }
}
