# Backup Management System

## Overview

The backup management system provides automated and manual backup functionality for the Laravel application with the following features:

### Features

1. **Auto-scheduled backups** with configurable frequency (daily, weekly, monthly)
2. **Manual backup creation** with custom naming
3. **Backup management** (rename, delete, download)
4. **Auto-cleanup** of old backups based on retention settings
5. **Database backup** support (MySQL/SQLite)
6. **Web interface** for backup management

## Setup

### 1. Cron Job Setup

To enable automatic backups, add this cron job to your server:

```bash
# Run every hour to check for scheduled backups
0 * * * * cd /opt/sites/admin.middleworldfarms.org && php artisan backup:run >/dev/null 2>&1
```

Add this to your crontab:
```bash
crontab -e
```

### 2. Environment Variables

Add these optional settings to your `.env` file:

```env
# Backup Settings
AUTO_BACKUP_ENABLED=true
AUTO_BACKUP_FREQUENCY=daily
AUTO_BACKUP_TIME=02:00
AUTO_BACKUP_RETENTION_DAYS=30
BACKUP_INCLUDE_DATABASE=true
BACKUP_INCLUDE_FILES=false
```

### 3. Directory Permissions

Ensure the backup directory has proper write permissions:

```bash
mkdir -p storage/app/backups
chmod 755 storage/app/backups
```

## Usage

### Web Interface

Access the backup management interface at:
```
/admin/backups
```

Features available in the web interface:
- View all existing backups
- Create manual backups with custom names
- Configure automatic backup schedule
- Rename existing backups
- Delete unwanted backups
- Download backup files

### Command Line

Create a manual backup:
```bash
php artisan backup:run --force --name=manual-backup
```

### Backup Schedule Options

- **Daily**: Creates a backup every day at the specified time
- **Weekly**: Creates a backup every Sunday at the specified time
- **Monthly**: Creates a backup on the 1st of each month at the specified time
- **Disabled**: No automatic backups

### What's Included in Backups

**Database backup**: 
- Complete MySQL dump including structure and data
- Stored as `database.sql` in the backup ZIP

**File backup** (optional, disabled by default):
- Application files excluding logs, cache, and vendor directories
- Can be enabled but may create large backup files

**Backup metadata**:
- Backup information in `backup_info.json`
- Creation timestamp, type, Laravel version, PHP version

## File Naming Convention

Backups are automatically named using this format:
```
{type}_{name}_{timestamp}.zip
```

Examples:
- `auto_scheduled_2025-07-26_02-00-15.zip`
- `manual_custom-name_2025-07-26_14-30-22.zip`

## Storage and Cleanup

- Backups are stored in `storage/app/backups/`
- Automatic cleanup removes backups older than retention period
- Only automatic backups are cleaned up, manual backups are preserved
- Default retention period is 30 days

## Security Considerations

- Backup files contain sensitive database information
- Ensure backup directory is not web-accessible
- Regular backup verification is recommended
- Store off-site copies for disaster recovery

## Troubleshooting

### Common Issues

1. **Permission errors**: Ensure backup directory is writable
2. **Database backup fails**: Check database credentials and mysqldump availability
3. **Large backup files**: Consider disabling file backups or excluding additional directories
4. **Disk space**: Monitor backup directory size and adjust retention settings

### Log Files

Check Laravel logs for backup-related errors:
```bash
tail -f storage/logs/laravel.log
```

### Manual Cleanup

Remove old backups manually:
```bash
find storage/app/backups/ -name "*.zip" -mtime +30 -delete
```

## API Endpoints

The backup system provides these API endpoints:

- `GET /admin/backups` - List all backups
- `POST /admin/backups/create` - Create manual backup
- `POST /admin/backups/rename/{filename}` - Rename backup
- `DELETE /admin/backups/delete/{filename}` - Delete backup
- `GET /admin/backups/download/{filename}` - Download backup
- `POST /admin/backups/schedule` - Update schedule settings
- `GET /admin/backups/status` - Get current status and settings
