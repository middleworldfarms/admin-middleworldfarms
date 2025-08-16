#!/bin/bash

# Enhanced Backup System Test Script
# Tests the extended backup functionality including AI service and vector database

echo "🧪 Testing Enhanced Backup System with AI Components"
echo "====================================================="

# Check if AI service directory exists
if [ -d "/opt/sites/admin.middleworldfarms.org/ai_service" ]; then
    echo "✅ AI service directory found"
else
    echo "❌ AI service directory not found"
fi

# Check if AI service is running
AI_RESPONSE=$(curl -s -w "%{http_code}" -o /dev/null http://localhost:8005/health 2>/dev/null)
if [ "$AI_RESPONSE" = "200" ]; then
    echo "✅ AI service is running on port 8005"
else
    echo "❌ AI service not responding on port 8005 (HTTP code: $AI_RESPONSE)"
fi

# Check PostgreSQL vector database
PG_CHECK=$(sudo -u postgres psql -lqt | cut -d \| -f 1 | grep -qw vector_db && echo "exists" || echo "missing")
if [ "$PG_CHECK" = "exists" ]; then
    echo "✅ Vector database 'vector_db' exists"
else
    echo "❌ Vector database 'vector_db' not found"
fi

# Test backup creation through Laravel artisan
echo ""
echo "🔄 Testing backup creation..."
cd /opt/sites/admin.middleworldfarms.org

# Create a test backup via PHP CLI to test the enhanced functionality
php -r "
require_once 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();

try {
    \$controller = new App\Http\Controllers\Admin\BackupController();
    echo \"Creating test backup with AI components...\\n\";
    
    // Use reflection to call the private createBackup method for testing
    \$reflection = new ReflectionClass(\$controller);
    \$method = \$reflection->getMethod('createBackup');
    \$method->setAccessible(true);
    
    \$result = \$method->invoke(\$controller, 'ai_test', true, true, 'test');
    echo \"✅ Backup created successfully: \$result\\n\";
    
    // Check backup contents
    \$backupPath = storage_path('app/backups/' . \$result);
    if (file_exists(\$backupPath)) {
        echo \"✅ Backup file exists at: \$backupPath\\n\";
        echo \"📊 Backup file size: \" . round(filesize(\$backupPath) / 1024 / 1024, 2) . \" MB\\n\";
        
        // List contents of the ZIP
        \$zip = new ZipArchive();
        if (\$zip->open(\$backupPath) === TRUE) {
            echo \"\\n📦 Backup contains:\" . PHP_EOL;
            for (\$i = 0; \$i < \$zip->numFiles; \$i++) {
                \$file = \$zip->getNameIndex(\$i);
                if (strpos(\$file, 'ai_service/') === 0 || 
                    \$file === 'backup_info.json' || 
                    \$file === 'database.sql' || 
                    \$file === 'vector_database.sql' ||
                    \$file === 'SYMBIOSIS_AI_DOCUMENTATION.md') {
                    echo \"   ✅ \$file\" . PHP_EOL;
                }
            }
            \$zip->close();
        }
    }
    
} catch (Exception \$e) {
    echo \"❌ Error creating backup: \" . \$e->getMessage() . \"\\n\";
}
"

echo ""
echo "🔍 Checking backup directory..."
BACKUP_DIR="/opt/sites/admin.middleworldfarms.org/storage/app/backups"
if [ -d "$BACKUP_DIR" ]; then
    echo "✅ Backup directory exists"
    BACKUP_COUNT=$(ls -1 "$BACKUP_DIR"/*.zip 2>/dev/null | wc -l)
    echo "📁 Number of backup files: $BACKUP_COUNT"
    
    if [ "$BACKUP_COUNT" -gt 0 ]; then
        echo "📋 Recent backups:"
        ls -lh "$BACKUP_DIR"/*.zip | tail -3
    fi
else
    echo "❌ Backup directory not found"
fi

echo ""
echo "✨ Enhanced backup system test completed!"
