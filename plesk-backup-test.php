<?php

require_once '/opt/sites/admin.middleworldfarms.org/vendor/autoload.php';

// Simple test without Laravel bootstrap
class TestPleskDashboard
{
    private $dumpsPath = '/var/lib/psa/dumps';

    public function generateHtml()
    {
        $backups = $this->listBackups();
        $status = $this->getStatus($backups);
        
        return "<!DOCTYPE html>
<html>
<head>
    <title>Plesk Backup Test Dashboard</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css' rel='stylesheet'>
</head>
<body>
    <div class='container mt-4'>
        <h1>Plesk Backup System Status</h1>
        
        <div class='row mb-4'>
            <div class='col-md-3'>
                <div class='card bg-primary text-white'>
                    <div class='card-body'>
                        <h5>Total Backups</h5>
                        <h2>{$status['total']}</h2>
                    </div>
                </div>
            </div>
            <div class='col-md-3'>
                <div class='card bg-success text-white'>
                    <div class='card-body'>
                        <h5>Total Size</h5>
                        <h2>{$status['size_formatted']}</h2>
                    </div>
                </div>
            </div>
            <div class='col-md-3'>
                <div class='card bg-info text-white'>
                    <div class='card-body'>
                        <h5>Extensions</h5>
                        <h2>{$status['by_type']['Extension']['count']}</h2>
                    </div>
                </div>
            </div>
            <div class='col-md-3'>
                <div class='card bg-warning text-white'>
                    <div class='card-body'>
                        <h5>Health</h5>
                        <h2>{$status['health']}</h2>
                    </div>
                </div>
            </div>
        </div>
        
        <h3>Backup Types</h3>
        <table class='table table-striped'>
            <thead>
                <tr>
                    <th>Type</th>
                    <th>Count</th>
                    <th>Size</th>
                </tr>
            </thead>
            <tbody>";
            
        foreach ($status['by_type'] as $type => $data) {
            $sizeFormatted = $this->formatBytes($data['size']);
            $html .= "<tr>
                <td>{$type}</td>
                <td>{$data['count']}</td>
                <td>{$sizeFormatted}</td>
            </tr>";
        }
        
        $html .= "</tbody>
        </table>
        
        <h3>Recent Backups (Last 10)</h3>
        <table class='table table-striped'>
            <thead>
                <tr>
                    <th>Component</th>
                    <th>Type</th>
                    <th>Created</th>
                    <th>Size</th>
                </tr>
            </thead>
            <tbody>";
            
        foreach (array_slice($backups, 0, 10) as $backup) {
            $html .= "<tr>
                <td><code>{$backup['component']}</code></td>
                <td><span class='badge bg-secondary'>{$backup['type']}</span></td>
                <td>{$backup['created']}</td>
                <td>{$backup['size_formatted']}</td>
            </tr>";
        }
        
        $html .= "</tbody>
        </table>
    </div>
</body>
</html>";
        
        return $html;
    }

    private function listBackups()
    {
        $backups = [];
        $files = glob($this->dumpsPath . '/*');
        
        foreach ($files as $file) {
            if (is_dir($file)) continue;
            
            $filename = basename($file);
            $backupInfo = $this->analyzeBackupFile($filename, $file);
            if ($backupInfo) {
                $backups[] = $backupInfo;
            }
        }
        
        usort($backups, function($a, $b) {
            return $b['timestamp'] - $a['timestamp'];
        });
        
        return $backups;
    }

    private function getStatus($backups)
    {
        $status = [
            'total' => count($backups),
            'total_size' => 0,
            'by_type' => [],
            'health' => 'unknown'
        ];
        
        foreach ($backups as $backup) {
            $status['total_size'] += $backup['size'];
            
            $type = $backup['type'];
            if (!isset($status['by_type'][$type])) {
                $status['by_type'][$type] = ['count' => 0, 'size' => 0];
            }
            $status['by_type'][$type]['count']++;
            $status['by_type'][$type]['size'] += $backup['size'];
        }
        
        $status['size_formatted'] = $this->formatBytes($status['total_size']);
        
        if (!empty($backups)) {
            $latest = $backups[0];
            $daysSince = (time() - $latest['timestamp']) / (24 * 60 * 60);
            $status['health'] = $daysSince <= 1 ? 'Healthy' : ($daysSince <= 7 ? 'Warning' : 'Critical');
        }
        
        return $status;
    }

    private function analyzeBackupFile($filename, $filepath)
    {
        if (preg_match('/backup_(.+)_(\d{10})(?:_(\d{10}))?\.(\w+)/', $filename, $matches)) {
            $component = $matches[1];
            $startTimestamp = $matches[2];
            $extension = $matches[4];
            
            $year = substr($startTimestamp, 0, 4);
            $month = substr($startTimestamp, 4, 2);
            $day = substr($startTimestamp, 6, 2);
            $hour = substr($startTimestamp, 8, 2);
            
            $created = sprintf('%04d-%02d-%02d %02d:00', $year, $month, $day, $hour);
            $timestamp = mktime($hour, 0, 0, $month, $day, $year);
            
            return [
                'filename' => $filename,
                'component' => $component,
                'created' => $created,
                'timestamp' => $timestamp,
                'size' => filesize($filepath),
                'size_formatted' => $this->formatBytes(filesize($filepath)),
                'extension' => $extension,
                'type' => $this->categorizeBackup($component, $extension)
            ];
        }
        
        return null;
    }

    private function categorizeBackup($component, $extension)
    {
        if (strpos($component, 'ext_') === 0) return 'Extension';
        
        $systemComponents = ['fail2ban', 'modsecurity', 'skel', 'lickey00', 'lickey01'];
        if (in_array($component, $systemComponents)) return 'System';
        
        if ($component === 'info' && $extension === 'xml') return 'Metadata';
        if (strpos($component, 'mysql') === 0) return 'Database';
        
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

// Generate and output the HTML
$dashboard = new TestPleskDashboard();
echo $dashboard->generateHtml();
