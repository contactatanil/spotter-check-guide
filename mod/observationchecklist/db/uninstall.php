
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Custom uninstallation procedure
 */
function xmldb_observationchecklist_uninstall() {
    global $DB;
    
    // Clean up any remaining data
    $DB->delete_records('observationchecklist_user_items');
    $DB->delete_records('observationchecklist_items');
    $DB->delete_records('observationchecklist');
    
    return true;
}
