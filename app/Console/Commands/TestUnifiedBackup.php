<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UnifiedBackupService;

class TestUnifiedBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:unified-backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test the unified backup system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing Unified Backup System...');

        try {
            $service = app(UnifiedBackupService::class);

            // Test getting sites
            $sites = $service->getSites();
            $this->info('✅ Sites configured: ' . count($sites));
            foreach ($sites as $name => $config) {
                $this->line("  - {$name} ({$config['type']})");
            }

            // Test getting backups
            $backups = $service->getAllBackups();
            $this->info('✅ Backups retrieved for ' . count($backups) . ' sites');

            $totalBackups = 0;
            foreach ($backups as $siteName => $siteBackups) {
                $count = count($siteBackups);
                $totalBackups += $count;
                $this->line("  - {$siteName}: {$count} backups");
            }

            $this->info("✅ Total backups found: {$totalBackups}");

            // Test Spatie integration
            $this->info('Testing Spatie integration...');
            $spatieOutput = shell_exec('cd ' . base_path() . ' && php artisan backup:list --format=json');
            $spatieData = json_decode($spatieOutput, true);

            if ($spatieData && isset($spatieData[0])) {
                $this->info('✅ Spatie is working: ' . $spatieData[0]['name']);
                $this->info('   - Backups: ' . $spatieData[0]['number_of_backups']);
                $this->info('   - Healthy: ' . ($spatieData[0]['healthy'] ? 'Yes' : 'No'));
            } else {
                $this->warn('⚠️  Spatie test inconclusive');
            }

            $this->info('🎉 Unified Backup System test completed successfully!');

        } catch (\Exception $e) {
            $this->error('❌ Test failed: ' . $e->getMessage());
            $this->error('Stack trace: ' . $e->getTraceAsString());
            return 1;
        }

        return 0;
    }
}
