<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PlantVariety;
use App\Models\VarietyAuditResult;
use App\Services\AI\SymbiosisAIService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AuditVarieties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'varieties:audit 
                            {--start-id= : Start from specific variety ID}
                            {--limit= : Limit number of varieties to process}
                            {--fix : Automatically fix issues where possible}
                            {--dry-run : Show what would be done without making changes}
                            {--category= : Only process specific category (e.g., "broad bean", "lettuce")}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'AI-powered audit of all plant varieties - validates data, flags issues, and optionally fixes them';

    protected $ai;
    protected $logFile;
    protected $issuesFile;
    protected $fixedFile;
    protected $startTime;
    protected $stats = [
        'total' => 0,
        'processed' => 0,
        'skipped' => 0,
        'issues_found' => 0,
        'auto_fixed' => 0,
        'needs_review' => 0,
        'errors' => 0
    ];

    public function __construct(SymbiosisAIService $ai)
    {
        parent::__construct();
        $this->ai = $ai;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->startTime = now();
        $timestamp = $this->startTime->format('Y-m-d_H-i-s');
        
        // Setup log files
        $logDir = storage_path('logs/variety-audit');
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $this->logFile = $logDir . "/audit_{$timestamp}.log";
        $this->issuesFile = $logDir . "/issues_{$timestamp}.log";
        $this->fixedFile = $logDir . "/fixed_{$timestamp}.log";
        
        $this->info("🔍 Starting AI Variety Audit");
        $this->info("📊 Log files:");
        $this->info("   Main: {$this->logFile}");
        $this->info("   Issues: {$this->issuesFile}");
        $this->info("   Fixed: {$this->fixedFile}");
        $this->newLine();
        
        // Check for existing progress file
        $progressFile = storage_path('logs/variety-audit/progress.json');
        $resumeFromId = null;
        if (file_exists($progressFile) && !$this->option('start-id')) {
            $progress = json_decode(file_get_contents($progressFile), true);
            $this->warn("⏸️  Previous audit found!");
            $this->info("   Last processed: Variety ID {$progress['last_processed_id']}");
            $this->info("   Processed: {$progress['processed']} varieties");
            $this->info("   Timestamp: {$progress['timestamp']}");
            $this->newLine();
            
            if ($this->confirm('Resume from where you left off?', true)) {
                $resumeFromId = $progress['last_processed_id'] + 1;
                $this->info("▶️  Resuming from ID: {$resumeFromId}");
            } else {
                $this->info("🔄 Starting fresh audit");
                unlink($progressFile);
            }
            $this->newLine();
        }
        
        // Build query
        $query = PlantVariety::query();
        
        $startId = $resumeFromId ?? $this->option('start-id');
        if ($startId) {
            $query->where('id', '>=', $startId);
            $this->info("▶️  Starting from ID: {$startId}");
        }
        
        if ($category = $this->option('category')) {
            $query->where(function($q) use ($category) {
                $q->where('name', 'LIKE', "%{$category}%")
                  ->orWhere('plant_type', 'LIKE', "%{$category}%")
                  ->orWhere('crop_family', 'LIKE', "%{$category}%");
            });
            $this->info("🔎 Category filter: {$category}");
        }
        
        if ($limit = $this->option('limit')) {
            $query->limit($limit);
            $this->info("⏱️  Processing limit: {$limit} varieties");
        }
        
        $this->stats['total'] = $query->count();
        $this->info("📦 Total varieties to process: {$this->stats['total']}");
        $this->newLine();
        
        if ($this->option('dry-run')) {
            $this->warn("🔍 DRY RUN MODE - No changes will be saved");
            $this->newLine();
        }
        
        $progressBar = $this->output->createProgressBar($this->stats['total']);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %memory:6s%');
        
        // Log start
        $this->log("=== AI VARIETY AUDIT STARTED ===");
        $this->log("Time: " . $this->startTime->toDateTimeString());
        $this->log("Total varieties: {$this->stats['total']}");
        $this->log("Options: " . json_encode($this->options()));
        $this->log("");
        
        // Process each variety
        $progressFile = storage_path('logs/variety-audit/progress.json');
        foreach ($query->cursor() as $variety) {
            try {
                $this->processVariety($variety);
                $this->stats['processed']++;
                
                // Save progress every 10 varieties for resume capability
                if ($this->stats['processed'] % 10 == 0) {
                    file_put_contents($progressFile, json_encode([
                        'last_processed_id' => $variety->id,
                        'processed' => $this->stats['processed'],
                        'timestamp' => now()->toDateTimeString(),
                        'stats' => $this->stats
                    ]));
                }
            } catch (\Exception $e) {
                $this->stats['errors']++;
                $this->logError($variety, "Processing error: " . $e->getMessage());
                Log::error("Variety audit error for ID {$variety->id}: " . $e->getMessage());
            }
            
            $progressBar->advance();
            
            // Small delay to avoid overwhelming AI service
            usleep(100000); // 0.1 second delay
        }
        
        $progressBar->finish();
        $this->newLine(2);
        
        // Final summary
        $this->showSummary();
        
        return Command::SUCCESS;
    }

    protected function processVariety($variety)
    {
        $this->log("Processing: [{$variety->id}] {$variety->name}");
        
        // Build AI prompt for this variety
        $prompt = $this->buildAuditPrompt($variety);
        
        // Get AI analysis
        $messages = [
            [
                'role' => 'system',
                'content' => 'You are an expert horticulturist auditing plant variety data for UK growing conditions. You MUST respond ONLY with valid JSON. No explanatory text before or after the JSON.'
            ],
            [
                'role' => 'user',
                'content' => $prompt
            ]
        ];
        
        try {
            // Add extra logging for debugging
            Log::info("Audit variety {$variety->id}: Calling AI", [
                'variety_id' => $variety->id,
                'variety_name' => $variety->name,
                'messages_count' => count($messages)
            ]);
            
            // Use dedicated audit AI instance on port 8006 with Mistral 7B
            $response = $this->ai->chat($messages, [
                'max_tokens' => 800, 
                'temperature' => 0.1,
                'model' => 'mistral:7b',
                'base_url' => 'http://localhost:8006/api'
            ]);
            
            if (!isset($response['choices'][0]['message']['content'])) {
                throw new \Exception('Invalid AI response structure: ' . json_encode($response));
            }
            
            $analysis = $response['choices'][0]['message']['content'];
            
            // Parse AI response and take action
            $this->processAIResponse($variety, $analysis);
            
        } catch (\Exception $e) {
            $errorMsg = "AI request failed: " . $e->getMessage();
            $this->logError($variety, $errorMsg);
            $this->stats['errors']++;
            
            // Careful logging to avoid "Array to string conversion" errors
            try {
                Log::error("Variety audit error for ID {$variety->id}", [
                    'variety_id' => $variety->id,
                    'variety_name' => $variety->name,
                    'error_message' => $e->getMessage(),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine()
                ]);
            } catch (\Exception $logError) {
                // Silently fail logging if it causes issues
            }
        }
    }

    protected function buildAuditPrompt($variety): string
    {
        // Helper to safely convert values to string
        $safeString = function($value) {
            if (is_array($value) || is_object($value)) {
                return json_encode($value);
            }
            return $value ?: 'MISSING';
        };
        
        $prompt = "Audit this plant variety data:\n\n";
        $prompt .= "ID: {$variety->id}\n";
        $prompt .= "Name: " . $safeString($variety->name) . "\n";
        $prompt .= "Plant Type: " . $safeString($variety->plant_type) . "\n";
        $prompt .= "Crop Family: " . $safeString($variety->crop_family) . "\n";
        $prompt .= "Description: " . $safeString($variety->description) . "\n";
        $prompt .= "Harvest Notes: " . $safeString($variety->harvest_notes) . "\n";
        $prompt .= "Maturity Days: " . $safeString($variety->maturity_days) . "\n";
        $prompt .= "Harvest Days: " . $safeString($variety->harvest_days) . "\n";
        $prompt .= "In-row Spacing: " . $safeString($variety->in_row_spacing_cm) . " cm\n";
        $prompt .= "Between-row Spacing: " . $safeString($variety->between_row_spacing_cm) . " cm\n";
        $prompt .= "Planting Method: " . $safeString($variety->planting_method) . "\n\n";
        
        $prompt .= "RESPOND WITH ONLY THIS JSON (no other text):\n";
        $prompt .= "{\n";
        $prompt .= "  \"issues\": [],\n";
        $prompt .= "  \"severity\": \"info\",\n";
        $prompt .= "  \"suggestions\": {},\n";
        $prompt .= "  \"confidence\": \"medium\"\n";
        $prompt .= "}\n\n";
        $prompt .= "CRITICAL RULES:\n";
        $prompt .= "1. List issues ONLY if data is wrong/missing (empty array if OK)\n";
        $prompt .= "2. severity: critical (missing required), warning (questionable), info (minor)\n";
        $prompt .= "3. confidence: high (certain from knowledge), medium (likely), low (unsure)\n";
        $prompt .= "4. Focus on UK growing conditions, realistic spacing, accurate timing\n\n";
        $prompt .= "SUGGESTION VALUE REQUIREMENTS:\n";
        $prompt .= "- For maturityDays: Provide ACTUAL NUMBER (e.g., '70', '90', '120') based on variety knowledge\n";
        $prompt .= "- For harvestDays: Provide ACTUAL NUMBER (e.g., '14', '21', '30') for harvest window duration\n";
        $prompt .= "- For spacing: Provide ACTUAL NUMBERS in cm (e.g., '30', '45', '60')\n";
        $prompt .= "- For descriptions: Provide COMPLETE SENTENCES with variety details\n";
        $prompt .= "- NEVER suggest vague instructions like 'Determine based on...' or 'Please provide...'\n";
        $prompt .= "- ALWAYS provide the actual value you would use\n";
        $prompt .= "- If you don't know exact value, give your best estimate based on similar plants\n\n";
        $prompt .= "KNOWLEDGE BASE:\n";
        $prompt .= "- Annuals typically: 60-90 days maturity, 14-30 days harvest window\n";
        $prompt .= "- Perennials typically: 90-120 days to first harvest, 30-60 days harvest window\n";
        $prompt .= "- Vegetables: 45-90 days maturity, 7-21 days harvest window\n";
        $prompt .= "- Flowers: 60-120 days maturity, 14-45 days harvest window\n";
        $prompt .= "- Small plants: 15-30cm spacing\n";
        $prompt .= "- Medium plants: 30-60cm spacing\n";
        $prompt .= "- Large plants: 60-120cm spacing\n\n";
        $prompt .= "Respond ONLY with valid JSON containing ACTUAL VALUES, nothing else";
        
        return $prompt;
    }

    protected function processAIResponse($variety, string $analysis)
    {
        // Try to extract JSON from response
        $json = $this->extractJSON($analysis);
        
        if (!$json) {
            $this->logError($variety, "Could not parse AI response as JSON");
            $this->log("Raw response: " . substr($analysis, 0, 200));
            return;
        }
        
        // Debug: Log what we got
        Log::info("AI JSON Response", ['variety_id' => $variety->id, 'json' => $json]);
        
        // Ensure arrays are arrays and strings are strings
        $issues = $json['issues'] ?? [];
        if (!is_array($issues)) {
            $issues = [$issues];
        }
        
        $severity = $json['severity'] ?? 'info';
        if (is_array($severity)) {
            Log::warning("Severity is array", ['severity' => $severity, 'variety_id' => $variety->id]);
            $severity = $severity[0] ?? 'info'; // Take first element if array
        }
        $severity = (string)$severity; // Force to string
        
        $suggestions = $json['suggestions'] ?? [];
        if (!is_array($suggestions)) {
            $suggestions = [];
        }
        
        $confidence = $json['confidence'] ?? 'low';
        if (is_array($confidence)) {
            Log::warning("Confidence is array", ['confidence' => $confidence, 'variety_id' => $variety->id]);
            $confidence = $confidence[0] ?? 'low'; // Take first element if array
        }
        $confidence = (string)$confidence; // Force to string
        
        // Save to database for each issue/suggestion
        if (!empty($issues) || !empty($suggestions)) {
            $this->stats['issues_found']++;
            $this->logIssue($variety, $severity, $issues, $suggestions, $confidence);
            $this->saveToDatabase($variety, $issues, $severity, $suggestions, $confidence);
            
            // Auto-fix if enabled and confidence is high
            if ($this->option('fix') && $confidence === 'high' && !$this->option('dry-run')) {
                $this->autoFix($variety, $suggestions);
            } elseif (!empty($suggestions)) {
                $this->stats['needs_review']++;
            }
        } else {
            $this->log("  ✅ No issues found");
        }
    }

    protected function extractJSON(string $text): ?array
    {
        // Try to find JSON in the response
        if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $matches)) {
            try {
                return json_decode($matches[0], true);
            } catch (\Exception $e) {
                return null;
            }
        }
        
        // Try parsing the whole thing
        try {
            return json_decode($text, true);
        } catch (\Exception $e) {
            return null;
        }
    }

    protected function autoFix($variety, array $suggestions)
    {
        $updates = [];
        $changes = [];
        
        foreach ($suggestions as $field => $value) {
            if ($value === null || $value === '') continue;
            
            // Map JSON fields to database columns
            $dbField = $field;
            
            // Only update if current value is missing or clearly wrong
            if (empty($variety->$dbField) || $this->shouldReplace($variety->$dbField, $value)) {
                $updates[$dbField] = $value;
                $changes[] = "{$field}: '{$variety->$dbField}' → '{$value}'";
            }
        }
        
        if (!empty($updates)) {
            PlantVariety::where('id', $variety->id)->update($updates);
            $this->stats['auto_fixed']++;
            
            $fixLog = "[{$variety->id}] {$variety->name}\n";
            $fixLog .= "  Changes: " . implode(", ", $changes) . "\n";
            $this->logFixed($fixLog);
            
            $this->log("  🔧 AUTO-FIXED: " . count($updates) . " fields");
        }
    }

    protected function shouldReplace($current, $suggested): bool
    {
        // Replace if current contains placeholder text
        $placeholders = ['MISSING', 'Estimated', 'Please verify', 'N/A', 'Unknown'];
        foreach ($placeholders as $placeholder) {
            if (stripos($current, $placeholder) !== false) {
                return true;
            }
        }
        return false;
    }

    protected function saveToDatabase($variety, array $issues, string $severity, array $suggestions, string $confidence)
    {
        try {
            // Combine issues into description
            $issueDescriptions = [];
            foreach ($issues as $issue) {
                if (is_array($issue)) {
                    $issueDescriptions[] = $issue['description'] ?? json_encode($issue);
                } else {
                    $issueDescriptions[] = $issue;
                }
            }
            $issueDescription = implode('; ', $issueDescriptions);
            
            // Create one audit result per suggestion field
            if (!empty($suggestions)) {
                foreach ($suggestions as $field => $suggestedValue) {
                    // Get current value from variety
                    $currentValue = $this->getCurrentFieldValue($variety, $field);
                    
                    VarietyAuditResult::create([
                        'variety_id' => $variety->id,
                        'issue_description' => $issueDescription,
                        'severity' => $severity,
                        'confidence' => $confidence,
                        'suggested_field' => $field,
                        'current_value' => is_array($currentValue) ? json_encode($currentValue) : $currentValue,
                        'suggested_value' => is_array($suggestedValue) ? json_encode($suggestedValue) : $suggestedValue,
                        'status' => 'pending',
                    ]);
                }
            } else {
                // No specific suggestions, just log the issue
                VarietyAuditResult::create([
                    'variety_id' => $variety->id,
                    'issue_description' => $issueDescription,
                    'severity' => $severity,
                    'confidence' => $confidence,
                    'status' => 'pending',
                ]);
            }
        } catch (\Exception $e) {
            Log::error("Failed to save audit result to database", [
                'variety_id' => $variety->id,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    protected function getCurrentFieldValue($variety, string $field)
    {
        // Map suggestion field names to variety model properties
        $fieldMap = [
            'maturityDays' => 'maturity_days',
            'harvestDays' => 'harvest_days',
            'inRowSpacing' => 'in_row_spacing_cm',
            'betweenRowSpacing' => 'between_row_spacing_cm',
            'plantingMethod' => 'planting_method',
            'description' => 'description',
            'harvestNotes' => 'harvest_notes',
        ];
        
        $propertyName = $fieldMap[$field] ?? $field;
        return $variety->$propertyName ?? null;
    }

    protected function logIssue($variety, string $severity, array $issues, array $suggestions, string $confidence)
    {
        $log = "⚠️  [{$variety->id}] {$variety->name}\n";
        $log .= "  Severity: {$severity} | Confidence: {$confidence}\n";
        $log .= "  Issues:\n";
        foreach ($issues as $issue) {
            $issueText = is_array($issue) ? json_encode($issue) : $issue;
            $log .= "    - {$issueText}\n";
        }
        if (!empty($suggestions)) {
            $log .= "  Suggestions:\n";
            foreach ($suggestions as $field => $value) {
                $displayValue = is_array($value) ? json_encode($value) : $value;
                $log .= "    {$field}: {$displayValue}\n";
            }
        }
        $log .= "\n";
        
        file_put_contents($this->issuesFile, $log, FILE_APPEND);
        $this->log("  ⚠️  Issues: " . count($issues) . " ({$severity})");
    }

    protected function logError($variety, string $message)
    {
        $log = "❌ [{$variety->id}] {$variety->name}: {$message}\n";
        file_put_contents($this->issuesFile, $log, FILE_APPEND);
    }

    protected function logFixed(string $message)
    {
        file_put_contents($this->fixedFile, $message . "\n", FILE_APPEND);
    }

    protected function log(string $message)
    {
        file_put_contents($this->logFile, $message . "\n", FILE_APPEND);
    }

    protected function showSummary()
    {
        $duration = $this->startTime->diffInSeconds(now());
        $perSecond = $this->stats['processed'] > 0 ? $duration / $this->stats['processed'] : 0;
        
        $this->info("=== AUDIT COMPLETE ===");
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Total Varieties', $this->stats['total']],
                ['Processed', $this->stats['processed']],
                ['Issues Found', $this->stats['issues_found']],
                ['Auto-Fixed', $this->stats['auto_fixed']],
                ['Needs Review', $this->stats['needs_review']],
                ['Errors', $this->stats['errors']],
                ['Duration', gmdate('H:i:s', $duration)],
                ['Avg Time/Variety', number_format($perSecond, 2) . 's'],
            ]
        );
        
        $this->newLine();
        $this->info("📄 Review the log files:");
        $this->info("   {$this->issuesFile}");
        if ($this->stats['auto_fixed'] > 0) {
            $this->info("   {$this->fixedFile}");
        }
        
        // Log summary
        $this->log("\n=== SUMMARY ===");
        $this->log("Processed: {$this->stats['processed']}/{$this->stats['total']}");
        $this->log("Issues: {$this->stats['issues_found']}");
        $this->log("Fixed: {$this->stats['auto_fixed']}");
        $this->log("Needs Review: {$this->stats['needs_review']}");
        $this->log("Errors: {$this->stats['errors']}");
        $this->log("Duration: " . gmdate('H:i:s', $duration));
    }
}
