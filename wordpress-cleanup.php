<?php

/**
 * WordPress Database Cleanup Script
 * 
 * Safely removes bloat from WordPress database while preserving content:
 * - WPForms error logs (397MB of failed update attempts)
 * - ActionScheduler stuck/failed actions (51MB)
 * - Old ActionScheduler logs (29MB)
 * 
 * This will NOT touch:
 * - Posts, pages, products
 * - Orders, customers, users
 * - Settings, options
 * - Comments, media
 */

// Database connection details
$host = 'localhost';
$username = 'wp_pteke';
$password = '4_Sl8a0kcaTgr*El';
$database = 'wp_pxmxy';
$prefix = 'D6sPMX_';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$database;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== WordPress Database Cleanup Started ===\n";
    echo "Database: $database\n";
    echo "Timestamp: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Get initial database size
    $stmt = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb FROM information_schema.tables WHERE table_schema = '$database'");
    $initialSize = $stmt->fetchColumn();
    echo "Initial database size: {$initialSize} MB\n\n";
    
    // 1. Clean WPForms logs (397MB of errors)
    echo "1. Cleaning WPForms logs...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM {$prefix}wpforms_logs");
    $totalLogs = $stmt->fetchColumn();
    echo "   Total logs: $totalLogs\n";
    
    // Count error logs specifically
    $stmt = $pdo->query("SELECT COUNT(*) FROM {$prefix}wpforms_logs WHERE types = 'error'");
    $errorLogs = $stmt->fetchColumn();
    echo "   Error logs: $errorLogs\n";
    
    // Keep only the last 100 non-error logs, delete all error logs older than 7 days
    $stmt = $pdo->prepare("DELETE FROM {$prefix}wpforms_logs WHERE types = 'error' AND create_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $deletedErrors = $stmt->rowCount();
    echo "   Deleted old error logs: $deletedErrors\n";
    
    // Delete old non-error logs, keep last 1000
    $stmt = $pdo->prepare("DELETE FROM {$prefix}wpforms_logs WHERE types != 'error' AND id NOT IN (SELECT id FROM (SELECT id FROM {$prefix}wpforms_logs WHERE types != 'error' ORDER BY id DESC LIMIT 1000) t)");
    $stmt->execute();
    $deletedOther = $stmt->rowCount();
    echo "   Deleted old non-error logs: $deletedOther\n";
    
    // 2. Clean ActionScheduler actions (51MB)
    echo "\n2. Cleaning ActionScheduler actions...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM {$prefix}actionscheduler_actions");
    $totalActions = $stmt->fetchColumn();
    echo "   Total actions: $totalActions\n";
    
    // Count by status
    $stmt = $pdo->query("SELECT status, COUNT(*) as count FROM {$prefix}actionscheduler_actions GROUP BY status");
    $statuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($statuses as $status) {
        echo "   {$status['status']}: {$status['count']}\n";
    }
    
    // Delete completed actions older than 30 days
    $stmt = $pdo->prepare("DELETE FROM {$prefix}actionscheduler_actions WHERE status = 'complete' AND scheduled_date_gmt < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $deletedComplete = $stmt->rowCount();
    echo "   Deleted old completed actions: $deletedComplete\n";
    
    // Delete failed actions older than 7 days
    $stmt = $pdo->prepare("DELETE FROM {$prefix}actionscheduler_actions WHERE status = 'failed' AND scheduled_date_gmt < DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $deletedFailed = $stmt->rowCount();
    echo "   Deleted old failed actions: $deletedFailed\n";
    
    // Delete canceled actions older than 7 days
    $stmt = $pdo->prepare("DELETE FROM {$prefix}actionscheduler_actions WHERE status = 'canceled' AND scheduled_date_gmt < DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stmt->execute();
    $deletedCanceled = $stmt->rowCount();
    echo "   Deleted old canceled actions: $deletedCanceled\n";
    
    // 3. Clean ActionScheduler logs (29MB)
    echo "\n3. Cleaning ActionScheduler logs...\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) FROM {$prefix}actionscheduler_logs");
    $totalSchedulerLogs = $stmt->fetchColumn();
    echo "   Total scheduler logs: $totalSchedulerLogs\n";
    
    // Delete logs for actions that no longer exist
    $stmt = $pdo->prepare("DELETE FROM {$prefix}actionscheduler_logs WHERE action_id NOT IN (SELECT action_id FROM {$prefix}actionscheduler_actions)");
    $stmt->execute();
    $deletedOrphanLogs = $stmt->rowCount();
    echo "   Deleted orphaned logs: $deletedOrphanLogs\n";
    
    // Delete logs older than 30 days
    $stmt = $pdo->prepare("DELETE FROM {$prefix}actionscheduler_logs WHERE log_date_gmt < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $deletedOldLogs = $stmt->rowCount();
    echo "   Deleted old logs: $deletedOldLogs\n";
    
    // 4. Clean up other common bloat tables
    echo "\n4. Cleaning other bloat tables...\n";
    
    // Clean old revisions (keep last 5 per post)
    $stmt = $pdo->prepare("DELETE FROM {$prefix}posts WHERE post_type = 'revision' AND ID NOT IN (
        SELECT ID FROM (
            SELECT ID FROM {$prefix}posts p1 
            WHERE p1.post_type = 'revision' 
            AND (
                SELECT COUNT(*) FROM {$prefix}posts p2 
                WHERE p2.post_type = 'revision' 
                AND p2.post_parent = p1.post_parent 
                AND p2.post_date >= p1.post_date
            ) <= 5
        ) t
    )");
    $stmt->execute();
    $deletedRevisions = $stmt->rowCount();
    echo "   Deleted old post revisions: $deletedRevisions\n";
    
    // Clean spam comments
    $stmt = $pdo->prepare("DELETE FROM {$prefix}comments WHERE comment_approved = 'spam' AND comment_date < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $deletedSpam = $stmt->rowCount();
    echo "   Deleted old spam comments: $deletedSpam\n";
    
    // Clean trash comments
    $stmt = $pdo->prepare("DELETE FROM {$prefix}comments WHERE comment_approved = 'trash' AND comment_date < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    $stmt->execute();
    $deletedTrash = $stmt->rowCount();
    echo "   Deleted old trash comments: $deletedTrash\n";
    
    // Clean expired transients
    $stmt = $pdo->prepare("DELETE FROM {$prefix}options WHERE option_name LIKE '_transient_timeout_%' AND option_value < UNIX_TIMESTAMP()");
    $stmt->execute();
    $deletedTransientTimeouts = $stmt->rowCount();
    
    $stmt = $pdo->prepare("DELETE FROM {$prefix}options WHERE option_name LIKE '_transient_%' AND option_name NOT LIKE '_transient_timeout_%' AND option_name NOT IN (
        SELECT REPLACE(option_name, '_transient_timeout_', '_transient_') FROM {$prefix}options WHERE option_name LIKE '_transient_timeout_%'
    )");
    $stmt->execute();
    $deletedTransients = $stmt->rowCount();
    echo "   Deleted expired transients: $deletedTransients (+ $deletedTransientTimeouts timeouts)\n";
    
    // 5. Optimize tables
    echo "\n5. Optimizing tables...\n";
    
    $tables = [
        "{$prefix}wpforms_logs",
        "{$prefix}actionscheduler_actions", 
        "{$prefix}actionscheduler_logs",
        "{$prefix}posts",
        "{$prefix}comments",
        "{$prefix}options"
    ];
    
    foreach ($tables as $table) {
        $stmt = $pdo->query("OPTIMIZE TABLE $table");
        echo "   Optimized: $table\n";
    }
    
    // Get final database size
    echo "\n=== Cleanup Complete ===\n";
    $stmt = $pdo->query("SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS db_size_mb FROM information_schema.tables WHERE table_schema = '$database'");
    $finalSize = $stmt->fetchColumn();
    $savedSpace = $initialSize - $finalSize;
    $percentSaved = round(($savedSpace / $initialSize) * 100, 1);
    
    echo "Initial size: {$initialSize} MB\n";
    echo "Final size: {$finalSize} MB\n";
    echo "Space saved: {$savedSpace} MB ({$percentSaved}%)\n";
    echo "Cleanup completed at: " . date('Y-m-d H:i:s') . "\n";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
