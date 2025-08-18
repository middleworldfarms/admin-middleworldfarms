<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\BackupController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class AutoBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:auto {--force : Force backup creation even if not scheduled}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an automatic backup of the database and essential files';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Starting automatic database backup...');
            
            // Use our custom backup service instead of Spatie
            $backupService = new \App\Services\DatabaseBackupService();
            
            // Get available connections
            $connections = ['mysql'];
            
            // Test and add WordPress connection if available
            try {
                DB::connection('wordpress')->getPdo();
                $connections[] = 'wordpress';
                $this->info('WordPress connection available for backup');
            } catch (\Exception $e) {
                $this->info('WordPress connection not available: ' . $e->getMessage());
            }
            
            // Test and add farmOS connection if available
            try {
                DB::connection('farmos')->getPdo();
                $connections[] = 'farmos';
                $this->info('farmOS connection available for backup');
            } catch (\Exception $e) {
                $this->info('farmOS connection not available: ' . $e->getMessage());
            }
            
            // Test and add POS system connection if available
            try {
                DB::connection('pos_system')->getPdo();
                $connections[] = 'pos_system';
                $this->info('POS system connection available for backup');
            } catch (\Exception $e) {
                $this->info('POS system connection not available: ' . $e->getMessage());
            }
            
            $result = $backupService->createBackup($connections);
            
            $this->info('Backup created successfully!');
            $this->info('File: ' . $result['file']);
            $this->info('Size: ' . number_format($result['size'] / 1024, 2) . ' KB');
            $this->info('Connections: ' . implode(', ', $result['connections']));
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            Log::error('Auto backup failed: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
    
    /**
     * Clean up old backups based on retention policy
     */
    private function cleanupOldBackups($controller)
    {
        try {
            $reflection = new \ReflectionClass($controller);
            
            // Get backup settings
            $settingsMethod = $reflection->getMethod('getBackupSettings');
            $settingsMethod->setAccessible(true);
            $settings = $settingsMethod->invoke($controller);
            
            // Get backup list
            $listMethod = $reflection->getMethod('getBackupList');
            $listMethod->setAccessible(true);
            $backups = $listMethod->invoke($controller);
            
            $retentionDays = $settings['auto_backup_retention_days'] ?? 30;
            $cutoffDate = Carbon::now()->subDays($retentionDays);
            
            $deletedCount = 0;
            
            foreach ($backups as $backup) {
                if ($backup['type'] === 'auto' && $backup['created_at']->lt($cutoffDate)) {
                    try {
                        \Illuminate\Support\Facades\Storage::disk('local')->delete('backups/' . $backup['filename']);
                        $deletedCount++;
                        $this->line("🗑️  Deleted old backup: {$backup['filename']}");
                    } catch (\Exception $e) {
                        $this->warn("Failed to delete old backup {$backup['filename']}: " . $e->getMessage());
                    }
                }
            }
            
            if ($deletedCount > 0) {
                $this->info("🧹 Cleaned up {$deletedCount} old backup(s)");
                Log::info("Cleaned up {$deletedCount} old automatic backups");
            } else {
                $this->line("✨ No old backups to clean up");
            }
            
        } catch (\Exception $e) {
            $this->warn("Cleanup failed: " . $e->getMessage());
            Log::warning("Backup cleanup failed: " . $e->getMessage());
        }
    }
}
