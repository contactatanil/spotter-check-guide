
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

        if ($oldversion < 2024122703) {
            // Clean installation - no special upgrade needed
            upgrade_mod_savepoint(true, 2024122703, 'observationchecklist');
        }

        return true;

    } catch (Exception $e) {
        debugging('Error during observationchecklist upgrade: ' . $e->getMessage(), DEBUG_DEVELOPER);
        throw $e;
    }
}
