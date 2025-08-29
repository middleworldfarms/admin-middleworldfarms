<?php

/*
|--------------------------------------------------------------------------
| Unified Backup Configuration
|--------------------------------------------------------------------------
|
| Configuration for all sites that need backup.
|
| IMPORTANT NOTES:
| - FarmOS is configured as an external API service
| - FarmOS data (crop_plans, harvest_logs) is synced to the admin database
| - This data is automatically backed up as part of the admin site's Spatie backup
| - No separate FarmOS backup is needed
|
*/

return [
    // Unified backup configuration for all sites
    'sites' => [
        'admin.middleworldfarms.org' => [
            'type' => 'spatie',
            'enabled' => true,
            'label' => 'Admin (Laravel)',
        ],
        'middleworldfarms.org' => [
            'type' => 'plesk',
            'enabled' => true,
            'label' => 'Main Website',
        ],
        'middleworld.farm' => [
            'type' => 'plesk',
            'enabled' => true,
            'label' => 'Middleworld Farm',
        ],
        'farmos.middleworldfarms.org' => [
            'type' => 'farmos',
            'enabled' => true,
            'label' => 'FarmOS (Drupal)',
            'source_path' => '/var/www/vhosts/middleworldfarms.org/subdomains/farmos',
        ],
    ],
    // Where to store all unified backups (relative to storage/app)
    'storage_path' => 'backups/unified',
    // Retention policy (in days)
    'retention_days' => 30,
];
