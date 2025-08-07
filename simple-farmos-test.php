<?php
/**
 * Simple farmOS API Taxonomy Tester (using cURL)
 * Tests farmOS connection and explores taxonomy structure
 */

function testFarmOSConnection() {
    echo "🌱 farmOS API Taxonomy Explorer\n";
    echo "==============================\n\n";
    
    // farmOS configuration - update these values
    $farmosUrl = 'https://farmos.middleworldfarms.org';  // Your farmOS URL
    $username = 'admin';  // Your farmOS username
    $password = 'Mackie1974';  // Add your farmOS password here
    
    if (empty($password)) {
        echo "⚠️ Please update the \$password variable in this script\n";
        echo "   with your farmOS admin password\n\n";
        return false;
    }
    
    echo "🔗 Testing connection to: $farmosUrl\n";
    echo "👤 Username: $username\n\n";
    
    // Get OAuth token
    $token = getFarmOSToken($farmosUrl, $username, $password);
    
    if (!$token) {
        echo "❌ Failed to get OAuth token\n";
        return false;
    }
    
    echo "✅ Successfully authenticated with farmOS\n\n";
    
    // Test API endpoints
    testTaxonomyEndpoints($farmosUrl, $token);
    
    return true;
}

function getFarmOSToken($baseUrl, $username, $password) {
    $tokenUrl = $baseUrl . '/oauth/token';
    
    $postData = [
        'grant_type' => 'client_credentials',
        'client_id' => 'NyIv5ejXa5xYRLKv0BXjUi-IHn3H2qbQQ3m-h2qp_xY',  // Your OAuth2 client ID
        'client_secret' => 'Qw7!pZ2rT9@xL6vB1#eF4sG8uJ0mN5cD',  // Your OAuth2 client secret
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
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);  // For development only
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        echo "❌ Token request failed with HTTP $httpCode\n";
        echo "Response: $response\n";
        return false;
    }
    
    $tokenData = json_decode($response, true);
    return $tokenData['access_token'] ?? false;
}

function apiRequest($url, $token, $method = 'GET', $data = null) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $token,
        'Accept: application/vnd.api+json',
        'Content-Type: application/vnd.api+json'
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    if ($method === 'POST' && $data) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode >= 200 && $httpCode < 300) {
        return json_decode($response, true);
    }
    
    echo "❌ API request failed with HTTP $httpCode\n";
    echo "URL: $url\n";
    echo "Response: $response\n";
    return false;
}

function testTaxonomyEndpoints($baseUrl, $token) {
    echo "📚 Exploring farmOS Taxonomy Structure\n";
    echo "=====================================\n";
    
    // Test different endpoints
    $endpoints = [
        'taxonomy_term' => 'Taxonomy Terms',
        'asset--plant' => 'Plant Assets',
        'plan' => 'Plans',
        'log' => 'Logs'
    ];
    
    foreach ($endpoints as $endpoint => $description) {
        echo "\n🔍 Testing: $description\n";
        echo str_repeat('-', 30) . "\n";
        
        $url = $baseUrl . '/api/' . $endpoint . '?page[limit]=5';
        $result = apiRequest($url, $token);
        
        if ($result && isset($result['data'])) {
            $count = count($result['data']);
            echo "✅ Found $count items (showing first 5)\n";
            
            foreach ($result['data'] as $item) {
                $id = $item['id'] ?? 'no-id';
                $type = $item['type'] ?? 'unknown';
                $name = $item['attributes']['name'] ?? 'Unnamed';
                
                echo "  - $name (ID: $id, Type: $type)\n";
                
                // For taxonomy terms, show vocabulary
                if (isset($item['attributes']['drupal_internal__vid'])) {
                    $vocab = $item['attributes']['drupal_internal__vid'];
                    echo "    Vocabulary: $vocab\n";
                }
            }
        } else {
            echo "⚠️ No data found or access denied\n";
        }
    }
    
    // Specific taxonomy vocabulary exploration
    echo "\n\n🏷️ Exploring Specific Vocabularies\n";
    echo "==================================\n";
    
    $vocabularies = [
        'plant_type' => 'Plant Types/Crop Types',
        'crop_family' => 'Crop Families', 
        'plant_variety' => 'Plant Varieties',
        'season' => 'Seasons',
        'unit' => 'Units'
    ];
    
    foreach ($vocabularies as $vocab => $description) {
        echo "\n🌿 $description (vocabulary: $vocab)\n";
        
        $url = $baseUrl . '/api/taxonomy_term?filter[drupal_internal__vid]=' . $vocab . '&page[limit]=10';
        $result = apiRequest($url, $token);
        
        if ($result && isset($result['data']) && !empty($result['data'])) {
            echo "✅ Found " . count($result['data']) . " terms\n";
            
            foreach ($result['data'] as $term) {
                $name = $term['attributes']['name'] ?? 'Unnamed';
                $id = $term['id'] ?? 'no-id';
                echo "  - $name (ID: $id)\n";
            }
        } else {
            echo "⚠️ No terms found for vocabulary '$vocab'\n";
        }
    }
}

function showImportGuidance() {
    echo "\n\n📋 farmOS Import Guidance\n";
    echo "========================\n";
    
    echo "Based on typical farmOS structure:\n\n";
    
    echo "1. 🌾 **Crop Types** (taxonomy_term with vocab 'plant_type'):\n";
    echo "   - Create main categories: Lettuce, Carrot, Tomato, etc.\n";
    echo "   - Add growing information from moles_crop_growing_guides.csv\n\n";
    
    echo "2. 🌱 **Varieties** (taxonomy_term with vocab 'plant_variety'):\n";
    echo "   - Link to parent crop type\n";
    echo "   - Import from moles_vegetables_with_codes.csv\n";
    echo "   - Include variety codes, maturity days, etc.\n\n";
    
    echo "3. 📊 **Custom Fields** (to add):\n";
    echo "   - days_to_maturity (integer)\n";
    echo "   - days_to_transplant (integer)\n";
    echo "   - direct_sow (boolean)\n";
    echo "   - succession_interval (integer)\n";
    echo "   - variety_code (text)\n";
    echo "   - seeds_per_gram (integer)\n\n";
    
    echo "4. 🖼️ **Images**:\n";
    echo "   - Use extract-images.sh to get variety photos\n";
    echo "   - Upload to farmOS media library\n";
    echo "   - Associate with taxonomy terms\n\n";
    
    echo "💡 **Next Steps**:\n";
    echo "1. Run this script with your farmOS credentials\n";
    echo "2. Create crop type taxonomy terms\n";
    echo "3. Import varieties with relationships\n";
    echo "4. Add succession planning features\n";
}

// Main execution
echo "🚀 Starting farmOS API exploration...\n\n";

$success = testFarmOSConnection();

if (!$success) {
    echo "\n💡 **To continue**:\n";
    echo "1. Edit this script and add your farmOS password\n";
    echo "2. Verify your farmOS URL is correct\n";
    echo "3. Ensure your farmOS has API access enabled\n\n";
}

showImportGuidance();

echo "\n🎉 Exploration complete!\n";
echo "\n📁 **Your extracted data ready for import**:\n";
echo "- moles_vegetables_with_codes.csv (774 varieties)\n";
echo "- moles_crop_growing_guides.csv (crop information)\n";
echo "- extract-images.sh (for variety photos)\n";
?>
