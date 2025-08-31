<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\PlantVariety;
use App\Services\FarmOSApi;
use Illuminate\Support\Facades\Log;

class SyncPlantVarieties extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'farmos:sync-varieties {--force : Force sync all varieties regardless of last sync time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync plant variety data from FarmOS to local database';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🌱 Starting FarmOS variety sync...');

        $force = $this->option('force');

        try {
            $farmOSApi = app(FarmOSApi::class);

            // Get all plant types first
            $this->info('📋 Fetching plant types from FarmOS...');
            $plantTypes = $farmOSApi->getPlantTypes();

            if (empty($plantTypes)) {
                $this->error('❌ No plant types found in FarmOS');
                return 1;
            }

            $this->info("📊 Found " . count($plantTypes) . " plant types");

            // Get all varieties
            $this->info('🌿 Fetching plant varieties from FarmOS...');
            $varieties = $farmOSApi->getVarieties();

            if (empty($varieties)) {
                $this->error('❌ No varieties found in FarmOS');
                return 1;
            }

            $this->info("🌱 Found " . count($varieties) . " varieties to process");

            // Create a lookup map for plant types
            $plantTypeMap = [];
            foreach ($plantTypes as $type) {
                $plantTypeMap[$type['id']] = $type;
            }

            $processed = 0;
            $updated = 0;
            $created = 0;
            $skipped = 0;
            $errors = 0;

            $progressBar = $this->output->createProgressBar(count($varieties));
            $progressBar->start();

            foreach ($varieties as $variety) {
                try {
                    $result = $this->syncVariety($variety, $plantTypeMap, $force);
                    
                    switch ($result) {
                        case 'created':
                            $created++;
                            break;
                        case 'updated':
                            $updated++;
                            break;
                        case 'skipped':
                            $skipped++;
                            break;
                    }
                    
                    $processed++;
                    $progressBar->advance();
                    
                } catch (\Exception $e) {
                    $this->error("❌ Error processing variety {$variety['id']}: " . $e->getMessage());
                    $errors++;
                    $progressBar->advance();
                }
            }

            $progressBar->finish();
            $this->newLine(2);

            $this->info("🎉 Sync complete!");
            $this->info("📊 Summary:");
            $this->info("   ✅ Created: {$created}");
            $this->info("   🔄 Updated: {$updated}");
            $this->info("   ⏭️  Skipped: {$skipped}");
            $this->info("   ❌ Errors: {$errors}");
            $this->info("   📊 Total processed: {$processed}");

            Log::info('Plant variety sync completed', [
                'created' => $created,
                'updated' => $updated,
                'skipped' => $skipped,
                'errors' => $errors,
                'total_processed' => $processed
            ]);

            return 0;

        } catch (\Exception $e) {
            $this->error('💥 Fatal error during sync: ' . $e->getMessage());
            Log::error('PlantVariety sync command failed: ' . $e->getMessage());
            return 1;
        }
    }

    /**
     * Sync a single variety
     */
    private function syncVariety(array $variety, array $plantTypeMap, bool $force): string
    {
        $attributes = $variety['attributes'] ?? [];
        $relationships = $variety['relationships'] ?? [];

        // Check if we need to sync this variety
        if (!$force) {
            $existing = PlantVariety::where('farmos_id', $variety['id'])->first();
            if ($existing && $existing->last_synced_at && $existing->last_synced_at->diffInDays(now()) < 7) {
                return 'skipped';
            }
        }

        // Get parent plant type
        $parentId = null;
        if (isset($relationships['parent']['data']) && is_array($relationships['parent']['data'])) {
            foreach ($relationships['parent']['data'] as $parent) {
                if (isset($parent['id'])) {
                    $parentId = $parent['id'];
                    break;
                }
            }
        }

        $plantTypeData = $parentId && isset($plantTypeMap[$parentId]) ? $plantTypeMap[$parentId] : null;

        // Extract description
        $description = '';
        if (isset($attributes['description']['value'])) {
            $description = $attributes['description']['value'];
        }

        // Parse description for additional data
        $parsedData = $this->parseDescription($description);

        // Prepare data for database
        $data = [
            'farmos_id' => $variety['id'] ?? '',
            'farmos_tid' => $attributes['drupal_internal__tid'] ?? null,
            'name' => $attributes['name'] ?? 'Unknown',
            'description' => $description,
            'scientific_name' => $parsedData['scientific_name'] ?? null,
            'crop_family' => $parsedData['family'] ?? null,
            'plant_type' => $plantTypeData ? ($plantTypeData['attributes']['name'] ?? null) : null,
            'plant_type_id' => $parentId,
            'maturity_days' => $parsedData['maturity_days'] ?? $attributes['maturity_days'] ?? null,
            'transplant_days' => $parsedData['transplant_days'] ?? $attributes['transplant_days'] ?? null,
            'harvest_days' => $parsedData['harvest_days'] ?? $attributes['harvest_days'] ?? null,
            'min_temperature' => $parsedData['min_temp'] ?? null,
            'max_temperature' => $parsedData['max_temp'] ?? null,
            'optimal_temperature' => $parsedData['optimal_temp'] ?? null,
            'season' => $parsedData['season'] ?? null,
            'frost_tolerance' => $parsedData['frost_tolerance'] ?? null,
            'companions' => isset($relationships['companions']['data']) ? $relationships['companions']['data'] : null,
            'external_uris' => $attributes['external_uri'] ?? null,
            'farmos_data' => $variety, // Store complete FarmOS response
            'is_active' => true,
            'last_synced_at' => now(),
            'sync_status' => 'synced'
        ];

        // Use updateOrCreate to handle duplicates
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
     * Parse description text for structured data
     */
    private function parseDescription(string $description): array
    {
        $data = [];

        if (empty($description)) {
            return $data;
        }

        // Extract scientific name
        if (preg_match('/Scientific name:\s*([^,\n]+)/i', $description, $matches)) {
            $data['scientific_name'] = trim($matches[1]);
        }

        // Extract family
        if (preg_match('/Family:\s*([^,\n]+)/i', $description, $matches)) {
            $data['family'] = trim($matches[1]);
        }

        // Extract season
        if (preg_match('/Season:\s*([^,\n]+)/i', $description, $matches)) {
            $data['season'] = trim($matches[1]);
        }

        // Extract growing days
        if (preg_match('/Growing days:\s*(\d+)(?:\s*-\s*(\d+))?/i', $description, $matches)) {
            $data['maturity_days'] = (int)$matches[1];
            if (isset($matches[2])) {
                $data['maturity_days'] = (int)$matches[2]; // Use upper range
            }
        }

        // Extract temperature range
        if (preg_match('/Temperature range:\s*([-\d]+)°C\s*-\s*([-\d]+)°C/i', $description, $matches)) {
            $data['min_temp'] = (float)$matches[1];
            $data['max_temp'] = (float)$matches[2];
            $data['optimal_temp'] = ($data['min_temp'] + $data['max_temp']) / 2;
        }

        return $data;
    }
}
