<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Service for building farmOS Quick Form URLs with pre-populated data
 */
class FarmOSQuickFormService
{
    protected $farmOSBaseUrl;
    protected $authToken;

    public function __construct()
    {
        // Use reverse proxy URL for Quick Forms to avoid CORS issues
        $this->farmOSBaseUrl = config('app.url', 'https://admin.middleworldfarms.org:8444') . '/farmos';
        $this->authToken = session('farmos_token'); // Get from session
    }

    /**
     * Build Quick Form URL for a succession planting
     */
    public function buildSuccessionFormUrl(array $successionData, string $logType = 'seeding'): string
    {
        $baseUrl = $this->getQuickFormBaseUrl($logType);

        $parameters = $this->formatParametersForFarmOS($successionData, $logType);

        return $baseUrl . '?' . http_build_query($parameters);
    }

    /**
     * Get the base URL for different Quick Form types
     */
    protected function getQuickFormBaseUrl(string $logType): string
    {
        // Check if Quick Forms are available, otherwise use standard log forms
        $quickFormAvailable = $this->testQuickFormAccess();

        if ($quickFormAvailable) {
            // Allow overrides from config to match your farmOS quick form module paths
            $formUrls = config('services.farmos.quick_form_paths', [
                'seeding' => '/quick/seeding',
                'transplant' => '/quick/transplant',
                'harvest' => '/quick/harvest',
                'observation' => '/quick/observation',
            ]);

            return $this->farmOSBaseUrl . ($formUrls[$logType] ?? '/quick/seeding');
        } else {
            // Fallback to standard farmOS log forms
            $standardUrls = [
                'seeding' => '/log/add/seeding',
                'transplant' => '/log/add/transplanting',
                'harvest' => '/log/add/harvest',
                'observation' => '/log/add/observation',
            ];

            return $this->farmOSBaseUrl . ($standardUrls[$logType] ?? '/log/add/seeding');
        }
    }

    /**
     * Format succession data for farmOS Quick Form parameters
     */
    protected function formatParametersForFarmOS(array $successionData, string $logType): array
    {
        $parameters = [];

        // Common parameters for all log types
        if (isset($successionData['crop_name'])) {
            $parameters['crop'] = $successionData['crop_name'];
        }

        if (isset($successionData['variety_name'])) {
            $parameters['variety'] = $successionData['variety_name'];
        }

        if (isset($successionData['bed_name'])) {
            $parameters['location'] = $successionData['bed_name'];
        }

        if (isset($successionData['quantity'])) {
            $parameters['quantity'] = $successionData['quantity'];
        }

        // Log type specific parameters
        switch ($logType) {
            case 'seeding':
                if (isset($successionData['seeding_date'])) {
                    $parameters['date'] = $successionData['seeding_date'];
                }
                $parameters['notes'] = "AI-calculated seeding for succession #" . ($successionData['succession_number'] ?? '1');
                break;

            case 'transplant':
                if (isset($successionData['transplant_date'])) {
                    $parameters['date'] = $successionData['transplant_date'];
                }
                $parameters['notes'] = "AI-calculated transplant for succession #" . ($successionData['succession_number'] ?? '1');
                break;

            case 'harvest':
                if (isset($successionData['harvest_date'])) {
                    $parameters['date'] = $successionData['harvest_date'];
                }
                $parameters['notes'] = "AI-calculated harvest for succession #" . ($successionData['succession_number'] ?? '1');
                break;
        }

        // Add authentication token if available
        if ($this->authToken) {
            $parameters['token'] = $this->authToken;
        }

        return $parameters;
    }

    /**
     * Generate URLs for all log types for a succession
     */
    public function generateAllFormUrls(array $successionData): array
    {
        return [
            'seeding' => $this->buildSuccessionFormUrl($successionData, 'seeding'),
            'transplant' => $this->buildSuccessionFormUrl($successionData, 'transplant'),
            'harvest' => $this->buildSuccessionFormUrl($successionData, 'harvest'),
        ];
    }

    /**
     * Test if Quick Forms are accessible
     */
    public function testQuickFormAccess(): bool
    {
        try {
            // First check if Quick Forms API returns any data
            $response = Http::withToken($this->authToken)
                ->get($this->farmOSBaseUrl . '/api/quick_form/quick_form');

            if ($response->successful()) {
                $data = $response->json();
                // If data array is not empty, Quick Forms are configured
                return !empty($data['data'] ?? []);
            }

            // Fallback: test if quick form URLs are accessible
            $response = Http::withToken($this->authToken)
                ->get($this->farmOSBaseUrl . '/quick/seeding');

            return $response->successful();
        } catch (\Exception $e) {
            \Log::warning('Quick Form access test failed: ' . $e->getMessage());
            return false;
        }
    }
}
