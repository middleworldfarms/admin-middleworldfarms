# farmOS OAuth2 Laravel Integration Guide

## Overview

OAuth2 is a token-based authentication system. Instead of sending your username/password with every API request, you:
1. Exchange credentials for an **access token**
2. Use that token for API requests
3. Optionally refresh the token when it expires

## Grant Types Available in farmOS

### Client Credentials Grant (Machine-to-Machine)
- Your Laravel app authenticates using only client_id + client_secret
- No user credentials needed
- Best for server-to-server communication

### Password Grant (Username/Password)
- Your Laravel app uses client_id + client_secret + username + password
- Acts on behalf of a specific farmOS user
- Good when you want user-specific permissions

### Refresh Token Grant
- Uses a refresh_token to get new access_tokens
- Keeps sessions alive without re-authentication

## Authentication Flow Examples

### Client Credentials Grant
```php
// Step 1: Get access token
$client = new \GuzzleHttp\Client();
$response = $client->post('https://admin.middleworldfarms.org/oauth/token', [
    'form_params' => [
        'grant_type' => 'client_credentials',
        'client_id' => 'NyIv5ejXa5xYRLKv0BXjUi-IHn3H2qbQQ3m-h2qp_xY',
        'client_secret' => 'Qw7!pZ2rT9@xL6vB1#eF4sG8uJ0mN5cD',
        'scope' => 'farm_manager', // or whatever scope you need
    ]
]);

$tokenData = json_decode($response->getBody(), true);
$accessToken = $tokenData['access_token'];

// Step 2: Use token for API requests
$apiResponse = $client->get('https://admin.middleworldfarms.org/api/asset/land', [
    'headers' => [
        'Authorization' => 'Bearer ' . $accessToken,
        'Accept' => 'application/vnd.api+json',
        'Content-Type' => 'application/vnd.api+json',
    ]
]);
```

### Password Grant
```php
// Step 1: Get access token
$response = $client->post('https://admin.middleworldfarms.org/oauth/token', [
    'form_params' => [
        'grant_type' => 'password',
        'client_id' => 'NyIv5ejXa5xYRLKv0BXjUi-IHn3H2qbQQ3m-h2qp_xY',
        'client_secret' => 'Qw7!pZ2rT9@xL6vB1#eF4sG8uJ0mN5cD',
        'username' => 'your_farmos_username',
        'password' => 'your_farmos_password',
    ]
]);
```

## Complete Laravel Service Class

```php
<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;

class FarmOSApiService
{
    private $client;
    private $baseUrl;
    private $clientId;
    private $clientSecret;
    private $username;
    private $password;

    public function __construct()
    {
        $this->client = new Client();
        $this->baseUrl = config('farmos.url', 'https://admin.middleworldfarms.org');
        $this->clientId = config('farmos.client_id');
        $this->clientSecret = config('farmos.client_secret');
        $this->username = config('farmos.username');
        $this->password = config('farmos.password');
    }

    public function getAccessToken($grantType = 'password')
    {
        $cacheKey = 'farmos_access_token';
        
        return Cache::remember($cacheKey, 240, function () use ($grantType) {
            $params = [
                'grant_type' => $grantType,
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
            ];

            if ($grantType === 'password') {
                $params['username'] = $this->username;
                $params['password'] = $this->password;
            }

            $response = $this->client->post($this->baseUrl . '/oauth/token', [
                'form_params' => $params
            ]);

            $data = json_decode($response->getBody(), true);
            return $data['access_token'];
        });
    }

    public function apiRequest($endpoint, $method = 'GET', $data = null)
    {
        $token = $this->getAccessToken();
        
        $options = [
            'headers' => [
                'Authorization' => 'Bearer ' . $token,
                'Accept' => 'application/vnd.api+json',
                'Content-Type' => 'application/vnd.api+json',
            ]
        ];

        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $options['json'] = $data;
        }

        $response = $this->client->request($method, $this->baseUrl . '/api' . $endpoint, $options);
        
        return json_decode($response->getBody(), true);
    }

    public function getAssets($type = null)
    {
        $endpoint = $type ? "/asset/{$type}" : '/asset';
        return $this->apiRequest($endpoint);
    }

    public function getLogs($type = null)
    {
        $endpoint = $type ? "/log/{$type}" : '/log';
        return $this->apiRequest($endpoint);
    }
}
```

