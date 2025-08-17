<?php

return [
    /*
    |--------------------------------------------------------------------------
    | farmOS Succession Planner Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains configuration options for the Middleworld farmOS
    | Succession Planner package. Configure your farmOS instance and AI
    | service endpoints here.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | farmOS Instance Configuration
    |--------------------------------------------------------------------------
    */
    
    'farmos_url' => env('FARMOS_URL', 'https://your-farm.farmos.net'),
    
    'oauth_client_id' => env('FARMOS_OAUTH_CLIENT_ID'),
    
    'oauth_client_secret' => env('FARMOS_OAUTH_CLIENT_SECRET'),
    
    'oauth_redirect_uri' => env('FARMOS_OAUTH_REDIRECT_URI', '/farmos/oauth/callback'),
    
    /*
    |--------------------------------------------------------------------------
    | AI Service Configuration (Symbiosis Integration)
    |--------------------------------------------------------------------------
    */
    
    'ai_service_url' => env('SYMBIOSIS_AI_URL', 'http://localhost:8005'),
    
    'ai_timeout' => env('SYMBIOSIS_AI_TIMEOUT', 60),
    
    'ai_enabled' => env('SYMBIOSIS_AI_ENABLED', true),
    
    /*
    |--------------------------------------------------------------------------
    | Succession Planner Features
    |--------------------------------------------------------------------------
    */
    
    'features' => [
        'ai_timing_optimization' => true,
        'drag_drop_timeline' => true,
        'real_time_calculations' => true,
        'bed_availability_check' => true,
        'weather_integration' => false, // Future feature
        'market_price_optimization' => false, // Future feature
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Default Crop Settings
    |--------------------------------------------------------------------------
    */
    
    'defaults' => [
        'succession_interval_days' => 14,
        'minimum_harvest_window' => 7,
        'maximum_successions' => 20,
        'transplant_buffer_days' => 3,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Timeline Configuration
    |--------------------------------------------------------------------------
    */
    
    'timeline' => [
        'chart_height' => 400,
        'color_scheme' => [
            'available_harvest' => '#28a745',
            'missed_harvest' => '#dc3545',
            'current_period' => '#007bff',
            'planning_period' => '#6c757d',
        ],
        'date_format' => 'M j, Y',
        'grid_lines' => true,
        'drag_sensitivity' => 5,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    
    'cache' => [
        'enabled' => true,
        'crop_data_ttl' => 3600, // 1 hour
        'variety_data_ttl' => 1800, // 30 minutes
        'ai_predictions_ttl' => 7200, // 2 hours
    ],
    
    /*
    |--------------------------------------------------------------------------
    | API Rate Limiting
    |--------------------------------------------------------------------------
    */
    
    'rate_limits' => [
        'farmos_api_calls_per_minute' => 60,
        'ai_service_calls_per_minute' => 10,
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    */
    
    'logging' => [
        'enabled' => true,
        'level' => env('SUCCESSION_PLANNER_LOG_LEVEL', 'info'),
        'channel' => 'succession-planner',
    ],
];
