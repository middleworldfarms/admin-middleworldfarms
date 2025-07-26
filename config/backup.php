<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auto Backup Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the automatic backup functionality.
    |
    */

    'auto_backup_enabled' => env('AUTO_BACKUP_ENABLED', true),

    'auto_backup_frequency' => env('AUTO_BACKUP_FREQUENCY', 'daily'), // daily, weekly, monthly, disabled

    'auto_backup_time' => env('AUTO_BACKUP_TIME', '02:00'), // 24-hour format

    'auto_backup_retention_days' => env('AUTO_BACKUP_RETENTION_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Backup Content Settings
    |--------------------------------------------------------------------------
    |
    | Control what gets included in backups.
    |
    */

    'include_database' => env('BACKUP_INCLUDE_DATABASE', true),

    'include_files' => env('BACKUP_INCLUDE_FILES', false), // Can be large

    /*
    |--------------------------------------------------------------------------
    | Backup Storage
    |--------------------------------------------------------------------------
    |
    | Where backups are stored locally.
    |
    */

    'storage_path' => storage_path('app/backups'),

    /*
    |--------------------------------------------------------------------------
    | Excluded Paths
    |--------------------------------------------------------------------------
    |
    | These paths will be excluded from file backups.
    |
    */

    'exclude_paths' => [
        'storage/app/backups',
        'storage/logs',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'node_modules',
        '.git',
        'vendor',
        '.env'
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Settings
    |--------------------------------------------------------------------------
    |
    | Settings specific to database backups.
    |
    */

    'database' => [
        'connection' => env('DB_CONNECTION', 'mysql'),
        'mysqldump_path' => env('MYSQLDUMP_PATH', 'mysqldump'),
        'pg_dump_path' => env('PG_DUMP_PATH', 'pg_dump'),
    ],

];
