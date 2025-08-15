<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PleskBackupService;
use App\Services\DatabaseBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PleskBackupController extends Controller
{
    protected $pleskBackupService;
    protected $databaseBackupService;

    public function __construct(PleskBackupService $pleskBackupService, DatabaseBackupService $databaseBackupService)
    {
        $this->pleskBackupService = $pleskBackupService;
        $this->databaseBackupService = $databaseBackupService;
    }

    /**
     * Display backup dashboard
     */
    public function index()
    {
        try {
            $pleskStatus = $this->pleskBackupService->getBackupStatus();
            $pleskBackups = $this->pleskBackupService->listBackups();
            $localBackups = $this->databaseBackupService->listBackups();
            
            $data = [
                'plesk_status' => $pleskStatus,
                'plesk_backups' => array_slice($pleskBackups, 0, 10), // Last 10 backups
                'local_backups' => $localBackups,
                'backup_summary' => [
                    'plesk_total' => count($pleskBackups),
                    'local_total' => count($localBackups),
                    'health_status' => $pleskStatus['backup_health'] ?? 'unknown',
                    'last_plesk_backup' => $pleskStatus['last_backup']['created'] ?? 'Never',
                    'total_plesk_size' => $this->formatBytes($pleskStatus['total_size'] ?? 0),
                ]
            ];
            
            return view('admin.plesk-backup.index', $data);
            
        } catch (\Exception $e) {
            Log::error('Plesk backup dashboard failed: ' . $e->getMessage());
            
            // Return error view instead of redirect
            return view('admin.plesk-backup.index', [
                'plesk_status' => ['backup_health' => 'error'],
                'plesk_backups' => [],
                'local_backups' => [],
                'backup_summary' => [
                    'plesk_total' => 0,
                    'local_total' => 0,
                    'health_status' => 'error',
                    'last_plesk_backup' => 'Error: ' . $e->getMessage(),
                    'total_plesk_size' => '0 B',
                ],
                'error_message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create a new Plesk backup
     */
    public function createPlesk(Request $request)
    {
        try {
            $domains = $request->input('domains', []);
            $description = $request->input('description', 'Manual backup from admin panel');
            
            Log::info('Creating Plesk backup', ['domains' => $domains, 'description' => $description]);
            
            $result = $this->pleskBackupService->createBackup($domains, $description);
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Plesk backup created successfully',
                    'file' => basename($result['file']),
                    'size' => $this->formatBytes($result['size']),
                    'domains' => $result['domains']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Plesk backup failed: ' . $result['message']
                ], 500);
            }
            
        } catch (\Exception $e) {
            Log::error('Plesk backup creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create a local admin backup
     */
    public function createLocal(Request $request)
    {
        try {
            Log::info('Creating local admin backup');
            
            $result = $this->databaseBackupService->createBackup(['mysql']);
            
            return response()->json([
                'success' => true,
                'message' => 'Local admin backup created successfully',
                'file' => basename($result['file']),
                'size' => $this->formatBytes($result['size']),
                'connections' => $result['connections']
            ]);
            
        } catch (\Exception $e) {
            Log::error('Local backup creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Local backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get backup status API
     */
    public function status()
    {
        try {
            $pleskStatus = $this->pleskBackupService->getBackupStatus();
            $localBackups = $this->databaseBackupService->listBackups();
            
            return response()->json([
                'success' => true,
                'plesk' => $pleskStatus,
                'local' => [
                    'total_backups' => count($localBackups),
                    'last_backup' => !empty($localBackups) ? date('Y-m-d H:i:s', $localBackups[0]['created']) : null,
                    'total_size' => array_sum(array_column($localBackups, 'size'))
                ]
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download a backup file
     */
    public function download($filename)
    {
        try {
            $filepath = $this->pleskBackupService->downloadBackup($filename);
            return response()->download($filepath);
            
        } catch (\Exception $e) {
            Log::error('Backup download failed: ' . $e->getMessage());
            abort(404, 'Backup file not found');
        }
    }

    /**
     * Show backup details
     */
    public function details($filename)
    {
        try {
            $backups = $this->pleskBackupService->listBackups();
            $backup = collect($backups)->firstWhere('filename', $filename);
            
            if (!$backup) {
                abort(404, 'Backup not found');
            }
            
            return response()->json([
                'success' => true,
                'backup' => $backup
            ]);
            
        } catch (\Exception $e) {
            Log::error('Backup details failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test Plesk connectivity
     */
    public function testPlesk()
    {
        try {
            $access = $this->pleskBackupService->testPleskAccess();
            
            return response()->json([
                'success' => $access['accessible'],
                'version' => $access['version'] ?? null,
                'error' => $access['error'] ?? null
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes($size, $precision = 2)
    {
        if ($size === 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($size, 1024);
        
        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
    }
}
