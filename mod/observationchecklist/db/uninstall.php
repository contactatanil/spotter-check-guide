
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

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
    global $DB, $CFG;

    // Create uninstall log
    $logdir = $CFG->dataroot . '/temp';
    if (!is_dir($logdir)) {
        mkdir($logdir, 0777, true);
    }
    $logfile = $logdir . '/observationchecklist_uninstall_' . date('Y-m-d_H-i-s') . '.log';
    $log = fopen($logfile, 'w');
    
    function write_uninstall_log($log, $message) {
        $timestamp = date('Y-m-d H:i:s');
        fwrite($log, "[$timestamp] $message\n");
        error_log("observationchecklist uninstall: $message");
        flush();
    }
    
    write_uninstall_log($log, "=== OBSERVATIONCHECKLIST UNINSTALL START ===");
    
    try {
        // Clean up any orphaned records that might exist.
        // This is belt-and-suspenders as the foreign key constraints should handle most cleanup.
        
        write_uninstall_log($log, "Cleaning up orphaned user items...");
        $sql = "DELETE FROM {observationchecklist_user_items} 
                WHERE checklistid NOT IN (SELECT id FROM {observationchecklist})";
        $DB->execute($sql);
        write_uninstall_log($log, "Orphaned user items cleaned up");

        write_uninstall_log($log, "Cleaning up orphaned checklist items...");
        $sql = "DELETE FROM {observationchecklist_items} 
                WHERE checklistid NOT IN (SELECT id FROM {observationchecklist})";
        $DB->execute($sql);
        write_uninstall_log($log, "Orphaned checklist items cleaned up");

        write_uninstall_log($log, "Cleaning up orphaned grades...");
        $sql = "DELETE FROM {observationchecklist_grades} 
                WHERE checklistid NOT IN (SELECT id FROM {observationchecklist})";
        $DB->execute($sql);
        write_uninstall_log($log, "Orphaned grades cleaned up");

        // Clean up any temporary files or caches
        write_uninstall_log($log, "Cleaning up temporary files...");
        $temp_pattern = $CFG->dataroot . '/temp/observationchecklist_*';
        $temp_files = glob($temp_pattern);
        foreach ($temp_files as $temp_file) {
            if (is_file($temp_file) && filemtime($temp_file) < (time() - 86400)) { // Older than 24 hours
                unlink($temp_file);
                write_uninstall_log($log, "Removed temp file: " . basename($temp_file));
            }
        }

        write_uninstall_log($log, "=== OBSERVATIONCHECKLIST UNINSTALL END - SUCCESS ===");
        fclose($log);
        
        return true;
        
    } catch (Exception $e) {
        write_uninstall_log($log, "ERROR during uninstall: " . $e->getMessage());
        write_uninstall_log($log, "=== OBSERVATIONCHECKLIST UNINSTALL END - ERROR ===");
        fclose($log);
        return false;
    }
}
