<?php

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel minimal environment
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

use App\Services\HolisticAICropService;
use App\Services\FarmOSApi;

// Test the timeout fix for Phi-3 AI integration
function testTimeoutFix() {
    echo "🧪 Testing Timeout Fix for Phi-3 AI Integration\n";
    echo "============================================\n\n";
    
    try {
        // Initialize the services
        $farmOSApi = new FarmOSApi();
        $holisticService = new HolisticAICropService($farmOSApi);
        
        echo "✅ Services initialized successfully\n";
        
        // Test with Brussels Sprout F1 Doric (known variety with comprehensive data)
        echo "🥬 Testing Brussels Sprout F1 Doric harvest window calculation...\n";
        
        $startTime = microtime(true);
        
        $harvestWindow = $holisticService->getOptimalHarvestWindow(
            'Brussels Sprout',
            'F1 Doric',
            'Field A',
            []
        );
        
        $endTime = microtime(true);
        $duration = round($endTime - $startTime, 2);
        
        echo "⏱️  Request completed in {$duration} seconds\n";
        
        if ($harvestWindow && isset($harvestWindow['max_harvest_days'])) {
            echo "✅ SUCCESS: Received comprehensive AI response!\n";
            echo "📊 Harvest Window Data:\n";
            echo "   - Max Harvest Days: " . $harvestWindow['max_harvest_days'] . "\n";
            echo "   - Optimal Harvest Days: " . $harvestWindow['optimal_harvest_days'] . "\n";
            echo "   - Recommended Successions: " . $harvestWindow['recommended_successions'] . "\n";
            echo "   - Days Between Plantings: " . $harvestWindow['days_between_plantings'] . "\n";
            echo "   - Confidence Level: " . $harvestWindow['confidence_level'] . "\n";
            
            if (isset($harvestWindow['reasoning'])) {
                echo "🧠 AI Reasoning: " . substr($harvestWindow['reasoning'], 0, 200) . "...\n";
            }
            
            if ($duration < 120) {
                echo "🚀 Performance: Within timeout limits ({$duration}s < 120s)\n";
            } else {
                echo "⚠️  Performance: Close to timeout limit ({$duration}s)\n";
            }
            
        } else {
            echo "❌ FAILED: No comprehensive data received from AI\n";
            echo "📄 Response received: " . json_encode($harvestWindow) . "\n";
        }
        
    } catch (\Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
        echo "📍 File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}

// Run the test
testTimeoutFix();

echo "\n🔍 Next Steps:\n";
echo "1. If successful: Enhance AI prompt for comprehensive Quick Form data\n";
echo "2. If timeout: Check Phi-3 service status and system resources\n";
echo "3. If failed: Verify Phi-3 API endpoint and JSON parsing\n";

?>
