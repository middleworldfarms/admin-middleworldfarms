<?php
/**
 * Test Plesk Backup Service
 */

require_once '/opt/sites/admin.middleworldfarms.org/vendor/autoload.php';

// Simple test without Laravel bootstrap
class TestPleskBackupService
{
    private $dumpsPath = '/var/lib/psa/dumps';

    public function listBackups()
    {
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
        
        return $backups;
    }

    private function analyzeBackupFile($filename, $filepath)
    {
        // Parse filename to extract information
        if (preg_match('/backup_(.+)_(\d{10})(?:_(\d{10}))?\.(\w+)/', $filename, $matches)) {
            
            $component = $matches[1];
            $startTimestamp = $matches[2];
            $endTimestamp = isset($matches[3]) ? $matches[3] : null;
            $extension = $matches[4];
            
            // Convert timestamps to readable dates
            $year = substr($startTimestamp, 0, 4);
            $month = substr($startTimestamp, 4, 2);
            $day = substr($startTimestamp, 6, 2);
            $hour = substr($startTimestamp, 8, 2);
            
            $created = sprintf('%04d-%02d-%02d %02d:00', $year, $month, $day, $hour);
            $timestamp = mktime($hour, 0, 0, $month, $day, $year);
            
            $endDate = null;
            if ($endTimestamp) {
                $endYear = substr($endTimestamp, 0, 4);
                $endMonth = substr($endTimestamp, 4, 2);
                $endDay = substr($endTimestamp, 6, 2);
                $endHour = substr($endTimestamp, 8, 2);
                $endDate = sprintf('%04d-%02d-%02d %02d:00', $endYear, $endMonth, $endDay, $endHour);
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

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

try {
    echo "=== Testing Plesk Backup Service ===\n";
    
    $service = new TestPleskBackupService();
    
    echo "Getting backup list...\n";
    $backups = $service->listBackups();
    
    echo "Found " . count($backups) . " backup files\n";
    
    if (!empty($backups)) {
        echo "\nFirst 5 backups:\n";
        $sample = array_slice($backups, 0, 5);
        
        foreach ($sample as $backup) {
            echo sprintf(
                "- %s (%s) - %s - %s\n",
                $backup['component'],
                $backup['type'],
                $backup['created'],
                $backup['size_formatted']
            );
        }
    }
    
    echo "\nGetting backup status...\n";
    $backupsByType = [];
    $totalSize = 0;
    
    foreach ($backups as $backup) {
        $type = $backup['type'];
        if (!isset($backupsByType[$type])) {
            $backupsByType[$type] = ['count' => 0, 'size' => 0];
        }
        $backupsByType[$type]['count']++;
        $backupsByType[$type]['size'] += $backup['size'];
        $totalSize += $backup['size'];
    }
    
    echo "Total backups: " . count($backups) . "\n";
    echo "Total size: " . number_format($totalSize / 1024 / 1024, 2) . " MB\n";
    
    if (!empty($backups)) {
        $latest = $backups[0];
        $daysSince = (time() - $latest['timestamp']) / (24 * 60 * 60);
        $health = $daysSince <= 1 ? 'healthy' : ($daysSince <= 7 ? 'warning' : 'critical');
        echo "Health status: $health\n";
    }
    
    echo "\nBreakdown by type:\n";
    foreach ($backupsByType as $type => $data) {
        echo sprintf("- %s: %d files (%.1f MB)\n", 
            $type, 
            $data['count'], 
            $data['size'] / 1024 / 1024
        );
    }
    
    echo "\n=== Test completed successfully! ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
