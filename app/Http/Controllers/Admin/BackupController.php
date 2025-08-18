<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class BackupController extends Controller
{
    private $backupPath = 'backups';

    public function __construct()
    {
        // Ensure backup directory exists
        if (!Storage::disk('local')->exists($this->backupPath)) {
            Storage::disk('local')->makeDirectory($this->backupPath);
        }
    }

    /**
     * Display backup management page
     */
    public function index(Request $request)
    {
        try {
            $backups = $this->getBackupList();
            $backupSettings = $this->getBackupSettings();
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'backups' => $backups,
                    'settings' => $backupSettings
                ]);
            }

            return view('admin.backups.index', compact('backups', 'backupSettings'));
        } catch (\Exception $e) {
            Log::error('Backup index failed: ' . $e->getMessage());
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load backups: ' . $e->getMessage()
                ], 500);
            }
            
            return view('admin.backups.index', ['backups' => [], 'backupSettings' => []]);
        }
    }

    /**
     * Create backup using Spatie Laravel Backup
     */
    public function create(Request $request)
    {
        try {
            $name = $request->input('name', 'manual_backup_' . date('Y-m-d_H-i-s'));
            
            Log::info('Starting backup creation', ['name' => $name]);

            // Use Spatie Laravel Backup package - much more reliable!
            $exitCode = Artisan::call('backup:run', [
                '--force' => true,
                '--name' => $name
            ]);

            $output = Artisan::output();
            Log::info('Backup command output', ['output' => $output, 'exit_code' => $exitCode]);

            if ($exitCode === 0) {
                // Extract backup filename from output
                if (preg_match('/Backup created successfully: (.+\.zip)/', $output, $matches)) {
                    $backupFile = $matches[1];
                } else {
                    // Fallback: list newest backup file
                    $backups = $this->getBackupList();
                    $backupFile = !empty($backups) ? $backups[0]['filename'] : 'backup_created.zip';
                }

                Log::info('Backup created successfully', ['file' => $backupFile]);

                return response()->json([
                    'success' => true,
                    'message' => 'Backup created successfully',
                    'backup' => $backupFile
                ]);
            } else {
                throw new \Exception('Backup command failed with exit code: ' . $exitCode . '. Output: ' . $output);
            }

        } catch (\Exception $e) {
            Log::error('Backup creation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download backup file
     */
    public function download(Request $request, $filename)
    {
        try {
            $backupPath = storage_path('app/' . $this->backupPath . '/' . $filename);
            
            if (!file_exists($backupPath)) {
                return response()->json(['error' => 'Backup file not found'], 404);
            }

            return response()->download($backupPath);
        } catch (\Exception $e) {
            Log::error('Backup download failed', ['error' => $e->getMessage(), 'file' => $filename]);
            return response()->json(['error' => 'Download failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Delete backup file
     */
    public function delete(Request $request, $filename)
    {
        try {
            $backupPath = storage_path('app/' . $this->backupPath . '/' . $filename);
            
            if (file_exists($backupPath)) {
                unlink($backupPath);
                Log::info('Backup deleted', ['file' => $filename]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Backup deleted successfully'
                ]);
            } else {
                return response()->json(['error' => 'Backup file not found'], 404);
            }
        } catch (\Exception $e) {
            Log::error('Backup deletion failed', ['error' => $e->getMessage(), 'file' => $filename]);
            return response()->json(['error' => 'Delete failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * List available backups using Spatie package
     */
    public function list(Request $request)
    {
        try {
            $exitCode = Artisan::call('backup:list');
            $output = Artisan::output();
            
            $backups = $this->getBackupList();
            
            return response()->json([
                'success' => true,
                'backups' => $backups
            ]);
        } catch (\Exception $e) {
            Log::error('Backup list failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to list backups'], 500);
        }
    }

    /**
     * Clean old backups using Spatie package
     */
    public function clean(Request $request)
    {
        try {
            $exitCode = Artisan::call('backup:clean');
            $output = Artisan::output();
            
            Log::info('Backup cleanup completed', ['output' => $output]);
            
            return response()->json([
                'success' => true,
                'message' => 'Old backups cleaned successfully'
            ]);
        } catch (\Exception $e) {
            Log::error('Backup cleanup failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Cleanup failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Get list of backup files from storage
     */
    private function getBackupList()
    {
        $backups = [];
        $backupDir = storage_path('app/' . $this->backupPath);
        
        if (is_dir($backupDir)) {
            $files = glob($backupDir . '/*.zip');
            
            foreach ($files as $file) {
                $filename = basename($file);
                $backups[] = [
                    'filename' => $filename,
                    'size' => $this->formatBytes(filesize($file)),
                    'created_at' => date('Y-m-d H:i:s', filemtime($file)),
                    'type' => $this->getBackupType($filename)
                ];
            }
            
            // Sort by creation time, newest first
            usort($backups, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
        }
        
        return $backups;
    }

    /**
     * Get backup settings
     */
    private function getBackupSettings()
    {
        return [
            'auto_backup_enabled' => config('backup.auto_backup_enabled', true),
            'retention_days' => config('backup.auto_backup_retention_days', 30),
            'storage_path' => storage_path('app/' . $this->backupPath)
        ];
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Determine backup type from filename
     */
    private function getBackupType($filename)
    {
        if (strpos($filename, 'manual') !== false) {
            return 'Manual';
        } elseif (strpos($filename, 'scheduled') !== false || strpos($filename, 'auto') !== false) {
            return 'Scheduled';
        } else {
            return 'Unknown';
        }
    }
}
