<?php
require_once __DIR__.'/includes/init.php';

// Create / Get / Delete variable
$db->variable_save('test_array', [
    'current_script' => basename($_SERVER['PHP_SELF']),
    'time' => time(),
]);
$variable_in_database = $db->variable_get('test_array');
d($variable_in_database, 'Variable in database');

$db->variable_delete('test_array');


// Get variable
$timestamp = $db->variable_get('timestamp');
d($timestamp, 'Timestamp from database');

// Edit variable
$timestamp = time();
$db->variable_save('timestamp', $timestamp);
