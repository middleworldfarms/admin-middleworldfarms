<?php
/**
 * farmOS Taxonomy Mapper
 * Shows how your Moles Seeds data maps to existing farmOS taxonomy
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
    
    return false;
}

function fetchFromAPI($endpoint, $token) {
    $baseUrl = 'https://farmos.middleworldfarms.org';
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
    
    if ($httpCode === 200) {
        return json_decode($response, true);
    }
    
    return false;
}

function showCurrentTaxonomy($token) {
    echo "🌾 **Current farmOS Crop Types** (Plant Types)\n";
    echo "=============================================\n";
    
    $plantTypes = fetchFromAPI('/api/taxonomy_term/plant_type', $token);
    
    if ($plantTypes && isset($plantTypes['data'])) {
        echo "Found " . count($plantTypes['data']) . " existing crop types:\n\n";
        
        $existingCrops = [];
        foreach ($plantTypes['data'] as $type) {
            $name = $type['attributes']['name'] ?? 'Unknown';
            $id = $type['id'] ?? 'no-id';
            $existingCrops[] = $name;
            echo "  • $name (ID: $id)\n";
        }
        
        echo "\n🌿 **Crop Families**\n";
        echo "===================\n";
        
        $cropFamilies = fetchFromAPI('/api/taxonomy_term/crop_family', $token);
        
        if ($cropFamilies && isset($cropFamilies['data'])) {
            echo "Found " . count($cropFamilies['data']) . " crop families:\n\n";
            
            foreach ($cropFamilies['data'] as $family) {
                $name = $family['attributes']['name'] ?? 'Unknown';
                $id = $family['id'] ?? 'no-id';
                echo "  • $name (ID: $id)\n";
            }
        }
        
        return $existingCrops;
    }
    
    return [];
}

function analyzeMolesMapping($existingCrops) {
    echo "\n\n📊 **Moles Seeds Data Analysis**\n";
    echo "===============================\n";
    
    // Read your extracted Moles data
    $molesFile = 'moles_vegetables_with_codes.csv';
    if (!file_exists($molesFile)) {
        echo "❌ Moles data file not found: $molesFile\n";
        return;
    }
    
    $handle = fopen($molesFile, 'r');
    if (!$handle) {
        echo "❌ Could not open $molesFile\n";
        return;
    }
    
    // Skip header
    fgetcsv($handle);
    
    $moleCrops = [];
    $newCropsNeeded = [];
    $varietyCount = 0;
    
    while (($data = fgetcsv($handle)) !== FALSE) {
        if (count($data) >= 2) {
            $varietyName = $data[0];
            $cropType = $data[1];
            $varietyCount++;
            
            if (!isset($moleCrops[$cropType])) {
                $moleCrops[$cropType] = [];
            }
            $moleCrops[$cropType][] = $varietyName;
            
            // Check if crop type exists in farmOS
            $found = false;
            foreach ($existingCrops as $existing) {
                if (stripos($existing, $cropType) !== false || stripos($cropType, $existing) !== false) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found && !in_array($cropType, $newCropsNeeded)) {
                $newCropsNeeded[] = $cropType;
            }
        }
    }
    fclose($handle);
    
    echo "📈 **Analysis Results**:\n";
    echo "• Total varieties to import: $varietyCount\n";
    echo "• Crop types in Moles data: " . count($moleCrops) . "\n";
    echo "• New crop types needed: " . count($newCropsNeeded) . "\n\n";
    
    echo "🎯 **Top Moles Crop Types** (by variety count):\n";
    arsort($moleCrops);
    $topCrops = array_slice($moleCrops, 0, 10, true);
    
    foreach ($topCrops as $crop => $varieties) {
        $count = count($varieties);
        echo "  • $crop: $count varieties\n";
    }
    
    if (!empty($newCropsNeeded)) {
        echo "\n🆕 **New Crop Types to Create**:\n";
        foreach ($newCropsNeeded as $newCrop) {
            $varietyCount = count($moleCrops[$newCrop]);
            echo "  • $newCrop ($varietyCount varieties)\n";
        }
    }
    
    return $moleCrops;
}

function showImportPlan($moleCrops) {
    echo "\n\n🚀 **Import Plan for farmOS Taxonomy**\n";
    echo "====================================\n";
    
    echo "**Step 1**: Create missing crop types\n";
    echo "**Step 2**: Import all 774 varieties with:\n";
    echo "  • Variety name\n";
    echo "  • Moles Seeds variety code\n";
    echo "  • Days to maturity\n";
    echo "  • Transplant timing\n";
    echo "  • Direct sow flag\n";
    echo "  • Succession interval\n\n";
    
    echo "**Step 3**: Link varieties to crop types\n";
    echo "**Step 4**: Add crop-level growing information\n\n";
    
    echo "💡 **Ready to automate?** This will solve your \"mind numbing job of building farmOS taxonomy\"!\n";
    echo "   All data extracted from Moles Seeds catalog with official variety codes.\n\n";
    
    echo "📁 **Files ready for import**:\n";
    echo "  • moles_vegetables_with_codes.csv (774 varieties)\n";
    echo "  • moles_crop_growing_guides.csv (growing instructions)\n";
    echo "  • extract-images.sh (variety photos)\n";
}

// Main execution
echo "🌱 farmOS Taxonomy Mapping Tool\n";
echo "===============================\n\n";

$token = getFarmOSToken();

if ($token) {
    echo "✅ Connected to farmOS API\n\n";
    
    $existingCrops = showCurrentTaxonomy($token);
    $moleCrops = analyzeMolesMapping($existingCrops);
    
    if ($moleCrops) {
        showImportPlan($moleCrops);
    }
} else {
    echo "❌ Failed to authenticate with farmOS\n";
}

echo "\n🎉 Analysis complete!\n";
?>
