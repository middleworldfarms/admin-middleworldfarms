<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Exception;

class UnifiedBackupService
{
    protected $config;

    public function __construct()
    {
        $this->config = config('unified_backup');
    }

    /**
     * Get all configured sites
     */
    public function getSites()
    {
        return $this->config['sites'] ?? [];
    }

    /**
     * Get backups for all sites
     */
    public function getAllBackups()
    {
        $allBackups = [];

        foreach ($this->getSites() as $siteName => $siteConfig) {
            if (!$siteConfig['enabled']) {
                continue;
            }

            try {
                $backups = $this->getSiteBackups($siteName, $siteConfig);
                $allBackups[$siteName] = $backups;
            } catch (Exception $e) {
                Log::error("Failed to get backups for {$siteName}: " . $e->getMessage());
                $allBackups[$siteName] = [];
            }
        }

        return $allBackups;
    }

    /**
     * Get backups for a specific site
     */
    protected function getSiteBackups($siteName, $siteConfig)
    {
        switch ($siteConfig['type']) {
            case 'spatie':
                return $this->getSpatieBackups($siteName);
            case 'plesk':
                return $this->getPleskBackups($siteName, $siteConfig);
            case 'farmos':
                return $this->getFarmOsBackups($siteName, $siteConfig);
            case 'remote_api':
                return $this->getRemoteApiBackups($siteName, $siteConfig);
            default:
                return [];
        }
    }

