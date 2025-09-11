<?php

return [

    /*
     * The name of this application. You can use this name to monitor
     * the backups.
     */
    'name' => env('APP_NAME', 'Laravel'),

    'source' => [

        'files' => [

            /*
             * The list of directories and files that will be included in the backup.
             */
            'include' => [
                base_path(),
            ],

            /*
             * These directories and files will be excluded from the backup.
             *
             * Directories used by the backup process will automatically be excluded.
             */
            'exclude' => [
                base_path('vendor'),
                base_path('node_modules'),
                base_path('storage/app/backup-temp'),
            ],

            /*
             * Determines if symlinks should be followed.
             */
            'follow_links' => false,

            /*
             * Note that this option is only used for the "include" paths.
             * The "exclude" paths will always be followed by default.
             */
        ],

        'databases' => [

            /*
             * The names of the connections to the databases that should be backed up
             * Only MySQL and PostgreSQL databases are supported.
             */
            'include' => [
                'mysql',
            ],

            /*
             * If you are using only InnoDB tables on a MySQL server, you can
             * also supply the --single-transaction option to the mysqldump command.
             * This makes sure the backup won't hang when database migrations are running.
             * Do not forget to add the --single-transaction option to the dump_command below.
             */
            'mysql' => [
                'dump_command_path' => '/usr/bin', // or null
                'dump_command_timeout' => 60 * 5, // 5 minute timeout
                'chunk_size_in_mb' => 200,
                'use_single_transaction' => true,
                'exclude_tables' => [],
            ],

            'postgresql' => [
                'dump_command_path' => '/usr/bin', // or null
                'dump_command_timeout' => 60 * 5, // 5 minute timeout
                'chunk_size_in_mb' => 200,
                'exclude_tables' => [],
            ],
        ],
    ],

    'destination' => [

        'disks' => [
            'local',
        ],
    ],

    'temporary_directory' => storage_path('app/backup-temp'),

    'password' => env('BACKUP_PASSWORD'),

    'encryption' => 'default',
];
