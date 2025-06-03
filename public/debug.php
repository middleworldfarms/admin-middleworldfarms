<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "PHP Version: " . phpversion() . "<br>";
echo "Date: " . date('Y-m-d H:i:s') . "<br>";
echo "Debug test successful!<br>";

// Test basic Laravel bootstrap
echo "Testing Laravel bootstrap...<br>";
try {
    require_once __DIR__ . '/../vendor/autoload.php';
    echo "Autoloader loaded successfully<br>";
    
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    echo "Laravel app bootstrapped successfully<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
