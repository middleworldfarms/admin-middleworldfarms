<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\WeatherService;

echo "🌤️ WEATHER API TEST\n";
echo "==================\n\n";

try {
    $weatherService = app(WeatherService::class);
    
    // Test OpenWeatherMap API
    echo "🌍 Testing OpenWeatherMap API...\n";
    $openWeatherData = $weatherService->getCurrentWeather();
    
    if ($openWeatherData && isset($openWeatherData['success']) && $openWeatherData['success']) {
        echo "✅ OpenWeatherMap: SUCCESS\n";
        echo "   Temperature: " . ($openWeatherData['data']['temperature'] ?? 'N/A') . "°C\n";
        echo "   Condition: " . ($openWeatherData['data']['condition'] ?? 'N/A') . "\n";
        echo "   Humidity: " . ($openWeatherData['data']['humidity'] ?? 'N/A') . "%\n\n";
    } else {
        echo "❌ OpenWeatherMap: FAILED\n";
        echo "   Error: " . ($openWeatherData['error'] ?? 'Unknown error') . "\n\n";
    }
    
    // Test Met Office API
    echo "🇬🇧 Testing Met Office API...\n";
    $metOfficeData = $weatherService->getMetOfficeWeather();
    
    if ($metOfficeData && isset($metOfficeData['success']) && $metOfficeData['success']) {
        echo "✅ Met Office: SUCCESS\n";
        echo "   Data available: " . (isset($metOfficeData['data']) ? 'YES' : 'NO') . "\n";
    } else {
        echo "❌ Met Office: FAILED\n";
        echo "   Error: " . ($metOfficeData['error'] ?? 'Unknown error') . "\n";
    }
    
    echo "\n🎯 Weather system ready for your farm!\n";
    echo "📍 Location: " . config('weather.location.name') . "\n";
    echo "🔗 Dashboard: https://admin.middleworldfarms.org:8444/admin/weather\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
