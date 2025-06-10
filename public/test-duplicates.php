<?php
// Web test for duplicate prevention
use App\Services\DirectDatabaseService;

try {
    require_once '../vendor/autoload.php';
    
    $app = require_once '../bootstrap/app.php';
    
    echo "<h2>Duplicate Prevention Test</h2>";
    echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
    
    $directDb = new DirectDatabaseService();
    $rawData = $directDb->getDeliveryScheduleData(50);
    
    echo "<p><strong>Raw Data:</strong></p>";
    echo "<ul>";
    echo "<li>Deliveries: " . $rawData['deliveries']->count() . "</li>";
    echo "<li>Collections: " . $rawData['collections']->count() . "</li>";
    echo "</ul>";
    
    // Check for Ben Anderson in both
    $benInDeliveries = $rawData['deliveries']->filter(function($d) {
        return stripos($d['customer_email'], 'anderson.ben') !== false;
    });
    
    $benInCollections = $rawData['collections']->filter(function($c) {
        return stripos($c['customer_email'], 'anderson.ben') !== false;
    });
    
    echo "<p><strong>Ben Anderson Check:</strong></p>";
    echo "<ul>";
    echo "<li>In Deliveries: " . $benInDeliveries->count() . "</li>";
    echo "<li>In Collections: " . $benInCollections->count() . "</li>";
    echo "<li>Total: " . ($benInDeliveries->count() + $benInCollections->count()) . "</li>";
    echo "</ul>";
    
    if ($benInDeliveries->count() > 0) {
        echo "<p>Ben in Deliveries:</p><ul>";
        foreach ($benInDeliveries as $ben) {
            echo "<li>ID: {$ben['id']}, Type: {$ben['type']}, Status: {$ben['status']}</li>";
        }
        echo "</ul>";
    }
    
    if ($benInCollections->count() > 0) {
        echo "<p>Ben in Collections:</p><ul>";
        foreach ($benInCollections as $ben) {
            echo "<li>ID: {$ben['id']}, Type: {$ben['type']}, Status: {$ben['status']}</li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
