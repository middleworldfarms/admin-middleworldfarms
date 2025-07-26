<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\BackupController;
use Illuminate\Support\Facades\Log;
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
        $this->info('Starting automatic backup process...');
        
        try {
            // Create a controller instance to use the backup logic
            $controller = new BackupController();
            
            // Use reflection to access the private method
            $reflection = new \ReflectionClass($controller);
            $createBackupMethod = $reflection->getMethod('createBackup');
            $createBackupMethod->setAccessible(true);
            
            // Create the backup
            $backupName = 'scheduled_' . Carbon::now()->format('Y-m-d_H-i-s');
            $filename = $createBackupMethod->invoke(
                $controller, 
                'scheduled', 
                true,  // include database
                false, // exclude files (can be large)
                'auto'
            );
            
            $this->info("✅ Backup created successfully: {$filename}");
            
            // Clean up old backups based on retention policy
            $this->cleanupOldBackups($controller);
            
            Log::info("Automatic backup created: {$filename}");
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error("❌ Backup failed: " . $e->getMessage());
            Log::error("Automatic backup failed: " . $e->getMessage());
            
            return 1;
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
