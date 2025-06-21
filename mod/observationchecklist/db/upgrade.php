
<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Execute observationchecklist upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_observationchecklist_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2024062100) {
        // Observationchecklist savepoint reached.
        upgrade_mod_savepoint(true, 2024062100, 'observationchecklist');
    }

    return true;
}
