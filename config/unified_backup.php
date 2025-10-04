<?php

return [
    // Unified backup configuration for all sites
    'sites' => [
        'admin.middleworldfarms.org' => [
            'type' => 'spatie',
            'enabled' => true,
            'label' => 'Admin (Laravel)',
        ],
        'farmos.middleworldfarms.org' => [
            'type' => 'remote_api',
            'enabled' => true,
            'label' => 'FarmOS',
            'api_url' => 'https://farmos.middleworldfarms.org/api/backup',
            'api_token' => env('FARMOS_BACKUP_API_TOKEN'),
        ],
        'middleworldfarms.org' => [
            'type' => 'remote_api',
            'enabled' => true,
            'label' => 'Main Website',
            'api_url' => 'https://middleworldfarms.org/api/backup',
            'api_token' => env('MWF_BACKUP_API_TOKEN'),
        ],
        'middleworld.farm' => [
            'type' => 'remote_api',
            'enabled' => true,
            'label' => 'Middleworld Farm',
            'api_url' => 'https://middleworld.farm/api/backup',
            'api_token' => env('MWFARM_BACKUP_API_TOKEN'),
        ],
    ],
    // Where to store all unified backups (relative to storage/app)
    'storage_path' => 'backups/unified',
    // Retention policy (in days)
    'retention_days' => 30,
];