    /**
     * Get Spatie backups for Laravel admin
     */
    protected function getSpatieBackups($siteName)
    {
        try {
            $backups = [];

            // Check multiple backup directories
            $backupDirs = [
                storage_path('app/backups'),
                storage_path('app/private/backups'),
                storage_path('app/private/Middle World Farms Admin'),
            ];

            foreach ($backupDirs as $backupDir) {
                if (!is_dir($backupDir)) {
                    continue;
                }

                $files = scandir($backupDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' &&
                        (pathinfo($file, PATHINFO_EXTENSION) === 'zip' ||
                         pathinfo($file, PATHINFO_EXTENSION) === 'json')) {

                        $filePath = $backupDir . '/' . $file;
                        $fileSize = filesize($filePath);
                        $fileTime = filemtime($filePath);

                        $backups[] = [
                            'filename' => $file,
                            'site' => $siteName,
                            'type' => 'spatie',
                            'created' => date('Y-m-d H:i:s', $fileTime),
                            'size' => $fileSize,
                            'size_formatted' => $this->formatBytes($fileSize),
                            'path' => $filePath,
                        ];
                    }
                }
            }

            // Sort by creation date (newest first)
            usort($backups, function($a, $b) {
                return strtotime($b['created']) - strtotime($a['created']);
            });

            return $backups;

        } catch (Exception $e) {
            Log::error("Failed to get Spatie backups: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get backups from remote API (placeholder for future)
     */
    protected function getRemoteApiBackups($siteName, $siteConfig)
    {
        // Placeholder for remote API integration
        // This will be implemented when we add backup APIs to remote sites
        return [];
    }

    /**
     * Get Plesk domain backups
     */
    protected function getPleskBackups($siteName, $siteConfig)
    {
        try {
            $backups = [];

            // Check for Plesk backup directory
            $pleskBackupDir = "/var/www/vhosts/{$siteName}/backups";
            if (!is_dir($pleskBackupDir)) {
                // Create backup directory if it doesn't exist
                mkdir($pleskBackupDir, 0755, true);
            }

            if (is_dir($pleskBackupDir)) {
                $files = scandir($pleskBackupDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' &&
                        (pathinfo($file, PATHINFO_EXTENSION) === 'zip' ||
                         pathinfo($file, PATHINFO_EXTENSION) === 'tar' ||
                         pathinfo($file, PATHINFO_EXTENSION) === 'gz')) {

                        $filePath = $pleskBackupDir . '/' . $file;
                        $fileSize = filesize($filePath);
                        $fileTime = filemtime($filePath);

                        $backups[] = [
                            'filename' => $file,
                            'site' => $siteName,
                            'type' => 'plesk',
                            'created' => date('Y-m-d H:i:s', $fileTime),
                            'size' => $fileSize,
                            'size_formatted' => $this->formatBytes($fileSize),
                            'path' => $filePath,
                        ];
                    }
                }
            }

            // Sort by creation date (newest first)
            usort($backups, function($a, $b) {
                return strtotime($b['created']) - strtotime($a['created']);
            });

            return $backups;

        } catch (Exception $e) {
            Log::error("Failed to get Plesk backups for {$siteName}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get FarmOS backups (custom implementation)
     */
    protected function getFarmOsBackups($siteName, $siteConfig)
    {
        try {
            $backups = [];

            // Use custom source path for FarmOS
            $sourcePath = $siteConfig['source_path'] ?? '/var/www/vhosts/middleworldfarms.org/subdomains/farmos/web';
            $backupDir = "/var/www/vhosts/{$siteName}/backups";

            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            if (is_dir($backupDir)) {
                $files = scandir($backupDir);
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..' &&
                        (pathinfo($file, PATHINFO_EXTENSION) === 'zip' ||
                         pathinfo($file, PATHINFO_EXTENSION) === 'tar' ||
                         pathinfo($file, PATHINFO_EXTENSION) === 'gz')) {

                        $filePath = $backupDir . '/' . $file;
                        $fileSize = filesize($filePath);
                        $fileTime = filemtime($filePath);

                        $backups[] = [
                            'filename' => $file,
                            'site' => $siteName,
                            'type' => 'farmos',
                            'created' => date('Y-m-d H:i:s', $fileTime),
                            'size' => $fileSize,
                            'size_formatted' => $this->formatBytes($fileSize),
                            'path' => $filePath,
                        ];
                    }
                }
            }

            // Sort by creation date (newest first)
            usort($backups, function($a, $b) {
                return strtotime($b['created']) - strtotime($a['created']);
            });

            return $backups;

        } catch (Exception $e) {
            Log::error("Failed to get FarmOS backups for {$siteName}: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Create backup for a specific site
     */
    public function createBackup($siteName)
    {
        $sites = $this->getSites();

        if (!isset($sites[$siteName])) {
            throw new Exception("Site {$siteName} not configured");
        }

        $siteConfig = $sites[$siteName];

        switch ($siteConfig['type']) {
            case 'spatie':
                return $this->createSpatieBackup();
            case 'plesk':
                return $this->createPleskBackup($siteName, $siteConfig);
            case 'farmos':
                return $this->createFarmOsBackup($siteName, $siteConfig);
            case 'remote_api':
                return $this->createRemoteApiBackup($siteName, $siteConfig);
            default:
                throw new Exception("Unsupported backup type: {$siteConfig['type']}");
        }
    }

    /**
     * Create Spatie backup
     */
    protected function createSpatieBackup()
    {
        try {
            // Create a full backup (files + database) - single comprehensive backup
            $output = shell_exec('cd ' . base_path() . ' && php artisan backup:run --disable-notifications 2>&1');
            Log::info("Spatie backup output: " . $output);

            return ['success' => true, 'message' => 'Spatie backup completed successfully'];
        } catch (Exception $e) {
            Log::error("Spatie backup failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create Plesk domain backup
     */
    protected function createPleskBackup($siteName, $siteConfig)
    {
        try {
            $sitePath = "/var/www/vhosts/{$siteName}";
            $httpdocsPath = "/var/www/vhosts/{$siteName}/httpdocs";
            $backupDir = "/var/www/vhosts/{$siteName}/backups";

            // Ensure backup directory exists
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Check what content directories exist
            $contentDirs = [];
            if (is_dir($httpdocsPath)) {
                $contentDirs[] = 'httpdocs';
            }

            // Look for other common web directories
            $possibleDirs = ['public_html', 'www', 'web', 'html'];
            foreach ($possibleDirs as $dir) {
                if (is_dir("/var/www/vhosts/{$siteName}/{$dir}")) {
                    $contentDirs[] = $dir;
                }
            }

            // If no content directories found, backup the whole site (excluding backups to avoid recursion)
            if (empty($contentDirs)) {
                Log::warning("No standard web directories found for {$siteName}, backing up entire site");
                $contentDirs = ['.'];
            }

            // Create timestamp for backup filename
            $timestamp = date('Y-m-d-H-i-s');
            $backupFilename = "{$siteName}_backup_{$timestamp}.tar.gz";
            $backupPath = $backupDir . '/' . $backupFilename;

            // Create tar.gz backup
            $excludeBackups = "--exclude='backups' --exclude='*.tar.gz' --exclude='*.zip' --exclude='*.log' --exclude='cache' --exclude='.git'";
            $dirsToBackup = implode(' ', array_map(function($dir) use ($siteName) {
                return "{$siteName}/{$dir}";
            }, $contentDirs));

            $command = "cd /var/www/vhosts && tar {$excludeBackups} -czf {$backupPath} {$dirsToBackup} 2>&1";
            $output = shell_exec($command);

            // Check if backup was created and is reasonably sized
            if (file_exists($backupPath)) {
                $fileSize = filesize($backupPath);

                if ($fileSize > 1000) { // More than 1KB
                    Log::info("Plesk backup created successfully: {$backupPath} ({$fileSize} bytes)");
                    return ['success' => true, 'message' => "Plesk backup created: {$backupFilename} (" . $this->formatBytes($fileSize) . ")"];
                } else {
                    Log::warning("Plesk backup too small: {$backupPath} ({$fileSize} bytes)");
                    unlink($backupPath); // Remove the tiny file
                    return ['success' => false, 'message' => 'Backup created but too small - site may be empty'];
                }
            } else {
                Log::error("Plesk backup creation failed: {$output}");
                return ['success' => false, 'message' => 'Backup creation failed: ' . $output];
            }

        } catch (Exception $e) {
            Log::error("Plesk backup failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create FarmOS backup (custom implementation)
     */
    protected function createFarmOsBackup($siteName, $siteConfig)
    {
        try {
            // Use the configured source path for FarmOS
            $sourcePath = $siteConfig['source_path'] ?? '/var/www/vhosts/middleworldfarms.org/subdomains/farmos';
            $backupDir = "/var/www/vhosts/{$siteName}/backups";

            // Ensure backup directory exists
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Ensure source path exists
            if (!is_dir($sourcePath)) {
                return ['success' => false, 'message' => "FarmOS source directory not found: {$sourcePath}"];
            }

            // Create timestamp for backup filename
            $timestamp = date('Y-m-d-H-i-s');
            $backupFilename = "{$siteName}_backup_{$timestamp}.tar.gz";
            $backupPath = $backupDir . '/' . $backupFilename;

            // Create temporary directory for staging files
            $tempDir = "/tmp/farmos_backup_{$timestamp}";
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Step 1: Copy FarmOS files to temp directory using tar
            $excludePatterns = "--exclude='*.log' --exclude='cache' --exclude='.git' --exclude='tmp' --exclude='temp' --exclude='node_modules'";
            $sourceDirName = basename($sourcePath);
            $sourceParentDir = dirname($sourcePath);

            $copyCommand = "cd '{$sourceParentDir}' && tar {$excludePatterns} -cf - '{$sourceDirName}' | (cd '{$tempDir}' && tar -xf -)";
            Log::info("FarmOS copy command: {$copyCommand}");
            shell_exec($copyCommand);

            // Check temp directory contents before backup
            $tempContents = shell_exec("ls -la '{$tempDir}'");
            Log::info("Temp directory contents: {$tempContents}");
            
            $farmosContents = shell_exec("ls -la '{$tempDir}/farmos' 2>/dev/null | head -10");
            Log::info("FarmOS directory contents: {$farmosContents}");
            
            $dbSize = file_exists("{$tempDir}/farmos_database.sql") ? filesize("{$tempDir}/farmos_database.sql") : 0;
            Log::info("Database file size: {$dbSize} bytes");

            // Step 2: Create database backup
            $dbConfig = [
                'database' => 'farmos_db',
                'username' => 'martin_farmos',
                'password' => 'b6X5c3_8q',
                'host' => 'localhost',
                'port' => '3306',
            ];

            $dbBackupPath = "{$tempDir}/farmos_database.sql";
            $dbCommand = "mysqldump --user='{$dbConfig['username']}' --password='{$dbConfig['password']}' --host='{$dbConfig['host']}' --port='{$dbConfig['port']}' '{$dbConfig['database']}' > '{$dbBackupPath}' 2>&1";
            Log::info("FarmOS DB command: {$dbCommand}");
            $dbOutput = shell_exec($dbCommand);
            Log::info("FarmOS DB output: '{$dbOutput}'");
            
            if (file_exists($dbBackupPath)) {
                $dbSize = filesize($dbBackupPath);
                Log::info("Database backup created: {$dbSize} bytes");
            } else {
                Log::error("Database backup failed: {$dbOutput}");
            }

            // Verify both files exist before creating archive
            $tempContentsAfterDB = shell_exec("ls -la '{$tempDir}'");
            Log::info("Temp directory contents after DB backup: {$tempContentsAfterDB}");
            
            $dbFileExists = file_exists("{$tempDir}/farmos_database.sql");
            $farmosDirExists = is_dir("{$tempDir}/farmos");
            Log::info("Before archive - DB file exists: {$dbFileExists}, FarmOS dir exists: {$farmosDirExists}");

            // Step 3: Create compressed backup using PHP ZipArchive
            $tempBackupPath = "{$tempDir}/{$backupFilename}";
            
            Log::info("Creating backup using PHP ZipArchive: {$tempBackupPath}");
            
            $zip = new \ZipArchive();
            if ($zip->open($tempBackupPath, \ZipArchive::CREATE) === TRUE) {
                // Add FarmOS directory
                $this->addDirectoryToZip($zip, "{$tempDir}/farmos", 'farmos');
                
                // Add database file
                if (file_exists("{$tempDir}/farmos_database.sql")) {
                    $zip->addFile("{$tempDir}/farmos_database.sql", 'farmos_database.sql');
                    Log::info("Added database file to zip");
                }
                
                $zip->close();
                Log::info("Zip archive created successfully");
            } else {
                Log::error("Failed to create zip archive");
                return ['success' => false, 'message' => 'Failed to create backup archive'];
            }
            
            // Check zip file size and move to final location
            if (file_exists($tempBackupPath)) {
                $zipSize = filesize($tempBackupPath);
                Log::info("Zip backup file size: {$zipSize} bytes");
                
                // Move to final location
                $moveCommand = "mv '{$tempBackupPath}' '{$backupPath}' 2>&1";
                $moveOutput = shell_exec($moveCommand);
                Log::info("FarmOS move output: {$moveOutput}");
            } else {
                Log::error("Zip file was not created");
                return ['success' => false, 'message' => 'Backup archive creation failed'];
            }
            
            // Clean up temp directory
            shell_exec("rm -rf '{$tempDir}'");

            // Check if backup was created and is reasonably sized
            if (file_exists($backupPath)) {
                $fileSize = filesize($backupPath);

                if ($fileSize > 50000000) { // More than 50MB (reasonable for compressed FarmOS installation with DB)
                    Log::info("FarmOS backup created successfully: {$backupPath} ({$fileSize} bytes)");
                    return ['success' => true, 'message' => "FarmOS backup created: {$backupFilename} (" . $this->formatBytes($fileSize) . ")"];
                } else {
                    Log::warning("FarmOS backup too small: {$backupPath} ({$fileSize} bytes)");
                    unlink($backupPath); // Remove the small file
                    return ['success' => false, 'message' => 'Backup created but too small - FarmOS may be missing database or files'];
                }
            } else {
                Log::error("FarmOS backup creation failed: {$tarOutput}");
                return ['success' => false, 'message' => 'Backup creation failed: ' . $tarOutput];
            }

        } catch (Exception $e) {
            Log::error("FarmOS backup failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Rename a backup file
     */
    public function renameBackup($siteName, $currentFilename, $newFilename)
    {
        try {
            $siteConfig = $this->config['sites'][$siteName] ?? null;
            if (!$siteConfig) {
                return ['success' => false, 'message' => 'Site configuration not found'];
            }

            Log::info("Renaming backup for site: {$siteName}, from: {$currentFilename} to: {$newFilename}");

            $currentPath = $this->findBackupFile($siteName, $siteConfig, $currentFilename);
            if (!$currentPath) {
                return ['success' => false, 'message' => 'Current backup file not found in any backup directory'];
            }

            $backupDir = dirname($currentPath);
            $newPath = $backupDir . '/' . $newFilename;

            if (file_exists($newPath)) {
                return ['success' => false, 'message' => 'A backup with the new name already exists'];
            }

            // Validate new filename
            if (!preg_match('/^[a-zA-Z0-9._-]+\.(zip|tar\.gz|tar|json|sql)$/i', $newFilename)) {
                return ['success' => false, 'message' => 'Invalid filename. Only letters, numbers, dots, underscores, and hyphens are allowed.'];
            }

            if (rename($currentPath, $newPath)) {
                Log::info("Backup renamed successfully: {$currentFilename} -> {$newFilename} in {$backupDir}");
                return ['success' => true, 'message' => 'Backup renamed successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to rename backup file'];
            }

        } catch (Exception $e) {
            Log::error("Backup rename failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Delete a backup file
     */
    public function deleteBackup($siteName, $filename)
    {
        try {
            $siteConfig = $this->config['sites'][$siteName] ?? null;
            if (!$siteConfig) {
                return ['success' => false, 'message' => 'Site configuration not found'];
            }

            Log::info("Deleting backup for site: {$siteName}, file: {$filename}");

            $filePath = $this->findBackupFile($siteName, $siteConfig, $filename);
            if (!$filePath) {
                return ['success' => false, 'message' => 'Backup file not found in any backup directory'];
            }

            $fileSize = filesize($filePath);
            
            if (unlink($filePath)) {
                Log::info("Backup deleted successfully: {$filename} ({$fileSize} bytes) from " . dirname($filePath));
                return ['success' => true, 'message' => 'Backup deleted successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to delete backup file'];
            }

        } catch (Exception $e) {
            Log::error("Backup delete failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get backup directory for a site
     */
    protected function getBackupDirectory($siteName, $siteConfig)
    {
        switch ($siteConfig['type']) {
            case 'spatie':
                // Try multiple possible Spatie backup directories
                $possibleDirs = [
                    storage_path('app/backups'),
                    storage_path('app/private/backups'),
                    storage_path('app/private/Middle World Farms Admin'),
                ];
                foreach ($possibleDirs as $dir) {
                    if (is_dir($dir)) {
                        return $dir;
                    }
                }
                return storage_path('app/backups'); // Default fallback
            case 'plesk':
            case 'farmos':
            default:
                return "/var/www/vhosts/{$siteName}/backups";
        }
    }

    /**
     * Find backup file path across all possible directories
     */
    protected function findBackupFile($siteName, $siteConfig, $filename)
    {
        $possibleDirs = [];
        
        switch ($siteConfig['type']) {
            case 'spatie':
                $possibleDirs = [
                    storage_path('app/backups'),
                    storage_path('app/private/backups'),
                    storage_path('app/private/Middle World Farms Admin'),
                ];
                break;
            case 'plesk':
            case 'farmos':
            default:
                $possibleDirs = ["/var/www/vhosts/{$siteName}/backups"];
                break;
        }
        
        foreach ($possibleDirs as $dir) {
            $filePath = $dir . '/' . $filename;
            if (file_exists($filePath)) {
                return $filePath;
            }
        }
        
        return null; // File not found in any directory
    }

    /**
     * Restore backup for any site type
     */
    public function restoreBackup($siteName, $backupFilename, $restoreType = 'full')
    {
        $siteConfig = $this->config['sites'][$siteName] ?? null;
        if (!$siteConfig) {
            return ['success' => false, 'message' => 'Site configuration not found'];
        }

        Log::info("Starting restore for site: {$siteName}, type: {$siteConfig['type']}, restore type: {$restoreType}");

        switch ($siteConfig['type']) {
            case 'farmos':
                return $this->restoreFarmOsBackup($siteName, $backupFilename, $restoreType);
            case 'spatie':
                return $this->restoreSpatieBackup($siteName, $backupFilename);
            case 'plesk':
                return $this->restorePleskBackup($siteName, $backupFilename);
            default:
                return ['success' => false, 'message' => 'Restore not supported for site type: ' . $siteConfig['type']];
        }
    }

    /**
     * Restore Spatie backup
     */
    protected function restoreSpatieBackup($siteName, $backupFilename)
    {
        try {
            $backupDir = storage_path('app/backups');
            $backupPath = $backupDir . '/' . $backupFilename;

            if (!file_exists($backupPath)) {
                // Try alternative backup directories
                $altDirs = [
                    storage_path('app/private/backups'),
                    storage_path('app/private/Middle World Farms Admin'),
                ];
                
                $backupPath = null;
                foreach ($altDirs as $dir) {
                    $altPath = $dir . '/' . $backupFilename;
                    if (file_exists($altPath)) {
                        $backupPath = $altPath;
                        break;
                    }
                }
                
                if (!$backupPath) {
                    return ['success' => false, 'message' => 'Backup file not found in any backup directory'];
                }
            }

            Log::info("Starting Spatie backup restore: {$backupPath}");

            // For Spatie backups, use the Laravel backup restore command
            $command = "cd /opt/sites/admin.middleworldfarms.org && php artisan backup:restore {$backupFilename} --yes 2>&1";
            $output = shell_exec($command);

            if (strpos($output, 'successfully') !== false || strpos($output, 'completed') !== false) {
                Log::info("Spatie backup restored successfully: {$backupFilename}");
                return ['success' => true, 'message' => 'Laravel application restored successfully'];
            } else {
                Log::error("Spatie restore failed: {$output}");
                return ['success' => false, 'message' => 'Laravel restore failed: ' . substr($output, 0, 200)];
            }

        } catch (Exception $e) {
            Log::error("Spatie restore failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Restore Plesk backup
     */
    protected function restorePleskBackup($siteName, $backupFilename)
    {
        try {
            $backupDir = "/var/www/vhosts/{$siteName}/backups";
            $backupPath = $backupDir . '/' . $backupFilename;

            if (!file_exists($backupPath)) {
                return ['success' => false, 'message' => 'Backup file not found'];
            }

            Log::info("Starting Plesk backup restore: {$backupPath}");

            // For Plesk backups, we'll use a simple file extraction approach
            // In a production environment, you'd want to use Plesk's API or CLI tools
            
            $tempDir = "/tmp/plesk_restore_{$siteName}_" . time();
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Extract the backup
            if (pathinfo($backupFilename, PATHINFO_EXTENSION) === 'tar') {
                $extractCommand = "tar -xzf '{$backupPath}' -C '{$tempDir}' 2>&1";
            } else {
                $extractCommand = "unzip -q '{$backupPath}' -d '{$tempDir}' 2>&1";
            }
            
            $extractOutput = shell_exec($extractCommand);
            
            if (!is_dir($tempDir)) {
                shell_exec("rm -rf '{$tempDir}'");
                return ['success' => false, 'message' => 'Failed to extract backup archive'];
            }

            // For Plesk sites, copy files to the web root
            $webRoot = "/var/www/vhosts/{$siteName}/httpdocs";
            if (is_dir($webRoot)) {
                // Create backup of current site
                $currentBackup = "/tmp/plesk_pre_restore_{$siteName}_" . time();
                shell_exec("cp -r '{$webRoot}' '{$currentBackup}'");
                
                // Clear current site
                shell_exec("rm -rf '{$webRoot}'/*");
                
                // Copy restored files
                shell_exec("cp -r '{$tempDir}'/* '{$webRoot}/'");
                
                // Set proper permissions
                shell_exec("chown -R www-data:www-data '{$webRoot}'");
                shell_exec("chmod -R 755 '{$webRoot}'");
                
                // Clean up
                shell_exec("rm -rf '{$tempDir}' '{$currentBackup}'");
                
                Log::info("Plesk website restored successfully: {$siteName}");
                return ['success' => true, 'message' => 'Plesk website restored successfully'];
            } else {
                shell_exec("rm -rf '{$tempDir}'");
                return ['success' => false, 'message' => 'Website directory not found'];
            }

        } catch (Exception $e) {
            Log::error("Plesk restore failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Restore FarmOS backup
     */
    public function restoreFarmOsBackup($siteName, $backupFilename, $restoreType = 'both')
    {
        try {
            $siteConfig = $this->config['sites'][$siteName] ?? null;
            if (!$siteConfig || $siteConfig['type'] !== 'farmos') {
                return ['success' => false, 'message' => 'Invalid FarmOS site configuration'];
            }

            // Handle "full" as "both" for consistency
            if ($restoreType === 'full') {
                $restoreType = 'both';
            }

            $backupDir = "/var/www/vhosts/{$siteName}/backups";
            $backupPath = $backupDir . '/' . $backupFilename;

            if (!file_exists($backupPath)) {
                return ['success' => false, 'message' => 'Backup file not found'];
            }

            // Create temporary directory for extraction
            $tempDir = "/tmp/farmos_restore_{$siteName}_" . time();
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            $results = [];

            // Extract backup to temp directory
            $extractCommand = "unzip -q '{$backupPath}' -d '{$tempDir}' 2>&1";
            $extractOutput = shell_exec($extractCommand);
            
            if (!is_dir("{$tempDir}/farmos")) {
                shell_exec("rm -rf '{$tempDir}'");
                return ['success' => false, 'message' => 'Invalid backup archive - missing FarmOS directory'];
            }

            // Restore based on type
            if ($restoreType === 'files' || $restoreType === 'both') {
                $results['files'] = $this->restoreFarmOsFiles($siteName, $siteConfig, $tempDir);
            }

            if ($restoreType === 'database' || $restoreType === 'both') {
                $results['database'] = $this->restoreFarmOsDatabase($siteName, $tempDir);
            }

            // Clean up temp directory
            shell_exec("rm -rf '{$tempDir}'");

            $success = !in_array(false, array_column($results, 'success'));
            $messages = array_column($results, 'message');
            
            return [
                'success' => $success,
                'message' => implode('; ', $messages),
                'results' => $results
            ];

        } catch (Exception $e) {
            Log::error("FarmOS restore failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Restore FarmOS files
     */
    protected function restoreFarmOsFiles($siteName, $siteConfig, $tempDir)
    {
        try {
            $sourcePath = $siteConfig['source_path'] ?? '/var/www/vhosts/middleworldfarms.org/subdomains/farmos';
            
            // Create backup of current files before restore
            $currentBackup = "/tmp/farmos_pre_restore_" . date('Y-m-d-H-i-s');
            if (is_dir($sourcePath)) {
                shell_exec("cp -r '{$sourcePath}' '{$currentBackup}'");
            }

            // Remove current FarmOS installation
            shell_exec("rm -rf '{$sourcePath}'");
            
            // Restore from backup
            $parentDir = dirname($sourcePath);
            shell_exec("mkdir -p '{$parentDir}'");
            shell_exec("cp -r '{$tempDir}/farmos' '{$sourcePath}'");
            
            // Set proper permissions
            shell_exec("chown -R www-data:www-data '{$sourcePath}'");
            shell_exec("chmod -R 755 '{$sourcePath}'");
            
            // Clean up pre-restore backup after successful restore
            if (is_dir($currentBackup)) {
                shell_exec("rm -rf '{$currentBackup}'");
            }

            Log::info("FarmOS files restored successfully to: {$sourcePath}");
            return ['success' => true, 'message' => 'FarmOS files restored successfully'];

        } catch (Exception $e) {
            Log::error("FarmOS files restore failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Files restore failed: ' . $e->getMessage()];
        }
    }

    /**
     * Restore FarmOS database
     */
    protected function restoreFarmOsDatabase($siteName, $tempDir)
    {
        try {
            $dbConfig = [
                'database' => 'farmos_db',
                'username' => 'martin_farmos',
                'password' => 'b6X5c3_8q',
                'host' => 'localhost',
                'port' => '3306',
            ];

            $dbBackupPath = "{$tempDir}/farmos_database.sql";
            
            if (!file_exists($dbBackupPath)) {
                return ['success' => false, 'message' => 'Database backup file not found in archive'];
            }

            // Create database backup before restore
            $preRestoreBackup = "/tmp/farmos_db_pre_restore_" . date('Y-m-d-H-i-s') . '.sql';
            $backupCommand = "mysqldump --user='{$dbConfig['username']}' --password='{$dbConfig['password']}' --host='{$dbConfig['host']}' --port='{$dbConfig['port']}' '{$dbConfig['database']}' > '{$preRestoreBackup}' 2>&1";
            shell_exec($backupCommand);

            // Drop and recreate database
            $dropCommand = "mysql --user='{$dbConfig['username']}' --password='{$dbConfig['password']}' --host='{$dbConfig['host']}' --port='{$dbConfig['port']}' -e 'DROP DATABASE IF EXISTS `{$dbConfig['database']}`; CREATE DATABASE `{$dbConfig['database']}`;' 2>&1";
            $dropOutput = shell_exec($dropCommand);
            
            if (strpos($dropOutput, 'ERROR') !== false) {
                return ['success' => false, 'message' => 'Failed to recreate database: ' . $dropOutput];
            }

            // Restore database from backup
            $restoreCommand = "mysql --user='{$dbConfig['username']}' --password='{$dbConfig['password']}' --host='{$dbConfig['host']}' --port='{$dbConfig['port']}' '{$dbConfig['database']}' < '{$dbBackupPath}' 2>&1";
            $restoreOutput = shell_exec($restoreCommand);
            
            if (strpos($restoreOutput, 'ERROR') !== false) {
                // Attempt to restore from pre-restore backup
                if (file_exists($preRestoreBackup)) {
                    shell_exec("mysql --user='{$dbConfig['username']}' --password='{$dbConfig['password']}' --host='{$dbConfig['host']}' --port='{$dbConfig['port']}' '{$dbConfig['database']}' < '{$preRestoreBackup}' 2>&1");
                }
                return ['success' => false, 'message' => 'Database restore failed: ' . $restoreOutput];
            }

            // Clean up pre-restore backup
            if (file_exists($preRestoreBackup)) {
                unlink($preRestoreBackup);
            }

            Log::info("FarmOS database restored successfully");
            return ['success' => true, 'message' => 'FarmOS database restored successfully'];

        } catch (Exception $e) {
            Log::error("FarmOS database restore failed: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database restore failed: ' . $e->getMessage()];
        }
    }

    /**
     * Format bytes into human readable format
     */
    protected function formatBytes($bytes)
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

    /**
     * Recursively add directory to ZIP archive
     */
    protected function addDirectoryToZip($zip, $dirPath, $zipPath)
    {
        $dirHandle = opendir($dirPath);
        if (!$dirHandle) {
            return false;
        }

        while (($file = readdir($dirHandle)) !== false) {
            if ($file == '.' || $file == '..') {
                continue;
            }

            $fullPath = $dirPath . '/' . $file;
            $relativePath = $zipPath . '/' . $file;

            if (is_dir($fullPath)) {
                $zip->addEmptyDir($relativePath);
                $this->addDirectoryToZip($zip, $fullPath, $relativePath);
            } else {
                $zip->addFile($fullPath, $relativePath);
            }
        }

        closedir($dirHandle);
        return true;
    }
}