<?php
/**
 * Seed Data Importer - Import seed catalogs and timing data into farmOS
 * 
 * This script can process:
 * - CSV/Excel files from seed companies
 * - PDF catalogs (converted to text)
 * - Online seed databases
 * - Agricultural extension data
 */

require_once 'vendor/autoload.php';

class SeedDataImporter {
    private $farmosApi;
    private $csvReader;
    private $pdfParser;
    
    public function __construct() {
        $this->farmosApi = new FarmOSApiClient();
        $this->setupParsers();
    }
    
    /**
     * Import from popular seed company catalogs
     */
    public function importFromSeedCatalog($source, $filePath) {
        $seedData = [];
        
        switch ($source) {
            case 'johnny_seeds':
                $seedData = $this->parseJohnnySeeds($filePath);
                break;
            case 'fedco':
                $seedData = $this->parseFedcoSeeds($filePath);
                break;
            case 'high_mowing':
                $seedData = $this->parseHighMowingSeeds($filePath);
                break;
            case 'southern_exposure':
                $seedData = $this->parseSouthernExposure($filePath);
                break;
            case 'seed_savers':
                $seedData = $this->parseSeedSavers($filePath);
                break;
            case 'moles_seeds':
                $seedData = $this->parseMolesSeeds($filePath);
                break;
            case 'generic_csv':
                $seedData = $this->parseGenericCSV($filePath);
                break;
            case 'pdf_catalog':
                $seedData = $this->parsePdfCatalog($filePath);
                break;
            default:
                throw new Exception("Unsupported seed catalog source: $source");
        }
        
        return $this->importToFarmOS($seedData);
    }
    
    /**
     * Parse Johnny's Seeds catalog format
     */
    private function parseJohnnySeeds($filePath) {
        $data = $this->readCSV($filePath);
        $seedData = [];
        
        foreach ($data as $row) {
            // Johnny's Seeds typical format:
            // Name, Type, Days to Maturity, Spacing, Description, etc.
            $seedData[] = [
                'name' => $row['Name'] ?? $row['Variety'],
                'crop_type' => $this->standardizeCropType($row['Type'] ?? $row['Crop']),
                'days_to_maturity' => $this->parseDaysToMaturity($row['Days to Maturity'] ?? $row['Maturity']),
                'days_to_transplant' => $this->estimateTransplantDays($row),
                'days_to_harvest' => $this->calculateHarvestDays($row),
                'harvest_window' => $this->estimateHarvestWindow($row['Type']),
                'spacing' => $row['Spacing'] ?? null,
                'description' => $row['Description'] ?? '',
                'variety_type' => $row['Variety Type'] ?? 'standard',
                'source' => 'johnny_seeds',
                'catalog_number' => $row['Item #'] ?? null,
                'organic' => $this->isOrganic($row),
                'direct_sow' => $this->isDirectSow($row),
                'succession_interval' => $this->getSuccessionInterval($row['Type'])
            ];
        }
        
        return $seedData;
    }
    
    /**
     * Parse generic CSV with flexible column mapping
     */
    private function parseGenericCSV($filePath) {
        $data = $this->readCSV($filePath);
        $seedData = [];
        
        // Auto-detect column mapping
        $columnMap = $this->detectColumnMapping($data[0] ?? []);
        
        foreach ($data as $index => $row) {
            if ($index === 0) continue; // Skip header
            
            $seedData[] = [
                'name' => $row[$columnMap['name']] ?? "Variety $index",
                'crop_type' => $this->standardizeCropType($row[$columnMap['crop_type']] ?? 'unknown'),
                'days_to_maturity' => $this->parseDaysToMaturity($row[$columnMap['days_to_maturity']] ?? ''),
                'days_to_transplant' => $this->parseDaysToMaturity($row[$columnMap['days_to_transplant']] ?? ''),
                'spacing' => $row[$columnMap['spacing']] ?? null,
                'description' => $row[$columnMap['description']] ?? '',
                'source' => 'custom_import',
                'direct_sow' => $this->parseBoolean($row[$columnMap['direct_sow']] ?? 'false')
            ];
        }
        
        return $seedData;
    }

    
    /**
     * Add timing and growing metadata to variety
     */
    private function addTimingMetadata($varietyId, $seedData) {
        $metadata = [
            'days_to_maturity' => $seedData['days_to_maturity'],
            'days_to_transplant' => $seedData['days_to_transplant'],
            'days_to_harvest' => $seedData['days_to_harvest'],
            'harvest_window_days' => $seedData['harvest_window'],
            'spacing' => $seedData['spacing'],
            'direct_sow' => $seedData['direct_sow'],
            'succession_interval_days' => $seedData['succession_interval'],
            'source_catalog' => $seedData['source'],
            'catalog_number' => $seedData['catalog_number'],
            'organic_available' => $seedData['organic'],
            'last_updated' => date('Y-m-d H:i:s')
        ];
        
        // Store as custom fields or log entries
        return $this->farmosApi->addVarietyMetadata($varietyId, $metadata);
    }
    
