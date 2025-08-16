<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\BackupController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Carbon\Carbon;

class RunScheduledBackup extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:run 
                            {--force : Force backup creation even if not scheduled}
                            {--name= : Custom name for the backup}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Run scheduled backup if due, or force create a backup';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $force = $this->option('force');
        $customName = $this->option('name') ?: 'scheduled';

        try {
            // Get backup settings
            $settings = $this->getBackupSettings();
            
            if (!$force && !$this->shouldRunBackup($settings)) {
                $this->info('No backup scheduled at this time.');
                return 0;
            }

            $this->info('Starting backup process...');
            
            // Use Spatie Laravel Backup for multi-site backups
            $backupName = $customName . '_' . date('Y-m-d_H-i-s');
            
            $exitCode = Artisan::call('backup:run', [
                '--force' => true,
                '--name' => $backupName
            ]);

            $output = Artisan::output();
            
            if ($exitCode === 0) {
                if (preg_match('/Backup created successfully: (.+\.zip)/', $output, $matches)) {
                    $backupFile = $matches[1];
                } else {
                    $backupFile = $backupName . '.zip';
                }
                
                $this->info("Multi-site backup created successfully: {$backupFile}");
                $this->info("Includes: Laravel, WordPress, farmOS, POS System databases and files");
                
                // Clean up old backups using Spatie's cleanup
                if (!$force) {
                    $this->call('backup:clean');
                }
            } else {
                throw new \Exception('Backup command failed: ' . $output);
            }
            
            Log::info('Scheduled backup completed', [
                'backup_file' => $backupFile,
                'forced' => $force
            ]);
            
            return 0;
            
        } catch (\Exception $e) {
            $this->error('Backup failed: ' . $e->getMessage());
            Log::error('Scheduled backup failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return 1;
        }
    }

    /**
     * Check if backup should run based on schedule
     */
    private function shouldRunBackup($settings)
    {
        $frequency = $settings['auto_backup_frequency'] ?? 'disabled';
        
        if ($frequency === 'disabled') {
            return false;
        }
        
        $now = Carbon::now();
        $backupTime = $settings['auto_backup_time'] ?? '02:00';
        $todayBackupTime = Carbon::today()->setTimeFromTimeString($backupTime);
        
        // Check if we're within the backup time window (±5 minutes)
        $timeDiff = abs($now->diffInMinutes($todayBackupTime));
        
        if ($timeDiff > 5) {
            return false;
        }
        
        // Check if we already ran a backup today for daily frequency
        if ($frequency === 'daily') {
            return !$this->hasBackupToday();
        }
        
        // Check for weekly frequency (run on Sundays)
        if ($frequency === 'weekly') {
            return $now->isSunday() && !$this->hasBackupThisWeek();
        }
        
        // Check for monthly frequency (run on 1st of month)
        if ($frequency === 'monthly') {
            return $now->day === 1 && !$this->hasBackupThisMonth();
        }
        
        return false;
    }

    /**
     * Check if backup was already created today
     */
    private function hasBackupToday()
    {
        $controller = new BackupController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getBackupList');
        $method->setAccessible(true);
        
        $backups = $method->invoke($controller);
        
        foreach ($backups as $backup) {
            if ($backup['type'] === 'auto' && 
                Carbon::parse($backup['created_at'])->isToday()) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if backup was already created this week
     */
    private function hasBackupThisWeek()
    {
        $controller = new BackupController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getBackupList');
        $method->setAccessible(true);
        
        $backups = $method->invoke($controller);
        
        $startOfWeek = Carbon::now()->startOfWeek();
        
        foreach ($backups as $backup) {
            if ($backup['type'] === 'auto' && 
                Carbon::parse($backup['created_at'])->isAfter($startOfWeek)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Check if backup was already created this month
     */
    private function hasBackupThisMonth()
    {
        $controller = new BackupController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getBackupList');
        $method->setAccessible(true);
        
        $backups = $method->invoke($controller);
        
        $startOfMonth = Carbon::now()->startOfMonth();
        
        foreach ($backups as $backup) {
            if ($backup['type'] === 'auto' && 
                Carbon::parse($backup['created_at'])->isAfter($startOfMonth)) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Clean up old backups based on retention settings
     */
    private function cleanupOldBackups($settings)
    {
        $retentionDays = $settings['auto_backup_retention_days'] ?? 30;
        $cutoffDate = Carbon::now()->subDays($retentionDays);
        
        $controller = new BackupController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('getBackupList');
        $method->setAccessible(true);
        
        $backups = $method->invoke($controller);
        $deletedCount = 0;
        
        foreach ($backups as $backup) {
            if ($backup['type'] === 'auto' && 
                Carbon::parse($backup['created_at'])->isBefore($cutoffDate)) {
                
                try {
                    $deleteMethod = $reflection->getMethod('delete');
                    $deleteMethod->setAccessible(true);
                    $deleteMethod->invoke($controller, $backup['filename']);
                    
                    $deletedCount++;
                    $this->info("Deleted old backup: {$backup['filename']}");
                    
                } catch (\Exception $e) {
                    $this->warn("Failed to delete backup {$backup['filename']}: " . $e->getMessage());
                }
            }
        }
        
        if ($deletedCount > 0) {
            $this->info("Cleaned up {$deletedCount} old backup(s)");
        }
    }

    /**
     * Get backup settings
     */
    private function getBackupSettings()
    {
        return [
            'auto_backup_enabled' => config('backup.auto_backup_enabled', true),
            'auto_backup_frequency' => config('backup.auto_backup_frequency', 'daily'),
            'auto_backup_time' => config('backup.auto_backup_time', '02:00'),
            'auto_backup_retention_days' => config('backup.auto_backup_retention_days', 30),
        ];
    }
}
