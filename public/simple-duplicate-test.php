<?php
// Simple Test - Check if our duplicate fix works
echo "<h2>🧪 Simple Duplicate Fix Test</h2>\n";

try {
    // Bootstrap Laravel
    require_once __DIR__ . '/../vendor/autoload.php';
    $app = require_once __DIR__ . '/../bootstrap/app.php';
    
    $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
    $kernel->bootstrap();
    
    echo "✅ Laravel loaded\n";
    
    // Test the service directly
    $directDb = new \App\Services\DirectDatabaseService();
    $rawData = $directDb->getDeliveryScheduleData(100);
    
    echo "📊 Raw data: " . $rawData['deliveries']->count() . " deliveries, " . $rawData['collections']->count() . " collections\n";
    
    // Simple duplicate prevention simulation
    $seenEmails = [];
    $finalDeliveries = [];
    $finalCollections = [];
    $duplicatesRemoved = 0;
    
    // Process deliveries first
    foreach ($rawData['deliveries'] as $delivery) {
        $email = strtolower(trim($delivery['customer_email']));
        if (!isset($seenEmails[$email])) {
            $seenEmails[$email] = 'delivery';
            $finalDeliveries[] = $delivery;
        } else {
            $duplicatesRemoved++;
            echo "⚠️ Removed delivery duplicate: $email\n";
        }
    }
    
    // Process collections, check against already seen emails
    foreach ($rawData['collections'] as $collection) {
        $email = strtolower(trim($collection['customer_email']));
        if (!isset($seenEmails[$email])) {
            $seenEmails[$email] = 'collection';
            $finalCollections[] = $collection;
        } else {
            $duplicatesRemoved++;
            echo "⚠️ Removed collection duplicate: $email (already in " . $seenEmails[$email] . ")\n";
        }
    }
    
    echo "\n📈 Results:\n";
    echo "- Final deliveries: " . count($finalDeliveries) . "\n";
    echo "- Final collections: " . count($finalCollections) . "\n";
    echo "- Duplicates removed: $duplicatesRemoved\n";
    
    // Check Ben specifically
    $benFound = false;
    foreach ($finalDeliveries as $delivery) {
        if (stripos($delivery['customer_email'], 'anderson.ben0405') !== false) {
            echo "👤 Ben Anderson found in DELIVERIES\n";
            $benFound = true;
        }
    }
    
    foreach ($finalCollections as $collection) {
        if (stripos($collection['customer_email'], 'anderson.ben0405') !== false) {
            echo "👤 Ben Anderson found in COLLECTIONS\n";
            $benFound = true;
        }
    }
    
    if (!$benFound) {
        echo "❓ Ben Anderson not found in final data\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
