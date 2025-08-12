<?php
/**
 * Clean Variety Taxonomy Builder
 * Alternative to PDF extraction - manual structured approach
 */

require_once 'vendor/autoload.php';
require_once 'app/Services/FarmOSApi.php';

use App\Services\FarmOSApi;

echo "🌱 Clean Taxonomy Builder - Manual Approach\n";
echo "=========================================\n\n";

// Initialize farmOS API
$farmOSApi = new FarmOSApi();

// Test connection first
try {
    $cropTypes = $farmOSApi->getCropTypes();
    echo "✅ Connected to farmOS - found " . count($cropTypes) . " crop types\n\n";
} catch (Exception $e) {
    echo "❌ farmOS connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Define clean crop type to variety mappings
// This replaces the corrupted PDF data with manual verification
$cleanVarieties = [
    'Beetroot' => [
        'Detroit 2 Crimson Globe' => 'VBE40',
        'Albina Ice' => 'VBE15',  
        'F1 Boro' => 'VOG101',
        'Chioggia' => 'VBE20',
        'Boltardy' => 'VBE30',
        'Boston' => 'VBE16',
        'Detroit 2 Bolivar' => 'VOG100',
        'Action' => 'VBE05',
        'Pablo' => 'VBE45',
        'Red Ace' => 'VBE50',
        'Moneta' => 'VBE35',
        'Libero' => 'VBE25',
        'Touchstone Gold' => 'VBE60',
        'Boldor' => 'VBE10',
        'Cylindra' => 'VBE18',
        'Forono' => 'VBE22',
        'MacGregor' => 'VBE32'
    ],
    'Carrot' => [
        'Nantes 2' => 'VCA100',
        'Amsterdam Forcing' => 'VCA05',
        'Paris Market' => 'VCA120',
        'Chantenay Red Cored' => 'VCA20',
        'Autumn King' => 'VCA10',
        'F1 Maestro' => 'VCA90',
        'F1 Nairobi' => 'VCA95',
        'F1 Romance' => 'VCA130',
        'Cosmic Purple' => 'VCA25',
        'Solar Yellow' => 'VCA140',
        'Snow White' => 'VCA135',
        'Parmex' => 'VCA115',
        'Rondo' => 'VCA125',
        'F1 Flyaway' => 'VCA70',
        'F1 Resistafly' => 'VCA128'
    ],
    'Lettuce' => [
        'Tom Thumb' => 'VLE200',
        'Black Seeded Simpson' => 'VLE20',
        'All The Year Round' => 'VLE05',
        'Webb\'s Wonderful' => 'VLE210',
        'Lollo Rosso' => 'VLE90',
        'Lollo Biondo' => 'VLE85',
        'Oak Leaf' => 'VLE110',
        'Cos Paris White' => 'VLE30',
        'Little Gem' => 'VLE80',
        'F1 Salanova' => 'VLE150'
    ],
    'Cabbage' => [
        'Golden Acre' => 'VCA60',
        'January King' => 'VCA75',
        'Primo' => 'VCA125',
        'F1 Stonehead' => 'VCA145',
        'Red Drumhead' => 'VCA130',
        'Savoy King' => 'VCA140',
        'F1 Minicole' => 'VCA100'
    ]
];

echo "📋 Sample Clean Variety Data:\n";
echo "-----------------------------\n";
foreach ($cleanVarieties as $cropType => $varieties) {
    echo "$cropType (" . count($varieties) . " varieties):\n";
    $count = 0;
    foreach ($varieties as $name => $code) {
        if ($count < 3) {
            echo "  - $name ($code)\n";
        }
        $count++;
    }
    if (count($varieties) > 3) {
        echo "  - ... and " . (count($varieties) - 3) . " more\n";
    }
    echo "\n";
}

echo "💡 Next Steps:\n";
echo "1. Verify crop types exist in farmOS\n";
echo "2. Add missing crop types if needed\n";
echo "3. Import clean variety data\n";
echo "4. Test variety-to-crop-type relationships\n\n";

echo "Would you like to:\n";
echo "A) Import this sample data to test the process\n";
echo "B) Add more varieties manually to the arrays above\n";
echo "C) Create a web interface for easier variety entry\n";
echo "D) Export current farmOS data for manual cleanup\n\n";
