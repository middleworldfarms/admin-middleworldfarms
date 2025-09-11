<?php

// Load environment variables manually
function loadEnv($path) {
    if (!file_exists($path)) {
        return false;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) {
            continue;
        }

        list($name, $value) = explode('=', $line, 2);
        $name = trim($name);
        $value = trim($value);

        if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
            putenv(sprintf('%s=%s', $name, $value));
            $_ENV[$name] = $value;
            $_SERVER[$name] = $value;
        }
    }
    return true;
}

loadEnv(__DIR__ . '/.env');

echo "🔍 Testing Acronis API Connection\n";
echo "================================\n\n";

class StandaloneIonosBackupService
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private int $timeout = 30;

    public function __construct()
    {
        // Use the local Acronis agent (aakore) on the correct port
        $this->baseUrl = 'http://127.0.0.1:45943';  // Correct aakore port
        $this->username = getenv('IONOS_USERNAME') ?: 'NGCS_2F5B4_8082.admin';
        $this->password = getenv('IONOS_PASSWORD') ?: 'I6quJc8Qp2';
    }

    private function authenticate(): ?string
    {
        try {
            $ch = curl_init();

            // Use client credentials flow with the discovered OAuth credentials
            $postData = http_build_query([
                'grant_type' => 'client_credentials',
                'client_id' => 'b09c0375-5f26-4a0c-afdc-141bf72fe0a4',
                'client_secret' => '5mctonhs5hfjh45gb4hxzdqnmybcskvkyg6g35vdbnjkpgfgfoyq',
                'scope' => 'urn:acronis.com:iam:access-token-scope:management',
            ]);

            curl_setopt_array($ch, [
                CURLOPT_URL => "{$this->baseUrl}/idp/token",
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $postData,
                CURLOPT_HTTPHEADER => [
                    'Content-Type: application/x-www-form-urlencoded',
                    'Accept: application/json',
                ],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
                CURLOPT_CAINFO => '/opt/acronis/var/aakore/cert_bundle.pem', // Use Acronis certificate
            ]);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                echo "❌ Authentication cURL Error: {$error}\n";
                return null;
            }

            if ($httpCode === 200) {
                $data = json_decode($response, true);
                return $data['access_token'] ?? null;
            }

            echo "❌ Authentication failed (HTTP {$httpCode}): {$response}\n";
            return null;
        } catch (\Exception $e) {
            echo "❌ Authentication error: " . $e->getMessage() . "\n";
            return null;
        }
    }

    private function makeApiRequest(string $method, string $endpoint, array $data = []): ?array
    {
        $token = $this->authenticate();

        if (!$token) {
            return null;
        }

        try {
            $ch = curl_init();
            $url = "{$this->baseUrl}{$endpoint}";

            $headers = [
                'Content-Type: application/json',
                'Accept: application/json',
            ];

            // Only add authorization header if we have a real token (not the dummy local one)
            if ($token !== 'local_agent_token') {
                $headers[] = 'Authorization: Bearer ' . $token;
            }

            $curlOptions = [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CAINFO => '/opt/acronis/var/aakore/cert_bundle.pem', // Use Acronis certificate
            ];

            if ($method === 'POST') {
                $curlOptions[CURLOPT_POST] = true;
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data);
            } elseif ($method === 'PUT') {
                $curlOptions[CURLOPT_CUSTOMREQUEST] = 'PUT';
                $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data);
            } elseif ($method === 'DELETE') {
                $curlOptions[CURLOPT_CUSTOMREQUEST] = 'DELETE';
            }

            curl_setopt_array($ch, $curlOptions);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);

            if ($error) {
                echo "❌ API Request cURL Error: {$error}\n";
                return null;
            }

            if ($httpCode >= 200 && $httpCode < 300) {
                return json_decode($response, true);
            }

            echo "❌ API Request failed (HTTP {$httpCode}): {$response}\n";
            return null;
        } catch (\Exception $e) {
            echo "❌ API Request error: " . $e->getMessage() . "\n";
            return null;
        }
    }

    public function getBackupUnits(): ?array
    {
        return $this->makeApiRequest('GET', '/api/agent/v1/units/');
    }

    public function testConnection(): array
    {
        echo "� Testing authentication...\n";
        $token = $this->authenticate();

        if (!$token) {
            return [
                'success' => false,
                'message' => 'Authentication failed',
                'details' => 'Unable to obtain access token'
            ];
        }

        echo "✅ Authentication successful\n";
        echo "📡 Testing backup units API...\n";

        $units = $this->getBackupUnits();

        return [
            'success' => $units !== null,
            'message' => $units !== null ? 'Connection successful' : 'API request failed',
            'details' => $units !== null ? 'Retrieved backup units successfully' : 'Failed to retrieve backup units',
            'data' => $units
        ];
    }
}

try {
    $backupService = new StandaloneIonosBackupService();

    echo "📡 Testing API connectivity...\n";
    $testResult = $backupService->testConnection();

    if ($testResult['success']) {
        echo "✅ Connection successful!\n";
        echo "📊 Retrieved " . count($testResult['data'] ?? []) . " backup units\n";

        if (!empty($testResult['data'])) {
            echo "\n📋 Backup Units Found:\n";
            foreach ($testResult['data'] as $unit) {
                echo "  - " . ($unit['name'] ?? 'Unnamed Unit') . " (ID: " . ($unit['id'] ?? 'N/A') . ")\n";
            }
        }
    } else {
        echo "❌ Connection failed: " . $testResult['message'] . "\n";
        echo "Details: " . $testResult['details'] . "\n";
    }

    echo "\n📈 System Status:\n";
    echo "  Host: {$backupService->baseUrl}\n";
    echo "  User: {$backupService->username}\n";

} catch (Exception $e) {
    echo "❌ Test failed with error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n🏁 Test completed.\n";
