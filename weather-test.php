<?php

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🌤️ Weather API Test\n";
echo "==================\n\n";

// Test environment variables
echo "1. Environment Variables:\n";
echo "OPENWEATHER_API_KEY: " . (env('OPENWEATHER_API_KEY') ? 'SET (' . substr(env('OPENWEATHER_API_KEY'), 0, 8) . '...)' : 'NOT SET') . "\n";
echo "MET_OFFICE_API_KEY: " . (env('MET_OFFICE_API_KEY') ? 'SET (JWT token)' : 'NOT SET') . "\n";
echo "FARM_LAT: " . (env('FARM_LAT') ? env('FARM_LAT') : 'NOT SET') . "\n";
echo "FARM_LON: " . (env('FARM_LON') ? env('FARM_LON') : 'NOT SET') . "\n\n";

// Test OpenWeatherMap API
echo "2. Testing OpenWeatherMap API:\n";
try {
    $apiKey = env('OPENWEATHER_API_KEY');
    $lat = env('FARM_LAT', 51.4934);
    $lon = env('FARM_LON', 0.0098);
    
    $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: {$httpCode}\n";
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "✅ SUCCESS! Location: " . $data['name'] . ", " . $data['sys']['country'] . "\n";
        echo "Temperature: " . $data['main']['temp'] . "°C\n";
        echo "Description: " . $data['weather'][0]['description'] . "\n";
    } else {
        echo "❌ FAILED: " . $response . "\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n3. Testing Met Office API:\n";
try {
    $apiKey = env('MET_OFFICE_API_KEY');
    $lat = env('FARM_LAT', 51.4934);
    $lon = env('FARM_LON', 0.0098);
    
    // Met Office API endpoint (using WDH API)
    $url = "https://api-metoffice.apiconnect.ibmcloud.com/metoffice/production/v0/forecasts/point/hourly?excludeParameterMetadata=false&includeLocationName=true&latitude={$lat}&longitude={$lon}";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'X-IBM-Client-Id: ' . $apiKey
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "HTTP Code: {$httpCode}\n";
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        echo "✅ SUCCESS! Met Office API working\n";
        echo "Response preview: " . substr($response, 0, 200) . "...\n";
    } else {
        echo "❌ FAILED: " . $response . "\n";
    }
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n4. Testing Laravel Weather Service:\n";
try {
    $weatherService = app(\App\Services\WeatherService::class);
    
    echo "Weather service instantiated: ✅\n";
    
    // Test current weather
    $current = $weatherService->getCurrentWeather();
    if ($current && isset($current['temperature'])) {
        echo "✅ Current weather: " . $current['temperature'] . "°C\n";
    } else {
        echo "❌ Failed to get current weather\n";
        echo "Response: " . json_encode($current) . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n🎯 Test Complete!\n";
