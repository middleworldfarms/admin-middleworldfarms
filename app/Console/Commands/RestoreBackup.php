<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\UnifiedBackupService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class RestoreBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:restore {site : The site name to restore} {filename : The backup filename to restore} {--type=full : Restore type (full/database/files)} {--confirm : Skip confirmation prompt}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore backup for a specific site via command line (disaster recovery)';

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
        $siteName = $this->argument('site');
        $filename = $this->argument('filename');
        $restoreType = $this->option('type');
        $skipConfirm = $this->option('confirm');

        $this->info('🚨 DISASTER RECOVERY MODE 🚨');
        $this->warn('This command is for emergency restoration when the web interface is unavailable.');
        $this->newLine();

        // Validate site exists
        $sites = config('unified_backup.sites');
        if (!isset($sites[$siteName])) {
            $this->error("❌ Site '{$siteName}' not found in configuration.");
            $this->info('Available sites:');
            foreach ($sites as $name => $config) {
                $this->info("  - {$name} ({$config['label']})");
            }
            return 1;
        }

        $siteConfig = $sites[$siteName];
        $this->info("📍 Target Site: {$siteConfig['label']} ({$siteName})");
        $this->info("📁 Backup File: {$filename}");
        $this->info("🔄 Restore Type: {$restoreType}");
        $this->newLine();

        // Check if backup file exists
        $backupPath = '';
        $availableBackups = $this->getAvailableBackups($siteName);

        // First try the exact filename in backups directory
        $standardPath = storage_path("app/private/backups/{$filename}");
        if (file_exists($standardPath)) {
            $backupPath = $standardPath;
        } else {
            // Look for the file in available backups
            foreach ($availableBackups as $backup) {
                if ($backup['filename'] === $filename) {
                    $backupPath = $backup['path'];
                    break;
                }
            }
        }

        if (empty($backupPath) || !file_exists($backupPath)) {
            $this->error("❌ Backup file not found: {$filename}");

            // List available backups for this site
            $this->info('Available backups:');
            if (empty($availableBackups)) {
                $this->error('No backups found for this site.');
                return 1;
            }

            foreach ($availableBackups as $backup) {
                $this->info("  - {$backup['filename']} ({$backup['size']}, {$backup['date']})");
            }
            return 1;
        }

        // Show backup file info
        $fileSize = $this->formatBytes(filesize($backupPath));
        $fileDate = date('Y-m-d H:i:s', filemtime($backupPath));
        $this->info("📊 Backup Info:");
        $this->info("  Size: {$fileSize}");
        $this->info("  Created: {$fileDate}");
        $this->newLine();

        // Confirmation prompt (unless --confirm is used)
        if (!$skipConfirm) {
            if (!$this->confirm('⚠️  Are you sure you want to restore this backup? This may overwrite existing data.')) {
                $this->info('❌ Restore cancelled.');
                return 0;
            }
        }

        $this->info('🔄 Starting restore process...');
        $this->newLine();

        try {
            // Perform the restore
            $result = $this->backupService->restoreBackup($siteName, $filename, $restoreType);

            if ($result['success']) {
                $this->info('✅ Restore completed successfully!');
                $this->info($result['message']);

                // Log the successful restore
                Log::info("Command-line restore completed: Site {$siteName}, File {$filename}, Type {$restoreType}");

                return 0;
            } else {
                $this->error('❌ Restore failed!');
                $this->error($result['message']);
                Log::error("Command-line restore failed: Site {$siteName}, File {$filename}, Error: {$result['message']}");
                return 1;
            }

        } catch (\Exception $e) {
            $this->error('❌ Restore failed with exception!');
            $this->error($e->getMessage());
            Log::error("Command-line restore exception: Site {$siteName}, File {$filename}, Error: {$e->getMessage()}");
            return 1;
        }
    }

    /**
     * Get available backups for a site
     */
    protected function getAvailableBackups($siteName)
    {
        $backups = [];
        $backupDirs = [
            storage_path('app/private/backups'), // Unified backups
            storage_path('app/private'), // Laravel/Spatie backups (main directory)
        ];

        foreach ($backupDirs as $backupDir) {
            if (!is_dir($backupDir)) {
                continue;
            }

            // Scan directory recursively for ZIP files
            $this->scanDirectoryForBackups($backupDir, $backups, $siteName);
        }

        // Sort by date (newest first)
        usort($backups, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return $backups;
    }

    /**
     * Recursively scan directory for backup files
     */
    protected function scanDirectoryForBackups($directory, &$backups, $siteName)
    {
        $files = scandir($directory);

        foreach ($files as $file) {
            if ($file === '.' || $file === '..') continue;

            $filePath = $directory . '/' . $file;

            if (is_file($filePath) && pathinfo($file, PATHINFO_EXTENSION) === 'zip') {
                // For admin site, accept any ZIP file since it's the main Laravel backup
                if ($siteName === 'admin.middleworldfarms.org' ||
                    strpos($file, $siteName) !== false ||
                    strpos($file, str_replace('.', '-', $siteName)) !== false) {
                    $backups[] = [
                        'filename' => $file,
                        'path' => $filePath,
                        'size' => $this->formatBytes(filesize($filePath)),
                        'date' => date('Y-m-d H:i:s', filemtime($filePath))
                    ];
                }
            } elseif (is_dir($filePath)) {
                // Recursively scan subdirectories
                $this->scanDirectoryForBackups($filePath, $backups, $siteName);
            }
        }
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }
        return round($bytes, 2) . ' ' . $units[$i];
    }
}
