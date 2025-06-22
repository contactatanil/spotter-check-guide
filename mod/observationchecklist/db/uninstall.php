
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Uninstall script for mod_observationchecklist.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Custom uninstallation procedure.
 */
function xmldb_observationchecklist_uninstall() {
    global $DB;

    // Clean up any orphaned records that might exist.
    // This is belt-and-suspenders as the foreign key constraints should handle most cleanup.
    
    // Remove any orphaned user items.
    $sql = "DELETE FROM {observationchecklist_user_items} 
            WHERE checklistid NOT IN (SELECT id FROM {observationchecklist})";
    $DB->execute($sql);

    // Remove any orphaned checklist items.
    $sql = "DELETE FROM {observationchecklist_items} 
            WHERE checklistid NOT IN (SELECT id FROM {observationchecklist})";
    $DB->execute($sql);

    return true;
}
