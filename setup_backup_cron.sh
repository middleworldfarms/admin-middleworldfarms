#!/bin/bash

# Backup System Cron Setup Script
# This script sets up the cron job for automatic backups

LARAVEL_PATH="/opt/sites/admin.middleworldfarms.org"
CRON_USER="devuser"

echo "Setting up backup cron job..."

# Check if Laravel path exists
if [ ! -d "$LARAVEL_PATH" ]; then
    echo "Error: Laravel application not found at $LARAVEL_PATH"
    exit 1
fi

# Check if artisan exists
if [ ! -f "$LARAVEL_PATH/artisan" ]; then
    echo "Error: Artisan command not found in $LARAVEL_PATH"
    exit 1
fi

# Create cron job entry
CRON_ENTRY="0 * * * * cd $LARAVEL_PATH && php artisan backup:run >/dev/null 2>&1"

# Add to crontab
echo "Adding cron job: $CRON_ENTRY"
(crontab -u $CRON_USER -l 2>/dev/null; echo "$CRON_ENTRY") | crontab -u $CRON_USER -

echo "Cron job added successfully!"
echo "The backup system will now check for scheduled backups every hour."
echo ""
echo "To verify the cron job was added:"
echo "crontab -u $CRON_USER -l"
echo ""
echo "To test a manual backup:"
echo "cd $LARAVEL_PATH && php artisan backup:run --force --name=test"
echo ""
echo "To configure backup schedule, visit:"
echo "http://your-domain/admin/backups"
