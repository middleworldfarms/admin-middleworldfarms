<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use ZipArchive;

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
     * Display backup management page or return JSON for AJAX
     */
    public function index(Request $request)
    {
        try {
            $backups = $this->getBackupList();
            $lastAutoBackup = $this->getLastAutoBackupTime();
            $nextAutoBackup = $this->getNextAutoBackupTime();
            $backupSettings = $this->getBackupSettings();
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'backups' => $backups,
                    'settings' => $backupSettings
                ]);
            }

            return view('admin.backups.index', compact(
                'backups', 
                'lastAutoBackup', 
                'nextAutoBackup', 
                'backupSettings'
            ));
        } catch (\Exception $e) {
            Log::error('Backup index failed: ' . $e->getMessage());
            
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load backups: ' . $e->getMessage(),
                    'backups' => [],
                    'settings' => []
                ], 500);
            }
            
            return view('admin.backups.index', [
                'backups' => [],
                'lastAutoBackup' => null,
                'nextAutoBackup' => null,
                'backupSettings' => [],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create manual backup
     */
    public function create(Request $request)
    {
        $request->validate([
            'custom_name' => 'nullable|string|max:100|regex:/^[a-zA-Z0-9_-]+$/',
            'include_database' => 'boolean',
            'include_files' => 'boolean',
        ]);

        try {
            $customName = $request->get('custom_name');
            $name = $customName ?: 'manual';

            $backupFile = $this->createBackup($name, true, true, 'manual');

            return response()->json([
                'success' => true,
                'message' => 'Backup created successfully',
                'backup_file' => $backupFile
            ]);

        } catch (\Exception $e) {
            Log::error('Manual backup failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Backup failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update backup schedule settings
     */
    public function updateSchedule(Request $request)
    {
        $request->validate([
            'frequency' => 'required|in:disabled,daily,weekly,monthly',
            'time' => 'required|date_format:H:i',
            'retention' => 'required|integer|min:1|max:365',
        ]);

        try {
            // In a real implementation, you'd save these to database or config
            // For now, we'll store in a simple cache or config file
            $settings = [
                'frequency' => $request->get('frequency'),
                'time' => $request->get('time'),
                'retention' => $request->get('retention'),
                'updated_at' => Carbon::now()->toISOString(),
            ];

            // Store settings in a file for now (in production, use database)
            Storage::disk('local')->put('backup_settings.json', json_encode($settings));

            return response()->json([
                'success' => true,
                'message' => 'Backup schedule updated successfully',
                'settings' => $settings
            ]);

        } catch (\Exception $e) {
            Log::error('Backup schedule update failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update schedule: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get backup status and schedule information
     */
    public function status()
    {
        try {
            $settings = $this->getBackupSettings();
            $lastBackup = $this->getLastAutoBackupTime();
            $nextBackup = $this->getNextAutoBackupTime();

            return response()->json([
                'success' => true,
                'frequency' => $settings['auto_backup_frequency'] ?? 'disabled',
                'time' => $settings['auto_backup_time'] ?? '02:00',
                'retention' => $settings['auto_backup_retention_days'] ?? 30,
                'last_backup' => $lastBackup ? $lastBackup->toISOString() : null,
                'next_backup' => $nextBackup ? $nextBackup->toISOString() : null,
            ]);

        } catch (\Exception $e) {
            Log::error('Backup status check failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to get backup status: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rename backup
     */
    public function rename(Request $request, $filename)
    {
        $request->validate([
            'new_name' => 'required|string|max:100|regex:/^[^\/\\\\:*?"<>|]+$/',
        ]);

        try {
            // Find the file in any of the backup directories
            $backupDirs = [
                storage_path('app/' . $this->backupPath),
                storage_path('app/private/backups'),
            ];
            
            $oldFullPath = null;
            $sourceDir = null;
            
            foreach ($backupDirs as $dir) {
                $testPath = $dir . '/' . $filename;
                if (file_exists($testPath)) {
                    $oldFullPath = $testPath;
                    $sourceDir = $dir;
                    break;
                }
            }

            if (!$oldFullPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found'
                ], 404);
            }

            $pathInfo = pathinfo($filename);
            $newNameInput = $request->get('new_name');
            $ext = $pathInfo['extension'] ?? 'zip';
            // Remove .zip if user added it
            $newName = preg_replace('/\.zip$/i', '', $newNameInput);
            $newFilename = $newName . '.' . $ext;
            // Keep the file in the same directory where it was found
            $newFullPath = $sourceDir . '/' . $newFilename;
            // Check if target file already exists in any backup directory
            foreach ($backupDirs as $dir) {
                if (file_exists($dir . '/' . $newFilename)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'A backup with that name already exists'
                    ], 400);
                }
            }
            // Use direct filesystem rename within the same directory
            if (!rename($oldFullPath, $newFullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to rename backup file'
                ], 500);
            }
            return response()->json([
                'success' => true,
                'message' => 'Backup renamed successfully',
                'new_filename' => $newFilename
            ]);

        } catch (\Exception $e) {
            Log::error('Backup rename failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Rename failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete backup
     */
    public function delete($filename)
    {
        try {
            // Find the file in any of the backup directories
            $backupDirs = [
                storage_path('app/' . $this->backupPath),
                storage_path('app/private/backups'),
            ];
            
            $fullPath = null;
            
            foreach ($backupDirs as $dir) {
                $testPath = $dir . '/' . $filename;
                if (file_exists($testPath)) {
                    $fullPath = $testPath;
                    break;
                }
            }

            if (!$fullPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found'
                ], 404);
            }

            // Use direct filesystem delete
            if (!unlink($fullPath)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to delete backup file'
                ], 500);
            }

            return response()->json([
                'success' => true,
                'message' => 'Backup deleted successfully'
            ]);

        } catch (\Exception $e) {
            Log::error('Backup deletion failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Delete failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download backup
     */
    public function download($filename)
    {
        // Find the file in any of the backup directories
        $backupDirs = [
            storage_path('app/' . $this->backupPath),
            storage_path('app/private/backups'),
        ];
        
        $fullPath = null;
        
        foreach ($backupDirs as $dir) {
            $testPath = $dir . '/' . $filename;
            if (file_exists($testPath)) {
                $fullPath = $testPath;
                break;
            }
        }

        if (!$fullPath) {
            abort(404, 'Backup file not found');
        }

        // Return file download using direct path
        return response()->download($fullPath);
    }

    /**
     * Create backup (used by both manual and scheduled)
     */
    private function createBackup($name, $includeDatabase = true, $includeFiles = false, $type = 'manual')
    {
        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $backupName = "{$type}_{$name}_{$timestamp}";
        $zipFilename = $backupName . '.zip';
        $zipPath = storage_path('app/' . $this->backupPath . '/' . $zipFilename);

        // Ensure directory exists
        $backupDir = dirname($zipPath);
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        // Check if ZipArchive extension is available
        if (!extension_loaded('zip')) {
            throw new \Exception('PHP ZIP extension is not installed');
        }

        $zip = new ZipArchive();
        $result = $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        
        if ($result !== TRUE) {
            $errorMessage = $this->getZipErrorMessage($result);
            throw new \Exception('Cannot create backup ZIP file: ' . $errorMessage);
        }

        try {
            // Add backup info first
            $backupInfo = [
                'created_at' => Carbon::now()->toISOString(),
                'type' => $type,
                'name' => $name,
                'includes_database' => $includeDatabase,
                'includes_files' => $includeFiles,
                'laravel_version' => app()->version(),
                'php_version' => PHP_VERSION,
                'server' => $_SERVER['SERVER_NAME'] ?? 'unknown',
            ];
            
            if (!$zip->addFromString('backup_info.json', json_encode($backupInfo, JSON_PRETTY_PRINT))) {
                throw new \Exception('Failed to add backup info to ZIP');
            }

            // Add database dump if requested
            if ($includeDatabase) {
                try {
                    $dbDump = $this->createDatabaseDump();
                    if ($dbDump && !$zip->addFromString('database.sql', $dbDump)) {
                        throw new \Exception('Failed to add database dump to ZIP');
                    }
                } catch (\Exception $e) {
                    Log::warning('Database backup failed: ' . $e->getMessage());
                    // Add error info to backup
                    $zip->addFromString('database_error.txt', 'Database backup failed: ' . $e->getMessage());
                }
            }

            // Add essential Laravel files (config, routes, etc.) but skip large directories
            if ($includeFiles) {
                $this->addEssentialFilesToZip($zip);
            }

            if (!$zip->close()) {
                throw new \Exception('Failed to close ZIP file');
            }

            return $zipFilename;
            
        } catch (\Exception $e) {
            if ($zip && is_resource($zip)) {
                $zip->close();
            }
            // Clean up failed backup file
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            throw $e;
        }
    }

    /**
     * Get human-readable ZIP error message
     */
    private function getZipErrorMessage($code)
    {
        switch($code) {
            case ZipArchive::ER_OK: return 'No error';
            case ZipArchive::ER_MULTIDISK: return 'Multi-disk zip archives not supported';
            case ZipArchive::ER_RENAME: return 'Renaming temporary file failed';
            case ZipArchive::ER_CLOSE: return 'Closing zip archive failed';
            case ZipArchive::ER_SEEK: return 'Seek error';
            case ZipArchive::ER_READ: return 'Read error';
            case ZipArchive::ER_WRITE: return 'Write error';
            case ZipArchive::ER_CRC: return 'CRC error';
            case ZipArchive::ER_ZIPCLOSED: return 'Containing zip archive was closed';
            case ZipArchive::ER_NOENT: return 'No such file';
            case ZipArchive::ER_EXISTS: return 'File already exists';
            case ZipArchive::ER_OPEN: return 'Can not open file';
            case ZipArchive::ER_TMPOPEN: return 'Failure to create temporary file';
            case ZipArchive::ER_ZLIB: return 'Zlib error';
            case ZipArchive::ER_MEMORY: return 'Memory allocation failure';
            case ZipArchive::ER_CHANGED: return 'Entry has been changed';
            case ZipArchive::ER_COMPNOTSUPP: return 'Compression method not supported';
            case ZipArchive::ER_EOF: return 'Premature EOF';
            case ZipArchive::ER_INVAL: return 'Invalid argument';
            case ZipArchive::ER_NOZIP: return 'Not a zip archive';
            case ZipArchive::ER_INTERNAL: return 'Internal error';
            case ZipArchive::ER_INCONS: return 'Zip archive inconsistent';
            case ZipArchive::ER_REMOVE: return 'Can not remove file';
            case ZipArchive::ER_DELETED: return 'Entry has been deleted';
            default: return 'Unknown error code: ' . $code;
        }
    }

    /**
     * Create database dump
     */
    private function createDatabaseDump()
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        
        if ($config['driver'] === 'mysql') {
            // Check if mysqldump is available
            $mysqldumpPath = 'mysqldump';
            if (!shell_exec("which mysqldump 2>/dev/null")) {
                // Try common paths
                $paths = ['/usr/bin/mysqldump', '/usr/local/bin/mysqldump', '/opt/lampp/bin/mysqldump'];
                foreach ($paths as $path) {
                    if (file_exists($path)) {
                        $mysqldumpPath = $path;
                        break;
                    }
                }
            }

            $command = sprintf(
                '%s --single-transaction --routines --triggers --user=%s --password=%s --host=%s --port=%s %s 2>/dev/null',
                $mysqldumpPath,
                escapeshellarg($config['username']),
                escapeshellarg($config['password']),
                escapeshellarg($config['host']),
                escapeshellarg($config['port'] ?? 3306),
                escapeshellarg($config['database'])
            );
            
            $output = shell_exec($command);
            
            if ($output === null || empty(trim($output))) {
                // Try alternative method using Laravel's DB facade
                return $this->createLaravelDatabaseDump();
            }
            
            return $output;
        }
        
        if ($config['driver'] === 'sqlite') {
            $dbPath = $config['database'];
            if (file_exists($dbPath)) {
                return file_get_contents($dbPath);
            }
            throw new \Exception('SQLite database file not found: ' . $dbPath);
        }
        
        // Fallback: try Laravel-based dump
        return $this->createLaravelDatabaseDump();
    }

    /**
     * Create database dump using Laravel DB facade (fallback method)
     */
    private function createLaravelDatabaseDump()
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        
        if ($config['driver'] !== 'mysql') {
            throw new \Exception('Laravel-based dump only supports MySQL');
        }

        // Get all table names
        $tables = DB::select('SHOW TABLES');
        $databaseName = $config['database'];
        $tableKey = 'Tables_in_' . $databaseName;
        
        $dump = "-- Laravel Database Backup\n";
        $dump .= "-- Generated: " . Carbon::now()->toDateTimeString() . "\n\n";
        $dump .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

        foreach ($tables as $table) {
            $tableName = $table->$tableKey;
            
            // Get table structure
            $createTable = DB::select("SHOW CREATE TABLE `$tableName`");
            $dump .= "-- Table structure for `$tableName`\n";
            $dump .= "DROP TABLE IF EXISTS `$tableName`;\n";
            $dump .= $createTable[0]->{'Create Table'} . ";\n\n";
            
            // Get table data (limit to prevent memory issues)
            $rows = DB::table($tableName)->limit(1000)->get();
            if ($rows->count() > 0) {
                $dump .= "-- Data for table `$tableName`\n";
                $dump .= "INSERT INTO `$tableName` VALUES\n";
                
                $values = [];
                foreach ($rows as $row) {
                    $rowData = [];
                    foreach ($row as $value) {
                        $rowData[] = $value === null ? 'NULL' : "'" . addslashes($value) . "'";
                    }
                    $values[] = '(' . implode(',', $rowData) . ')';
                }
                
                $dump .= implode(",\n", $values) . ";\n\n";
            }
        }
        
        $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
        
        return $dump;
    }

    /**
     * Add files to ZIP archive
     */
    private function addFilesToZip($zip, $sourcePath, $zipPath = '')
    {
        $excludePaths = [
            'storage/app/backups',
            'storage/logs',
            'storage/framework/cache',
            'storage/framework/sessions',
            'storage/framework/views',
            'node_modules',
            '.git',
            'vendor',
            '.env'
        ];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            $filePath = $file->getRealPath();
            $relativePath = substr($filePath, strlen($sourcePath) + 1);
            
            // Skip excluded paths
            $skip = false;
            foreach ($excludePaths as $excludePath) {
                if (strpos($relativePath, $excludePath) === 0) {
                    $skip = true;
                    break;
                }
            }
            
            if ($skip) continue;

            $zipFilePath = $zipPath . $relativePath;

            if ($file->isDir()) {
                $zip->addEmptyDir($zipFilePath);
            } elseif ($file->isFile()) {
                $zip->addFile($filePath, $zipFilePath);
            }
        }
    }

    /**
     * Add essential Laravel files to ZIP (including all critical directories)
     */
    private function addEssentialFilesToZip($zip)
    {
        $essentialPaths = [
            'app/',
            'config/',
            'database/',
            'routes/',
            'resources/',
            'public/',
            'storage/app/',
            'storage/framework/views/',
            'vendor/',
            '.env',
            '.env.example',
            'composer.json',
            'composer.lock',
            'package.json',
            'artisan',
            'README.md',
        ];

        foreach ($essentialPaths as $path) {
            $fullPath = base_path($path);
            if (is_file($fullPath)) {
                $zip->addFile($fullPath, $path);
            } elseif (is_dir($fullPath)) {
                $this->addDirectoryToZip($zip, $fullPath, $path);
            }
        }
    }

    /**
     * Add directory to ZIP recursively with size limits
     */
    private function addDirectoryToZip($zip, $sourcePath, $zipPath)
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourcePath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        $fileCount = 0;
        $maxFiles = 15000; // Increased to handle full vendor directory (8,581 files)
        $maxFileSize = 100 * 1024 * 1024; // Increased to 100MB for very large files

        foreach ($iterator as $file) {
            if ($fileCount >= $maxFiles) {
                break;
            }

            $filePath = $file->getRealPath();
            $relativePath = $zipPath . substr($filePath, strlen($sourcePath));

            if ($file->isDir()) {
                $zip->addEmptyDir($relativePath);
            } elseif ($file->isFile() && $file->getSize() < $maxFileSize) {
                $zip->addFile($filePath, $relativePath);
                $fileCount++;
            }
        }
    }

    /**
     * Get list of backups
     */
    private function getBackupList()
    {
        $backups = [];
        
        // Check multiple backup locations
        $backupDirs = [
            storage_path('app/' . $this->backupPath),
            storage_path('app/private/backups'),
        ];

        foreach ($backupDirs as $backupDir) {
            if (!is_dir($backupDir)) {
                continue;
            }

            $files = scandir($backupDir);
            
            foreach ($files as $file) {
                if ($file === '.' || $file === '..' || pathinfo($file, PATHINFO_EXTENSION) !== 'zip') {
                    continue;
                }
                
                $fullPath = $backupDir . '/' . $file;
                if (!is_file($fullPath)) {
                    continue;
                }
                
                // Skip if we already have this file (avoid duplicates)
                $exists = false;
                foreach ($backups as $existingBackup) {
                    if ($existingBackup['filename'] === $file) {
                        $exists = true;
                        break;
                    }
                }
                if ($exists) continue;
                
                $size = filesize($fullPath);
                $lastModified = filemtime($fullPath);
                
                // Parse backup info from filename
                $nameWithoutExt = pathinfo($file, PATHINFO_FILENAME);
                $type = $this->determineBackupType($nameWithoutExt);
                
                // Generate display name
                $displayName = $this->generateDisplayName($nameWithoutExt, $file);
                
                $backups[] = [
                    'filename' => $file,
                    'name' => $displayName,
                    'size' => $this->formatBytes($size),
                    'size_bytes' => $size,
                    'created_at' => Carbon::createFromTimestamp($lastModified),
                    'type' => $type,
                    'can_rename' => true,
                    'full_path' => $fullPath, // Store full path for operations
                ];
            }
        }

        // Sort by creation date (newest first)
        usort($backups, function($a, $b) {
            return $b['created_at']->timestamp - $a['created_at']->timestamp;
        });

        return $backups;
    }

    /**
     * Generate display name from filename
     */
    private function generateDisplayName($filenameWithoutExt, $fullFilename = null)
    {
        // If the filename contains special characters like parentheses, it's likely manually renamed
        if (strpos($filenameWithoutExt, '(') !== false && strpos($filenameWithoutExt, ')') !== false) {
            // This is likely a manually renamed file, use the filename as-is (cleaned up)
            return $filenameWithoutExt;
        }
        
        $parts = explode('_', $filenameWithoutExt);
        
        if (count($parts) >= 3) {
            $type = ucfirst($parts[0]);
            $name = $parts[1];
            
            // Find date and time parts
            $datePart = '';
            $timePart = '';
            
            // Look for date pattern (YYYY-MM-DD)
            foreach ($parts as $part) {
                if (preg_match('/(\d{4}-\d{2}-\d{2})/', $part, $matches)) {
                    $datePart = $matches[1];
                }
            }
            
            // Look for time pattern (HH-MM-SS)
            foreach ($parts as $part) {
                if (preg_match('/(\d{2}-\d{2}-\d{2})/', $part, $matches)) {
                    $timePart = str_replace('-', ':', $matches[1]);
                }
            }
            
            if ($datePart && $timePart) {
                return $type . ' Backup - ' . ucfirst($name) . ' (' . $datePart . ' at ' . $timePart . ')';
            }
        }
        
        // Fallback to cleaned up filename
        return str_replace('_', ' ', ucfirst($filenameWithoutExt));
    }

    /**
     * Determine backup type from filename
     */
    private function determineBackupType($filenameWithoutExt)
    {
        $parts = explode('_', $filenameWithoutExt);
        
        if (count($parts) > 0) {
            $firstPart = strtolower($parts[0]);
            if (in_array($firstPart, ['auto', 'manual', 'uploaded'])) {
                return $firstPart;
            }
        }
        
        // If filename contains "manual" anywhere, assume it's manual
        if (stripos($filenameWithoutExt, 'manual') !== false) {
            return 'manual';
        }
        
        // Default to manual for custom named files
        return 'manual';
    }

    /**
     * Get backup settings from config or database
     */
    private function getBackupSettings()
    {
        return [
            'auto_backup_enabled' => config('backup.auto_backup_enabled', true),
            'auto_backup_frequency' => config('backup.auto_backup_frequency', 'daily'), // daily, weekly
            'auto_backup_time' => config('backup.auto_backup_time', '02:00'),
            'auto_backup_retention_days' => config('backup.auto_backup_retention_days', 30),
            'include_database' => config('backup.include_database', true),
            'include_files' => config('backup.include_files', false), // Files can be large
        ];
    }

    /**
     * Get last auto backup time
     */
    private function getLastAutoBackupTime()
    {
        $backups = $this->getBackupList();
        
        foreach ($backups as $backup) {
            if ($backup['type'] === 'auto') {
                return $backup['created_at'];
            }
        }
        
        return null;
    }

    /**
     * Get next scheduled auto backup time
     */
    private function getNextAutoBackupTime()
    {
        $settings = $this->getBackupSettings();
        
        if (!$settings['auto_backup_enabled']) {
            return null;
        }
        
        $time = $settings['auto_backup_time'];
        $frequency = $settings['auto_backup_frequency'];
        
        $next = Carbon::today()->setTimeFromTimeString($time);
        
        // If time has passed today, schedule for tomorrow/next period
        if ($next->isPast()) {
            if ($frequency === 'daily') {
                $next->addDay();
            } elseif ($frequency === 'weekly') {
                $next->addWeek();
            }
        }
        
        return $next;
    }

    /**
     * Format bytes to human readable
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
     * Upload backup file
     */
    public function upload(Request $request)
    {
        $request->validate([
            'backup_file' => 'required|file|mimes:zip|max:512000', // Max 500MB
        ]);

        try {
            $file = $request->file('backup_file');
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
            $filename = "uploaded_{$originalName}_{$timestamp}.zip";
            
            // Store the file
            $path = $file->storeAs($this->backupPath, $filename, 'local');
            
            // Validate backup structure
            $validation = $this->validateBackupFile($filename);
            
            return response()->json([
                'success' => true,
                'message' => 'Backup uploaded successfully',
                'filename' => $filename,
                'validation' => $validation
            ]);

        } catch (\Exception $e) {
            Log::error('Backup upload failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get backup info/preview
     */
    public function preview($filename)
    {
        try {
            // Find the file in any of the backup directories
            $backupDirs = [
                storage_path('app/' . $this->backupPath),
                storage_path('app/private/backups'),
            ];
            $fullPath = null;
            foreach ($backupDirs as $dir) {
                $testPath = $dir . '/' . $filename;
                if (file_exists($testPath)) {
                    $fullPath = $testPath;
                    break;
                }
            }
            if (!$fullPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found'
                ], 404);
            }
            $info = $this->extractBackupInfoFromPath($fullPath, $filename);
            return response()->json([
                'success' => true,
                'info' => $info
            ]);
        } catch (\Exception $e) {
            Log::error('Backup preview failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Preview failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore from backup
     */
    public function restore(Request $request, $filename)
    {
        $request->validate([
            'restore_database' => 'boolean',
            'restore_files' => 'boolean',
            'create_backup_before_restore' => 'boolean',
        ]);

        try {
            $restoreDatabase = $request->get('restore_database', false);
            $restoreFiles = $request->get('restore_files', false);
            $createBackupFirst = $request->get('create_backup_before_restore', true);
            // Find the file in any of the backup directories
            $backupDirs = [
                storage_path('app/' . $this->backupPath),
                storage_path('app/private/backups'),
            ];
            $fullPath = null;
            foreach ($backupDirs as $dir) {
                $testPath = $dir . '/' . $filename;
                if (file_exists($testPath)) {
                    $fullPath = $testPath;
                    break;
                }
            }
            if (!$fullPath) {
                return response()->json([
                    'success' => false,
                    'message' => 'Backup file not found'
                ], 404);
            }
            // Create backup before restore if requested
            $preRestoreBackup = null;
            if ($createBackupFirst) {
                $preRestoreBackup = $this->createBackup('pre_restore', true, true, 'auto');
                Log::info("Created pre-restore backup: {$preRestoreBackup}");
            }
            $result = $this->performRestoreFromPath($fullPath, $restoreDatabase, $restoreFiles);
            return response()->json([
                'success' => true,
                'message' => 'Restore completed successfully',
                'pre_restore_backup' => $preRestoreBackup,
                'restored_items' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Backup restore failed: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Restore failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate backup file structure
     */
    private function validateBackupFile($filename)
    {
        // Find the file in any of the backup directories
        $backupDirs = [
            storage_path('app/' . $this->backupPath),
            storage_path('app/private/backups'),
        ];
        $fullPath = null;
        foreach ($backupDirs as $dir) {
            $testPath = $dir . '/' . $filename;
            if (file_exists($testPath)) {
                $fullPath = $testPath;
                break;
            }
        }
        if (!$fullPath) {
            throw new \Exception('Cannot open backup file');
        }
        $zip = new ZipArchive();
        if ($zip->open($fullPath) !== TRUE) {
            throw new \Exception('Cannot open backup file');
        }
        $validation = [
            'has_backup_info' => false,
            'has_database' => false,
            'has_files' => false,
            'file_count' => $zip->numFiles,
            'files' => []
        ];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $validation['files'][] = $filename;
            if ($filename === 'backup_info.json') {
                $validation['has_backup_info'] = true;
            }
            if ($filename === 'database.sql') {
                $validation['has_database'] = true;
            }
            if (strpos($filename, 'app/') === 0 || strpos($filename, 'config/') === 0) {
                $validation['has_files'] = true;
            }
        }
        $zip->close();
        return $validation;
    }

    /**
     * Extract backup info from ZIP using full path
     */
    private function extractBackupInfoFromPath($fullPath, $filename)
    {
        $zip = new ZipArchive();
        if ($zip->open($fullPath) !== TRUE) {
            throw new \Exception('Cannot open backup file');
        }
        $info = [
            'filename' => $filename,
            'size' => filesize($fullPath),
            'size_formatted' => $this->formatBytes(filesize($fullPath)),
            'file_count' => $zip->numFiles,
            'created_at' => date('Y-m-d H:i:s', filemtime($fullPath)),
            'backup_info' => null,
            'contains' => []
        ];
        // Try to read backup_info.json
        $backupInfoContent = $zip->getFromName('backup_info.json');
        if ($backupInfoContent !== false) {
            $info['backup_info'] = json_decode($backupInfoContent, true);
        }
        // Check what the backup contains
        $contains = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filenameInZip = $zip->getNameIndex($i);
            if ($filenameInZip === 'database.sql') {
                $contains[] = 'Database dump';
            }
            if (strpos($filenameInZip, 'app/') === 0) {
                $contains[] = 'Application code';
            }
            if (strpos($filenameInZip, 'config/') === 0) {
                $contains[] = 'Configuration files';
            }
            if (strpos($filenameInZip, 'resources/') === 0) {
                $contains[] = 'Resources (views, assets)';
            }
        }
        $info['contains'] = array_unique($contains);
        $zip->close();
        return $info;
    }

    /**
     * Perform restore from full path
     */
    private function performRestoreFromPath($fullPath, $restoreDatabase = false, $restoreFiles = false)
    {
        $zip = new ZipArchive();
        if ($zip->open($fullPath) !== TRUE) {
            throw new \Exception('Cannot open backup file');
        }
        $restored = [];
        try {
            // Create temporary extraction directory
            $tempDir = storage_path('app/temp_restore_' . time());
            if (!mkdir($tempDir, 0755, true)) {
                throw new \Exception('Cannot create temporary directory');
            }
            // Extract ZIP to temp directory
            if (!$zip->extractTo($tempDir)) {
                throw new \Exception('Failed to extract backup');
            }
            $zip->close();
            // Restore database if requested and available
            if ($restoreDatabase && file_exists($tempDir . '/database.sql')) {
                $this->restoreDatabase($tempDir . '/database.sql');
                $restored[] = 'database';
            }
            // Restore files if requested
            if ($restoreFiles) {
                $this->restoreFiles($tempDir);
                $restored[] = 'files';
            }
            // Clean up temporary directory
            $this->removeDirectory($tempDir);
            return $restored;
        } catch (\Exception $e) {
            $zip->close();
            // Clean up on error
            if (isset($tempDir) && is_dir($tempDir)) {
                $this->removeDirectory($tempDir);
            }
            throw $e;
        }
    }

    /**
     * Extract backup info from ZIP
     */
    private function extractBackupInfo($filename)
    {
        $path = Storage::disk('local')->path($this->backupPath . '/' . $filename);
        $zip = new ZipArchive();
        
        if ($zip->open($path) !== TRUE) {
            throw new \Exception('Cannot open backup file');
        }
        
        $info = [
            'filename' => $filename,
            'size' => filesize($path),
            'size_formatted' => $this->formatBytes(filesize($path)),
            'file_count' => $zip->numFiles,
            'created_at' => date('Y-m-d H:i:s', filemtime($path)),
            'backup_info' => null,
            'contains' => []
        ];
        
        // Try to read backup_info.json
        $backupInfoContent = $zip->getFromName('backup_info.json');
        if ($backupInfoContent !== false) {
            $info['backup_info'] = json_decode($backupInfoContent, true);
        }
        
        // Check what the backup contains
        $contains = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            
            if ($filename === 'database.sql') {
                $contains[] = 'Database dump';
            }
            if (strpos($filename, 'app/') === 0) {
                $contains[] = 'Application code';
            }
            if (strpos($filename, 'config/') === 0) {
                $contains[] = 'Configuration files';
            }
            if (strpos($filename, 'resources/') === 0) {
                $contains[] = 'Resources (views, assets)';
            }
        }
        
        $info['contains'] = array_unique($contains);
        $zip->close();
        
        return $info;
    }

    /**
     * Perform the actual restore operation
     */
    private function performRestore($filename, $restoreDatabase = false, $restoreFiles = false)
    {
        $path = Storage::disk('local')->path($this->backupPath . '/' . $filename);
        $zip = new ZipArchive();
        
        if ($zip->open($path) !== TRUE) {
            throw new \Exception('Cannot open backup file');
        }
        
        $restored = [];
        
        try {
            // Create temporary extraction directory
            $tempDir = storage_path('app/temp_restore_' . time());
            if (!mkdir($tempDir, 0755, true)) {
                throw new \Exception('Cannot create temporary directory');
            }
            
            // Extract ZIP to temp directory
            if (!$zip->extractTo($tempDir)) {
                throw new \Exception('Failed to extract backup');
            }
            $zip->close();
            
            // Restore database if requested and available
            if ($restoreDatabase && file_exists($tempDir . '/database.sql')) {
                $this->restoreDatabase($tempDir . '/database.sql');
                $restored[] = 'database';
            }
            
            // Restore files if requested
            if ($restoreFiles) {
                $this->restoreFiles($tempDir);
                $restored[] = 'files';
            }
            
            // Clean up temporary directory
            $this->removeDirectory($tempDir);
            
            return $restored;
            
        } catch (\Exception $e) {
            $zip->close();
            // Clean up on error
            if (isset($tempDir) && is_dir($tempDir)) {
                $this->removeDirectory($tempDir);
            }
            throw $e;
        }
    }

    /**
     * Restore database from SQL file
     */
    private function restoreDatabase($sqlFilePath)
    {
        if (!file_exists($sqlFilePath)) {
            throw new \Exception('Database dump file not found');
        }
        
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        
        if ($config['driver'] === 'mysql') {
            $command = sprintf(
                'mysql --user=%s --password=%s --host=%s --port=%s %s < %s 2>&1',
                escapeshellarg($config['username']),
                escapeshellarg($config['password']),
                escapeshellarg($config['host']),
                escapeshellarg($config['port'] ?? 3306),
                escapeshellarg($config['database']),
                escapeshellarg($sqlFilePath)
            );
            
            $output = shell_exec($command);
            
            if ($output && strpos(strtolower($output), 'error') !== false) {
                throw new \Exception('Database restore failed: ' . $output);
            }
            
        } else {
            throw new \Exception('Database restore only supports MySQL currently');
        }
    }

    /**
     * Restore files from backup
     */
    private function restoreFiles($tempDir)
    {
        $basePath = base_path();
        $dirsToRestore = ['app', 'config', 'database/migrations', 'routes'];
        
        foreach ($dirsToRestore as $dir) {
            $sourceDir = $tempDir . '/' . $dir;
            $targetDir = $basePath . '/' . $dir;
            
            if (is_dir($sourceDir)) {
                // Create backup of current directory first
                if (is_dir($targetDir)) {
                    $backupDir = $targetDir . '_backup_' . time();
                    rename($targetDir, $backupDir);
                }
                
                // Copy restored files
                $this->copyDirectory($sourceDir, $targetDir);
            }
        }
    }

    /**
     * Copy directory recursively
     */
    private function copyDirectory($source, $destination)
    {
        if (!is_dir($destination)) {
            mkdir($destination, 0755, true);
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        
        foreach ($iterator as $file) {
            $sourcePath = $file->getRealPath();
            $relativePath = substr($sourcePath, strlen($source) + 1);
            $targetPath = $destination . DIRECTORY_SEPARATOR . $relativePath;
            
            if ($file->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                copy($file->getRealPath(), $targetPath);
            }
        }
    }

    /**
     * Remove directory recursively
     */
    private function removeDirectory($dir)
    {
        if (!is_dir($dir)) {
            return;
        }
        
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        
        foreach ($iterator as $file) {
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                unlink($file->getRealPath());
            }
        }
        
        rmdir($dir);
    }
}
