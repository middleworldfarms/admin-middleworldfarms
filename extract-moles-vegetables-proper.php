<?php
/**
 * Proper Moles Seeds Vegetable Extraction - Pages 170-233
 * Extract clean variety data from specific pages with proper crop type mapping
 */

require_once 'vendor/autoload.php';

echo "🌱 Moles Seeds Vegetable Extraction (Pages 170-233)\n";
echo "===================================================\n\n";

if (!file_exists('Moles_Seeds_2025_Catalogue.pdf')) {
    echo "❌ PDF file 'Moles_Seeds_2025_Catalogue.pdf' not found\n";
    exit(1);
}

echo "📖 Extracting text from vegetable pages (170-233)...\n";

// Extract text from specific pages using pdftotext
$command = "pdftotext -f 170 -l 233 -layout 'Moles_Seeds_2025_Catalogue.pdf' vegetable_pages_clean.txt";
$output = shell_exec($command);

if (!file_exists('vegetable_pages_clean.txt')) {
    echo "❌ Failed to extract text from PDF\n";
    exit(1);
}

$text = file_get_contents('vegetable_pages_clean.txt');
$lines = explode("\n", $text);

echo "✅ Extracted " . count($lines) . " lines from PDF\n\n";

// Known crop types and their variety patterns
$cropTypes = [
    'Asparagus' => ['patterns' => ['F1 ', 'Gijnlim', 'Mondeo', 'Equinox', 'Connover'], 'section' => 'asparagus'],
    'Aubergine' => ['patterns' => ['F1 ', 'Black Beauty', 'Green Knight', 'White Knight'], 'section' => 'aubergine'],
    'Broad Beans' => ['patterns' => ['Aquadulce', 'The Sutton', 'Imperial Green'], 'section' => 'broad'],
    'French Beans' => ['patterns' => ['Algarve', 'Goldfield', 'Nassau', 'Dior', 'Stanley'], 'section' => 'french'],
    'Runner Beans' => ['patterns' => ['Enorma', 'Lady Di', 'Red Flowered'], 'section' => 'runner'],
    'Beetroot' => ['patterns' => ['Detroit', 'Chioggia', 'Bulls Blood', 'Bona', 'Pablo'], 'section' => 'beetroot'],
    'Kale' => ['patterns' => ['Redbor', 'Black Magic', 'Kapitan', 'Curled', 'Tuscany'], 'section' => 'kale'],
    'Broccoli' => ['patterns' => ['Early Purple', 'Claret', 'Red Fire', 'Purple Rain'], 'section' => 'broccoli'],
    'Brussels Sprouts' => ['patterns' => ['Abacus', 'Churchill', 'Maximus', 'Bosworth'], 'section' => 'sprouts'],
    'Cabbage' => ['patterns' => ['Stonehead', 'Greyhound', 'Golden Acre', 'Wheelers'], 'section' => 'cabbage'],
    'Carrot' => ['patterns' => ['Nantes', 'Berlicum', 'Sugarsnax', 'Eskimo', 'Resistafly', 'Laguna', 'Sylvano', 'Napoli', 'Mercurio'], 'section' => 'carrot'],
    'Cauliflower' => ['patterns' => ['Romanesco', 'Cheddar', 'All Year Round'], 'section' => 'cauliflower'],
    'Celeriac' => ['patterns' => ['Brilliant', 'Monarch'], 'section' => 'celeriac'],
    'Celery' => ['patterns' => ['Celebrity', 'Victoria'], 'section' => 'celery'],
    'Courgette' => ['patterns' => ['Defender', 'Eight Ball', 'Patio Star'], 'section' => 'courgette'],
    'Cucumber' => ['patterns' => ['Telegraph', 'Marketmore', 'La Diva'], 'section' => 'cucumber'],
    'Leek' => ['patterns' => ['Musselburgh', 'Lyon', 'Toledo'], 'section' => 'leek'],
    'Lettuce' => ['patterns' => ['Iceberg', 'Lollo', 'Oak Leaf', 'Cos'], 'section' => 'lettuce'],
    'Onion' => ['patterns' => ['Stuttgart', 'Ailsa Craig', 'Red Baron'], 'section' => 'onion'],
    'Parsnip' => ['patterns' => ['Hollow Crown', 'Tender True'], 'section' => 'parsnip'],
    'Peas' => ['patterns' => ['Hurst Green', 'Ambassador', 'Sugar Snap'], 'section' => 'peas'],
    'Pepper' => ['patterns' => ['California', 'Gourmet', 'Hot'], 'section' => 'pepper'],
    'Radish' => ['patterns' => ['Cherry Belle', 'French Breakfast'], 'section' => 'radish'],
    'Spinach' => ['patterns' => ['Bloomsdale', 'Space', 'Perpetual'], 'section' => 'spinach'],
    'Sweetcorn' => ['patterns' => ['Lark', 'Swift', 'Incredible'], 'section' => 'sweetcorn'],
    'Tomato' => ['patterns' => ['Gardeners Delight', 'Moneymaker', 'Shirley'], 'section' => 'tomato']
];

$varieties = [];
$currentCropType = null;
$inVarietySection = false;

echo "🔍 Parsing text for varieties...\n";

