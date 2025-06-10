<!DOCTYPE html>
<html>
<head>
    <title>🔍 Duplicate Fix Verification</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .duplicate { border: 1px solid #f00; padding: 10px; margin: 5px; background: #ffe6e6; }
        .fixed { border: 1px solid #0a0; padding: 10px; margin: 5px; background: #e6ffe6; }
        .info { border: 1px solid #00f; padding: 10px; margin: 5px; background: #e6f3ff; }
    </style>
</head>
<body>
    <h1>🔍 Duplicate Fix Verification Test</h1>
    
    <?php
    try {
        // Bootstrap Laravel
        require_once __DIR__ . '/../vendor/autoload.php';
        $app = require_once __DIR__ . '/../bootstrap/app.php';
        
        $kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
        $request = Illuminate\Http\Request::createFromGlobals();
        $kernel->bootstrap();
        
        echo "<div class='success'>✅ Laravel Bootstrapped Successfully</div>";
        echo "<p><strong>App:</strong> " . config('app.name') . "</p>";
        echo "<p><strong>PHP Version:</strong> " . phpversion() . "</p>";
        
        // Step 1: Get raw data (before duplicate prevention)
        echo "<h2>📊 Step 1: Raw Data Analysis</h2>";
        $directDb = new \App\Services\DirectDatabaseService();
        $rawData = $directDb->getDeliveryScheduleData(100);
        
        echo "<div class='info'>";
        echo "<strong>Raw Data Retrieved:</strong><br>";
        echo "• Deliveries: " . $rawData['deliveries']->count() . "<br>";
        echo "• Collections: " . $rawData['collections']->count() . "<br>";
        echo "• Total: " . ($rawData['deliveries']->count() + $rawData['collections']->count());
        echo "</div>";
        
        // Step 2: Analyze raw duplicates
        echo "<h2>🔄 Step 2: Raw Duplicate Analysis</h2>";
        $deliveryEmails = [];
        $collectionEmails = [];
        $crossTypeDuplicates = [];
        
        foreach ($rawData['deliveries'] as $delivery) {
            $email = strtolower(trim($delivery['customer_email']));
            $deliveryEmails[$email] = true;
        }
        
        foreach ($rawData['collections'] as $collection) {
            $email = strtolower(trim($collection['customer_email']));
            $collectionEmails[$email] = true;
            
            if (isset($deliveryEmails[$email])) {
                $crossTypeDuplicates[] = $email;
            }
        }
        
        echo "<div class='warning'>";
        echo "<strong>Cross-Type Duplicates Found (Before Fix):</strong> " . count($crossTypeDuplicates) . "<br>";
        if (count($crossTypeDuplicates) > 0) {
            echo "<strong>Duplicate Emails:</strong><br>";
            foreach (array_slice($crossTypeDuplicates, 0, 5) as $email) {
                echo "• $email<br>";
            }
            if (count($crossTypeDuplicates) > 5) {
                echo "• ... and " . (count($crossTypeDuplicates) - 5) . " more<br>";
            }
        }
        echo "</div>";
        
        // Step 3: Apply controller transformation (with duplicate prevention)
        echo "<h2>🔧 Step 3: Apply Duplicate Prevention Logic</h2>";
        $controller = new \App\Http\Controllers\Admin\DeliveryController();
        
        // Use reflection to access the private method
        $reflection = new ReflectionClass($controller);
        $method = $reflection->getMethod('transformScheduleData');
        $method->setAccessible(true);
        
        // Apply the transformation
        $transformedData = $method->invoke($controller, $rawData);
        
        // Count final results
        $finalDeliveryCount = 0;
        $finalCollectionCount = 0;
        $finalDeliveryEmails = [];
        $finalCollectionEmails = [];
        
        foreach ($transformedData['data'] as $dateGroup) {
            $finalDeliveryCount += count($dateGroup['deliveries']);
            $finalCollectionCount += count($dateGroup['collections']);
            
            foreach ($dateGroup['deliveries'] as $delivery) {
                $email = strtolower(trim($delivery['customer_email']));
                $finalDeliveryEmails[$email] = true;
            }
            
            foreach ($dateGroup['collections'] as $collection) {
                $email = strtolower(trim($collection['customer_email']));
                $finalCollectionEmails[$email] = true;
            }
        }
        
        echo "<div class='info'>";
        echo "<strong>After Duplicate Prevention:</strong><br>";
        echo "• Final Deliveries: $finalDeliveryCount<br>";
        echo "• Final Collections: $finalCollectionCount<br>";
        echo "• Total Final: " . ($finalDeliveryCount + $finalCollectionCount);
        echo "</div>";
        
        // Step 4: Check for remaining cross-type duplicates
        echo "<h2>✅ Step 4: Verify Duplicate Prevention</h2>";
        $remainingDuplicates = [];
        
        foreach ($finalCollectionEmails as $email => $true) {
            if (isset($finalDeliveryEmails[$email])) {
                $remainingDuplicates[] = $email;
            }
        }
        
        if (count($remainingDuplicates) === 0) {
            echo "<div class='fixed'>";
            echo "<strong>🎉 SUCCESS! No cross-type duplicates remain!</strong><br>";
            echo "The duplicate prevention logic is working correctly.";
            echo "</div>";
        } else {
            echo "<div class='duplicate'>";
            echo "<strong>❌ ISSUE: " . count($remainingDuplicates) . " cross-type duplicates still exist:</strong><br>";
            foreach ($remainingDuplicates as $email) {
                echo "• $email<br>";
            }
            echo "</div>";
        }
        
        // Step 5: Ben Anderson specific test
        echo "<h2>👤 Step 5: Ben Anderson Specific Test</h2>";
        $benEmail = 'anderson.ben0405@gmail.com';
        $benInFinalDeliveries = isset($finalDeliveryEmails[$benEmail]);
        $benInFinalCollections = isset($finalCollectionEmails[$benEmail]);
        
        echo "<div class='info'>";
        echo "<strong>Ben Anderson (anderson.ben0405@gmail.com):</strong><br>";
        echo "• In Final Deliveries: " . ($benInFinalDeliveries ? '✅ YES' : '❌ NO') . "<br>";
        echo "• In Final Collections: " . ($benInFinalCollections ? '✅ YES' : '❌ NO') . "<br>";
        
        if ($benInFinalDeliveries && $benInFinalCollections) {
            echo "<span class='error'>❌ Ben appears in BOTH sections (still duplicated)</span>";
        } else if ($benInFinalDeliveries || $benInFinalCollections) {
            echo "<span class='success'>✅ Ben appears in only ONE section (correct)</span>";
        } else {
            echo "<span class='warning'>⚠️ Ben doesn't appear in either section</span>";
        }
        echo "</div>";
        
        // Step 6: Summary
        echo "<h2>📋 Step 6: Final Summary</h2>";
        $duplicatesRemoved = count($crossTypeDuplicates) - count($remainingDuplicates);
        
        echo "<div class='info'>";
        echo "<strong>Duplicate Prevention Results:</strong><br>";
        echo "• Original cross-type duplicates: " . count($crossTypeDuplicates) . "<br>";
        echo "• Remaining cross-type duplicates: " . count($remainingDuplicates) . "<br>";
        echo "• Duplicates successfully removed: $duplicatesRemoved<br>";
        echo "• Success rate: " . (count($crossTypeDuplicates) > 0 ? round(($duplicatesRemoved / count($crossTypeDuplicates)) * 100, 1) : 100) . "%";
        echo "</div>";
        
        if (count($remainingDuplicates) === 0) {
            echo "<div class='fixed'>";
            echo "<h3>🎯 CONCLUSION: DUPLICATE FIX IS WORKING! ✅</h3>";
            echo "<p>The duplicate prevention logic successfully eliminates all cross-type duplicates.</p>";
            echo "</div>";
        } else {
            echo "<div class='duplicate'>";
            echo "<h3>🎯 CONCLUSION: DUPLICATE FIX NEEDS ADJUSTMENT ❌</h3>";
            echo "<p>Some cross-type duplicates are still present in the final output.</p>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='error'>";
        echo "<strong>❌ Error:</strong> " . htmlspecialchars($e->getMessage()) . "<br>";
        echo "<strong>File:</strong> " . $e->getFile() . " (Line " . $e->getLine() . ")";
        echo "</div>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    }
    ?>
    
    <hr>
    <p><em>Test completed at <?php echo date('Y-m-d H:i:s'); ?></em></p>
</body>
</html>
