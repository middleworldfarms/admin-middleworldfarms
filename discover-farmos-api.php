<?php
/**
 * Simple farmOS API Structure Explorer
 * Uses your working OAuth2 credentials to explore available endpoints
 */

function getFarmOSToken() {
    $baseUrl = 'https://farmos.middleworldfarms.org';
    $tokenUrl = $baseUrl . '/oauth/token';
    
    $postData = [
        'grant_type' => 'client_credentials',
        'client_id' => 'NyIv5ejXa5xYRLKv0BXjUi-IHn3H2qbQQ3m-h2qp_xY',
        'client_secret' => 'Qw7!pZ2rT9@xL6vB1#eF4sG8uJ0mN5cD',
        'scope' => 'farm_manager'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokenUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $tokenData = json_decode($response, true);
        return $tokenData['access_token'] ?? false;
    }
    
    echo "❌ Token request failed with HTTP $httpCode\n";
    echo "Response: $response\n";
    return false;
}

function exploreAPI($token) {
    $baseUrl = 'https://farmos.middleworldfarms.org';
    
    echo "🔍 Exploring farmOS API structure...\n\n";
    
    // Test different potential API endpoints
    $endpoints = [
        '/api',
        '/jsonapi',
        '/api/taxonomy_term',
        '/jsonapi/taxonomy_term',
        '/api/taxonomy_term/plant_type',
        '/jsonapi/taxonomy_term/plant_type',
        '/api/taxonomy_term/crop_family',
        '/jsonapi/taxonomy_term/crop_family',
        '/api/asset/plant',
        '/jsonapi/asset/plant',
        '/api/log',
        '/jsonapi/log',
        '/api/plan',
        '/jsonapi/plan'
    ];
    
    foreach ($endpoints as $endpoint) {
        $url = $baseUrl . $endpoint;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token,
            'Accept: application/vnd.api+json',
            'Content-Type: application/vnd.api+json'
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "📍 Testing: $endpoint\n";
        echo "   HTTP Status: $httpCode\n";
        
        if ($httpCode === 200) {
            echo "   ✅ SUCCESS - Endpoint exists!\n";
            
            // Try to parse response
            $data = json_decode($response, true);
            if ($data && isset($data['data'])) {
                $count = is_array($data['data']) ? count($data['data']) : 'single item';
                echo "   📊 Found: $count\n";
                
                // Show first item structure if available
                if (is_array($data['data']) && !empty($data['data'])) {
                    $firstItem = $data['data'][0];
                    if (isset($firstItem['type'])) {
                        echo "   🏷️ Type: " . $firstItem['type'] . "\n";
                    }
                    if (isset($firstItem['attributes']['name'])) {
                        echo "   📝 Example: " . $firstItem['attributes']['name'] . "\n";
                    }
                    if (isset($firstItem['attributes']['drupal_internal__vid'])) {
                        echo "   📚 Vocabulary: " . $firstItem['attributes']['drupal_internal__vid'] . "\n";
                    }
                }
            } elseif ($data && isset($data['links'])) {
                echo "   🔗 Contains navigation links\n";
                if (isset($data['links']['related'])) {
                    foreach ($data['links']['related'] as $link) {
                        echo "     - " . ($link['meta']['title'] ?? 'Unknown') . "\n";
                    }
                }
            }
        } else {
            echo "   ❌ Failed (HTTP $httpCode)\n";
            
            // Show first 200 chars of error for context
            if (strlen($response) > 0) {
                $shortError = substr($response, 0, 200);
                if (strlen($response) > 200) $shortError .= "...";
                echo "   Error preview: " . trim($shortError) . "\n";
            }
        }
        echo "\n";
    }
}

function showDiscoveredStructure() {
    echo "\n🎯 **farmOS Taxonomy Import Strategy**\n";
    echo "=====================================\n\n";
    
    echo "Based on API exploration, here's how to import your seed data:\n\n";
    
    echo "1. **Find Working Endpoint**: Use the successful endpoint from above\n";
    echo "2. **Import Crop Types**: Create main categories (Lettuce, Carrot, etc.)\n";
    echo "3. **Import Varieties**: Link varieties to their crop types\n";
    echo "4. **Add Custom Fields**: Days to maturity, variety codes, etc.\n\n";
    
    echo "📁 **Your Data Ready for Import**:\n";
    echo "- moles_vegetables_with_codes.csv (774 varieties)\n";
    echo "- moles_crop_growing_guides.csv (12 crop guides)\n";
    echo "- All with official Moles Seeds codes and timing data\n\n";
    
    echo "💡 **Next Step**: Use the working endpoint to build import script!\n";
}

// Main execution
echo "🚀 farmOS API Discovery Tool\n";
echo "============================\n\n";

$token = getFarmOSToken();

if ($token) {
    echo "✅ Successfully authenticated with farmOS\n\n";
    exploreAPI($token);
    showDiscoveredStructure();
} else {
    echo "❌ Failed to authenticate\n";
    echo "Check your OAuth2 credentials in the script\n";
}

echo "\n🎉 Discovery complete!\n";
?>