foreach ($lines as $lineNum => $line) {
    $line = trim($line);
    
    // Skip empty lines and page headers/footers
    if (empty($line) || 
        strpos($line, 'www.molesseeds.co.uk') !== false ||
        strpos($line, 'Moles Seeds') !== false ||
        preg_match('/^\d+$/', $line)) {
        continue;
    }
    
    // Detect crop type sections
    foreach ($cropTypes as $cropName => $config) {
        if (stripos($line, $cropName) !== false && strlen($line) < 50) {
            $currentCropType = $cropName;
            $inVarietySection = true;
            echo "📂 Found section: $cropName\n";
            break;
        }
    }
    
    if (!$currentCropType) continue;
    
    // Look for variety codes (V followed by 2-3 letters and numbers)
    if (preg_match('/\b(V[A-Z]{2,3}\d+)\s+(.+)/', $line, $matches)) {
        $varietyCode = $matches[1];
        $varietyInfo = trim($matches[2]);
        
        // Extract variety name (usually first few words before description)
        $varietyName = '';
        $words = explode(' ', $varietyInfo);
        
        // Take first 1-3 words as variety name, avoiding common descriptors
        for ($i = 0; $i < min(3, count($words)); $i++) {
            $word = $words[$i];
            if (in_array(strtolower($word), ['f1', 'hybrid', 'organic', 'poly', 'glas'])) {
                $varietyName .= $word . ' ';
            } elseif (preg_match('/^[A-Z][a-z]+/', $word)) {
                $varietyName .= $word . ' ';
            } else {
                break;
            }
        }
        
        $varietyName = trim($varietyName);
        
        if (!empty($varietyName) && strlen($varietyName) > 1) {
            // Fix known mappings
            $correctCropType = $currentCropType;
            if (stripos($varietyName, 'Redbor') !== false || 
                stripos($varietyName, 'Kapital') !== false ||
                stripos($varietyName, 'Black Magic') !== false) {
                $correctCropType = 'Kale';
            }
            
            $varieties[] = [
                'name' => $varietyName,
                'code' => $varietyCode,
                'crop_type' => $correctCropType,
                'description' => $varietyInfo,
                'line' => $lineNum + 1
            ];
        }
    }
    
    // Also look for variety names in patterns without codes
    if ($currentCropType && $inVarietySection) {
        foreach ($cropTypes[$currentCropType]['patterns'] as $pattern) {
            if (stripos($line, $pattern) !== false && !preg_match('/\bV[A-Z]{2,3}\d+/', $line)) {
                // This might be a variety name
                $words = explode(' ', $line);
                $varietyName = '';
                for ($i = 0; $i < min(4, count($words)); $i++) {
                    if (preg_match('/^[A-Z]/', $words[$i])) {
                        $varietyName .= $words[$i] . ' ';
                    }
                }
                $varietyName = trim($varietyName);
                
                if (!empty($varietyName) && strlen($varietyName) > 2) {
                    $varieties[] = [
                        'name' => $varietyName,
                        'code' => '',
                        'crop_type' => $currentCropType,
                        'description' => $line,
                        'line' => $lineNum + 1
                    ];
                }
            }
        }
    }
}

echo "\n📊 Extraction Results:\n";
echo "Found " . count($varieties) . " potential varieties\n\n";

// Group by crop type
$groupedVarieties = [];
foreach ($varieties as $variety) {
    $groupedVarieties[$variety['crop_type']][] = $variety;
}

// Show carrot and beetroot counts
$carrotCount = count($groupedVarieties['Carrot'] ?? []);
$beetrootCount = count($groupedVarieties['Beetroot'] ?? []);

echo "🥕 Carrot varieties found: $carrotCount\n";
echo "🍠 Beetroot varieties found: $beetrootCount\n\n";

// Preview carrot varieties
if (isset($groupedVarieties['Carrot'])) {
    echo "🥕 CARROT VARIETIES:\n";
    foreach (array_slice($groupedVarieties['Carrot'], 0, 10) as $variety) {
        echo "  - {$variety['name']} ({$variety['code']})\n";
    }
    if ($carrotCount > 10) echo "  ... and " . ($carrotCount - 10) . " more\n";
    echo "\n";
}

// Preview beetroot varieties  
if (isset($groupedVarieties['Beetroot'])) {
    echo "🍠 BEETROOT VARIETIES:\n";
    foreach (array_slice($groupedVarieties['Beetroot'], 0, 10) as $variety) {
        echo "  - {$variety['name']} ({$variety['code']})\n";
    }
    if ($beetrootCount > 10) echo "  ... and " . ($beetrootCount - 10) . " more\n";
    echo "\n";
}

// Save clean CSV
$csvFile = 'moles_vegetables_clean_extraction.csv';
$handle = fopen($csvFile, 'w');

if ($handle) {
    fputcsv($handle, ['Name', 'Variety_Code', 'Crop_Type', 'Days_to_Maturity', 'Days_to_Transplant', 'Direct_Sow', 'Succession_Interval', 'Description']);
    
    foreach ($varieties as $variety) {
        fputcsv($handle, [
            $variety['name'],
            $variety['code'],
            $variety['crop_type'],
            60, // Default days to maturity
            21, // Default days to transplant  
            'No', // Default direct sow
            21, // Default succession interval
            $variety['description']
        ]);
    }
    
    fclose($handle);
    echo "✅ Clean CSV saved as: $csvFile\n";
    echo "📋 Total varieties exported: " . count($varieties) . "\n\n";
} else {
    echo "❌ Failed to create CSV file\n";
}

// Cleanup
unlink('vegetable_pages_clean.txt');

echo "🎉 Extraction complete!\n";
echo "Review the CSV file before importing to farmOS.\n";
?>
