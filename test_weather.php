<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Services\WeatherService;
use Illuminate\Foundation\Application;
use Illuminate\Contracts\Console\Kernel;

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

$weather = new WeatherService();

echo "Site Specific Key: " . (!empty($weather->metOfficeSiteSpecificKey) ? 'SET' : 'NOT SET') . PHP_EOL;
echo "Land Observations Key: " . (!empty($weather->metOfficeLandObservationsKey) ? 'SET' : 'NOT SET') . PHP_EOL;
echo "Atmospheric Key: " . (!empty($weather->metOfficeAtmosphericKey) ? 'SET' : 'NOT SET') . PHP_EOL;
echo "Map Images Key: " . (!empty($weather->metOfficeMapImagesKey) ? 'SET' : 'NOT SET') . PHP_EOL;

echo PHP_EOL . "Testing current weather fetch..." . PHP_EOL;
$data = $weather->getCurrentWeather();
if ($data) {
    echo "SUCCESS: Weather data retrieved!" . PHP_EOL;
    echo "Source: " . ($data['source'] ?? 'unknown') . PHP_EOL;
    echo "Temperature: " . ($data['temperature'] ?? 'N/A') . "°C" . PHP_EOL;
} else {
    echo "FAILED: No weather data retrieved" . PHP_EOL;
}