    /**
     * Ensure crop type exists in farmOS
     */
    private function ensureCropType($cropType) {
        // Check if crop type already exists
        $existing = $this->farmosApi->findTaxonomyTermByName($cropType, 'plant_type');
        if ($existing) {
            return $existing['data'][0]['id'];
        }
        
        // Create new crop type
        $cropData = [
            'type' => 'taxonomy_term--plant_type',
            'attributes' => [
                'name' => ucfirst($cropType),
                'description' => "Auto-imported crop type: $cropType",
                'status' => true
            ]
        ];
        
        $result = $this->farmosApi->createTaxonomyTerm($cropData);
        return $result ? $result['data']['id'] : null;
    }
    
    /**
     * Standardize crop type names
     */
    private function standardizeCropType($input) {
        $standardTypes = [
            'lettuce' => ['lettuce', 'head lettuce', 'leaf lettuce', 'romaine'],
            'carrot' => ['carrot', 'carrots', 'baby carrot'],
            'tomato' => ['tomato', 'tomatoes', 'cherry tomato', 'paste tomato'],
            'pepper' => ['pepper', 'peppers', 'bell pepper', 'hot pepper'],
            'cucumber' => ['cucumber', 'cucumbers', 'pickling cucumber'],
            'radish' => ['radish', 'radishes', 'daikon'],
            'spinach' => ['spinach', 'baby spinach'],
            'kale' => ['kale', 'baby kale'],
            'arugula' => ['arugula', 'rocket'],
            'beet' => ['beet', 'beets', 'beetroot'],
            'turnip' => ['turnip', 'turnips'],
            'cabbage' => ['cabbage', 'napa cabbage'],
            'broccoli' => ['broccoli', 'baby broccoli'],
            'cauliflower' => ['cauliflower'],
            'onion' => ['onion', 'onions', 'yellow onion', 'red onion'],
            'scallion' => ['scallion', 'scallions', 'green onion', 'spring onion'],
            'leek' => ['leek', 'leeks'],
            'chard' => ['chard', 'swiss chard', 'rainbow chard'],
            'peas' => ['pea', 'peas', 'snap pea', 'snow pea'],
            'beans' => ['bean', 'beans', 'green bean', 'bush bean'],
            'zucchini' => ['zucchini', 'summer squash'],
            'eggplant' => ['eggplant', 'aubergine'],
            'herbs' => ['herb', 'herbs', 'basil', 'cilantro', 'parsley', 'dill']
        ];
        
        $input = strtolower(trim($input));
        
        foreach ($standardTypes as $standard => $variations) {
            if (in_array($input, $variations)) {
                return $standard;
            }
        }
        
        return $input;
    }
    
    /**
     * Parse days to maturity from various formats
     */
    private function parseDaysToMaturity($input) {
        if (empty($input)) return null;
        
        // Handle ranges like "45-60 days"
        if (preg_match('/(\d+)-(\d+)/', $input, $matches)) {
            return intval(($matches[1] + $matches[2]) / 2); // Use average
        }
        
        // Handle single numbers
        if (preg_match('/(\d+)/', $input, $matches)) {
            return intval($matches[1]);
        }
        
        return null;
    }
    
