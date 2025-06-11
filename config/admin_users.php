<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Admin Users Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the authorized admin users for the MWF Admin Dashboard.
    | Each user should have a unique email and a secure password hash.
    |
    */

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
