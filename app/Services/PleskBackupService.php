<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Exception;

class PleskBackupService
{
    private $pleskCliPath;
    private $dumpsPath;
    private $domains;

    public function __construct()
    {
        $this->pleskCliPath = env('PLESK_CLI_PATH', '/usr/sbin/plesk');
        $this->dumpsPath = env('PLESK_DUMPS_PATH', '/var/lib/psa/dumps');
        $this->domains = explode(',', env('PLESK_DOMAINS', ''));
    }

    /**
     * Get list of available backup files with categorization
     */
    public function listBackups()
    {
        try {
            $backups = [];
            
            // Scan the dumps directory for all backup files
            $files = glob($this->dumpsPath . '/*');
            
            foreach ($files as $file) {
                $filename = basename($file);
                
                // Skip directories
                if (is_dir($file)) {
                    continue;
                }
                
                $backupInfo = $this->analyzeBackupFile($filename, $file);
                if ($backupInfo) {
                    $backups[] = $backupInfo;
                }
            }

            // Sort by date, newest first
            usort($backups, function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });

            return $backups;        } catch (Exception $e) {
            Log::error('Failed to list Plesk backups: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Analyze backup file and extract metadata
     */
    private function analyzeBackupFile($filename, $filepath)
    {
        // Parse filename to extract information
        if (preg_match('/backup_(.+)_(\d{10})(?:_(\d{10}))?\.(\w+)/', $filename, $matches)) {
            
            $component = $matches[1];
            $startTimestamp = $matches[2];
            $endTimestamp = isset($matches[3]) ? $matches[3] : null;
            $extension = $matches[4];
            
            // Convert timestamps to readable dates (YYMMDDHHMI format)
            $year = 2000 + (int)substr($startTimestamp, 0, 2); // 25 -> 2025
            $month = (int)substr($startTimestamp, 2, 2);
            $day = (int)substr($startTimestamp, 4, 2);
            $hour = (int)substr($startTimestamp, 6, 2);
            $minute = (int)substr($startTimestamp, 8, 2);
            
            $created = sprintf('%04d-%02d-%02d %02d:%02d', $year, $month, $day, $hour, $minute);
            $timestamp = mktime($hour, $minute, 0, $month, $day, $year);
            
            $endDate = null;
            if ($endTimestamp) {
                $endYear = 2000 + (int)substr($endTimestamp, 0, 2);
                $endMonth = (int)substr($endTimestamp, 2, 2);
                $endDay = (int)substr($endTimestamp, 4, 2);
                $endHour = (int)substr($endTimestamp, 6, 2);
                $endMinute = (int)substr($endTimestamp, 8, 2);
                $endDate = sprintf('%04d-%02d-%02d %02d:%02d', $endYear, $endMonth, $endDay, $endHour, $endMinute);
            }
            
            return [
                'filename' => $filename,
                'component' => $component,
                'created' => $created,
                'end_date' => $endDate,
                'timestamp' => $timestamp,
                'size' => file_exists($filepath) ? filesize($filepath) : 0,
                'size_formatted' => $this->formatBytes(file_exists($filepath) ? filesize($filepath) : 0),
                'extension' => $extension,
                'type' => $this->categorizeBackup($component, $extension),
                'is_incremental' => !is_null($endDate),
                'path' => $filepath
            ];
        }
        
        return null;
    }

    /**
     * Categorize backup based on component and extension
     */
    private function categorizeBackup($component, $extension)
    {
        // Extension backups
        if (strpos($component, 'ext_') === 0) {
            return 'Extension';
        }
        
        // System components
        $systemComponents = ['fail2ban', 'modsecurity', 'skel', 'lickey00', 'lickey01'];
        if (in_array($component, $systemComponents)) {
            return 'System';
        }
        
        // Info files
        if ($component === 'info' && $extension === 'xml') {
            return 'Metadata';
        }
        
        // MySQL dumps
        if (strpos($component, 'mysql') === 0) {
            return 'Database';
        }
        
        return 'Other';
    }

    /**
     * Create a new backup for specific domains
     */
    public function createBackup($domains = null, $description = 'Admin panel backup')
    {
        try {
            $domainsToBackup = $domains ?: $this->domains;
            $domainsList = is_array($domainsToBackup) ? implode(' ', $domainsToBackup) : $domainsToBackup;
            
            $timestamp = date('Y-m-d_H-i-s');
            $outputFile = "/tmp/backup_admin_{$timestamp}.tar";
            
            $command = sprintf(
                '%s bin pleskbackup --domains-name "%s" --output-file "%s" --description "%s" --verbose 2>&1',
                $this->pleskCliPath,
                $domainsList,
                $outputFile,
                $description
            );
            
            exec($command, $output, $returnCode);
            
            if ($returnCode === 0) {
                $size = file_exists($outputFile) ? filesize($outputFile) : 0;
                
                return [
                    'success' => true,
                    'file' => $outputFile,
                    'size' => $size,
                    'domains' => $domainsToBackup,
                    'output' => implode("\n", $output)
                ];
            } else {
                throw new Exception('Backup failed: ' . implode("\n", $output));
            }
            
        } catch (Exception $e) {
            Log::error('Plesk backup creation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get backup status summary
     */
    public function getBackupStatus()
    {
        try {
            $backups = $this->listBackups();
            
            $status = [
                'total_backups' => count($backups),
                'total_size' => 0,
                'backup_health' => 'unknown',
                'last_backup' => null,
                'by_type' => [],
                'by_component' => []
            ];
            
            if (!empty($backups)) {
                $latest = $backups[0]; // Already sorted by newest first
                $status['last_backup'] = $latest;
                
                // Calculate total size and categorize
                foreach ($backups as $backup) {
                    $status['total_size'] += $backup['size'] ?? 0;
                    
                    // Count by type
                    $type = $backup['type'];
                    if (!isset($status['by_type'][$type])) {
                        $status['by_type'][$type] = ['count' => 0, 'size' => 0];
                    }
                    $status['by_type'][$type]['count']++;
                    $status['by_type'][$type]['size'] += $backup['size'] ?? 0;
                    
                    // Count by component
                    $component = $backup['component'];
                    if (!isset($status['by_component'][$component])) {
                        $status['by_component'][$component] = ['count' => 0, 'size' => 0];
                    }
                    $status['by_component'][$component]['count']++;
                    $status['by_component'][$component]['size'] += $backup['size'] ?? 0;
                }
                
                // Check backup health (recent backup = healthy)
                $lastBackupTime = $latest['timestamp'];
                $daysSinceLastBackup = (time() - $lastBackupTime) / (24 * 60 * 60);
                
                if ($daysSinceLastBackup <= 1) {
                    $status['backup_health'] = 'healthy';
                } elseif ($daysSinceLastBackup <= 7) {
                    $status['backup_health'] = 'warning';
                } else {
                    $status['backup_health'] = 'critical';
                }
            }
            
            return $status;
            
        } catch (Exception $e) {
            Log::error('Failed to get backup status: ' . $e->getMessage());
            return ['backup_health' => 'error', 'message' => $e->getMessage()];
        }
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
    }    /**
     * Download a backup file
     */
    public function downloadBackup($backupId)
    {
        try {
            $backupPath = $this->dumpsPath . '/' . $backupId;
            
            if (!file_exists($backupPath)) {
                throw new Exception("Backup file not found: {$backupId}");
            }
            
            return $backupPath;
            
        } catch (Exception $e) {
            Log::error('Failed to download backup: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Parse backup info XML file
     */
    private function parseBackupInfo($infoFile)
    {
        try {
            if (!file_exists($infoFile)) {
                return null;
            }
            
            $xml = simplexml_load_file($infoFile);
            if (!$xml) {
                return null;
            }
            
            $basename = basename($infoFile, '.xml');
            $timestamp = str_replace('backup_info_', '', $basename);
            
            return [
                'id' => $basename,
                'file' => $infoFile,
                'created' => $this->parseTimestamp($timestamp),
                'size' => filesize($infoFile),
                'type' => 'plesk_backup',
                'domains' => $this->extractDomainsFromXml($xml),
                'status' => 'completed'
            ];
            
        } catch (Exception $e) {
            Log::warning('Failed to parse backup info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse Plesk timestamp format
     */
    private function parseTimestamp($timestamp)
    {
        // Plesk format: YYMMDDHHMMSS
        if (strlen($timestamp) >= 10) {
            $year = '20' . substr($timestamp, 0, 2);
            $month = substr($timestamp, 2, 2);
            $day = substr($timestamp, 4, 2);
            $hour = substr($timestamp, 6, 2);
            $minute = substr($timestamp, 8, 2);
            
            return "{$year}-{$month}-{$day} {$hour}:{$minute}:00";
        }
        
        return date('Y-m-d H:i:s');
    }

    /**
     * Extract domain list from backup XML
     */
    private function extractDomainsFromXml($xml)
    {
        $domains = [];
        
        // Try to extract domains from XML structure
        // This is a simplified version - real implementation would parse XML structure
        if (isset($xml->domain)) {
            foreach ($xml->domain as $domain) {
                if (isset($domain['name'])) {
                    $domains[] = (string)$domain['name'];
                }
            }
        }
        
        return $domains;
    }

    /**
     * Test if Plesk CLI is accessible
     */
    public function testPleskAccess()
    {
        try {
            $command = $this->pleskCliPath . ' version 2>&1';
            exec($command, $output, $returnCode);
            
            return [
                'accessible' => $returnCode === 0,
                'version' => implode("\n", $output),
                'command' => $command
            ];
            
        } catch (Exception $e) {
            return [
                'accessible' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
