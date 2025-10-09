<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PlantVariety;
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
        
        // Build query
        $query = PlantVariety::query();
        
        if ($startId = $this->option('start-id')) {
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
        foreach ($query->cursor() as $variety) {
            try {
                $this->processVariety($variety);
                $this->stats['processed']++;
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
            $response = $this->ai->chat($messages, ['max_tokens' => 800, 'temperature' => 0.1]);
            $analysis = $response['choices'][0]['message']['content'] ?? '';
            
            // Parse AI response and take action
            $this->processAIResponse($variety, $analysis);
            
        } catch (\Exception $e) {
            $this->logError($variety, "AI request failed: " . $e->getMessage());
            $this->stats['errors']++;
        }
    }

    protected function buildAuditPrompt($variety): string
    {
        $prompt = "Audit this plant variety data:\n\n";
        $prompt .= "ID: {$variety->id}\n";
        $prompt .= "Name: {$variety->name}\n";
        $prompt .= "Plant Type: {$variety->plant_type}\n";
        $prompt .= "Crop Family: {$variety->crop_family}\n";
        $prompt .= "Description: " . ($variety->description ?: 'MISSING') . "\n";
        $prompt .= "Harvest Notes: " . ($variety->harvest_notes ?: 'MISSING') . "\n";
        $prompt .= "Maturity Days: " . ($variety->maturity_days ?: 'MISSING') . "\n";
        $prompt .= "Harvest Days: " . ($variety->harvest_days ?: 'MISSING') . "\n";
        $prompt .= "In-row Spacing: " . ($variety->in_row_spacing_cm ?: 'MISSING') . " cm\n";
        $prompt .= "Between-row Spacing: " . ($variety->between_row_spacing_cm ?: 'MISSING') . " cm\n";
        $prompt .= "Planting Method: " . ($variety->planting_method ?: 'MISSING') . "\n\n";
        
        $prompt .= "RESPOND WITH ONLY THIS JSON (no other text):\n";
        $prompt .= "{\n";
        $prompt .= "  \"issues\": [],\n";
        $prompt .= "  \"severity\": \"info\",\n";
        $prompt .= "  \"suggestions\": {},\n";
        $prompt .= "  \"confidence\": \"medium\"\n";
        $prompt .= "}\n\n";
        $prompt .= "Rules:\n";
        $prompt .= "- List issues ONLY if data is wrong/missing (empty array if OK)\n";
        $prompt .= "- severity: critical (missing required), warning (questionable), info (minor)\n";
        $prompt .= "- suggestions: ONLY include fields that need updating\n";
        $prompt .= "- confidence: high (certain), medium (likely), low (unsure)\n";
        $prompt .= "- Focus on UK growing, realistic spacing, accurate timing\n";
        $prompt .= "- Respond ONLY with valid JSON, nothing else";
        
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
        
        $issues = $json['issues'] ?? [];
        $severity = $json['severity'] ?? 'info';
        $suggestions = $json['suggestions'] ?? [];
        $confidence = $json['confidence'] ?? 'low';
        
        // Log if issues found
        if (!empty($issues)) {
            $this->stats['issues_found']++;
            $this->logIssue($variety, $severity, $issues, $suggestions, $confidence);
            
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

    protected function logIssue($variety, string $severity, array $issues, array $suggestions, string $confidence)
    {
        $log = "⚠️  [{$variety->id}] {$variety->name}\n";
        $log .= "  Severity: {$severity} | Confidence: {$confidence}\n";
        $log .= "  Issues:\n";
        foreach ($issues as $issue) {
            $log .= "    - {$issue}\n";
        }
        if (!empty($suggestions)) {
            $log .= "  Suggestions:\n";
            foreach ($suggestions as $field => $value) {
                $log .= "    {$field}: {$value}\n";
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
