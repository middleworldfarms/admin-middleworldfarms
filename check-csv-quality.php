<?php
// Quick preview of varieties in the messy CSV
echo "🔍 Checking Carrot and Beetroot varieties in the current CSV\n";
echo "============================================================\n\n";

$handle = fopen('moles_varieties_corrected.csv', 'r');
if (!$handle) {
    echo "❌ Could not open CSV file\n";
    exit;
}

// Skip header
fgetcsv($handle);

$carrots = [];
$beetroots = [];
$totalRows = 0;
$junkRows = 0;

while (($data = fgetcsv($handle)) !== FALSE) {
    $totalRows++;
    $name = trim($data[0] ?? '');
    $cropType = trim($data[2] ?? '');
    
    // Check if it's junk (variety code as crop type)
    if (preg_match('/^V[A-Z]{2,3}\d+/', $cropType) || 
        strlen($cropType) < 3 || 
        strpos($cropType, 'sd') !== false ||
        empty($name) ||
        strlen($name) < 2) {
        $junkRows++;
        continue;
    }
    
    if (stripos($cropType, 'carrot') !== false) {
        $carrots[] = $name;
    }
    
    if (stripos($cropType, 'beetroot') !== false || stripos($cropType, 'beet') !== false) {
        $beetroots[] = $name;
    }
}

fclose($handle);

echo "📊 File Analysis:\n";
echo "Total rows: $totalRows\n";
echo "Junk rows: $junkRows\n";
echo "Good rows: " . ($totalRows - $junkRows) . "\n\n";

echo "🥕 CARROT VARIETIES FOUND (" . count($carrots) . "):\n";
foreach ($carrots as $carrot) {
    echo "  - $carrot\n";
}

echo "\n🍠 BEETROOT VARIETIES FOUND (" . count($beetroots) . "):\n";
foreach ($beetroots as $beetroot) {
    echo "  - $beetroot\n";
}

echo "\n❌ VERDICT: This CSV is too corrupted to use reliably.\n";
echo "We need to re-extract the data from the original PDF properly.\n";
?>
