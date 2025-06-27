
<?php
// This file is part of Moodle - http://moodle.org/

/**
 * Upgrade script for mod_observationchecklist.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute observationchecklist upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_observationchecklist_upgrade($oldversion) {
    global $DB, $CFG;

    $dbman = $DB->get_manager();
    
    // Create upgrade log
    $logfile = $CFG->dataroot . '/observationchecklist_upgrade.log';
    $log = fopen($logfile, 'a');
    
    function write_upgrade_log($log, $message) {
        $timestamp = date('Y-m-d H:i:s');
        fwrite($log, "[$timestamp] $message\n");
        debugging($message, DEBUG_DEVELOPER);
    }
    
    write_upgrade_log($log, "=== OBSERVATIONCHECKLIST UPGRADE START ===");
    write_upgrade_log($log, "Upgrading from version: $oldversion");
    write_upgrade_log($log, "Current Moodle version: " . $CFG->version);
    write_upgrade_log($log, "Database type: " . $DB->get_dbfamily());

    try {
        if ($oldversion < 2024062201) {
            write_upgrade_log($log, "Applying upgrade 2024062201...");
            
            // Define field category to be added to observationchecklist_items.
            $table = new xmldb_table('observationchecklist_items');
            $field = new xmldb_field('category', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, 'General', 'itemtext');

            // Conditionally launch add field category.
            if (!$dbman->field_exists($table, $field)) {
                write_upgrade_log($log, "Adding category field to observationchecklist_items...");
                $dbman->add_field($table, $field);
                write_upgrade_log($log, "Category field added successfully");
            } else {
                write_upgrade_log($log, "Category field already exists");
            }

            // Observationchecklist savepoint reached.
            upgrade_mod_savepoint(true, 2024062201, 'observationchecklist');
            write_upgrade_log($log, "Upgrade 2024062201 completed");
        }

        if ($oldversion < 2024062701) {
            write_upgrade_log($log, "Applying upgrade 2024062701...");
            
            // Add new fields for enhanced functionality
            $table = new xmldb_table('observationchecklist');
            
            // Add allowstudentadd field if it doesn't exist
            $field = new xmldb_field('allowstudentadd', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'introformat');
            if (!$dbman->field_exists($table, $field)) {
                write_upgrade_log($log, "Adding allowstudentadd field...");
                $dbman->add_field($table, $field);
                write_upgrade_log($log, "allowstudentadd field added successfully");
            } else {
                write_upgrade_log($log, "allowstudentadd field already exists");
            }
            
            // Add allowstudentsubmit field if it doesn't exist
            $field = new xmldb_field('allowstudentsubmit', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'allowstudentadd');
            if (!$dbman->field_exists($table, $field)) {
                write_upgrade_log($log, "Adding allowstudentsubmit field...");
                $dbman->add_field($table, $field);
                write_upgrade_log($log, "allowstudentsubmit field added successfully");
            } else {
                write_upgrade_log($log, "allowstudentsubmit field already exists");
            }
            
            // Add enableprinting field if it doesn't exist
            $field = new xmldb_field('enableprinting', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'allowstudentsubmit');
            if (!$dbman->field_exists($table, $field)) {
                write_upgrade_log($log, "Adding enableprinting field...");
                $dbman->add_field($table, $field);
                write_upgrade_log($log, "enableprinting field added successfully");
            } else {
                write_upgrade_log($log, "enableprinting field already exists");
            }

            // Observationchecklist savepoint reached.
            upgrade_mod_savepoint(true, 2024062701, 'observationchecklist');
            write_upgrade_log($log, "Upgrade 2024062701 completed");
        }

        if ($oldversion < 2024122704) {
            write_upgrade_log($log, "Applying upgrade 2024122704...");
            
            // Clean installation - verify all tables exist
            $required_tables = [
                'observationchecklist',
                'observationchecklist_items', 
                'observationchecklist_user_items',
                'observationchecklist_grades'
            ];
            
            foreach ($required_tables as $tablename) {
                $table = new xmldb_table($tablename);
                if ($dbman->table_exists($table)) {
                    write_upgrade_log($log, "Table $tablename: OK");
                } else {
                    write_upgrade_log($log, "Table $tablename: MISSING - this is a problem!");
                }
            }
            
            upgrade_mod_savepoint(true, 2024122704, 'observationchecklist');
            write_upgrade_log($log, "Upgrade 2024122704 completed");
        }

        write_upgrade_log($log, "=== OBSERVATIONCHECKLIST UPGRADE END - SUCCESS ===");
        fclose($log);
        return true;

    } catch (Exception $e) {
        write_upgrade_log($log, "CRITICAL ERROR during upgrade: " . $e->getMessage());
        write_upgrade_log($log, "Error trace: " . $e->getTraceAsString());
        write_upgrade_log($log, "=== OBSERVATIONCHECKLIST UPGRADE END - FAILED ===");
        fclose($log);
        debugging('Error during observationchecklist upgrade: ' . $e->getMessage(), DEBUG_DEVELOPER);
        throw $e;
    }
}
