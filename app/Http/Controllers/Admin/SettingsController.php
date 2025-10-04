<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class SettingsController extends Controller
{
    /**
     * Display the settings page
     */
    public function index()
    {
        // Get current settings from database or defaults
        $settings = $this->getAllSettings();
        
        return view('admin.settings.index', compact('settings'));
    }
    
    /**
     * Update settings
     */
    public function update(Request $request)
    {
        $request->validate([
            'packing_slips_per_page' => 'required|integer|min:1|max:6',
            'auto_print_mode' => 'required|boolean',
            'print_company_logo' => 'required|boolean',
            'default_printer_paper_size' => 'required|string|in:A4,Letter',
            'enable_route_optimization' => 'required|boolean',
            'delivery_time_slots' => 'required|boolean',
            'collection_reminder_hours' => 'required|integer|min:1|max:168',
            'email_notifications' => 'required|boolean',
            // API Key validations
            'farmos_username' => 'nullable|string|max:255',
            'farmos_password' => 'nullable|string|max:255',
            'farmos_oauth_client_id' => 'nullable|string|max:255',
            'farmos_oauth_client_secret' => 'nullable|string|max:255',
            'woocommerce_consumer_key' => 'nullable|string|max:255',
            'woocommerce_consumer_secret' => 'nullable|string|max:255',
            'mwf_api_key' => 'nullable|string|max:255',
            'google_maps_api_key' => 'nullable|string|max:255',
            'met_office_api_key' => 'nullable|string|max:1000',
            'met_office_land_observations_key' => 'nullable|string|max:1000',
            'met_office_site_specific_key' => 'nullable|string|max:1000',
            'met_office_atmospheric_key' => 'nullable|string|max:1000',
            'met_office_map_images_key' => 'nullable|string|max:1000',
            'openweather_api_key' => 'nullable|string|max:255',
            'huggingface_api_key' => 'nullable|string|max:255',
            'stripe_key' => 'nullable|string|max:255',
            'stripe_secret' => 'nullable|string|max:255',
        ]);
        
        // Store settings in database
        $settingsData = [
            'packing_slips_per_page' => [
                'value' => (int) $request->packing_slips_per_page,
                'type' => 'integer',
                'description' => 'Number of packing slips per printed page (1-6)'
            ],
            'auto_print_mode' => [
                'value' => (bool) $request->auto_print_mode,
                'type' => 'boolean',
                'description' => 'Skip print preview and send directly to printer'
            ],
            'print_company_logo' => [
                'value' => (bool) $request->print_company_logo,
                'type' => 'boolean',
                'description' => 'Include company logo on packing slips'
            ],
            'default_printer_paper_size' => [
                'value' => $request->default_printer_paper_size,
                'type' => 'string',
                'description' => 'Default paper size for printing (A4 or Letter)'
            ],
            'enable_route_optimization' => [
                'value' => (bool) $request->enable_route_optimization,
                'type' => 'boolean',
                'description' => 'Enable route planning and optimization features'
            ],
            'delivery_time_slots' => [
                'value' => (bool) $request->delivery_time_slots,
                'type' => 'boolean',
                'description' => 'Enable delivery time slot selection'
            ],
            'collection_reminder_hours' => [
                'value' => (int) $request->collection_reminder_hours,
                'type' => 'integer',
                'description' => 'Hours before collection to send reminder email'
            ],
            'email_notifications' => [
                'value' => (bool) $request->email_notifications,
                'type' => 'boolean',
                'description' => 'Enable email notifications for customers'
            ],
        ];
        
        // Add API keys to settings data (encrypted)
        $apiKeys = [
            'farmos_username' => $request->farmos_username,
            'farmos_password' => $request->farmos_password,
            'farmos_oauth_client_id' => $request->farmos_oauth_client_id,
            'farmos_oauth_client_secret' => $request->farmos_oauth_client_secret,
            'woocommerce_consumer_key' => $request->woocommerce_consumer_key,
            'woocommerce_consumer_secret' => $request->woocommerce_consumer_secret,
            'mwf_api_key' => $request->mwf_api_key,
            'google_maps_api_key' => $request->google_maps_api_key,
            'met_office_api_key' => $request->met_office_api_key,
            'met_office_land_observations_key' => $request->met_office_land_observations_key,
            'met_office_site_specific_key' => $request->met_office_site_specific_key,
            'met_office_atmospheric_key' => $request->met_office_atmospheric_key,
            'met_office_map_images_key' => $request->met_office_map_images_key,
            'openweather_api_key' => $request->openweather_api_key,
            'huggingface_api_key' => $request->huggingface_api_key,
            'stripe_key' => $request->stripe_key,
            'stripe_secret' => $request->stripe_secret,
        ];
        
        foreach ($apiKeys as $key => $value) {
            if ($value !== null) {
                $settingsData[$key] = [
                    'value' => $this->encryptApiKey($value),
                    'type' => 'string',
                    'description' => $this->getApiKeyDescription($key)
                ];
            }
        }
        
        Setting::setMultiple($settingsData);
        
        return redirect()->route('admin.settings')->with('success', 'Settings and API keys updated successfully!');
    }
    
    /**
     * Reset settings to defaults
     */
    public function reset()
    {
        // Reset all settings to defaults by storing the default values in database
        $defaults = $this->getDefaultSettingsWithTypes();
        Setting::setMultiple($defaults);
        
        return redirect()->route('admin.settings')->with('success', 'Settings reset to defaults successfully and saved to database!');
    }
    
    /**
     * Get API endpoint for settings (for JavaScript)
     */
    public function api()
    {
        $settings = $this->getAllSettings();
        
        return response()->json([
            'success' => true,
            'settings' => $settings
        ]);
    }
    
    /**
     * Get default settings
     */
    private function getDefaultSettings()
    {
        return [
            'packing_slips_per_page' => 1,           // 1-6 slips per page
            'auto_print_mode' => true,               // Skip preview, direct to printer
            'print_company_logo' => true,            // Include farm logo on slips
            'default_printer_paper_size' => 'A4',   // A4 or Letter
            'enable_route_optimization' => true,     // Enable route planning features
            'delivery_time_slots' => false,         // Enable time slot selection
            'collection_reminder_hours' => 24,      // Hours before collection to send reminder
            'email_notifications' => true,          // Send email notifications
            'updated_at' => now()->toISOString(),
        ];
    }
    
    /**
     * Get all settings from database or defaults
     */
    private function getAllSettings()
    {
        $defaults = $this->getDefaultSettings();
        $dbSettings = Setting::getAll();
        
        // Merge defaults with database settings, preferring database values
        $settings = array_merge($defaults, $dbSettings);
        
        // Add decrypted API keys to settings
        $apiKeys = self::getAllApiKeys();
        $settings = array_merge($settings, $apiKeys);
        
        // Ensure updated_at is set
        if (!isset($settings['updated_at'])) {
            $settings['updated_at'] = now()->toISOString();
        }
        
        return $settings;
    }
    
    /**
     * Get default settings with type information for database storage
     */
    private function getDefaultSettingsWithTypes()
    {
        return [
            'packing_slips_per_page' => [
                'value' => 1,
                'type' => 'integer',
                'description' => 'Number of packing slips per printed page (1-6)'
            ],
            'auto_print_mode' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Skip print preview and send directly to printer'
            ],
            'print_company_logo' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Include company logo on packing slips'
            ],
            'default_printer_paper_size' => [
                'value' => 'A4',
                'type' => 'string',
                'description' => 'Default paper size for printing (A4 or Letter)'
            ],
            'enable_route_optimization' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Enable route planning and optimization features'
            ],
            'delivery_time_slots' => [
                'value' => false,
                'type' => 'boolean',
                'description' => 'Enable delivery time slot selection'
            ],
            'collection_reminder_hours' => [
                'value' => 24,
                'type' => 'integer',
                'description' => 'Hours before collection to send reminder email'
            ],
            'email_notifications' => [
                'value' => true,
                'type' => 'boolean',
                'description' => 'Enable email notifications for customers'
            ],
        ];
    }
    
    /**
     * Get server performance metrics for monitoring IONOS I/O throttling
     */
    public function serverMetrics()
    {
        try {
            // Add debug logging
            \Log::info('Server metrics requested', [
                'session_authenticated' => Session::get('admin_authenticated', false),
                'session_id' => session()->getId(),
                'ip' => request()->ip()
            ]);
            
            $metrics = [
                'cpu_usage' => $this->getCpuUsage(),
                'memory_usage' => $this->getMemoryUsage(),
                'disk_io_speed' => $this->getDiskIOSpeed(),
                'load_average' => $this->getLoadAverage(),
                'response_time' => $this->getAverageResponseTime(),
                'timestamp' => now()->toISOString(),
                'debug_info' => [
                    'authenticated' => Session::get('admin_authenticated', false),
                    'session_id' => substr(session()->getId(), 0, 8) . '...'
                ]
            ];
            
            return response()->json([
                'success' => true,
                'metrics' => $metrics
            ]);
        } catch (\Exception $e) {
            \Log::error('Server metrics error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Test disk I/O speed to detect IONOS throttling
     */
    public function testIOSpeed()
    {
        try {
            $testFileSize = 10 * 1024 * 1024; // 10MB test file
            $testData = str_repeat('A', $testFileSize);
            $testFile = storage_path('logs/io_speed_test.tmp');
            
            // Test write speed
            $writeStart = microtime(true);
            file_put_contents($testFile, $testData);
            $writeEnd = microtime(true);
            $writeTime = $writeEnd - $writeStart;
            $writeSpeed = round(($testFileSize / 1024 / 1024) / $writeTime, 2);
            
            // Test read speed
            $readStart = microtime(true);
            $readData = file_get_contents($testFile);
            $readEnd = microtime(true);
            $readTime = $readEnd - $readStart;
            $readSpeed = round(($testFileSize / 1024 / 1024) / $readTime, 2);
            
            // Clean up
            @unlink($testFile);
            
            return response()->json([
                'success' => true,
                'write_speed' => $writeSpeed,
                'read_speed' => $readSpeed,
                'test_file_size' => '10MB',
                'write_time' => round($writeTime * 1000, 2) . 'ms',
                'read_time' => round($readTime * 1000, 2) . 'ms'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Test database performance
     */
    public function testDatabasePerformance()
    {
        try {
            $startTime = microtime(true);
            
            // Test connection time
            $connectionStart = microtime(true);
            \DB::connection()->getPdo();
            $connectionTime = round((microtime(true) - $connectionStart) * 1000, 2);
            
            // Test simple queries
            $queryStart = microtime(true);
            $testQueries = 10;
            
            for ($i = 0; $i < $testQueries; $i++) {
                \DB::select('SELECT 1 as test');
            }
            
            $queryEnd = microtime(true);
            $totalQueryTime = round(($queryEnd - $queryStart) * 1000, 2);
            $avgQueryTime = round($totalQueryTime / $testQueries, 2);
            
            return response()->json([
                'success' => true,
                'connection_time' => $connectionTime,
                'query_time' => $totalQueryTime,
                'test_queries' => $testQueries,
                'avg_query_time' => $avgQueryTime
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get CPU usage percentage (IONOS hosting compatible)
     */
    private function getCpuUsage()
    {
        try {
            // Try to get CPU usage by running the CLI script if available
            // Point to the script in the project root
            $cliScript = base_path('cpu_test.php');
            $phpBinary = '/var/www/vhosts/middleworldfarms.org/subdomains/admin/php82';
            $debug = [];
            if (file_exists($cliScript) && is_executable($phpBinary)) {
                $output = [];
                $result = null;
                $cmd = $phpBinary . ' ' . escapeshellarg($cliScript) . ' 2>&1';
                exec($cmd, $output, $result);
                $debug['cmd'] = $cmd;
                $debug['output'] = $output;
                $debug['result'] = $result;
                if (is_array($output) && count($output) > 0) {
                    if (preg_match('/CPU Usage: ([0-9.]+)/', $output[0], $matches)) {
                        return (float)$matches[1];
                    } else {
                        // Log unexpected output
                        \Log::warning('CPU CLI script output did not match expected pattern', $debug);
                        return 'CLI output: ' . implode(' | ', $output) . ' (result: ' . $result . ')';
                    }
                } else {
                    \Log::warning('CPU CLI script returned no output', $debug);
                    return 'No CLI output (result: ' . $result . ')';
                }
            } else {
                $debug['cliScript_exists'] = file_exists($cliScript);
                $debug['phpBinary_executable'] = is_executable($phpBinary);
                \Log::warning('CPU CLI script or PHP binary not available or not executable', $debug);
                return 'CLI script or PHP binary not executable';
            }
            // Fallback: use load average as rough estimate
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                $cpuCount = $this->getCpuCount();
                return round(($load[0] / $cpuCount) * 100, 1);
            }
            return 0;
        } catch (\Exception $e) {
            \Log::warning('CPU usage detection failed: ' . $e->getMessage());
            return 'Exception: ' . $e->getMessage();
        }
    }
    
    /**
     * Get memory usage percentage (IONOS hosting compatible)
     */
    private function getMemoryUsage()
    {
        try {
            // Use PHP's memory functions (these work on shared hosting)
            $memoryLimit = $this->convertToBytes(ini_get('memory_limit'));
            $memoryUsed = memory_get_usage(true);
            
            if ($memoryLimit > 0) {
                return round(($memoryUsed / $memoryLimit) * 100, 1);
            }
            
            // If no memory limit is set, estimate based on peak usage
            $peakMemory = memory_get_peak_usage(true);
            $currentMemory = memory_get_usage(true);
            
            // Assume reasonable default limits based on hosting
            $estimatedLimit = 512 * 1024 * 1024; // 512MB default for shared hosting
            
            return round(($currentMemory / $estimatedLimit) * 100, 1);
            
        } catch (\Exception $e) {
            \Log::warning('Memory usage detection failed: ' . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * Get disk I/O speed (IONOS hosting compatible)
     */
    private function getDiskIOSpeed()
    {
        try {
            // Use a smaller test size for shared hosting
            $testSize = 256 * 1024; // 256KB instead of 1MB
            $testData = str_repeat('X', $testSize);
            
            // Use storage path which should be within allowed paths
            $testFile = storage_path('app/io_test_' . uniqid() . '.tmp');
            
            $start = microtime(true);
            
            // Test write speed
            $writeStart = microtime(true);
            if (file_put_contents($testFile, $testData) === false) {
                throw new \Exception('Write test failed');
            }
            $writeTime = microtime(true) - $writeStart;
            
            // Test read speed
            $readStart = microtime(true);
            if (file_get_contents($testFile) === false) {
                throw new \Exception('Read test failed');
            }
            $readTime = microtime(true) - $readStart;
            
            // Clean up
            @unlink($testFile);
            
            $avgTime = ($writeTime + $readTime) / 2;
            $speed = ($testSize / 1024 / 1024) / $avgTime; // MB/s
            
            return round($speed, 1);
            
        } catch (\Exception $e) {
            \Log::warning('Disk I/O speed test failed: ' . $e->getMessage());
            // Return a reasonable estimate for shared hosting
            return 50.0; // Typical shared hosting I/O speed
        }
    }
    
    /**
     * Get system load average
     */
    private function getLoadAverage()
    {
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            return round($load[0], 2);
        }
        
        // Linux fallback
        if (file_exists('/proc/loadavg')) {
            $load = file_get_contents('/proc/loadavg');
            $load = explode(' ', $load);
            return round((float)$load[0], 2);
        }
        
        return 0;
    }
    
    /**
     * Get average response time (estimated)
     */
    private function getAverageResponseTime()
    {
        // Measure current request processing time
        if (defined('LARAVEL_START')) {
            $currentTime = (microtime(true) - LARAVEL_START) * 1000;
            return round($currentTime, 2);
        }
        
        return 0;
    }
    
    /**
     * Get CPU core count
     */
    private function getCpuCount()
    {
        if (file_exists('/proc/cpuinfo')) {
            $cpuinfo = file_get_contents('/proc/cpuinfo');
            preg_match_all('/^processor/m', $cpuinfo, $matches);
            return count($matches[0]);
        }
        
        return 1; // Default fallback
    }
    
    /**
     * Convert memory string to bytes
     */
    private function convertToBytes($value)
    {
        if (is_numeric($value)) {
            return $value;
        }
        
        $unit = strtolower(substr($value, -1));
        $value = (int) $value;
        
        switch ($unit) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Encrypt API key for secure storage
     */
    private function encryptApiKey(string $key): string
    {
        return encrypt($key);
    }
    
    /**
     * Decrypt API key for use
     */
    private function decryptApiKey(string $encryptedKey): string
    {
        try {
            return decrypt($encryptedKey);
        } catch (\Exception $e) {
            // If decryption fails, return empty string
            return '';
        }
    }
    
    /**
     * Get API key description
     */
    private function getApiKeyDescription(string $key): string
    {
        $descriptions = [
            'farmos_username' => 'FarmOS admin username for API authentication',
            'farmos_password' => 'FarmOS admin password for API authentication',
            'farmos_oauth_client_id' => 'FarmOS OAuth2 client ID for API access',
            'farmos_oauth_client_secret' => 'FarmOS OAuth2 client secret for API access',
            'woocommerce_consumer_key' => 'WooCommerce REST API consumer key',
            'woocommerce_consumer_secret' => 'WooCommerce REST API consumer secret',
            'mwf_api_key' => 'Middle World Farms integration API key',
            'google_maps_api_key' => 'Google Maps JavaScript API key',
            'met_office_api_key' => 'UK Met Office Weather API key',
            'met_office_land_observations_key' => 'Met Office Land Observations API key for soil moisture and temperature data',
            'met_office_site_specific_key' => 'Met Office Site-Specific Forecast API key for detailed local weather',
            'met_office_atmospheric_key' => 'Met Office Atmospheric Models API key for weather model data',
            'met_office_map_images_key' => 'Met Office Map Images API key for weather radar and satellite imagery',
            'openweather_api_key' => 'OpenWeatherMap API key',
            'huggingface_api_key' => 'Hugging Face Inference API key',
            'stripe_key' => 'Stripe publishable key (pk_...)',
            'stripe_secret' => 'Stripe secret key (sk_...)',
        ];
        
        return $descriptions[$key] ?? 'API key for external service integration';
    }
    
    /**
     * Get decrypted API key from database
     */
    public static function getApiKey(string $key): string
    {
        $encryptedKey = Setting::get($key, '');
        if (empty($encryptedKey)) {
            return '';
        }
        
        try {
            return decrypt($encryptedKey);
        } catch (\Exception $e) {
            return '';
        }
    }
    
    /**
     * Get all API keys as decrypted values
     */
    public static function getAllApiKeys(): array
    {
        $apiKeyFields = [
            'farmos_username',
            'farmos_password', 
            'farmos_oauth_client_id',
            'farmos_oauth_client_secret',
            'woocommerce_consumer_key',
            'woocommerce_consumer_secret',
            'mwf_api_key',
            'google_maps_api_key',
            'met_office_api_key',
            'openweather_api_key',
            'huggingface_api_key',
            'stripe_key',
            'stripe_secret',
        ];
        
        $apiKeys = [];
        foreach ($apiKeyFields as $field) {
            $apiKeys[$field] = self::getApiKey($field);
        }
        
        return $apiKeys;
    }
}

