<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UnifiedBackupService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RunScheduledBackups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:scheduled {--site= : Run backup for specific site only} {--test-time= : Override current time for testing (HH:MM format)} {--force : Force run backups regardless of schedule}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled backups for enabled sites based on their configured times';

    protected $backupService;

    public function __construct(UnifiedBackupService $backupService)
    {
        parent::__construct();
        $this->backupService = $backupService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔄 Starting scheduled backup process...');

        $specificSite = $this->option('site');
        $testTime = $this->option('test-time');
        $forceRun = $this->option('force');
        
        // Use test time if provided, otherwise use current time
        if ($testTime) {
            $currentTime = $testTime;
            $currentHour = date('H', strtotime($testTime));
            $this->warn("🧪 TEST MODE: Using time {$testTime} instead of current time");
        } else {
            // Use Laravel's configured timezone instead of system timezone
            $currentTime = now()->format('H:i');
            $currentHour = now()->format('H');
        }

        $this->info("📅 Current time: {$currentTime} (" . config('app.timezone') . ")");

        // Load backup settings
        $settings = $this->loadBackupSettings();

        if (empty($settings)) {
            $this->warn('⚠️  No backup settings found. Using default schedule (2:00 AM for all sites).');
            $settings = $this->getDefaultSettings();
        }

        $sitesProcessed = 0;
        $backupsCreated = 0;

        foreach ($settings as $siteName => $siteSettings) {
            // Skip if specific site requested and this isn't it
            if ($specificSite && $specificSite !== $siteName) {
                continue;
            }

            // Check if auto backup is enabled
            if (!isset($siteSettings['auto_backup']) || !$siteSettings['auto_backup']) {
                $this->info("⏭️  Skipping {$siteName} - auto backup disabled");
                continue;
            }

            // Check if it's time for this site's backup (or force run)
            $backupTime = $siteSettings['backup_time'] ?? '02:00';
            
            if ($forceRun) {
                $this->warn("💪 FORCE MODE: Running backup for {$siteName} regardless of schedule");
            } else {
                // Parse backup time in the application's timezone
                $backupDateTime = now()->setTimeFromTimeString($backupTime);
                $backupHour = $backupDateTime->format('H');

                if ($currentHour !== $backupHour) {
                    $this->info("⏰ Skipping {$siteName} - scheduled for {$backupTime}, current hour: {$currentHour}");
                    continue;
                }
            }

            $this->info("🚀 Processing backup for {$siteName} at {$backupTime}");
            $sitesProcessed++;

            try {
                $result = $this->backupService->createBackup($siteName);

                if ($result['success']) {
                    $this->info("✅ Backup created successfully for {$siteName}");
                    $backupsCreated++;
                    Log::info("Scheduled backup completed for {$siteName}");
                } else {
                    $this->error("❌ Backup failed for {$siteName}: {$result['message']}");
                    Log::error("Scheduled backup failed for {$siteName}: {$result['message']}");
                }

            } catch (\Exception $e) {
                $this->error("💥 Exception during backup for {$siteName}: {$e->getMessage()}");
                Log::error("Scheduled backup exception for {$siteName}: {$e->getMessage()}");
            }
        }

        $this->info("📊 Backup summary: {$sitesProcessed} sites processed, {$backupsCreated} backups created");
        $this->info('✅ Scheduled backup process completed!');
    }

    /**
     * Load backup settings from storage
     */
    protected function loadBackupSettings()
    {
        $settingsFile = storage_path('app/backup_settings.json');

        if (!file_exists($settingsFile)) {
            return [];
        }

        $settings = json_decode(file_get_contents($settingsFile), true);

        return $settings ?: [];
    }

    /**
     * Get default settings for all sites (2:00 AM)
     */
    protected function getDefaultSettings()
    {
        $sites = $this->backupService->getSites();
        $defaultSettings = [];

        foreach ($sites as $siteName => $siteConfig) {
            if ($siteConfig['enabled']) {
                $defaultSettings[$siteName] = [
                    'auto_backup' => true,
                    'backup_time' => '02:00'
                ];
            }
        }

        return $defaultSettings;
    }
}