    /**
     * Estimate transplant days based on crop type
     */
    private function estimateTransplantDays($row) {
        $cropType = strtolower($row['Type'] ?? $row['Crop'] ?? '');
        
        $transplantDays = [
            'lettuce' => 21,
            'tomato' => 42,
            'pepper' => 56,
            'cucumber' => 21,
            'kale' => 28,
            'cabbage' => 35,
            'broccoli' => 35,
            'cauliflower' => 35,
            'eggplant' => 56,
            'chard' => 21,
            'spinach' => 14,
            'herbs' => 14
        ];
        
        // Direct sow crops
        $directSowCrops = ['carrot', 'radish', 'beet', 'turnip', 'peas', 'beans', 'arugula'];
        
        foreach ($directSowCrops as $crop) {
            if (strpos($cropType, $crop) !== false) {
                return 0;
            }
        }
        
        foreach ($transplantDays as $crop => $days) {
            if (strpos($cropType, $crop) !== false) {
                return $days;
            }
        }
        
        return 21; // Default
    }
    
    /**
     * Get recommended succession planting interval
     */
    private function getSuccessionInterval($cropType) {
        $intervals = [
            'lettuce' => 7,
            'spinach' => 10,
            'arugula' => 7,
            'radish' => 7,
            'carrot' => 14,
            'beet' => 14,
            'kale' => 14,
            'chard' => 21,
            'herbs' => 21
        ];
        
        $cropType = strtolower($cropType);
        foreach ($intervals as $crop => $interval) {
            if (strpos($cropType, $crop) !== false) {
                return $interval;
            }
        }
        
        return 14; // Default 2 weeks
    }
    
    /**
     * Detect column mapping from CSV headers
     */
    private function detectColumnMapping($headers) {
        $map = [];
        
        foreach ($headers as $index => $header) {
            $header = strtolower(trim($header));
            
            // Name variations
            if (preg_match('/(name|variety|cultivar)/', $header)) {
                $map['name'] = $index;
            }
            // Crop type variations
            elseif (preg_match('/(type|crop|species|category)/', $header)) {
                $map['crop_type'] = $index;
            }
            // Days to maturity variations
            elseif (preg_match('/(days|maturity|mature)/', $header)) {
                $map['days_to_maturity'] = $index;
            }
            // Transplant timing
            elseif (preg_match('/(transplant|indoor|start)/', $header)) {
                $map['days_to_transplant'] = $index;
            }
            // Spacing
            elseif (preg_match('/(spacing|distance)/', $header)) {
                $map['spacing'] = $index;
            }
            // Description
            elseif (preg_match('/(description|notes|details)/', $header)) {
                $map['description'] = $index;
            }
            // Direct sow
            elseif (preg_match('/(direct|sow|method)/', $header)) {
                $map['direct_sow'] = $index;
            }
        }
        
        return $map;
    }
    
    /**
     * Read CSV file
     */
    private function readCSV($filePath) {
        $data = [];
        if (($handle = fopen($filePath, "r")) !== FALSE) {
            $headers = fgetcsv($handle);
            while (($row = fgetcsv($handle)) !== FALSE) {
                $data[] = array_combine($headers, $row);
            }
            fclose($handle);
        }
        return $data;
    }
    
    /**
     * Parse Moles Seeds PDF catalog
     */
    private function parseMolesSeeds($filePath) {
        // Extract text from PDF
        $pdfText = $this->extractPdfText($filePath);
        
        // Parse Moles Seeds specific format
        return $this->parseMolesSeedsText($pdfText);
    }
    
    /**
     * Parse PDF catalog (generic)
     */
    private function parsePdfCatalog($filePath) {
        $pdfText = $this->extractPdfText($filePath);
        return $this->parseGenericSeedCatalogText($pdfText);
    }
    
    /**
     * Extract text from PDF using multiple methods (PUBLIC)
     */
    public function extractPdfText($filePath) {
        $text = '';
        
        // Method 1: Try pdftotext command line tool (most reliable)
        if (shell_exec('which pdftotext')) {
            $tempFile = tempnam(sys_get_temp_dir(), 'pdf_text_');
            $command = "pdftotext -layout '$filePath' '$tempFile' 2>/dev/null";
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0 && file_exists($tempFile)) {
                $text = file_get_contents($tempFile);
                unlink($tempFile);
                if (!empty($text)) {
                    return $text;
                }
            }
        }
        
