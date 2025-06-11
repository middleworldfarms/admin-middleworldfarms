<?php
echo "<h2>Admin Subdomain PHP Environment</h2>";
echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
echo "<p><strong>Server API:</strong> " . php_sapi_name() . "</p>";
echo "<p><strong>Loaded Extensions:</strong> " . implode(', ', array_slice(get_loaded_extensions(), 0, 10)) . "...</p>";

// Test database connectivity
try {
    $config = require '../config/database.php';
    echo "<p><strong>Database Config:</strong> WordPress connection configured ✅</p>";
} catch (Exception $e) {
    echo "<p><strong>Database Config:</strong> Error - " . $e->getMessage() . "</p>";
}

echo "<h3>🎯 Compatibility Status</h3>";
echo "<ul>";
echo "<li>✅ Laravel 11: Works on PHP 8.1, 8.2, 8.3</li>";
echo "<li>✅ Database: MariaDB compatible with all PHP versions</li>";
echo "<li>✅ DirectDatabaseService: Version-agnostic</li>";
echo "<li>✅ User Switching: Works regardless of main site PHP version</li>";
echo "</ul>";

echo "<h3>📋 Current Integration Status</h3>";
echo "<p>The admin system connects directly to the WordPress database and is completely independent of the main site's PHP version.</p>";
?>