## Laravel Configuration

### Environment Variables (.env)
```env
FARMOS_URL=https://admin.middleworldfarms.org
FARMOS_CLIENT_ID=NyIv5ejXa5xYRLKv0BXjUi-IHn3H2qbQQ3m-h2qp_xY
FARMOS_CLIENT_SECRET=Qw7!pZ2rT9@xL6vB1#eF4sG8uJ0mN5cD
FARMOS_USERNAME=your_farmos_username
FARMOS_PASSWORD=your_farmos_password
```

### Configuration File (config/farmos.php)
```php
<?php

return [
    'url' => env('FARMOS_URL', 'https://admin.middleworldfarms.org'),
    'client_id' => env('FARMOS_CLIENT_ID'),
    'client_secret' => env('FARMOS_CLIENT_SECRET'),
    'username' => env('FARMOS_USERNAME'),
    'password' => env('FARMOS_PASSWORD'),
];
```

## Common Issues and Solutions

### "invalid_client" error
- Check that client_id and client_secret are correct
- Ensure the consumer is saved and enabled in farmOS

### "unsupported_grant_type" error
- Make sure you enabled the grant type in your farmOS consumer settings

### "invalid_scope" error
- Check that the scope exists and is allowed for your consumer
- Try without specifying scope first

### "access_denied" error
- For Password grant: check username/password are correct
- For Client Credentials: ensure the consumer has appropriate permissions

## Usage Example in Controller

```php
<?php

namespace App\Http\Controllers;

use App\Services\FarmOSApiService;
use Illuminate\Http\Request;

class FarmOSController extends Controller
{
    private $farmOS;

    public function __construct(FarmOSApiService $farmOS)
    {
        $this->farmOS = $farmOS;
    }

    public function getLandAssets()
    {
        try {
            $assets = $this->farmOS->getAssets('land');
            return response()->json($assets);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getActivityLogs()
    {
        try {
            $logs = $this->farmOS->getLogs('activity');
            return response()->json($logs);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
```

## Available API Endpoints

### Assets
- `/api/asset/land` - Land assets
- `/api/asset/plant` - Plant assets
- `/api/asset/animal` - Animal assets
- `/api/asset/equipment` - Equipment assets
- `/api/asset/structure` - Structure assets

### Logs
- `/api/log/activity` - Activity logs
- `/api/log/observation` - Observation logs
- `/api/log/seeding` - Seeding logs
- `/api/log/harvest` - Harvest logs
- `/api/log/input` - Input logs
- `/api/log/maintenance` - Maintenance logs

### Other Resources
- `/api/taxonomy_term/crop_family` - Crop families
- `/api/taxonomy_term/plant_type` - Plant types
- `/api/taxonomy_term/animal_type` - Animal types
- `/api/taxonomy_term/log_category` - Log categories

## Testing the Connection

```php
// Simple test to verify connection
public function testConnection()
{
    try {
        $farmOS = new FarmOSApiService();
        $token = $farmOS->getAccessToken();
        
        if ($token) {
            echo "✅ Successfully authenticated with farmOS!";
            
            // Test API call
            $assets = $farmOS->apiRequest('/asset');
            echo "✅ API call successful! Found " . count($assets['data']) . " assets.";
        }
    } catch (\Exception $e) {
        echo "❌ Error: " . $e->getMessage();
    }
}
```

## Notes

1. **Content-Type Headers**: farmOS expects `application/vnd.api+json` for JSON:API requests
2. **Token Caching**: The service automatically caches tokens for 4 minutes (240 seconds)
3. **Error Handling**: Always wrap API calls in try-catch blocks
4. **Rate Limiting**: Be mindful of API rate limits in production
5. **Security**: Never commit credentials to version control