        // Method 2: Try using Python with pdfplumber (if available)
        $pythonScript = $this->createPdfExtractorScript();
        if (file_exists($pythonScript)) {
            $command = "python3 '$pythonScript' '$filePath' 2>/dev/null";
            $text = shell_exec($command);
            if (!empty($text)) {
                return $text;
            }
        }
        
        // Method 3: Simple text extraction attempt
        $text = $this->simplePdfTextExtraction($filePath);
        
        return $text;
    }
    
    /**
     * Parse Moles Seeds catalog text (PUBLIC)
     */
    public function parseMolesSeedsText($text) {
        $seedData = [];
        $lines = explode("\n", $text);
        
        $currentCrop = '';
        $currentDescription = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Moles Seeds format patterns
            // Look for variety names (usually start with capital letters)
            if (preg_match('/^([A-Z][a-zA-Z\s\-\']+)\s+(\d+)\s*days?\s*(.*?)$/i', $line, $matches)) {
                $varietyName = trim($matches[1]);
                $daysToMaturity = intval($matches[2]);
                $description = trim($matches[3]);
                
                // Determine crop type from context or variety name
                $cropType = $this->inferCropTypeFromVariety($varietyName, $currentCrop);
                
                $seedData[] = [
                    'name' => $varietyName,
                    'crop_type' => $cropType,
                    'days_to_maturity' => $daysToMaturity,
                    'days_to_transplant' => $this->estimateTransplantDaysForCrop($cropType),
                    'harvest_window' => $this->estimateHarvestWindowForCrop($cropType),
                    'description' => $description,
                    'source' => 'moles_seeds',
                    'direct_sow' => $this->isDirectSowCrop($cropType),
                    'succession_interval' => $this->getSuccessionInterval($cropType)
                ];
                continue;
            }
            
            // Look for crop section headers
            if (preg_match('/^([A-Z\s]+)$/i', $line) && strlen($line) > 3 && strlen($line) < 30) {
                $possibleCrop = strtolower(trim($line));
                if ($this->isValidCropType($possibleCrop)) {
                    $currentCrop = $possibleCrop;
                }
                continue;
            }
            
            // Look for variety without explicit days (estimate from crop type)
            if (preg_match('/^([A-Z][a-zA-Z\s\-\']+)\s+(.+)$/i', $line, $matches) && 
                !empty($currentCrop)) {
                $varietyName = trim($matches[1]);
                $description = trim($matches[2]);
                
                // Skip if this looks like a header or category
                if (in_array(strtolower($varietyName), ['variety', 'type', 'name', 'description'])) {
                    continue;
                }
                
                $seedData[] = [
                    'name' => $varietyName,
                    'crop_type' => $currentCrop,
                    'days_to_maturity' => $this->getDefaultMaturityDays($currentCrop),
                    'days_to_transplant' => $this->estimateTransplantDaysForCrop($currentCrop),
                    'harvest_window' => $this->estimateHarvestWindowForCrop($currentCrop),
                    'description' => $description,
                    'source' => 'moles_seeds',
                    'direct_sow' => $this->isDirectSowCrop($currentCrop),
                    'succession_interval' => $this->getSuccessionInterval($currentCrop)
                ];
            }
        }
        
        return $seedData;
    }
    
    /**
     * Import processed seed data into farmOS taxonomy (PUBLIC)
     */
    public function importToFarmOS($seedData) {
        $results = [
            'crops_created' => 0,
            'varieties_created' => 0,
            'errors' => [],
            'summary' => []
        ];
        
        $cropTypes = [];
        
        foreach ($seedData as $seed) {
            try {
                // 1. Ensure crop type exists
                $cropTypeId = $this->ensureCropType($seed['crop_type']);
                if (!$cropTypeId) {
                    $results['errors'][] = "Failed to create crop type: {$seed['crop_type']}";
                    continue;
                }
                
                $cropTypes[$seed['crop_type']] = $cropTypeId;
                
                // 2. Create variety
                $varietyData = [
                    'type' => 'taxonomy_term--plant_type',
                    'attributes' => [
                        'name' => $seed['name'],
                        'description' => $seed['description'],
                        'status' => true
                    ],
                    'relationships' => [
                        'parent' => [
                            'data' => [
                                'type' => 'taxonomy_term--plant_type',
                                'id' => $cropTypeId
                            ]
                        ]
                    ]
                ];
                
                $variety = $this->farmosApi->createTaxonomyTerm($varietyData);
                if ($variety) {
                    $results['varieties_created']++;
                    
                    // 3. Add timing metadata
                    $this->addTimingMetadata($variety['data']['id'], $seed);
                } else {
                    $results['errors'][] = "Failed to create variety: {$seed['name']}";
                }
                
            } catch (Exception $e) {
                $results['errors'][] = "Error processing {$seed['name']}: " . $e->getMessage();
            }
        }
        
        // Create summary
        $results['summary'] = $this->generateImportSummary($cropTypes, $results);
        
        return $results;
    }
    
    /**
     * Parse generic seed catalog text
     */
    private function parseGenericSeedCatalogText($text) {
        $seedData = [];
        $lines = explode("\n", $text);
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Generic patterns for seed catalogs
            // Pattern: Variety Name - Days - Description
            if (preg_match('/^(.+?)\s*-\s*(\d+)\s*days?\s*-\s*(.+)$/i', $line, $matches)) {
                $varietyName = trim($matches[1]);
                $daysToMaturity = intval($matches[2]);
                $description = trim($matches[3]);
                
                $cropType = $this->inferCropTypeFromVariety($varietyName);
                
                $seedData[] = [
                    'name' => $varietyName,
                    'crop_type' => $cropType,
                    'days_to_maturity' => $daysToMaturity,
                    'days_to_transplant' => $this->estimateTransplantDaysForCrop($cropType),
                    'harvest_window' => $this->estimateHarvestWindowForCrop($cropType),
                    'description' => $description,
                    'source' => 'pdf_catalog',
                    'direct_sow' => $this->isDirectSowCrop($cropType),
                    'succession_interval' => $this->getSuccessionInterval($cropType)
                ];
            }
        }
        
        return $seedData;
    }
    
    /**
     * Infer crop type from variety name
     */
    private function inferCropTypeFromVariety($varietyName, $contextCrop = '') {
        $varietyLower = strtolower($varietyName);
        
        // Use context crop if available
        if (!empty($contextCrop)) {
            return $this->standardizeCropType($contextCrop);
        }
        
        // Common variety name patterns
        $patterns = [
            'lettuce' => ['buttercrunch', 'romaine', 'iceberg', 'oak leaf', 'lollo', 'cos'],
            'carrot' => ['nantes', 'chantenay', 'imperator', 'paris market'],
            'tomato' => ['cherry', 'beefsteak', 'roma', 'paste', 'indeterminate'],
            'pepper' => ['bell', 'jalapeño', 'habanero', 'cayenne', 'sweet'],
            'cucumber' => ['pickling', 'slicing', 'burpless', 'gherkin'],
            'radish' => ['cherry belle', 'french breakfast', 'daikon', 'black spanish'],
            'spinach' => ['baby leaf', 'bloomsdale', 'space'],
            'kale' => ['curly', 'lacinato', 'dinosaur', 'red russian'],
            'beet' => ['detroit', 'chioggia', 'golden', 'bull\'s blood'],
            'cabbage' => ['copenhagen', 'stonehead', 'savoy'],
            'broccoli' => ['calabrese', 'de cicco', 'romanesco'],
            'beans' => ['bush', 'pole', 'runner', 'french'],
            'peas' => ['snap', 'snow', 'shelling', 'sugar'],
            'herbs' => ['basil', 'cilantro', 'parsley', 'dill', 'thyme']
        ];
        
        foreach ($patterns as $crop => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($varietyLower, $keyword) !== false) {
                    return $crop;
                }
            }
        }
        
        // Default inference from first word
        $firstWord = explode(' ', $varietyLower)[0];
        return $this->standardizeCropType($firstWord);
    }
    
    /**
     * Check if crop type is valid
     */
    private function isValidCropType($cropType) {
        $validCrops = [
            'lettuce', 'carrot', 'tomato', 'pepper', 'cucumber', 'radish',
            'spinach', 'kale', 'arugula', 'beet', 'turnip', 'cabbage',
            'broccoli', 'cauliflower', 'onion', 'scallion', 'leek',
            'chard', 'peas', 'beans', 'zucchini', 'eggplant', 'herbs',
            'basil', 'cilantro', 'parsley', 'dill'
        ];
        
        return in_array(strtolower($cropType), $validCrops);
    }
    
    /**
     * Get default maturity days for crop type
     */
    private function getDefaultMaturityDays($cropType) {
        $defaults = [
            'lettuce' => 65, 'carrot' => 75, 'tomato' => 80, 'pepper' => 75,
            'cucumber' => 60, 'radish' => 25, 'spinach' => 45, 'kale' => 55,
            'arugula' => 25, 'beet' => 55, 'turnip' => 45, 'cabbage' => 75,
            'broccoli' => 70, 'cauliflower' => 75, 'onion' => 120,
            'scallion' => 60, 'leek' => 100, 'chard' => 60, 'peas' => 60,
            'beans' => 55, 'zucchini' => 50, 'eggplant' => 85, 'basil' => 75,
            'cilantro' => 45, 'parsley' => 80, 'dill' => 40
        ];
        
        return $defaults[strtolower($cropType)] ?? 60;
    }
    
    /**
     * Missing helper methods
     */
    private function calculateHarvestDays($row) {
        // Calculate total growing days
        $maturityDays = $this->parseDaysToMaturity($row['Days to Maturity'] ?? '');
        $transplantDays = $this->estimateTransplantDays($row);
        
        return $maturityDays ? ($maturityDays - $transplantDays) : null;
    }
    
    private function estimateHarvestWindow($cropType) {
        return $this->estimateHarvestWindowForCrop($cropType);
    }
    
    private function estimateHarvestWindowForCrop($cropType) {
        $windows = [
            'lettuce' => 21, 'spinach' => 28, 'arugula' => 21, 'kale' => 35,
            'chard' => 42, 'carrot' => 45, 'radish' => 14, 'beet' => 35,
            'broccoli' => 14, 'cauliflower' => 21, 'cabbage' => 35,
            'tomato' => 60, 'pepper' => 56, 'cucumber' => 35, 'zucchini' => 42,
            'beans' => 28, 'peas' => 21, 'basil' => 42, 'cilantro' => 35
        ];
        
        return $windows[strtolower($cropType)] ?? 21;
    }
    
    private function estimateTransplantDaysForCrop($cropType) {
        $transplantDays = [
            'lettuce' => 21, 'tomato' => 42, 'pepper' => 56, 'cucumber' => 21,
            'kale' => 28, 'cabbage' => 35, 'broccoli' => 35, 'cauliflower' => 35,
            'eggplant' => 56, 'chard' => 21, 'spinach' => 14, 'basil' => 14,
            'cilantro' => 14, 'parsley' => 14
        ];
        
        $directSowCrops = ['carrot', 'radish', 'beet', 'turnip', 'peas', 'beans', 'arugula', 'dill'];
        
        if (in_array(strtolower($cropType), $directSowCrops)) {
            return 0;
        }
        
        return $transplantDays[strtolower($cropType)] ?? 21;
    }
    
    private function isDirectSowCrop($cropType) {
        $directSowCrops = ['carrot', 'radish', 'beet', 'turnip', 'peas', 'beans', 'arugula', 'dill'];
        return in_array(strtolower($cropType), $directSowCrops);
    }
    
    private function isOrganic($row) {
        $text = strtolower(implode(' ', $row));
        return strpos($text, 'organic') !== false || strpos($text, 'certified') !== false;
    }
    
    private function isDirectSow($row) {
        $cropType = strtolower($row['Type'] ?? $row['Crop'] ?? '');
        return $this->isDirectSowCrop($cropType);
    }
    
    private function parseBoolean($input) {
        $input = strtolower(trim($input));
        return in_array($input, ['yes', 'true', '1', 'y']);
    }
    
    private function generateImportSummary($cropTypes, $results) {
        return [
            'total_crop_types' => count($cropTypes),
            'varieties_by_crop' => $cropTypes,
            'success_rate' => $results['varieties_created'] / max(1, $results['varieties_created'] + count($results['errors'])) * 100
        ];
    }
    
    /**
     * Create Python PDF extractor script
     */
    private function createPdfExtractorScript() {
        $scriptPath = sys_get_temp_dir() . '/pdf_extractor.py';
        
        if (!file_exists($scriptPath)) {
            $pythonCode = '#!/usr/bin/env python3
import sys
try:
    import pdfplumber
    
    if len(sys.argv) != 2:
        print("Usage: python3 pdf_extractor.py <pdf_file>")
        sys.exit(1)
    
    pdf_path = sys.argv[1]
    
    with pdfplumber.open(pdf_path) as pdf:
        text = ""
        for page in pdf.pages:
            page_text = page.extract_text()
            if page_text:
                text += page_text + "\n"
        
        print(text)

except ImportError:
    print("pdfplumber not installed")
    sys.exit(1)
except Exception as e:
    print(f"Error: {e}")
    sys.exit(1)
';
            
            file_put_contents($scriptPath, $pythonCode);
            chmod($scriptPath, 0755);
        }
        
        return $scriptPath;
    }
    
    /**
     * Simple PDF text extraction fallback
     */
    private function simplePdfTextExtraction($filePath) {
        // Very basic PDF text extraction - look for readable text patterns
        $content = file_get_contents($filePath);
        
        // Extract text between common PDF text markers
        $text = '';
        if (preg_match_all('/\(([^)]+)\)/', $content, $matches)) {
            foreach ($matches[1] as $match) {
                if (ctype_print($match) && strlen($match) > 2) {
                    $text .= $match . "\n";
                }
            }
        }
        
        return $text;
    }
    
    /**
     * Setup required parsers and dependencies
     */
    private function setupParsers() {
        // Check for required tools
        $this->checkDependencies();
    }
    
    /**
     * Check for PDF parsing dependencies
     */
    private function checkDependencies() {
        $dependencies = [
            'pdftotext' => 'poppler-utils package',
            'python3' => 'Python 3 interpreter'
        ];
        
        $missing = [];
        foreach ($dependencies as $command => $package) {
            if (!shell_exec("which $command")) {
                $missing[] = "$command ($package)";
            }
        }
        
        if (!empty($missing)) {
            error_log("PDF parsing dependencies missing: " . implode(', ', $missing));
        }
    }
    
    /**
     * Additional helper methods for PDF parsing, online data, etc.
     */
    
    // ... (Additional methods would go here)
}

