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
            'type' => 'plesk',
            'enabled' => true,
            'label' => 'FarmOS',
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
    ],
    // Where to store all unified backups (relative to storage/app)
    'storage_path' => 'backups/unified',
    // Retention policy (in days)
    'retention_days' => 30,
];
