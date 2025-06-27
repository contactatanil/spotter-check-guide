
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

// Extensive logging to catch server communication issues
try {
    // Log the version loading with error handling
    $logdir = $CFG->dataroot . '/temp';
    if (!is_dir($logdir)) {
        mkdir($logdir, 0777, true);
    }
    
    $version_log = $logdir . '/observationchecklist_version_' . date('Y-m-d_H-i-s') . '.log';
    $log_handle = fopen($version_log, 'w');
    
    if ($log_handle) {
        fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] === VERSION.PHP LOADING START ===\n");
        fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] PHP Version: " . phpversion() . "\n");
        fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] Memory limit: " . ini_get('memory_limit') . "\n");
        fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] Max execution time: " . ini_get('max_execution_time') . "\n");
        
        // Check if we're in CLI or web context
        if (php_sapi_name() === 'cli') {
            fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] Running in CLI mode\n");
        } else {
            fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] Running in web mode\n");
            if (isset($_SERVER['REQUEST_METHOD'])) {
                fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] Request method: " . $_SERVER['REQUEST_METHOD'] . "\n");
            }
            if (isset($_SERVER['REQUEST_URI'])) {
                fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] Request URI: " . $_SERVER['REQUEST_URI'] . "\n");
            }
        }
        
        // Check if this is during plugin installation
        if (isset($_GET['confirmplugincheck'])) {
            fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] Plugin confirmation check detected\n");
        }
        
        fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] Creating plugin object...\n");
    }
    
    $plugin = new stdClass();
    $plugin->component = 'mod_observationchecklist';
    $plugin->version = 2024122706; // Incremented version
    $plugin->release = '1.0.6';
    $plugin->requires = 2022112800; // Moodle 4.1 minimum
    $plugin->maturity = MATURITY_STABLE;
    $plugin->supported = [401, 405]; // Moodle 4.1-4.5 supported
    $plugin->dependencies = array(); // No dependencies
    
    if ($log_handle) {
        fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] Plugin object created successfully\n");
        fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] Component: " . $plugin->component . "\n");
        fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] Version: " . $plugin->version . "\n");
        fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] Release: " . $plugin->release . "\n");
        
        if (isset($CFG->version)) {
            fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] Moodle version: " . $CFG->version . "\n");
        }
        if (isset($CFG->release)) {
            fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] Moodle release: " . $CFG->release . "\n");
        }
        
        fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] === VERSION.PHP LOADING END - SUCCESS ===\n");
        fclose($log_handle);
    }
    
    // Also log to error log
    error_log("observationchecklist version.php loaded successfully - version: {$plugin->version} - log: $version_log");
    
} catch (Exception $e) {
    if (isset($log_handle) && $log_handle) {
        fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] EXCEPTION in version.php: " . $e->getMessage() . "\n");
        fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] Exception trace: " . $e->getTraceAsString() . "\n");
        fclose($log_handle);
    }
    error_log("observationchecklist version.php EXCEPTION: " . $e->getMessage());
    throw $e;
} catch (Error $e) {
    if (isset($log_handle) && $log_handle) {
        fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] FATAL ERROR in version.php: " . $e->getMessage() . "\n");
        fwrite($log_handle, "[" . date('Y-m-d H:i:s') . "] Error trace: " . $e->getTraceAsString() . "\n");
        fclose($log_handle);
    }
    error_log("observationchecklist version.php FATAL ERROR: " . $e->getMessage());
    throw $e;
}
