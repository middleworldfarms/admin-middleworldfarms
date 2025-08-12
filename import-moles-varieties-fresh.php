<?php
/**
 * Fresh Moles Seeds Variety Import
 * Deletes existing varieties and imports clean data with correct crop type mappings
 */

require_once 'vendor/autoload.php';

use Illuminate\Foundation\Application;
use App\Services\FarmOSAuthService;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "🌱 Fresh Moles Seeds Variety Import\n";
echo "==================================\n\n";

class FarmOSVarietyImporter {
    private $authService;
    private $baseUrl = 'https://farmos.middleworldfarms.org';
    
    public function __construct() {
        $this->authService = FarmOSAuthService::getInstance();
    }
    
    private function makeRequest($method, $endpoint, $data = null) {
        $headers = [
            'Authorization: Bearer ' . $this->authService->getAccessToken(),
            'Accept: application/vnd.api+json',
            'Content-Type: application/vnd.api+json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        } elseif ($method === 'POST' && $data) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return [
            'success' => $httpCode >= 200 && $httpCode < 300,
            'code' => $httpCode,
            'data' => json_decode($response, true)
        ];
    }
    
    public function deleteAllVarieties() {
        echo "🗑️  Deleting existing varieties...\n";
        
        // Get all existing varieties with small page size
        $response = $this->makeRequest('GET', '/api/taxonomy_term/plant_variety?page[limit]=25');
        
        if (!$response['success'] || !isset($response['data']['data'])) {
            echo "❌ Failed to fetch existing varieties\n";
            return false;
        }
        
        $varieties = $response['data']['data'];
        echo "Found " . count($varieties) . " existing varieties to delete\n";
        
        $deleted = 0;
        foreach ($varieties as $variety) {
            $deleteResponse = $this->makeRequest('DELETE', '/api/taxonomy_term/plant_variety/' . $variety['id']);
            if ($deleteResponse['success']) {
                $deleted++;
                echo "  ✓ Deleted: " . ($variety['attributes']['name'] ?? 'Unknown') . "\n";
            } else {
                echo "  ❌ Failed to delete: " . ($variety['attributes']['name'] ?? 'Unknown') . "\n";
            }
        }
        
        echo "Deleted $deleted varieties\n\n";
        return true;
    }
    
    public function getCropTypes() {
        echo "📋 Getting crop types...\n";
        
        $response = $this->makeRequest('GET', '/api/taxonomy_term/plant_type?page[limit]=1000');
        
        if (!$response['success'] || !isset($response['data']['data'])) {
            echo "❌ Failed to fetch crop types\n";
            return [];
        }
        
        $cropTypes = [];
        foreach ($response['data']['data'] as $type) {
            $name = $type['attributes']['name'] ?? '';
            $cropTypes[strtolower($name)] = $type['id'];
        }
        
        echo "Found " . count($cropTypes) . " crop types\n\n";
        return $cropTypes;
    }
    
    public function cleanCropTypeName($csvCropType) {
        // Map common variations to correct crop type names
        $mappings = [
            'asparagus' => 'asparagus',
            'aubergine' => 'aubergine', 
            'broad beans' => 'broad beans',
            'broad_beans' => 'broad beans',
            'french beans' => 'french beans',
            'french_beans' => 'french beans',
            'runner beans' => 'runner beans',
            'runner_beans' => 'runner beans',
            'beetroot' => 'beetroot',
            'broccoli' => 'broccoli',
            'cabbage' => 'cabbage',
            'carrot' => 'carrot',
            'cauliflower' => 'cauliflower',
            'celeriac' => 'celeriac',
            'celery' => 'celery',
            'chives' => 'chives',
            'courgette' => 'courgette',
            'cucumber' => 'cucumber',
            'endive' => 'endive',
            'florence fennel' => 'florence fennel',
            'fennel' => 'florence fennel',
            'greens' => 'salad greens',
            'herbs' => 'herbs'
        ];
        
        $cleaned = strtolower(trim($csvCropType));
        return $mappings[$cleaned] ?? $cleaned;
    }
    
