<?php
// Quick beetroot extraction from page 179
echo "🍠 Extracting Beetroot varieties from page 179\n";
echo "==============================================\n\n";

$text = file_get_contents('page179.txt');
$lines = explode("\n", $text);

$beetrootVarieties = [];
$inBeetrootSection = false;

foreach ($lines as $lineNum => $line) {
    $line = trim($line);
    
    // Start beetroot section
    if (stripos($line, 'Beetroot') !== false) {
        $inBeetrootSection = true;
        echo "📂 Found Beetroot section on line " . ($lineNum + 1) . "\n";
        continue;
    }
    
    // Stop at next major section
    if ($inBeetrootSection && (
        stripos($line, 'Broad Beans') !== false ||
        stripos($line, 'French Beans') !== false ||
        stripos($line, 'Runner Beans') !== false ||
        preg_match('/^[A-Z][a-z]+ [A-Z]/', $line) // New section pattern
    )) {
        $inBeetrootSection = false;
        echo "📂 End of Beetroot section\n";
        break;
    }
    
    if ($inBeetrootSection && !empty($line)) {
        // Look for variety codes
        if (preg_match('/\b(VBE\d+|VOG\d+)\s+(.+)/', $line, $matches)) {
            $varietyCode = $matches[1];
            $varietyInfo = trim($matches[2]);
            
            // Extract variety name
            $varietyName = '';
            $words = explode(' ', $varietyInfo);
            for ($i = 0; $i < min(4, count($words)); $i++) {
                if (preg_match('/^[A-Z]/', $words[$i]) || in_array(strtolower($words[$i]), ['f1'])) {
                    $varietyName .= $words[$i] . ' ';
                } else {
                    break;
                }
            }
            
            $varietyName = trim($varietyName);
            if (!empty($varietyName)) {
                $beetrootVarieties[] = [
                    'name' => $varietyName,
                    'code' => $varietyCode,
                    'line' => $lineNum + 1
                ];
                echo "  ✓ Found: $varietyName ($varietyCode)\n";
            }
        }
        
        // Look for variety names without codes
        if (preg_match('/^([A-Z][a-z]+(?:\s+[A-Z0-9][a-z]*)*)\s/', $line, $matches)) {
            $varietyName = trim($matches[1]);
            if (strlen($varietyName) > 3 && 
                !in_array(strtolower($varietyName), ['beetroot', 'detroit', 'other', 'types']) &&
                !preg_match('/\d+g|\d+kg|organic/', $varietyName)) {
                
                $beetrootVarieties[] = [
                    'name' => $varietyName,
                    'code' => '',
                    'line' => $lineNum + 1
                ];
                echo "  ✓ Found: $varietyName (no code)\n";
            }
        }
    }
}

echo "\n📊 Results:\n";
echo "Found " . count($beetrootVarieties) . " beetroot varieties\n\n";

echo "🍠 BEETROOT VARIETIES:\n";
foreach ($beetrootVarieties as $variety) {
    echo "  - {$variety['name']} ({$variety['code']})\n";
}

// Clean up
unlink('page179.txt');
?>