/**
 * Example usage and test data
 */
class SeedCatalogProcessor {
    
    /**
     * Process multiple seed catalogs
     */
    public static function processCatalogs() {
        $importer = new SeedDataImporter();
        $results = [];
        
        // Example: Process Johnny's Seeds catalog
        if (file_exists('data/johnnys_seeds_2024.csv')) {
            $results['johnnys'] = $importer->importFromSeedCatalog('johnny_seeds', 'data/johnnys_seeds_2024.csv');
        }
        
        // Example: Process custom CSV
        if (file_exists('data/custom_varieties.csv')) {
            $results['custom'] = $importer->importFromSeedCatalog('generic_csv', 'data/custom_varieties.csv');
        }
        
        return $results;
    }
    
    /**
     * Generate sample CSV template
     */
    public static function generateSampleCSV($filePath) {
        $sampleData = [
            ['Name', 'Crop Type', 'Days to Maturity', 'Days to Transplant', 'Spacing', 'Direct Sow', 'Description'],
            ['Buttercrunch', 'Lettuce', '65', '21', '6 inches', 'No', 'Excellent butterhead variety'],
            ['Cherry Belle', 'Radish', '22', '0', '1 inch', 'Yes', 'Classic red radish'],
            ['Nantes', 'Carrot', '75', '0', '2 inches', 'Yes', 'Sweet orange carrot'],
            ['Red Ace', 'Beet', '55', '0', '3 inches', 'Yes', 'Deep red beets'],
            ['Space', 'Spinach', '37', '14', '4 inches', 'No', 'Compact spinach variety']
        ];
        
        $file = fopen($filePath, 'w');
        foreach ($sampleData as $row) {
            fputcsv($file, $row);
        }
        fclose($file);
        
        return "Sample CSV created at: $filePath";
    }
}

