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

            $data = [
                'sites' => $sites,
                'backups' => $allBackups,
                'summary' => $this->calculateSummary($allBackups),
            ];

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
}
