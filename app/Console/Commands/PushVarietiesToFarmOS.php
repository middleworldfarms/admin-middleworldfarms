<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\FarmOSApi;
use App\Models\PlantVariety;
use Illuminate\Support\Facades\Log;

class PushVarietiesToFarmOS extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'farmos:push-varieties 
                            {--variety= : Specific variety ID or name to push}
                            {--filter= : Filter varieties by name (wildcard search)}
                            {--dry-run : Show what would be pushed without actually pushing}';

    /**
     * The console command description.
     */
    protected $description = 'Push local variety data changes back to FarmOS taxonomy (DEV ONLY)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->warn('⚠️  DEV MODE: Pushing local database to FarmOS');
        $this->warn('   This reverses the normal sync direction!');
        $this->newLine();

        $dryRun = $this->option('dry-run');
        $varietyFilter = $this->option('variety');
        $nameFilter = $this->option('filter');

        if ($dryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made to FarmOS');
            $this->newLine();
        }

        try {
            $farmOSApi = app(FarmOSApi::class);

            // Build query
            $query = PlantVariety::whereNotNull('farmos_id')
                                ->where('is_active', true);

            if ($varietyFilter) {
                if (is_numeric($varietyFilter)) {
                    $query->where('id', $varietyFilter);
                } else {
                    $query->where('name', 'like', "%{$varietyFilter}%");
                }
            }

            if ($nameFilter) {
                $query->where('name', 'like', "%{$nameFilter}%");
            }

            $varieties = $query->get();

            if ($varieties->isEmpty()) {
                $this->error('❌ No varieties found matching criteria');
                return 1;
            }

            $this->info("📊 Found {$varieties->count()} varieties to push");
            $this->newLine();

            $pushed = 0;
            $skipped = 0;
            $errors = 0;

            $progressBar = $this->output->createProgressBar($varieties->count());
            $progressBar->start();

            foreach ($varieties as $variety) {
                try {
                    // Prepare data to push
                    $updateData = [
                        'attributes' => [],
                        'relationships' => []
                    ];

                    // Add spacing data if available
                    if ($variety->in_row_spacing_cm) {
                        $updateData['attributes']['in_row_spacing_cm'] = (string)$variety->in_row_spacing_cm;
                    }
                    if ($variety->between_row_spacing_cm) {
                        $updateData['attributes']['between_row_spacing_cm'] = (string)$variety->between_row_spacing_cm;
                    }
                    if ($variety->planting_method) {
                        $updateData['attributes']['planting_method'] = $variety->planting_method;
                    }

                    // Add timing data if available
                    if ($variety->maturity_days) {
                        $updateData['attributes']['maturity_days'] = (int)$variety->maturity_days;
                    }
                    if ($variety->transplant_days) {
                        $updateData['attributes']['transplant_days'] = (int)$variety->transplant_days;
                    }
                    if ($variety->harvest_days) {
                        $updateData['attributes']['harvest_days'] = (int)$variety->harvest_days;
                    }

                    // Skip if no data to push
                    if (empty($updateData['attributes']) && empty($updateData['relationships'])) {
                        $skipped++;
                        $progressBar->advance();
                        continue;
                    }

                    if ($dryRun) {
                        $this->newLine();
                        $this->line("  Would push: {$variety->name}");
                        $this->line("    Data: " . json_encode($updateData['attributes'], JSON_PRETTY_PRINT));
                        $pushed++;
                    } else {
                        // Push to FarmOS
                        $result = $farmOSApi->updatePlantTypeTerm($variety->farmos_id, $updateData);

                        if ($result['success'] ?? false) {
                            $pushed++;
                        } else {
                            $errors++;
                            Log::error("Failed to push variety to FarmOS", [
                                'variety' => $variety->name,
                                'error' => $result['error'] ?? 'Unknown error'
                            ]);
                        }
                    }

                    $progressBar->advance();

                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Exception pushing variety: " . $e->getMessage(), [
                        'variety' => $variety->name
                    ]);
                    $progressBar->advance();
                }
            }

            $progressBar->finish();
            $this->newLine();
            $this->newLine();

            $this->info("✅ Push complete!");
            $this->info("📊 Pushed: {$pushed} | Skipped: {$skipped} | Errors: {$errors}");

            if ($dryRun) {
                $this->warn('🔍 This was a DRY RUN - no changes were made');
                $this->info('💡 Run without --dry-run to actually push changes');
            }

        } catch (\Exception $e) {
            $this->error('❌ Push failed: ' . $e->getMessage());
            Log::error('FarmOS variety push failed: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
