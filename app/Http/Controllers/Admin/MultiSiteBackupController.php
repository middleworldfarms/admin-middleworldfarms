<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Carbon\Carbon;

class MultiSiteBackupController extends Controller
{
    private $backupPath = 'backups';

    // Define all your sites/systems
    private $sites = [
        'laravel_admin' => [
            'name' => 'Laravel Admin (Current)',
            'type' => 'laravel',
            'path' => '/opt/sites/admin.middleworldfarms.org',
            'database' => 'mysql',
            'connection' => 'mysql',
            'enabled' => true
        ],
        'wordpress_main' => [
            'name' => 'WordPress Main Site',
            'type' => 'wordpress',
            'path' => '/var/www/middleworldfarms.org',
            'database' => 'wordpress',
            'connection' => 'wordpress',
            'enabled' => true
        ],
        'farmos' => [
            'name' => 'farmOS System',
            'type' => 'drupal',
            'path' => '/var/www/farmos.middleworldfarms.org',
            'database' => 'farmos',
            'connection' => 'farmos',
            'enabled' => true
        ],
<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Carbon\Carbon;

class MultiSiteBackupController extends Controller
{
    private $backupPath = 'backups';

    // Define all your sites/systems
    private $sites = [
        'laravel_admin' => [
            'name' => 'Laravel Admin (Current)',
            'type' => 'laravel',
            'path' => '/opt/sites/admin.middleworldfarms.org',
            'database' => 'mysql',
            'connection' => 'mysql',
            'enabled' => true
        ],
        'wordpress_main' => [
            'name' => 'WordPress Main Site',
            'type' => 'wordpress',
            'path' => '/var/www/middleworldfarms.org',
            'database' => 'wordpress',
            'connection' => 'wordpress',
            'enabled' => true
        ],
        'farmos' => [
            'name' => 'farmOS System',
            'type' => 'drupal',
            'path' => '/var/www/farmos.middleworldfarms.org',
            'database' => 'farmos',
            'connection' => 'farmos',
            'enabled' => true
        ],
        'pos_system' => [
            'name' => 'Self-Serve POS (middleworld.farm)',
            'type' => 'custom',
            'remote_host' => 'middleworld.farm',
            'remote_path' => '/var/www/pos',
            'database' => 'pos_db',
            'connection' => 'pos_remote',
            'enabled' => true,
            'remote' => true
        ]
    ];

    public function __construct()
    {
        if (!Storage::disk('local')->exists($this->backupPath)) {
            Storage::disk('local')->makeDirectory($this->backupPath);
        }
    }

    /**
     * Display multi-site backup management page
     */
    public function index(Request $request)
    {
        try {
            $backups = $this->getBackupList();
            $sites = $this->sites;
            $systemStatus = $this->checkAllSystemsStatus();
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'backups' => $backups,
                    'sites' => $sites,
                    'system_status' => $systemStatus
                ]);
            }

            return view('admin.backups.multisite', compact('backups', 'sites', 'systemStatus'));
        } catch (\Exception $e) {
            Log::error('Multi-site backup index failed: ' . $e->getMessage());
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load backup dashboard'
                ], 500);
            }
            
            return view('admin.backups.multisite', ['backups' => [], 'sites' => []]);
        }
    }

    /**
     * Create comprehensive multi-site backup
     */
    public function createMultiSiteBackup(Request $request)
    {
        try {
            $selectedSites = $request->input('sites', array_keys($this->sites));
            $backupType = $request->input('type', 'full'); // full, files_only, db_only
            $backupName = $request->input('name', 'multisite_' . date('Y-m-d_H-i-s'));
            
            Log::info('Starting multi-site backup', [
                'sites' => $selectedSites,
                'type' => $backupType,
                'name' => $backupName
            ]);

            $results = [];
            $errors = [];

            foreach ($selectedSites as $siteKey) {
                if (!isset($this->sites[$siteKey]) || !$this->sites[$siteKey]['enabled']) {
                    continue;
                }

                $site = $this->sites[$siteKey];
                
                try {
                    $siteBackupResult = $this->backupSingleSite($site, $backupType, $backupName);
                    $results[$siteKey] = $siteBackupResult;
                    
                    Log::info("Backup completed for {$site['name']}", $siteBackupResult);
                } catch (\Exception $e) {
                    $error = "Failed to backup {$site['name']}: " . $e->getMessage();
                    $errors[$siteKey] = $error;
                    Log::error($error);
                }
            }

            // Create master archive containing all site backups
            $masterBackupFile = $this->createMasterBackupArchive($results, $backupName);

            return response()->json([
                'success' => true,
                'message' => 'Multi-site backup completed',
                'master_backup' => $masterBackupFile,
                'individual_results' => $results,
                'errors' => $errors,
                'sites_backed_up' => count($results),
                'sites_failed' => count($errors)
            ]);

        } catch (\Exception $e) {
            Log::error('Multi-site backup failed', ['error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'message' => 'Multi-site backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Backup a single site/system
     */
    private function backupSingleSite($site, $backupType, $backupName)
    {
        $timestamp = date('Y-m-d_H-i-s');
        $siteBackupName = "{$backupName}_{$site['type']}_{$timestamp}";

        switch ($site['type']) {
            case 'laravel':
                return $this->backupLaravelSite($site, $backupType, $siteBackupName);
            
            case 'wordpress':
                return $this->backupWordPressSite($site, $backupType, $siteBackupName);
            
            case 'drupal':
                return $this->backupDrupalSite($site, $backupType, $siteBackupName);
            
            case 'custom':
                return $this->backupRemoteSite($site, $backupType, $siteBackupName);
            
            default:
                throw new \Exception("Unknown site type: {$site['type']}");
        }
    }

    /**
     * Backup Laravel site using Spatie package
     */
    private function backupLaravelSite($site, $backupType, $backupName)
    {
        $options = ['--force' => true, '--filename' => $backupName];
        
        if ($backupType === 'db_only') {
            $options['--only-db'] = true;
        } elseif ($backupType === 'files_only') {
            $options['--only-files'] = true;
        }

        $exitCode = Artisan::call('backup:run', $options);
        $output = Artisan::output();

        if ($exitCode === 0) {
            // Find the backup file
            $backupFiles = glob(storage_path('app/backups/*.zip'));
            $latestBackup = null;
            $latestTime = 0;
            
            foreach ($backupFiles as $file) {
                if (filemtime($file) > $latestTime) {
                    $latestTime = filemtime($file);
                    $latestBackup = $file;
                }
            }
            
            if ($latestBackup) {
                return [
                    'status' => 'success',
                    'backup_file' => basename($latestBackup),
                    'site_name' => $site['name'],
                    'type' => $backupType,
                    'size' => filesize($latestBackup),
                    'path' => $latestBackup
                ];
            }
        }
        
        throw new \Exception("Laravel backup failed: $output");
    }

    /**
     * Backup WordPress site
     */
    private function backupWordPressSite($site, $backupType, $backupName)
    {
        $backupDir = storage_path('app/backups/temp/' . $backupName);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $result = [
            'status' => 'success',
            'site_name' => $site['name'],
            'type' => $backupType,
            'files' => [],
            'database' => null
        ];

        // Backup WordPress files
        if ($backupType !== 'db_only' && is_dir($site['path'])) {
            $filesArchive = $backupDir . '/wordpress_files.tar.gz';
            $command = "tar -czf {$filesArchive} -C " . dirname($site['path']) . " " . basename($site['path']);
            
            $process = Process::run($command);
            if ($process->successful()) {
                $result['files'][] = $filesArchive;
            } else {
                throw new \Exception("WordPress files backup failed: " . $process->errorOutput());
            }
        }

        // Backup WordPress database
        if ($backupType !== 'files_only') {
            $dbBackup = $this->backupDatabase($site['connection'], $backupDir . '/wordpress_database.sql');
            $result['database'] = $dbBackup;
        }

        // Create site-specific archive
        $siteArchive = storage_path('app/backups/' . $backupName . '_wordpress.zip');
        $this->createArchiveFromDirectory($backupDir, $siteArchive);
        
        // Cleanup temp directory
        $this->removeDirectory($backupDir);
        
        $result['backup_file'] = basename($siteArchive);
        $result['path'] = $siteArchive;
        $result['size'] = filesize($siteArchive);
        
        return $result;
    }

    /**
     * Backup Drupal/farmOS site
     */
    private function backupDrupalSite($site, $backupType, $backupName)
    {
        $backupDir = storage_path('app/backups/temp/' . $backupName);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $result = [
            'status' => 'success',
            'site_name' => $site['name'],
            'type' => $backupType,
            'files' => [],
            'database' => null
        ];

        // Backup Drupal files
        if ($backupType !== 'db_only' && is_dir($site['path'])) {
            $filesArchive = $backupDir . '/drupal_files.tar.gz';
            $command = "tar -czf {$filesArchive} -C " . dirname($site['path']) . " " . basename($site['path']);
            
            $process = Process::run($command);
            if ($process->successful()) {
                $result['files'][] = $filesArchive;
            } else {
                throw new \Exception("Drupal files backup failed: " . $process->errorOutput());
            }
        }

        // Backup Drupal database
        if ($backupType !== 'files_only') {
            $dbBackup = $this->backupDatabase($site['connection'], $backupDir . '/drupal_database.sql');
            $result['database'] = $dbBackup;
        }

        // Create site-specific archive
        $siteArchive = storage_path('app/backups/' . $backupName . '_drupal.zip');
        $this->createArchiveFromDirectory($backupDir, $siteArchive);
        
        // Cleanup temp directory
        $this->removeDirectory($backupDir);
        
        $result['backup_file'] = basename($siteArchive);
        $result['path'] = $siteArchive;
        $result['size'] = filesize($siteArchive);
        
        return $result;
    }

    /**
     * Backup remote site (POS system)
     */
    private function backupRemoteSite($site, $backupType, $backupName)
    {
        // Create placeholder for remote backup - would implement SSH/rsync here
        $result = [
            'status' => 'success',
            'site_name' => $site['name'],
            'type' => $backupType,
            'message' => 'Remote backup placeholder - implement SSH/rsync logic',
            'backup_file' => $backupName . '_remote.txt',
            'size' => 0
        ];

        // Create a placeholder file for now
        $placeholderPath = storage_path('app/backups/' . $backupName . '_remote.txt');
        file_put_contents($placeholderPath, "Remote backup for {$site['name']} would be implemented here.\n" .
                                           "Host: {$site['remote_host']}\n" .
                                           "Path: {$site['remote_path']}\n" .
                                           "Time: " . now()->toISOString());
        
        $result['path'] = $placeholderPath;
        $result['size'] = filesize($placeholderPath);

        return $result;
    }

    /**
     * Create database backup
     */
    private function backupDatabase($connectionName, $outputFile)
    {
        $connection = config("database.connections.{$connectionName}");
        
        if (!$connection) {
            throw new \Exception("Database connection '{$connectionName}' not found");
        }

        $command = sprintf(
            'mysqldump -h %s -u %s -p%s %s > %s',
            $connection['host'],
            $connection['username'],
            $connection['password'],
            $connection['database'],
            $outputFile
        );

        $process = Process::run($command);
        
        if ($process->successful()) {
            return $outputFile;
        } else {
            throw new \Exception("Database backup failed: " . $process->errorOutput());
        }
    }

    /**
     * Create master backup archive containing all individual site backups
     */
    private function createMasterBackupArchive($results, $backupName)
    {
        $masterArchive = storage_path('app/backups/' . $backupName . '_MASTER.zip');
        $zip = new \ZipArchive();
        
        if ($zip->open($masterArchive, \ZipArchive::CREATE) === TRUE) {
            // Add backup summary
            $summary = [
                'backup_name' => $backupName,
                'created_at' => now()->toISOString(),
                'total_sites' => count($results),
                'sites' => $results
            ];
            $zip->addFromString('backup_summary.json', json_encode($summary, JSON_PRETTY_PRINT));
            
            // Add each individual site backup
            foreach ($results as $siteKey => $result) {
                if (isset($result['path']) && file_exists($result['path'])) {
                    $zip->addFile($result['path'], $siteKey . '_' . basename($result['path']));
                }
            }
            
            $zip->close();
            return basename($masterArchive);
        } else {
            throw new \Exception('Failed to create master backup archive');
        }
    }

    /**
     * Check status of all systems
     */
    private function checkAllSystemsStatus()
    {
        $status = [];
        
        foreach ($this->sites as $key => $site) {
            $status[$key] = [
                'name' => $site['name'],
                'type' => $site['type'],
                'accessible' => $this->checkSiteAccessibility($site),
                'database_connection' => $this->checkDatabaseConnection($site),
                'last_backup' => $this->getLastBackupDate($key)
            ];
        }
        
        return $status;
    }

    /**
     * Check if site is accessible
     */
    private function checkSiteAccessibility($site)
    {
        if (isset($site['remote_host'])) {
            // Check remote site via HTTP
            try {
                $response = file_get_contents("http://{$site['remote_host']}", false, stream_context_create([
                    'http' => ['timeout' => 10]
                ]));
                return $response !== false;
            } catch (\Exception $e) {
                return false;
            }
        } else {
            // Check local site directory
            return isset($site['path']) && is_dir($site['path']);
        }
    }

    /**
     * Check database connection
     */
    private function checkDatabaseConnection($site)
    {
        if (!isset($site['connection'])) {
            return null;
        }

        try {
            $connection = \DB::connection($site['connection']);
            $connection->getPdo();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get last backup date for a site
     */
    private function getLastBackupDate($siteKey)
    {
        $backups = $this->getBackupList();
        
        foreach ($backups as $backup) {
            if (strpos($backup['filename'], $siteKey) !== false) {
                return $backup['created_at'];
            }
        }
        
        return null;
    }

    /**
     * Helper methods
     */
    private function createArchiveFromDirectory($sourceDir, $archivePath)
    {
        $zip = new \ZipArchive();
        if ($zip->open($archivePath, \ZipArchive::CREATE) === TRUE) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($sourceDir),
                \RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $zip->addFile($file->getPathname(), substr($file->getPathname(), strlen($sourceDir) + 1));
                }
            }
            $zip->close();
        }
    }

    private function removeDirectory($dir)
    {
        if (is_dir($dir)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
            rmdir($dir);
        }
    }

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
                    'type' => $this->determineBackupType($filename)
                ];
            }
            
            usort($backups, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });
        }
        
        return $backups;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function determineBackupType($filename)
    {
        if (strpos($filename, 'MASTER') !== false) {
            return 'Multi-Site Master';
        } elseif (strpos($filename, 'wordpress') !== false) {
            return 'WordPress';
        } elseif (strpos($filename, 'drupal') !== false) {
            return 'farmOS/Drupal';
        } elseif (strpos($filename, 'laravel') !== false) {
            return 'Laravel Admin';
        } else {
            return 'Single Site';
        }
    }
}
