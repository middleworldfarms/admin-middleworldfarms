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
            // Create a full backup (files + database)
            $output = shell_exec('cd ' . base_path() . ' && php artisan backup:run --only-files --disable-notifications 2>&1');
            Log::info("Spatie backup output: " . $output);

            // Also create a database-only backup for quick restores
            $dbOutput = shell_exec('cd ' . base_path() . ' && php artisan backup:run --only-db --disable-notifications 2>&1');
            Log::info("Spatie DB backup output: " . $dbOutput);

            return ['success' => true, 'message' => 'Spatie backup completed successfully'];
        } catch (Exception $e) {
            Log::error("Spatie backup failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create Plesk backup
     */
    protected function createPleskBackup($siteName, $siteConfig)
    {
        try {
            $sitePath = "/var/www/vhosts/{$siteName}";
            $backupDir = "/var/www/vhosts/{$siteName}/backups";

            // Ensure backup directory exists
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Create timestamp for backup filename
            $timestamp = date('Y-m-d-H-i-s');
            $backupFilename = "{$siteName}_backup_{$timestamp}.tar.gz";
            $backupPath = $backupDir . '/' . $backupFilename;

            // Create tar.gz backup of the site
            $command = "cd /var/www/vhosts && tar -czf {$backupPath} {$siteName} 2>&1";
            $output = shell_exec($command);

            if (file_exists($backupPath)) {
                Log::info("Plesk backup created successfully: {$backupPath}");
                return ['success' => true, 'message' => "Plesk backup created: {$backupFilename}"];
            } else {
                Log::error("Plesk backup failed: {$output}");
                return ['success' => false, 'message' => 'Plesk backup creation failed'];
            }

        } catch (Exception $e) {
            Log::error("Plesk backup failed: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Create remote API backup (placeholder)
     */
    protected function createRemoteApiBackup($siteName, $siteConfig)
    {
        // Placeholder for remote API backup creation
        return ['success' => false, 'message' => 'Remote API backup not yet implemented'];
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
