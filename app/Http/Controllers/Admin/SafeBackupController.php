<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Process;

class SafeBackupController extends Controller
{
    private $backupBaseDir = '/opt/backups';
    private $logFile = '/var/log/safe-backup.log';

    public function index()
    {
        // Get backup status and summary
        $status = $this->getBackupStatus();
        $backupSummary = $this->getBackupSummary();
        $recentLogs = $this->getRecentLogs();
        
        return view('admin.safe-backup.index', compact('status', 'backupSummary', 'recentLogs'));
    }

    public function runBackup(Request $request)
    {
        try {
            // Run the safe backup script in the background
            $result = Process::run('/opt/safe-backup.sh');
            
            return response()->json([
                'success' => true,
                'message' => 'Backup process started successfully',
                'output' => $result->output()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to start backup process',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getStatus()
    {
        $status = $this->getBackupStatus();
        return response()->json($status);
    }

    public function listBackups()
    {
        $backups = $this->getBackupSummary();
        return response()->json($backups);
    }

    public function cleanBackups(Request $request)
    {
        try {
            $result = Process::run('/opt/backup-manager.sh clean');
            
            return response()->json([
                'success' => true,
                'message' => 'Backup cleanup completed',
                'output' => $result->output()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clean backups',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getLogs()
    {
        $logs = $this->getRecentLogs();
        return response()->json(['logs' => $logs]);
    }

    private function getBackupStatus()
    {
        $status = [
            'backup_script_exists' => file_exists('/opt/safe-backup.sh'),
            'backup_manager_exists' => file_exists('/opt/backup-manager.sh'),
            'restore_script_exists' => file_exists('/opt/restore-backup.sh'),
            'backup_directory_exists' => is_dir($this->backupBaseDir),
            'log_file_exists' => file_exists($this->logFile),
            'total_backup_size' => '0B',
            'backup_count' => 0,
            'disk_usage' => $this->getDiskUsage(),
            'cron_scheduled' => $this->isCronScheduled()
        ];

        if ($status['backup_directory_exists']) {
            $size = $this->getDirectorySize($this->backupBaseDir);
            $status['total_backup_size'] = $this->formatBytes($size);
            $status['backup_count'] = $this->countBackupFiles();
        }

        return $status;
    }

    private function getBackupSummary()
    {
        $summary = [];
        
        if (!is_dir($this->backupBaseDir)) {
            return $summary;
        }

        $sites = ['admin.middleworldfarms.org', 'middleworldfarms.org', 'farmos.middleworldfarms.org', 'databases'];
        
        foreach ($sites as $site) {
            $siteDir = $this->backupBaseDir . '/' . $site;
            if (is_dir($siteDir)) {
                $backups = glob($siteDir . '/*.{tar.gz,sql.gz}', GLOB_BRACE);
                usort($backups, function($a, $b) {
                    return filemtime($b) - filemtime($a);
                });

                $summary[$site] = [
                    'count' => count($backups),
                    'latest' => null,
                    'total_size' => 0,
                    'backups' => []
                ];

                foreach (array_slice($backups, 0, 5) as $backup) {
                    $fileInfo = [
                        'name' => basename($backup),
                        'size' => $this->formatBytes(filesize($backup)),
                        'date' => date('Y-m-d H:i:s', filemtime($backup)),
                        'age' => $this->timeAgo(filemtime($backup))
                    ];
                    
                    $summary[$site]['backups'][] = $fileInfo;
                    $summary[$site]['total_size'] += filesize($backup);
                    
                    if (!$summary[$site]['latest']) {
                        $summary[$site]['latest'] = $fileInfo;
                    }
                }

                $summary[$site]['total_size'] = $this->formatBytes($summary[$site]['total_size']);
            }
        }

        return $summary;
    }

    private function getRecentLogs()
    {
        if (!file_exists($this->logFile)) {
            return [];
        }

        $logs = file($this->logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return array_slice(array_reverse($logs), 0, 20);
    }

    private function getDiskUsage()
    {
        $disk = disk_total_space('/');
        $free = disk_free_space('/');
        $used = $disk - $free;

        return [
            'total' => $this->formatBytes($disk),
            'used' => $this->formatBytes($used),
            'free' => $this->formatBytes($free),
            'percentage' => round(($used / $disk) * 100, 1)
        ];
    }

    private function isCronScheduled()
    {
        try {
            $result = Process::run('crontab -l');
            return str_contains($result->output(), 'safe-backup.sh');
        } catch (\Exception $e) {
            return false;
        }
    }

    private function getDirectorySize($directory)
    {
        $size = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }
        return $size;
    }

    private function countBackupFiles()
    {
        $count = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($this->backupBaseDir));
        foreach ($iterator as $file) {
            if ($file->isFile() && (str_ends_with($file->getFilename(), '.tar.gz') || str_ends_with($file->getFilename(), '.sql.gz'))) {
                $count++;
            }
        }
        return $count;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function timeAgo($timestamp)
    {
        $difference = time() - $timestamp;
        
        if ($difference < 60) {
            return $difference . ' seconds ago';
        } elseif ($difference < 3600) {
            return floor($difference / 60) . ' minutes ago';
        } elseif ($difference < 86400) {
            return floor($difference / 3600) . ' hours ago';
        } else {
            return floor($difference / 86400) . ' days ago';
        }
    }
}
