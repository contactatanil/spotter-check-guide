
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
 * Plugin version and other meta-data are defined here.
 *
 * @package     mod_observationchecklist
 * @copyright   2024 Your Name <your@email.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin = new stdClass();
$plugin->component = 'mod_observationchecklist';
$plugin->version = 2024122707; // Incremented version for clean requirements check
$plugin->release = '1.0.7';
$plugin->requires = 2022112800; // Moodle 4.1 minimum
$plugin->maturity = MATURITY_STABLE;
$plugin->supported = [401, 405]; // Moodle 4.1-4.5 supported

// No dependencies - this should help with requirement checking
$plugin->dependencies = array();

// Log only essential information without interfering with plugin checks
if (defined('CLI_SCRIPT') || !empty($_REQUEST['confirmplugincheck'])) {
    error_log("observationchecklist: Plugin requirements check - version {$plugin->version}, requires Moodle {$plugin->requires}");
}
