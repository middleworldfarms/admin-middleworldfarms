<?php
echo "<h1>PHP Test</h1>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Current time: " . date('Y-m-d H:i:s') . "</p>";

// Test Laravel bootstrap
echo "<p>Attempting to load Laravel...</p>";
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    echo "<p>✓ Composer autoload loaded</p>";
    
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    echo "<p>✓ Laravel app loaded</p>";
} catch (Exception $e) {
    echo "<p>✗ Error loading Laravel: " . $e->getMessage() . "</p>";
}
?>
