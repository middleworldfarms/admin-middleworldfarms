<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\UnifiedBackupService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UnifiedBackupController extends Controller
{
    protected $backupService;

    public function __construct(UnifiedBackupService $backupService)
    {
        $this->backupService = $backupService;
    }

    /**
     * Display unified backup dashboard
     */
    public function index()
    {
        try {
            $sites = $this->backupService->getSites();
            $allBackups = $this->backupService->getAllBackups();
            $backupSettings = $this->loadBackupSettings();

            $data = [
                'sites' => $sites,
                'backups' => $allBackups,
                'summary' => $this->calculateSummary($allBackups),
                'backupSettings' => $backupSettings,
            ];

            // Debug logging
            Log::info('UnifiedBackup Dashboard Data', [
                'sites_count' => count($sites),
                'sites' => $sites,
                'backups_count' => count($allBackups),
                'backups' => array_map(function($siteBackups) {
                    return count($siteBackups);
                }, $allBackups),
            ]);

            return view('admin.unified-backup.index', $data);

        } catch (\Exception $e) {
            Log::error('Unified backup dashboard failed: ' . $e->getMessage());

            return view('admin.unified-backup.index', [
                'sites' => [],
                'backups' => [],
                'summary' => [
                    'total_sites' => 0,
                    'total_backups' => 0,
                    'total_size' => '0 B',
                    'last_backup' => 'Error: ' . $e->getMessage(),
                ],
                'error_message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create backup for a specific site
     */
    public function create(Request $request)
    {
        try {
            $siteName = $request->input('site');

            if (!$siteName) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site name is required'
                ], 400);
            }

            Log::info("Creating backup for site: {$siteName}");

            $result = $this->backupService->createBackup($siteName);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Backup creation failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get backup status API
     */
    public function status()
    {
        try {
            $allBackups = $this->backupService->getAllBackups();
            $summary = $this->calculateSummary($allBackups);

            return response()->json([
                'success' => true,
                'summary' => $summary,
                'backups' => $allBackups
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
    public function download($siteName, $filename)
    {
        try {
            $allBackups = $this->backupService->getAllBackups();

            if (!isset($allBackups[$siteName])) {
                abort(404, 'Site not found');
            }

            $backup = collect($allBackups[$siteName])->firstWhere('filename', $filename);

            if (!$backup || !isset($backup['path'])) {
                abort(404, 'Backup file not found');
            }

            return response()->download($backup['path']);

        } catch (\Exception $e) {
            Log::error('Backup download failed: ' . $e->getMessage());
            abort(404, 'Backup file not found');
        }
    }

    /**
     * Restore backup for a specific site
     */
    public function restore(Request $request)
    {
        try {
            $siteName = $request->input('site');
            $backupFilename = $request->input('backup');
            $restoreType = $request->input('type', 'full');

            if (!$siteName || !$backupFilename) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site name and backup filename are required'
                ], 400);
            }

            Log::info("Restoring backup for site: {$siteName}, file: {$backupFilename}, type: {$restoreType}");

            $result = $this->backupService->restoreBackup($siteName, $backupFilename, $restoreType);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Backup restore failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rename a backup file
     */
    public function rename(Request $request)
    {
        try {
            $siteName = $request->input('site');
            $currentFilename = $request->input('current_name');
            $newFilename = $request->input('new_name');

            if (!$siteName || !$currentFilename || !$newFilename) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site name, current filename, and new filename are required'
                ], 400);
            }

            Log::info("Renaming backup: {$siteName}/{$currentFilename} -> {$newFilename}");

            $result = $this->backupService->renameBackup($siteName, $currentFilename, $newFilename);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Backup rename failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Rename failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a backup file
     */
    public function delete(Request $request)
    {
        try {
            $siteName = $request->input('site');
            $filename = $request->input('filename');

            if (!$siteName || !$filename) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site name and filename are required'
                ], 400);
            }

            Log::info("Deleting backup: {$siteName}/{$filename}");

            $result = $this->backupService->deleteBackup($siteName, $filename);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => $result['message']
                ], 500);
            }

        } catch (\Exception $e) {
            Log::error('Backup delete failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate summary statistics
     */
    protected function calculateSummary($allBackups)
    {
        $totalBackups = 0;
        $totalSize = 0;
        $lastBackup = null;

        foreach ($allBackups as $siteBackups) {
            foreach ($siteBackups as $backup) {
                $totalBackups++;
                $totalSize += $backup['size'] ?? 0;

                if (!$lastBackup || strtotime($backup['created']) > strtotime($lastBackup)) {
                    $lastBackup = $backup['created'];
                }
            }
        }

        return [
            'total_sites' => count($allBackups),
            'total_backups' => $totalBackups,
            'total_size' => $this->formatBytes($totalSize),
            'last_backup' => $lastBackup ?: 'Never',
        ];
    }

    /**
     * Format bytes to human readable format
     */
    protected function formatBytes($size, $precision = 2)
    {
        if ($size === 0) return '0 B';

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $base = log($size, 1024);

        return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
    }

    /**
     * Toggle auto backup for a site
     */
    public function toggleAutoBackup(Request $request)
    {
        try {
            $siteName = $request->input('site');
            $enabled = $request->input('enabled', false);

            if (!$siteName) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site name is required'
                ], 400);
            }

            Log::info("Toggling auto backup for site: {$siteName} to " . ($enabled ? 'enabled' : 'disabled'));

            // Store the setting (we'll implement storage later)
            $this->storeAutoBackupSetting($siteName, $enabled);

            return response()->json([
                'success' => true,
                'message' => 'Auto backup ' . ($enabled ? 'enabled' : 'disabled') . ' for ' . $siteName
            ]);

        } catch (\Exception $e) {
            Log::error('Auto backup toggle failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle auto backup: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update backup time for a site
     */
    public function updateBackupTime(Request $request)
    {
        try {
            $siteName = $request->input('site');
            $time = $request->input('time');

            if (!$siteName || !$time) {
                return response()->json([
                    'success' => false,
                    'message' => 'Site name and time are required'
                ], 400);
            }

            Log::info("Updating backup time for site: {$siteName} to {$time}");

            // Store the setting (we'll implement storage later)
            $this->storeBackupTimeSetting($siteName, $time);

            return response()->json([
                'success' => true,
                'message' => 'Backup time updated to ' . $time . ' for ' . $siteName
            ]);

        } catch (\Exception $e) {
            Log::error('Backup time update failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to update backup time: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store auto backup setting (placeholder - implement storage)
     */
    protected function storeAutoBackupSetting($siteName, $enabled)
    {
        // TODO: Implement persistent storage for auto backup settings
        $settingsFile = storage_path('app/backup_settings.json');

        $settings = [];
        if (file_exists($settingsFile)) {
            $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
        }

        if (!isset($settings[$siteName])) {
            $settings[$siteName] = [];
        }

        $settings[$siteName]['auto_backup'] = $enabled;

        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
    }

    /**
     * Store backup time setting (placeholder - implement storage)
     */
    protected function storeBackupTimeSetting($siteName, $time)
    {
        // TODO: Implement persistent storage for backup time settings
        $settingsFile = storage_path('app/backup_settings.json');

        $settings = [];
        if (file_exists($settingsFile)) {
            $settings = json_decode(file_get_contents($settingsFile), true) ?: [];
        }

        if (!isset($settings[$siteName])) {
            $settings[$siteName] = [];
        }

        $settings[$siteName]['backup_time'] = $time;

        file_put_contents($settingsFile, json_encode($settings, JSON_PRETTY_PRINT));
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
}
