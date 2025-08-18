<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Exception;
use Illuminate\Support\Facades\Log;

class DatabaseBackupService
{
    public function createBackup($connections = ['mysql'])
    {
        $backupData = [];
        $timestamp = date('Y-m-d_H-i-s');
        
        foreach ($connections as $connection) {
            try {
                Log::info("Starting backup for connection: {$connection}");
                $backupData[$connection] = $this->exportDatabase($connection);
                Log::info("Completed backup for connection: {$connection}");
            } catch (Exception $e) {
                Log::error("Failed to backup {$connection}: " . $e->getMessage());
                throw $e;
            }
        }
        
        // Save to storage (use absolute path to backups directory)
        $filename = "database_backup_{$timestamp}.json";
        $backupDir = storage_path('app/backups');
        $filepath = $backupDir . '/' . $filename;
        
        // Ensure the backups directory exists
        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0755, true);
        }
        
        file_put_contents($filepath, json_encode($backupData, JSON_PRETTY_PRINT));
        
        return [
            'success' => true,
            'file' => "backups/{$filename}",
            'size' => filesize($filepath),
            'connections' => array_keys($backupData)
        ];
    }
    
    private function exportDatabase($connection)
    {
        $db = DB::connection($connection);
        $tables = $this->getTables($db);
        $export = [];
        
        foreach ($tables as $table) {
            $export[$table] = [
                'structure' => $this->getTableStructure($db, $table),
                'data' => $this->getTableData($db, $table)
            ];
        }
        
        return $export;
    }
    
    private function getTables($db)
    {
        $tables = [];
        try {
            $results = $db->select('SHOW TABLES');
            
            foreach ($results as $result) {
                $result = (array) $result;
                $tableName = array_values($result)[0];
                
                // Skip table with corrupted double prefix (D6sPMX_D6sPMX_*)
                if (strpos($tableName, 'D6sPMX_D6sPMX_') === 0) {
                    Log::warning("Skipping corrupted table with double prefix: {$tableName}");
                    continue;
                }
                
                $tables[] = $tableName;
            }
        } catch (\Exception $e) {
            Log::error("Failed to get table list: " . $e->getMessage());
            throw $e;
        }
        
        return $tables;
    }
    
    private function getTableStructure($db, $table)
    {
        try {
            $result = $db->select("SHOW CREATE TABLE `{$table}`");
            return $result[0]->{'Create Table'};
        } catch (\Exception $e) {
            Log::warning("Failed to get structure for table {$table}: " . $e->getMessage());
            return 'Table structure could not be retrieved: ' . $e->getMessage();
        }
    }
    
    private function getTableData($db, $table)
    {
        try {
            // Check table size first
            $sizeQuery = "SELECT ROUND(((data_length + index_length) / 1024 / 1024), 2) AS 'size_mb' 
                         FROM information_schema.tables 
                         WHERE table_schema = DATABASE() AND table_name = ?";
            
            $sizeResult = $db->select($sizeQuery, [$table]);
            $sizeMB = $sizeResult[0]->size_mb ?? 0;
            
            // For very large tables (>50MB), just store table info instead of all data
            if ($sizeMB > 50) {
                Log::info("Table {$table} is {$sizeMB}MB - storing metadata only");
                
                $count = $db->table($table)->count();
                return [
                    'large_table_notice' => "Table too large ({$sizeMB}MB, {$count} rows) - data not backed up to JSON",
                    'size_mb' => $sizeMB,
                    'row_count' => $count,
                    'sample_data' => $db->table($table)->limit(5)->get()->toArray()
                ];
            }
            
            // For smaller tables, get all data but chunk it to avoid memory issues
            if ($sizeMB > 5) {
                Log::info("Chunking table {$table} ({$sizeMB}MB)");
                $data = [];
                $db->table($table)->orderBy('id')->chunk(1000, function($records) use (&$data) {
                    $data = array_merge($data, $records->toArray());
                });
                return $data;
            }
            
            // Small tables - get all data at once
            return $db->table($table)->get()->toArray();
            
        } catch (\Exception $e) {
            Log::warning("Failed to backup data for table {$table}: " . $e->getMessage());
            return ['error' => 'Table data could not be retrieved: ' . $e->getMessage()];
        }
    }
    
    public function listBackups()
    {
        $backupDir = storage_path('app/backups');
        $backups = [];
        
        if (!file_exists($backupDir)) {
            return $backups;
        }
        
        $files = glob($backupDir . '/database_backup_*.json');
        
        foreach ($files as $file) {
            $backups[] = [
                'file' => basename($file),
                'size' => filesize($file),
                'created' => filemtime($file)
            ];
        }
        
        // Sort by creation time, newest first
        usort($backups, function($a, $b) {
            return $b['created'] - $a['created'];
        });
        
        return $backups;
    }
    
    public function deleteBackup($filename)
    {
        $filepath = storage_path('app/backups/' . $filename);
        
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return false;
    }
}
