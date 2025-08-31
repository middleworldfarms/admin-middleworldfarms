<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PlantVariety;
use App\Services\FarmOSApi;
use App\Services\AI\SymbiosisAIService;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SyncFarmOSVarietiesComplete extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'farmos:sync-complete
                            {--force : Force sync all varieties regardless of last sync time}
                            {--ai-only : Only update AI-generated data for existing varieties}
                            {--dry-run : Show what would be done without making changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Complete sync of FarmOS varieties with harvest windows and seeding data';

    protected $farmOSApi;
    protected $symbiosisAI;

    public function __construct(FarmOSApi $farmOSApi, SymbiosisAIService $symbiosisAI)
    {
        parent::__construct();
        $this->farmOSApi = $farmOSApi;
        $this->symbiosisAI = $symbiosisAI;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        $aiOnly = $this->option('ai-only');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        if ($aiOnly) {
            $this->info('🤖 AI-ONLY MODE - Only updating AI data for existing varieties');
            return $this->syncAIDataOnly($dryRun);
        }

        $this->info('🌱 Starting complete FarmOS variety sync...');

        try {
            // Step 1: Sync basic variety data from FarmOS
            $this->info('📋 Step 1: Syncing basic variety data from FarmOS...');
            $basicSyncResult = $this->syncBasicVarietyData($force, $dryRun);

            // Step 2: Enhance varieties with harvest and seeding data
            $this->info('🌾 Step 2: Enhancing varieties with harvest and seeding data...');
            $enhancementResult = $this->enhanceVarietiesWithAIData($dryRun);

            // Step 3: Clean up old/inactive varieties
            $this->info('🧹 Step 3: Cleaning up old data...');
            $cleanupResult = $this->cleanupOldData($dryRun);

            // Summary
            $this->displaySummary($basicSyncResult, $enhancementResult, $cleanupResult);

            $this->info('✅ Complete FarmOS sync finished successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->error('💥 Fatal error during sync: ' . $e->getMessage());
            Log::error('Complete FarmOS sync failed: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    /**
     * Sync basic variety data from FarmOS
     */
    private function syncBasicVarietyData(bool $force, bool $dryRun): array
    {
        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => 0];

        try {
            // Get plant types
            $plantTypes = $this->farmOSApi->getPlantTypes();
            if (empty($plantTypes)) {
                $this->warn('⚠️ No plant types found in FarmOS');
                return $result;
            }

            // Get varieties
            $varieties = $this->farmOSApi->getVarieties();
            if (empty($varieties)) {
                $this->warn('⚠️ No varieties found in FarmOS');
                return $result;
            }

            $this->info("📊 Processing " . count($varieties) . " varieties...");

            $progressBar = $this->output->createProgressBar(count($varieties));
            $progressBar->start();

            foreach ($varieties as $variety) {
                try {
                    $syncResult = $this->syncSingleVariety($variety, $plantTypes, $force, $dryRun);

                    switch ($syncResult) {
                        case 'created':
                            $result['created']++;
                            break;
                        case 'updated':
                            $result['updated']++;
                            break;
                        case 'skipped':
                            $result['skipped']++;
                            break;
                    }

                    $progressBar->advance();

                } catch (\Exception $e) {
                    $this->error("❌ Error syncing variety {$variety['id']}: " . $e->getMessage());
                    $result['errors']++;
                    $progressBar->advance();
                }
            }

            $progressBar->finish();
            $this->newLine();

        } catch (\Exception $e) {
            $this->error('❌ Error in basic sync: ' . $e->getMessage());
            $result['errors']++;
        }

        return $result;
    }

    /**
     * Sync a single variety from FarmOS
     */
    private function syncSingleVariety(array $variety, array $plantTypes, bool $force, bool $dryRun): string
    {
        $attributes = $variety['attributes'] ?? [];
        $relationships = $variety['relationships'] ?? [];

        // Check if we need to sync
        if (!$force && !$dryRun) {
            $existing = PlantVariety::where('farmos_id', $variety['id'])->first();
            if ($existing && $existing->last_synced_at && $existing->last_synced_at->diffInDays(now()) < 7) {
                return 'skipped';
            }
        }

        // Find parent plant type
        $plantTypeData = null;
        if (isset($relationships['parent']['data']) && is_array($relationships['parent']['data'])) {
            foreach ($relationships['parent']['data'] as $parent) {
                if (isset($parent['id'])) {
                    foreach ($plantTypes as $type) {
                        if ($type['id'] === $parent['id']) {
                            $plantTypeData = $type;
                            break 2;
                        }
                    }
                }
            }
        }

        // Prepare basic data
        $data = [
            'farmos_id' => $variety['id'] ?? '',
            'farmos_tid' => $attributes['drupal_internal__tid'] ?? null,
            'name' => $attributes['name'] ?? 'Unknown',
            'description' => $attributes['description']['value'] ?? '',
            'plant_type' => $plantTypeData ? ($plantTypeData['attributes']['name'] ?? null) : null,
            'plant_type_id' => $plantTypeData ? $plantTypeData['id'] : null,
            'farmos_data' => $variety,
            'is_active' => true,
            'last_synced_at' => now(),
            'sync_status' => 'synced'
        ];

        if ($dryRun) {
            $existing = PlantVariety::where('farmos_id', $data['farmos_id'])->first();
            $this->info("🔍 Would " . ($existing ? 'update' : 'create') . ": {$data['name']}");
            return $existing ? 'updated' : 'created';
        }

        // Update or create
        $existing = PlantVariety::where('farmos_id', $data['farmos_id'])->first();

        if ($existing) {
            $existing->update($data);
            return 'updated';
        } else {
            PlantVariety::create($data);
            return 'created';
        }
    }

    /**
     * Enhance existing varieties with AI-generated harvest and seeding data
     */
    private function enhanceVarietiesWithAIData(bool $dryRun): array
    {
        $result = ['enhanced' => 0, 'skipped' => 0, 'errors' => 0];

        // Get varieties that need enhancement
        $varietiesToEnhance = PlantVariety::where(function($query) {
            $query->whereNull('harvest_start')
                  ->orWhereNull('indoor_seed_start')
                  ->orWhere('last_synced_at', '<', now()->subDays(30));
        })->where('is_active', true)->get();

        if ($varietiesToEnhance->isEmpty()) {
            $this->info('✅ All varieties already have complete data');
            return $result;
        }

        $this->info("🤖 Enhancing " . $varietiesToEnhance->count() . " varieties with AI data...");

        $progressBar = $this->output->createProgressBar($varietiesToEnhance->count());
        $progressBar->start();

        foreach ($varietiesToEnhance as $variety) {
            try {
                $enhancementResult = $this->enhanceSingleVariety($variety, $dryRun);

                if ($enhancementResult) {
                    $result['enhanced']++;
                } else {
                    $result['skipped']++;
                }

                $progressBar->advance();

            } catch (\Exception $e) {
                $this->error("❌ Error enhancing variety {$variety->name}: " . $e->getMessage());
                $result['errors']++;
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine();

        return $result;
    }

    /**
     * Enhance a single variety with AI data
     */
    private function enhanceSingleVariety(PlantVariety $variety, bool $dryRun): bool
    {
        // Generate harvest window data
        $harvestData = $this->generateHarvestData($variety);
        if (!$harvestData) {
            return false;
        }

        // Generate seeding and transplant data
        $seedingData = $this->generateSeedingData($variety);
        if (!$seedingData) {
            return false;
        }

        // Combine all enhancement data
        $enhancementData = array_merge($harvestData, $seedingData, [
            'last_enhanced_at' => now(),
            'enhancement_status' => 'completed'
        ]);

        if ($dryRun) {
            $this->info("🔍 Would enhance: {$variety->name} with harvest and seeding data");
            return true;
        }

        // Update the variety
        $variety->update($enhancementData);
        return true;
    }

    /**
     * Generate harvest window data for a variety
     */
    private function generateHarvestData(PlantVariety $variety): ?array
    {
        try {
            $cropName = $variety->plant_type ?? $variety->name;
            $varietyName = $variety->name;

            // Use AI to get harvest window
            $aiResponse = $this->symbiosisAI->getCropTiming($cropName, $varietyName);

            if (!$aiResponse || !isset($aiResponse['optimal_start']) || !isset($aiResponse['optimal_end'])) {
                return null;
            }

            $currentYear = date('Y');
            $harvestStart = Carbon::createFromFormat('m-d', $aiResponse['optimal_start'])->setYear($currentYear);
            $harvestEnd = Carbon::createFromFormat('m-d', $aiResponse['optimal_end'])->setYear($currentYear);

            // Calculate yield peak (middle of harvest window)
            $yieldPeak = $harvestStart->copy()->addDays($harvestStart->diffInDays($harvestEnd) / 2);

            return [
                'harvest_start' => $harvestStart,
                'harvest_end' => $harvestEnd,
                'yield_peak' => $yieldPeak,
                'harvest_window_days' => $harvestStart->diffInDays($harvestEnd),
                'harvest_notes' => $aiResponse['notes'] ?? 'AI-generated harvest window',
                'harvest_method' => $aiResponse['method'] ?? 'continuous',
                'expected_yield_per_plant' => $aiResponse['yield_per_plant'] ?? 1.0,
                'yield_unit' => $aiResponse['yield_unit'] ?? 'pounds'
            ];

        } catch (\Exception $e) {
            Log::warning('Failed to generate harvest data for variety ' . $variety->name . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate seeding and transplant data for a variety
     */
    private function generateSeedingData(PlantVariety $variety): ?array
    {
        try {
            $cropName = $variety->plant_type ?? $variety->name;
            $varietyName = $variety->name;

            // Use AI to get seeding data
            $aiResponse = $this->symbiosisAI->getSeedingTransplantData($cropName, $varietyName);

            if (!$aiResponse) {
                return null;
            }

            $currentYear = date('Y');
            $data = [];

            // Process seeding dates
            if (isset($aiResponse['indoor_seed_start'])) {
                $data['indoor_seed_start'] = Carbon::createFromFormat('m-d', $aiResponse['indoor_seed_start'])->setYear($currentYear);
            }
            if (isset($aiResponse['indoor_seed_end'])) {
                $data['indoor_seed_end'] = Carbon::createFromFormat('m-d', $aiResponse['indoor_seed_end'])->setYear($currentYear);
            }
            if (isset($aiResponse['outdoor_seed_start'])) {
                $data['outdoor_seed_start'] = Carbon::createFromFormat('m-d', $aiResponse['outdoor_seed_start'])->setYear($currentYear);
            }
            if (isset($aiResponse['outdoor_seed_end'])) {
                $data['outdoor_seed_end'] = Carbon::createFromFormat('m-d', $aiResponse['outdoor_seed_end'])->setYear($currentYear);
            }

            // Process transplant dates
            if (isset($aiResponse['transplant_start'])) {
                $data['transplant_start'] = Carbon::createFromFormat('m-d', $aiResponse['transplant_start'])->setYear($currentYear);
            }
            if (isset($aiResponse['transplant_end'])) {
                $data['transplant_end'] = Carbon::createFromFormat('m-d', $aiResponse['transplant_end'])->setYear($currentYear);
            }

            // Add other seeding data
            $data = array_merge($data, [
                'transplant_window_days' => $aiResponse['transplant_window_days'] ?? 10,
                'germination_days_min' => $aiResponse['germination_days_min'] ?? 3,
                'germination_days_max' => $aiResponse['germination_days_max'] ?? 14,
                'germination_temp_min' => $aiResponse['germination_temp_min'] ?? 60,
                'germination_temp_max' => $aiResponse['germination_temp_max'] ?? 85,
                'germination_temp_optimal' => $aiResponse['germination_temp_optimal'] ?? 75,
                'planting_depth_inches' => $aiResponse['planting_depth_inches'] ?? 0.25,
                'seed_spacing_inches' => $aiResponse['seed_spacing_inches'] ?? 2,
                'row_spacing_inches' => $aiResponse['row_spacing_inches'] ?? 24,
                'seeds_per_hole' => $aiResponse['seeds_per_hole'] ?? 1,
                'requires_light_for_germination' => $aiResponse['requires_light_for_germination'] ?? false,
                'seed_starting_notes' => $aiResponse['seed_starting_notes'] ?? '',
                'seed_type' => $aiResponse['seed_type'] ?? 'raw',
                'transplant_soil_temp_min' => $aiResponse['transplant_soil_temp_min'] ?? 60,
                'transplant_soil_temp_max' => $aiResponse['transplant_soil_temp_max'] ?? 85,
                'transplant_notes' => $aiResponse['transplant_notes'] ?? '',
                'hardening_off_days' => $aiResponse['hardening_off_days'] ?? 7,
                'hardening_off_notes' => $aiResponse['hardening_off_notes'] ?? ''
            ]);

            return $data;

        } catch (\Exception $e) {
            Log::warning('Failed to generate seeding data for variety ' . $variety->name . ': ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync only AI data for existing varieties
     */
    private function syncAIDataOnly(bool $dryRun): int
    {
        $varieties = PlantVariety::where('is_active', true)->get();

        $this->info("🤖 Updating AI data for " . $varieties->count() . " existing varieties...");

        $progressBar = $this->output->createProgressBar($varieties->count());
        $progressBar->start();

        $updated = 0;
        $errors = 0;

        foreach ($varieties as $variety) {
            try {
                $harvestData = $this->generateHarvestData($variety);
                $seedingData = $this->generateSeedingData($variety);

                if ($harvestData || $seedingData) {
                    $updateData = array_merge(
                        $harvestData ?: [],
                        $seedingData ?: [],
                        ['last_enhanced_at' => now()]
                    );

                    if ($dryRun) {
                        $this->info("🔍 Would update AI data for: {$variety->name}");
                    } else {
                        $variety->update($updateData);
                    }
                    $updated++;
                }

                $progressBar->advance();

            } catch (\Exception $e) {
                $this->error("❌ Error updating AI data for {$variety->name}: " . $e->getMessage());
                $errors++;
                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->newLine();

        $this->info("✅ AI data sync complete: {$updated} updated, {$errors} errors");
        return 0;
    }

    /**
     * Clean up old/inactive data
     */
    private function cleanupOldData(bool $dryRun): array
    {
        $result = ['deactivated' => 0, 'deleted' => 0];

        // Deactivate varieties not synced in 90 days
        $oldVarieties = PlantVariety::where('last_synced_at', '<', now()->subDays(90))
                                   ->where('is_active', true);

        $count = $oldVarieties->count();
        if ($count > 0) {
            if ($dryRun) {
                $this->info("🔍 Would deactivate {$count} old varieties");
            } else {
                $oldVarieties->update(['is_active' => false]);
                $result['deactivated'] = $count;
            }
        }

        return $result;
    }

    /**
     * Display sync summary
     */
    private function displaySummary(array $basic, array $enhancement, array $cleanup): void
    {
        $this->newLine();
        $this->info('📊 Complete FarmOS Sync Summary:');
        $this->line('─' . str_repeat('─', 40));

        $this->info("📋 Basic Sync:");
        $this->info("   ✅ Created: {$basic['created']}");
        $this->info("   🔄 Updated: {$basic['updated']}");
        $this->info("   ⏭️  Skipped: {$basic['skipped']}");
        $this->info("   ❌ Errors: {$basic['errors']}");

        $this->info("🤖 AI Enhancement:");
        $this->info("   ✅ Enhanced: {$enhancement['enhanced']}");
        $this->info("   ⏭️  Skipped: {$enhancement['skipped']}");
        $this->info("   ❌ Errors: {$enhancement['errors']}");

        $this->info("🧹 Cleanup:");
        $this->info("   📦 Deactivated: {$cleanup['deactivated']}");
    }
}
