<?php
require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$keys = [
    'met_office_land_observations_key' => env('MET_OFFICE_LAND_OBSERVATIONS_KEY'),
    'met_office_site_specific_key' => env('MET_OFFICE_SITE_SPECIFIC_KEY'),
    'met_office_atmospheric_key' => env('MET_OFFICE_ATMOSPHERIC_KEY'),
    'met_office_map_images_key' => env('MET_OFFICE_MAP_IMAGES_KEY')
];

foreach ($keys as $key => $value) {
    if (!empty($value)) {
        App\Models\Setting::set($key, $value);
        echo "Set $key in database\n";
    } else {
        echo "$key is empty\n";
    }
}

echo "Done! All Met Office API keys copied from .env to database settings.\n";
