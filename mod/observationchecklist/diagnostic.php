
<?php
// Diagnostic script for observationchecklist plugin
// This file helps identify server communication issues

require_once('../../config.php');

// Must be admin to run this
require_login();
require_capability('moodle/site:config', context_system::instance());

$PAGE->set_url('/mod/observationchecklist/diagnostic.php');
$PAGE->set_context(context_system::instance());
$PAGE->set_title('Observation Checklist Diagnostic');

echo $OUTPUT->header();

echo '<h1>Observation Checklist Plugin Diagnostic</h1>';

echo '<div class="card"><div class="card-body">';

echo '<h3>System Information</h3>';
echo '<ul>';
echo '<li><strong>PHP Version:</strong> ' . phpversion() . '</li>';
echo '<li><strong>Moodle Version:</strong> ' . $CFG->version . '</li>';
echo '<li><strong>Moodle Release:</strong> ' . $CFG->release . '</li>';
echo '<li><strong>Database Type:</strong> ' . $CFG->dbtype . '</li>';
echo '<li><strong>Memory Limit:</strong> ' . ini_get('memory_limit') . '</li>';
echo '<li><strong>Max Execution Time:</strong> ' . ini_get('max_execution_time') . '</li>';
echo '</ul>';

echo '<h3>Database Connection Test</h3>';
try {
    $start_time = microtime(true);
    $test_query = $DB->get_record_sql("SELECT 1 as test");
    $end_time = microtime(true);
    $query_time = round(($end_time - $start_time) * 1000, 2);
    
    if ($test_query) {
        echo '<div class="alert alert-success">Database connection: OK (Query time: ' . $query_time . 'ms)</div>';
    } else {
        echo '<div class="alert alert-danger">Database connection: FAILED</div>';
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Database error: ' . $e->getMessage() . '</div>';
}

echo '<h3>Plugin Files Check</h3>';
$required_files = [
    'version.php',
    'lib.php',
    'db/install.xml',
    'db/install.php',
    'db/upgrade.php',
    'view.php'
];

echo '<ul>';
foreach ($required_files as $file) {
    $filepath = __DIR__ . '/' . $file;
    if (file_exists($filepath)) {
        echo '<li class="text-success">✓ ' . $file . ' (exists)</li>';
    } else {
        echo '<li class="text-danger">✗ ' . $file . ' (missing)</li>';
    }
}
echo '</ul>';

echo '<h3>Plugin Installation Status</h3>';
try {
    $plugin_manager = core_plugin_manager::instance();
    $plugin_info = $plugin_manager->get_plugin_info('mod_observationchecklist');
    
    if ($plugin_info) {
        echo '<div class="alert alert-info">';
        echo '<strong>Plugin Status:</strong> ' . $plugin_info->get_status_name() . '<br>';
        echo '<strong>Version:</strong> ' . $plugin_info->versiondb . '<br>';
        echo '<strong>Release:</strong> ' . $plugin_info->release . '<br>';
        echo '</div>';
    } else {
        echo '<div class="alert alert-warning">Plugin not found in plugin manager</div>';
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Plugin manager error: ' . $e->getMessage() . '</div>';
}

echo '<h3>Log Files</h3>';
echo '<p>Check the following log files in your Moodle data directory:</p>';
echo '<ul>';
echo '<li>' . $CFG->dataroot . '/temp/observationchecklist_version_*.log</li>';
echo '<li>' . $CFG->dataroot . '/temp/observationchecklist_install_*.log</li>';
echo '</ul>';

echo '</div></div>';

echo $OUTPUT->footer();
