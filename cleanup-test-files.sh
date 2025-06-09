#!/bin/bash

# MWF Admin Cleanup Script - Remove Test and Debug Files
# Created: June 9, 2025
# Purpose: Clean up all test, debug, and temporary files from the admin workspace

echo "🧹 MWF Admin Workspace Cleanup"
echo "=============================="

# Function to safely remove files with confirmation
remove_files() {
    local pattern="$1"
    local description="$2"
    
    echo ""
    echo "🗑️  Removing $description..."
    
    # Find and list files to be removed
    files=$(find . -name "$pattern" -type f 2>/dev/null)
    
    if [ -n "$files" ]; then
        echo "Files to be removed:"
        echo "$files" | sed 's/^/   /'
        echo "$files" | xargs rm -f
        echo "✅ Removed $description"
    else
        echo "ℹ️  No $description found"
    fi
}

# Function to remove files by full path
remove_specific_files() {
    local description="$1"
    shift
    local files=("$@")
    
    echo ""
    echo "🗑️  Removing $description..."
    
    for file in "${files[@]}"; do
        if [ -f "$file" ]; then
            echo "   Removing: $file"
            rm -f "$file"
        fi
    done
    echo "✅ Removed $description"
}

# Change to admin directory
cd /var/www/vhosts/middleworldfarms.org/subdomains/admin

echo "📂 Current directory: $(pwd)"
echo "🔍 Scanning for test and debug files..."

# Remove debug files
remove_files "debug-*.php" "debug files"

# Remove test files 
remove_files "test-*.php" "test files"
remove_files "test-*.sh" "test shell scripts"

# Remove HTML preview files
remove_files "*preview*.html" "HTML preview files"

# Remove status check files (keep check-permissions-route.php as it might be needed)
remove_specific_files "status check files" \
    "./public/check-statuses.php" \
    "./public/check-delivery-statuses.php"

# Remove analysis and verification files
remove_specific_files "analysis and verification files" \
    "./public/duplicate-analysis.php" \
    "./public/verify-duplicate-fix.php" \
    "./public/simple-duplicate-test.php" \
    "./public/complete-status-implementation-test.php"

# Remove simple test files
remove_specific_files "simple test files" \
    "./simple-debug-schedule.php"

# Remove public test files
remove_files "./public/php-compatibility-check.php" "PHP compatibility check"
remove_files "./public/phptest.php" "PHP test file"
remove_files "./public/envtest.php" "Environment test file"
remove_files "./public/dbtest.php" "Database test file"
remove_files "./public/debug.php" "Public debug file"
remove_files "./public/print-*.php" "Print test files"

# Remove test profile files
remove_files "./public/test-profile-*.html" "Test profile HTML files"

echo ""
echo "🧹 Cleaning up empty directories..."

# Remove empty directories in public if they exist
find ./public -type d -empty -delete 2>/dev/null || true

echo ""
echo "📊 Cleanup Summary"
echo "=================="

# Count remaining files by type
debug_files=$(find . -name "debug-*.php" -type f 2>/dev/null | wc -l)
test_files=$(find . -name "test-*.php" -type f 2>/dev/null | wc -l)
preview_files=$(find . -name "*preview*.html" -type f 2>/dev/null | wc -l)

echo "📈 Remaining files:"
echo "   Debug files: $debug_files"
echo "   Test files: $test_files"
echo "   Preview files: $preview_files"

if [ "$debug_files" -eq 0 ] && [ "$test_files" -eq 0 ] && [ "$preview_files" -eq 0 ]; then
    echo ""
    echo "✅ Cleanup completed successfully!"
    echo "🎉 Workspace is now clean and ready for production"
else
    echo ""
    echo "⚠️  Some files may remain - please review manually"
fi

echo ""
echo "🔒 Important files preserved:"
echo "   ✅ Laravel application files"
echo "   ✅ Configuration files"
echo "   ✅ Documentation (*.md files)"
echo "   ✅ Production scripts"
echo "   ✅ Composer files"
echo "   ✅ Core functionality"

echo ""
echo "🎯 Next steps:"
echo "   1. Review git status: git status"
echo "   2. Add cleaned files: git add ."
echo "   3. Commit cleanup: git commit -m 'chore: cleanup test and debug files'"
echo "   4. Test application functionality"

echo ""
echo "🧹 Cleanup script completed!"
