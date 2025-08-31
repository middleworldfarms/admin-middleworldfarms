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
        $this->farmOSBaseUrl = config('services.farmos.url', 'https://farmos.middleworldfarms.org');
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
        $formUrls = [
            'seeding' => '/quick/seeding',
            'transplant' => '/quick/transplant',
            'harvest' => '/quick/harvest',
            'observation' => '/quick/observation',
        ];

        return $this->farmOSBaseUrl . ($formUrls[$logType] ?? '/quick/seeding');
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
                $parameters['notes'] = "AI-calculated seeding for succession #{$successionData['succession_number']}";
                break;

            case 'transplant':
                if (isset($successionData['transplant_date'])) {
                    $parameters['date'] = $successionData['transplant_date'];
                }
                $parameters['notes'] = "AI-calculated transplant for succession #{$successionData['succession_number']}";
                break;

            case 'harvest':
                if (isset($successionData['harvest_date'])) {
                    $parameters['date'] = $successionData['harvest_date'];
                }
                $parameters['notes'] = "AI-calculated harvest for succession #{$successionData['succession_number']}";
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
            $response = Http::withToken($this->authToken)
                ->get($this->farmOSBaseUrl . '/quick/seeding');

            return $response->successful();
        } catch (\Exception $e) {
            \Log::warning('Quick Form access test failed: ' . $e->getMessage());
            return false;
        }
    }
}
