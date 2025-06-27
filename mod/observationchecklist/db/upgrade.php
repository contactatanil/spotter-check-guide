
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
    global $DB;

    $dbman = $DB->get_manager();

    try {
        // Set longer timeout for upgrades
        if (method_exists($DB, 'set_timeout')) {
            $DB->set_timeout(300);
        }
        
        // Ensure UTF-8 encoding and disable foreign key checks temporarily
        if ($DB->get_dbfamily() === 'mysql') {
            try {
                $DB->execute("SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
                $DB->execute("SET sql_mode = ''");
                $DB->execute("SET foreign_key_checks = 0");
            } catch (Exception $e) {
                debugging('Warning during database setup: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        if ($oldversion < 2024062201) {
            // Define field category to be added to observationchecklist_items.
            $table = new xmldb_table('observationchecklist_items');
            $field = new xmldb_field('category', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, 'General', 'itemtext');

            // Conditionally launch add field category.
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            // Observationchecklist savepoint reached.
            upgrade_mod_savepoint(true, 2024062201, 'observationchecklist');
        }

        if ($oldversion < 2024062701) {
            // Add new fields for enhanced functionality
            $table = new xmldb_table('observationchecklist');
            
            // Add allowstudentadd field if it doesn't exist
            $field = new xmldb_field('allowstudentadd', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'introformat');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            
            // Add allowstudentsubmit field if it doesn't exist
            $field = new xmldb_field('allowstudentsubmit', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'allowstudentadd');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
            
            // Add enableprinting field if it doesn't exist
            $field = new xmldb_field('enableprinting', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '1', 'allowstudentsubmit');
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }

            // Observationchecklist savepoint reached.
            upgrade_mod_savepoint(true, 2024062701, 'observationchecklist');
        }

        if ($oldversion < 2024122700) {
            // Add missing indexes for better performance - with error handling
            $table = new xmldb_table('observationchecklist_items');
            $index = new xmldb_index('checklistid_position', XMLDB_INDEX_NOTUNIQUE, array('checklistid', 'position'));
            if (!$dbman->index_exists($table, $index)) {
                try {
                    $dbman->add_index($table, $index);
                } catch (Exception $e) {
                    debugging('Could not add index checklistid_position: ' . $e->getMessage(), DEBUG_DEVELOPER);
                }
            }

            $table = new xmldb_table('observationchecklist_user_items');
            $index = new xmldb_index('checklistid_userid', XMLDB_INDEX_NOTUNIQUE, array('checklistid', 'userid'));
            if (!$dbman->index_exists($table, $index)) {
                try {
                    $dbman->add_index($table, $index);
                } catch (Exception $e) {
                    debugging('Could not add index checklistid_userid: ' . $e->getMessage(), DEBUG_DEVELOPER);
                }
            }

            // Observationchecklist savepoint reached.
            upgrade_mod_savepoint(true, 2024122700, 'observationchecklist');
        }

        if ($oldversion < 2024122702) {
            // Clean up any orphaned records that might cause issues
            try {
                // Remove user items that reference non-existent items
                $sql = "DELETE FROM {observationchecklist_user_items} 
                        WHERE itemid NOT IN (SELECT id FROM {observationchecklist_items})";
                $DB->execute($sql);
                
                // Remove user items that reference non-existent checklists
                $sql = "DELETE FROM {observationchecklist_user_items} 
                        WHERE checklistid NOT IN (SELECT id FROM {observationchecklist})";
                $DB->execute($sql);
                
                // Remove items that reference non-existent checklists
                $sql = "DELETE FROM {observationchecklist_items} 
                        WHERE checklistid NOT IN (SELECT id FROM {observationchecklist})";
                $DB->execute($sql);
                
            } catch (Exception $e) {
                debugging('Warning during cleanup: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }

            // Observationchecklist savepoint reached.
            upgrade_mod_savepoint(true, 2024122702, 'observationchecklist');
        }

        // Re-enable foreign key checks
        if ($DB->get_dbfamily() === 'mysql') {
            try {
                $DB->execute("SET foreign_key_checks = 1");
            } catch (Exception $e) {
                debugging('Warning re-enabling foreign key checks: ' . $e->getMessage(), DEBUG_DEVELOPER);
            }
        }

        // Clear caches after upgrade
        if (function_exists('purge_all_caches')) {
            purge_all_caches();
        }

        return true;

    } catch (Exception $e) {
        debugging('Error during observationchecklist upgrade: ' . $e->getMessage(), DEBUG_DEVELOPER);
        error_log('Observationchecklist upgrade error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        throw $e;
    }
}