    public function fixKnownMappings($varietyName, $csvCropType) {
        // Fix specific varieties that are incorrectly mapped
        $knownFixes = [
            'F1 Redbor' => 'kale',
            'Redbor' => 'kale', 
            'Kapral' => 'kale',
            'Black Magic' => 'kale',
            'Dwarf Green Curled' => 'kale'
        ];
        
        $cleanName = trim($varietyName);
        if (isset($knownFixes[$cleanName])) {
            return $knownFixes[$cleanName];
        }
        
        return $this->cleanCropTypeName($csvCropType);
    }
    
    public function importVarieties($csvFile) {
        echo "📥 Importing varieties from $csvFile...\n";
        
        if (!file_exists($csvFile)) {
            echo "❌ CSV file not found\n";
            return false;
        }
        
        $cropTypes = $this->getCropTypes();
        
        $handle = fopen($csvFile, 'r');
        if (!$handle) {
            echo "❌ Could not open CSV file\n";
            return false;
        }
        
        // Skip header
        fgetcsv($handle);
        
        $imported = 0;
        $skipped = 0;
        $errors = 0;
        
        while (($data = fgetcsv($handle)) !== FALSE) {
            $name = trim($data[0] ?? '');
            $varietyCode = trim($data[1] ?? '');
            $csvCropType = trim($data[2] ?? '');
            $daysToMaturity = intval($data[3] ?? 60);
            $description = trim($data[6] ?? '');
            
            // Skip junk entries
            if (empty($name) || 
                strlen($name) < 2 || 
                strpos($name, 'vegetables') !== false ||
                strpos($name, 'organic') !== false ||
                strpos($name, 'YP') !== false ||
                $name === 'veg' ||
                $name === 'Bveg' ||
                empty($csvCropType) ||
                $csvCropType === 'asparagus' && strpos($name, 'Green Knight') === false && strpos($name, 'Gijnlim') === false) {
                $skipped++;
                continue;
            }
            
            // Fix crop type mapping
            $correctCropType = $this->fixKnownMappings($name, $csvCropType);
            $cropTypeId = $cropTypes[strtolower($correctCropType)] ?? null;
            
            if (!$cropTypeId) {
                echo "  ⚠️  Unknown crop type '$correctCropType' for variety '$name'\n";
                $skipped++;
                continue;
            }
            
            // Create variety
            $varietyData = [
                'data' => [
                    'type' => 'taxonomy_term--plant_variety',
                    'attributes' => [
                        'name' => $name,
                        'description' => [
                            'value' => $description . ($varietyCode ? " (Code: $varietyCode)" : ''),
                            'format' => 'basic_html'
                        ]
                    ],
                    'relationships' => [
                        'parent' => [
                            'data' => [
                                [
                                    'type' => 'taxonomy_term--plant_type',
                                    'id' => $cropTypeId
                                ]
                            ]
                        ]
                    ]
                ]
            ];
            
            $response = $this->makeRequest('POST', '/api/taxonomy_term/plant_variety', $varietyData);
            
            if ($response['success']) {
                $imported++;
                echo "  ✓ Imported: $name ($correctCropType)\n";
            } else {
                $errors++;
                echo "  ❌ Failed: $name - " . ($response['data']['errors'][0]['detail'] ?? 'Unknown error') . "\n";
            }
            
            // Rate limiting
            usleep(100000); // 0.1 second delay
        }
        
        fclose($handle);
        
        echo "\n📊 Import Summary:\n";
        echo "  ✅ Imported: $imported varieties\n";
        echo "  ⏭️  Skipped: $skipped entries\n"; 
        echo "  ❌ Errors: $errors\n\n";
        
        return true;
    }
}

// Main execution
try {
    $importer = new FarmOSVarietyImporter();
    
    echo "🔐 Authenticating with farmOS...\n";
    if (!$importer->authService->authenticate()) {
        throw new Exception("Failed to authenticate with farmOS");
    }
    echo "✅ Authentication successful\n\n";
    
    // Step 1: Delete all existing varieties
    if (!$importer->deleteAllVarieties()) {
        throw new Exception("Failed to delete existing varieties");
    }
    
    // Step 2: Import fresh varieties
    if (!$importer->importVarieties('moles_varieties_corrected.csv')) {
        throw new Exception("Failed to import varieties");
    }
    
    echo "🎉 Fresh import complete!\n";
    echo "Your farmOS taxonomy now has clean variety data with correct crop type mappings.\n";
    echo "Redbor and other kale varieties should now be properly categorized!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
