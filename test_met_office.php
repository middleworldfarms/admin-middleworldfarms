<?php
/**
 * Test Met Office API connection
 * Usage: php test_met_office.php
 */

require __DIR__ . '/vendor/autoload.php';

use Illuminate\Support\Facades\Http;

// Load Laravel environment
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== Testing Met Office API Connection ===\n\n";

// Get API keys from config
$weatherKeys = \App\Services\ApiKeyService::getWeatherApiKeys();

$keys = [
    'Met Office (DataPoint)' => $weatherKeys['met_office'] ?? null,
    'Met Office Site-Specific' => $weatherKeys['met_office_site_specific'] ?? null,
];

foreach ($keys as $name => $key) {
    echo "Testing {$name}:\n";
    
    if (!$key) {
        echo "  ❌ No API key found in config\n\n";
        continue;
    }
    
    echo "  API Key: " . substr($key, 0, 8) . "****\n";
    
    try {
        // Test 1: Get location list
        $response = Http::timeout(10)->withHeaders([
            'apikey' => $key,
            'accept' => 'application/json'
        ])->get('https://data.hub.api.metoffice.gov.uk/sitespecific/v0/site/list');
        
        if ($response->successful()) {
            $data = $response->json();
            $locationCount = count($data['Locations']['Location'] ?? []);
            echo "  ✅ API Key is VALID!\n";
            echo "  ✅ Found {$locationCount} UK weather locations\n";
            
            // Test 2: Get weather for first location
            if ($locationCount > 0) {
                $firstLocation = $data['Locations']['Location'][0];
                $locationId = $firstLocation['id'];
                $locationName = $firstLocation['name'];
                
                echo "  Testing weather data for '{$locationName}' (ID: {$locationId})...\n";
                
                $weatherResponse = Http::timeout(10)->withHeaders([
                    'apikey' => $key,
                    'accept' => 'application/json'
                ])->get("https://data.hub.api.metoffice.gov.uk/sitespecific/v0/site/{$locationId}", [
                    'res' => '3hourly'
                ]);
                
                if ($weatherResponse->successful()) {
                    $weatherData = $weatherResponse->json();
                    $periods = $weatherData['SiteRep']['DV']['Location']['Period'] ?? [];
                    
                    if (!empty($periods)) {
                        $currentRep = $periods[0]['Rep'][0] ?? null;
                        if ($currentRep) {
                            echo "  ✅ Weather data retrieved successfully!\n";
                            echo "     Temperature: " . ($currentRep['T'] ?? 'N/A') . "°C\n";
                            echo "     Feels Like: " . ($currentRep['F'] ?? 'N/A') . "°C\n";
                            echo "     Wind Speed: " . ($currentRep['S'] ?? 'N/A') . " mph\n";
                            echo "     Humidity: " . ($currentRep['H'] ?? 'N/A') . "%\n";
                            echo "     Visibility: " . ($currentRep['V'] ?? 'N/A') . "\n";
                        }
                    }
                } else {
                    echo "  ⚠️  Weather data request failed: " . $weatherResponse->status() . "\n";
                    echo "     Body: " . substr($weatherResponse->body(), 0, 200) . "\n";
                }
            }
        } else {
            echo "  ❌ API Key is INVALID or EXPIRED\n";
            echo "  HTTP Status: " . $response->status() . "\n";
            echo "  Response: " . substr($response->body(), 0, 200) . "\n";
        }
    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Test 3: Test full WeatherService
echo "=== Testing WeatherService ===\n";
try {
    $weatherService = app(\App\Services\WeatherService::class);
    
    echo "Getting current weather...\n";
    $currentWeather = $weatherService->getCurrentWeather();
    
    if ($currentWeather) {
        echo "✅ Current weather retrieved successfully!\n";
        echo "   Source: " . ($currentWeather['source'] ?? 'unknown') . "\n";
        echo "   Temperature: " . ($currentWeather['temperature'] ?? 'N/A') . "°C\n";
        echo "   Humidity: " . ($currentWeather['humidity'] ?? 'N/A') . "%\n";
        echo "   Wind Speed: " . ($currentWeather['wind_speed'] ?? 'N/A') . " mph\n";
        echo "   Description: " . ($currentWeather['weather_description'] ?? 'N/A') . "\n";
    } else {
        echo "❌ No weather data retrieved (all APIs failed)\n";
    }
    
    echo "\nGetting 5-day forecast...\n";
    $forecast = $weatherService->getForecast(5);
    
    if ($forecast && isset($forecast['daily'])) {
        echo "✅ Forecast retrieved successfully!\n";
        echo "   Source: " . ($forecast['source'] ?? 'unknown') . "\n";
        echo "   Days: " . count($forecast['daily']) . "\n";
        
        if (!empty($forecast['daily'])) {
            $firstDay = $forecast['daily'][0];
            echo "   First day: " . ($firstDay['date'] ?? 'N/A') . "\n";
            echo "   Temp range: " . ($firstDay['temp']['min'] ?? 'N/A') . "°C - " . ($firstDay['temp']['max'] ?? 'N/A') . "°C\n";
        }
    } else {
        echo "❌ No forecast data retrieved\n";
    }
    
} catch (\Exception $e) {
    echo "❌ WeatherService test failed: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