// Example execution
if ($_POST['action'] ?? '' === 'extract_text') {
    header('Content-Type: application/json');
    
    try {
        if (!isset($_FILES['pdf_file'])) {
            throw new Exception('No PDF file uploaded');
        }
        
        $uploadedFile = $_FILES['pdf_file'];
        $tempPath = $uploadedFile['tmp_name'];
        
        $importer = new SeedDataImporter();
        $text = $importer->extractPdfText($tempPath);
        
        echo json_encode([
            'success' => true, 
            'text' => $text,
            'text_length' => strlen($text)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($_POST['action'] ?? '' === 'parse_moles_seeds') {
    header('Content-Type: application/json');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['text'])) {
            throw new Exception('No text provided for parsing');
        }
        
        $importer = new SeedDataImporter();
        $varieties = $importer->parseMolesSeedsText($input['text']);
        
        echo json_encode([
            'success' => true,
            'varieties' => $varieties,
            'count' => count($varieties)
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($_POST['action'] ?? '' === 'import_to_farmos') {
    header('Content-Type: application/json');
    
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['varieties'])) {
            throw new Exception('No varieties provided for import');
        }
        
        $importer = new SeedDataImporter();
        $results = $importer->importToFarmOS($input['varieties']);
        
        echo json_encode([
            'success' => true,
            'imported' => $results['varieties_created'],
            'errors' => $results['errors'],
            'summary' => $results['summary']
        ]);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($_GET['action'] ?? '' === 'process') {
    header('Content-Type: application/json');
    
    try {
        $results = SeedCatalogProcessor::processCatalogs();
        echo json_encode(['success' => true, 'results' => $results]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

if ($_GET['action'] ?? '' === 'sample') {
    $sampleFile = 'sample_seed_data.csv';
    $message = SeedCatalogProcessor::generateSampleCSV($sampleFile);
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $sampleFile . '"');
    readfile($sampleFile);
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Seed Data Importer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>🌱 Seed Catalog Importer</h1>
        <p class="text-muted">Import seed varieties and timing data into farmOS taxonomy</p>
        
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5>Supported Sources</h5>
                    </div>
                    <div class="card-body">
                        <ul>
                            <li><strong>Johnny's Seeds</strong> - CSV export from their catalog</li>
                            <li><strong>Fedco Seeds</strong> - Seed catalog data</li>
                            <li><strong>High Mowing Seeds</strong> - Organic varieties</li>
                            <li><strong>Southern Exposure</strong> - Heirloom varieties</li>
                            <li><strong>Custom CSV</strong> - Your own seed database</li>
                        </ul>
                        
                        <h6>What Gets Imported:</h6>
                        <ul>
                            <li>Crop types and varieties</li>
                            <li>Days to maturity</li>
                            <li>Transplant timing</li>
                            <li>Harvest windows</li>
                            <li>Succession intervals</li>
                            <li>Spacing requirements</li>
                            <li>Direct sow vs transplant</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="?action=sample" class="btn btn-outline-primary">
                                📄 Download Sample CSV
                            </a>
                            <button onclick="processFiles()" class="btn btn-success">
                                🚀 Process All Catalogs
                            </button>
                            <button onclick="showUploadForm()" class="btn btn-outline-secondary">
                                📤 Upload Custom CSV
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="card mt-3">
                    <div class="card-header">
                        <h6>Import Status</h6>
                    </div>
                    <div class="card-body">
                        <div id="importStatus">
                            <p class="text-muted">Ready to import...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    function processFiles() {
        document.getElementById('importStatus').innerHTML = '<div class="spinner-border spinner-border-sm"></div> Processing...';
        
        fetch('?action=process')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    let html = '<div class="alert alert-success">Import completed!</div>';
                    Object.keys(data.results).forEach(source => {
                        const result = data.results[source];
                        html += `<strong>${source}:</strong> ${result.varieties_created} varieties created<br>`;
                    });
                    document.getElementById('importStatus').innerHTML = html;
                } else {
                    document.getElementById('importStatus').innerHTML = 
                        `<div class="alert alert-danger">Error: ${data.error}</div>`;
                }
            })
            .catch(error => {
                document.getElementById('importStatus').innerHTML = 
                    `<div class="alert alert-danger">Request failed: ${error.message}</div>`;
            });
    }
    
    function showUploadForm() {
        // Implement file upload form
        alert('Upload form would be implemented here');
    }
    </script>
</body>
</html>
