<?php
require_once __DIR__ . '/vendor/autoload.php';

// Read the key directly from .env file since it's multi-line wrapped
$envContent = file_get_contents(__DIR__ . '/.env');
if (preg_match('/MET_OFFICE_SITE_SPECIFIC_KEY=(.+?)(?=\nMET_OFFICE|\n[A-Z_]+=|$)/s', $envContent, $matches)) {
    $apiKey = preg_replace('/\s+/', '', $matches[1]); // Remove all whitespace including line breaks
} else {
    die("ERROR: MET_OFFICE_SITE_SPECIFIC_KEY not found in .env\n");
}

if (empty($apiKey)) {
    die("ERROR: MET_OFFICE_SITE_SPECIFIC_KEY is empty\n");
}

echo "=== Met Office Site-Specific BPF EDR API ===\n\n";

// According to docs, the base path is in the JWT context
$parts = explode('.', $apiKey);
if (count($parts) === 3) {
    $payload = json_decode(base64_decode($parts[1]), true);
    $context = $payload['subscribedAPIs'][0]['context'] ?? '';
    echo "JWT Context: $context\n";
}

// EDR API pattern from documentation
$baseUrl = 'https://data.hub.api.metoffice.gov.uk/mo-site-specific-blended-probabilistic-forecast/1.0.0';

$client = new \GuzzleHttp\Client([
    'timeout' => 10,
    'verify' => false,
]);

echo "\n--- Step 1: Get API definition ---\n";
try {
    $response = $client->get("$baseUrl/", [
        'headers' => [
            'apikey' => $apiKey,
            'Accept' => 'application/json',
        ],
    ]);
    
    echo "✅ API Definition (HTTP " . $response->getStatusCode() . ")\n";
    $data = json_decode($response->getBody(), true);
    if (isset($data['title'])) {
        echo "   Title: " . $data['title'] . "\n";
    }
    if (isset($data['description'])) {
        echo "   Description: " . substr($data['description'], 0, 200) . "...\n";
    }
} catch (\Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}

echo "\n--- Step 2: Get collections list ---\n";
try {
    $response = $client->get("$baseUrl/collections", [
        'headers' => [
            'apikey' => $apiKey,
            'Accept' => 'application/json',
        ],
    ]);
    
    echo "✅ Collections (HTTP " . $response->getStatusCode() . ")\n";
    $collections = json_decode($response->getBody(), true);
    
    if (isset($collections['collections'])) {
        echo "   Found " . count($collections['collections']) . " collections:\n";
        foreach ($collections['collections'] as $collection) {
            $collectionId = $collection['id'] ?? 'unknown';
            $title = $collection['title'] ?? 'No title';
            echo "   - $collectionId: $title\n";
            
            // Step 3: Get locations for this collection
            echo "\n--- Step 3: Get locations for collection '$collectionId' ---\n";
            try {
                $locResponse = $client->get("$baseUrl/collections/$collectionId/locations", [
                    'headers' => [
                        'apikey' => $apiKey,
                        'Accept' => 'application/json',
                    ],
                ]);
                
                echo "✅ Locations (HTTP " . $locResponse->getStatusCode() . ")\n";
                $locations = json_decode($locResponse->getBody(), true);
                
                if (isset($locations['features'])) {
                    echo "   Found " . count($locations['features']) . " locations\n";
                    
                    // Show first few locations
                    $count = 0;
                    foreach ($locations['features'] as $feature) {
                        if ($count++ >= 5) {
                            echo "   ... and " . (count($locations['features']) - 5) . " more\n";
                            break;
                        }
                        
                        $locId = $feature['id'] ?? 'unknown';
                        $coords = $feature['geometry']['coordinates'] ?? [];
                        $name = $feature['properties']['name'] ?? 'Unknown';
                        
                        echo "   - $locId: $name at [" . implode(', ', $coords) . "]\n";
                    }
                    
                    // Try to find a location near Middleworld Farms (53.2307, -0.5406)
                    echo "\n--- Finding nearest location to Middleworld Farms ---\n";
                    $targetLat = 53.2307;
                    $targetLon = -0.5406;
                    $nearest = null;
                    $minDistance = PHP_FLOAT_MAX;
                    
                    foreach ($locations['features'] as $feature) {
                        $coords = $feature['geometry']['coordinates'] ?? [];
                        if (count($coords) >= 2) {
                            $lon = $coords[0];
                            $lat = $coords[1];
                            
                            // Simple distance calculation
                            $distance = sqrt(pow($lat - $targetLat, 2) + pow($lon - $targetLon, 2));
                            
                            if ($distance < $minDistance) {
                                $minDistance = $distance;
                                $nearest = $feature;
                            }
                        }
                    }
                    
                    if ($nearest) {
                        $nearestId = $nearest['id'];
                        $nearestName = $nearest['properties']['name'] ?? 'Unknown';
                        $nearestCoords = $nearest['geometry']['coordinates'];
                        
                        echo "Nearest location: $nearestName (ID: $nearestId)\n";
                        echo "Coordinates: [" . implode(', ', $nearestCoords) . "]\n";
                        echo "Distance: " . round($minDistance * 111, 2) . " km\n";
                        
                        // Step 4: Get weather data for this location!
                        echo "\n--- Step 4: Get weather data for location '$nearestId' ---\n";
                        try {
                            $weatherResponse = $client->get("$baseUrl/collections/$collectionId/locations/$nearestId", [
                                'headers' => [
                                    'apikey' => $apiKey,
                                    'Accept' => 'application/json',
                                ],
                            ]);
                            
                            echo "✅ WEATHER DATA! (HTTP " . $weatherResponse->getStatusCode() . ")\n";
                            $weatherData = json_decode($weatherResponse->getBody(), true);
                            
                            echo "Response preview:\n";
                            echo json_encode($weatherData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
                            
                        } catch (\Exception $e) {
                            echo "❌ Failed to get weather data: " . $e->getMessage() . "\n";
                        }
                    }
                }
                
            } catch (\Exception $e) {
                echo "❌ Failed to get locations: " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "No collections found in response\n";
        echo json_encode($collections, JSON_PRETTY_PRINT) . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ Failed: " . $e->getMessage() . "\n";
}
