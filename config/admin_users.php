<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Users Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the authorized admin users for the Symbiosis Admin Dashboard.
    | Each user should have a unique email and a secure password hash.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | WordPress Email Mapping
    |--------------------------------------------------------------------------
    |
    | Map Laravel admin emails to WordPress admin emails when they differ
    |
    */
    'wordpress_email_mapping' => [
        'martin@middleworldfarms.org' => 'middleworldfarms@gmail.com',
        'humanityawakeningproject@hotmail.com' => 'humanityawakeningproject@hotmail.com', // Jonathan uses same email for both
        // Add more mappings here if needed
    ],

    'users' => [
        [
            'name' => 'Martin',
            'email' => 'martin@middleworldfarms.org',
            'password' => 'Gogmyk-medmyt-3himsu', // Will be hashed in controller
            'role' => 'super_admin',
            'created_at' => '2025-06-09',
            'active' => true,
        ],
        [
            'name' => 'MWF Admin',
            'email' => 'admin@middleworldfarms.org',
            'password' => 'MWF2025Admin!', // Will be hashed in controller
            'role' => 'admin',
            'created_at' => '2025-06-09',
            'active' => true,
        ],
        [
            'name' => 'Jonathan',
            'email' => 'humanityawakeningproject@hotmail.com',
            'password' => 'Gump7h21', // Secure temporary password
            'role' => 'super_admin', // Full admin rights
            'created_at' => '2025-08-18',
            'active' => true,
        ]
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    */
    'session_timeout' => 240, // minutes (4 hours)
    'remember_me' => true,
    'max_login_attempts' => 5,
    'lockout_duration' => 15, // minutes

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'require_2fa' => false,
    'log_all_access' => true,
    'allowed_ips' => [], // Empty array means all IPs allowed
];
