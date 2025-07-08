<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\RouteOptimizationService;
use App\Services\DeliveryScheduleService;
use App\Services\DriverNotificationService;
use App\Services\WPGoMapsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RouteController extends Controller
{
    private $routeService;
    private $deliveryService;
    private $driverService;
    private $wpGoMapsService;

    public function __construct(
        RouteOptimizationService $routeService,
        DeliveryScheduleService $deliveryService,
        DriverNotificationService $driverService,
        WPGoMapsService $wpGoMapsService
    ) {
        $this->routeService = $routeService;
        $this->deliveryService = $deliveryService;
        $this->driverService = $driverService;
        $this->wpGoMapsService = $wpGoMapsService;
    }

    /**
     * Display route planning page
     */
    public function index(Request $request)
    {
        try {
            // Get delivery date from request or default to today
            $deliveryDate = $request->get('date', date('Y-m-d'));
            
            // Check if deliveries were passed via request (from the deliveries page)
            $deliveries = [];
            
            // Method 1: Check if deliveries are passed in the request
            if ($request->has('deliveries')) {
                $deliveries = $request->get('deliveries', []);
            }
            
            // Method 2: Check if we're in bulk selection mode
            if ($request->get('selected') === 'bulk') {
                // Deliveries will be loaded from localStorage by JavaScript
                return view('admin.routes.index', [
                    'deliveries' => [],
                    'delivery_date' => $deliveryDate,
                    'google_maps_key' => config('services.google_maps.api_key'),
                    'bulk_mode' => true
                ]);
            }
            
            // Method 3: If no deliveries passed, get all deliveries for today using WpApiService
            if (empty($deliveries)) {
                $wpApi = app(\App\Services\WpApiService::class);
                $scheduleData = $wpApi->getDeliveryScheduleData(100);
                
                // Extract deliveries only (not collections) and format them for routing
                $allDeliveries = $scheduleData['deliveries'] ?? [];
                
                // Filter and format deliveries for routing
                foreach ($allDeliveries as $delivery) {
                    $deliveries[] = [
                        'id' => $delivery['id'] ?? '',
                        'name' => $delivery['customer_name'] ?? 'Unknown Customer',
                        'address' => $this->formatDeliveryAddressForRouting($delivery),
                        'phone' => $delivery['customer_phone'] ?? '',
                        'email' => $delivery['customer_email'] ?? '',
                        'products' => $delivery['products'] ?? [],
                        'notes' => $delivery['notes'] ?? ''
                    ];
                }
            }
            
            return view('admin.routes.index', [
                'deliveries' => $deliveries,
                'delivery_date' => $deliveryDate,
                'google_maps_key' => config('services.google_maps.api_key'),
                'bulk_mode' => false
            ]);

        } catch (\Exception $e) {
            Log::error('Route planning page failed: ' . $e->getMessage());
            
            return view('admin.routes.index', [
                'deliveries' => [],
                'error' => 'Failed to load deliveries: ' . $e->getMessage(),
                'delivery_date' => $deliveryDate ?? date('Y-m-d'),
                'google_maps_key' => config('services.google_maps.api_key'),
                'bulk_mode' => false
            ]);
        }
    }

    /**
     * Optimize route for given deliveries
     */
    public function optimize(Request $request)
    {
        try {
            $deliveries = $request->input('deliveries', []);
            $startLocation = $request->input('start_location');
            
            if (empty($deliveries)) {
                return response()->json([
                    'success' => false,
                    'error' => 'No deliveries provided'
                ], 400);
            }

            // Optimize the route
            $result = $this->routeService->optimizeRoute($deliveries, $startLocation);
            
            if (isset($result['error'])) {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }

            return response()->json([
                'success' => true,
                'optimized_deliveries' => $result['optimized_deliveries'],
                'route_details' => $result['route_details'],
                'total_distance' => $result['total_distance'],
                'total_duration' => $result['total_duration'],
                'polyline' => $result['route_details']['polyline'] ?? '',
                'bounds' => $result['route_details']['bounds'] ?? null
            ]);

        } catch (\Exception $e) {
            Log::error('Route optimization failed: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Route optimization failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send route to driver via email
     */
    public function sendToDriver(Request $request)
    {
        try {
            $request->validate([
                'driver_email' => 'required|email',
                'deliveries' => 'required|array',
                'route_details' => 'required|array'
            ]);

            $result = $this->driverService->sendRouteByEmail(
                $request->input('driver_email'),
                $request->input('deliveries'),
                $request->input('route_details'),
                $request->input('delivery_date')
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Route sent to driver successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send route to driver: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to send route: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send route to driver via SMS
     */
    public function sendToDriverSMS(Request $request)
    {
        try {
            $request->validate([
                'driver_phone' => 'required|string',
                'deliveries' => 'required|array',
                'route_details' => 'required|array'
            ]);

            $result = $this->driverService->sendRouteBySMS(
                $request->input('driver_phone'),
                $request->input('deliveries'),
                $request->input('route_details'),
                $request->input('delivery_date')
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Route sent to driver via SMS successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Failed to send SMS to driver: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to send SMS: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get map data for frontend
     */
    public function getMapData(Request $request)
    {
        try {
            $deliveries = $request->input('deliveries', []);
            
            $mapData = [
                'markers' => [],
                'center' => ['lat' => 53.2307, 'lng' => -0.5406], // Lincoln, UK center
                'zoom' => 10
            ];

            // Add depot marker
            $depotAddress = config('services.delivery.depot_address', 'Middle World Farms, Bradney Road, Washingborough, Lincoln, LN4 1AQ, UK');
            $depotCoords = $this->routeService->geocodeAddress($depotAddress);
            
            if ($depotCoords) {
                $mapData['markers'][] = [
                    'type' => 'depot',
                    'position' => ['lat' => $depotCoords['lat'], 'lng' => $depotCoords['lng']],
                    'title' => 'Middle World Farms (Depot)',
                    'icon' => 'depot'
                ];
                $mapData['center'] = ['lat' => $depotCoords['lat'], 'lng' => $depotCoords['lng']];
            }

            // Add delivery markers
            foreach ($deliveries as $index => $delivery) {
                $address = $this->formatDeliveryAddress($delivery);
                if ($address) {
                    $coords = $this->routeService->geocodeAddress($address);
                    if ($coords) {
                        $mapData['markers'][] = [
                            'type' => 'delivery',
                            'position' => ['lat' => $coords['lat'], 'lng' => $coords['lng']],
                            'title' => ($delivery['name'] ?? 'Customer') . ' - ' . $address,
                            'info' => [
                                'customer' => $delivery['name'] ?? 'Unknown',
                                'address' => $coords['formatted_address'],
                                'products' => $delivery['products'] ?? [],
                                'order_number' => $index + 1
                            ],
                            'icon' => 'delivery'
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'map_data' => $mapData
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get map data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get map data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create shareable WP Go Maps route for drivers
     */
    public function createShareableMap(Request $request)
    {
        try {
            $request->validate([
                'deliveries' => 'required|array',
                'route_details' => 'required|array',
                'delivery_date' => 'required|date'
            ]);

            $result = $this->wpGoMapsService->createDeliveryRouteMap(
                $request->input('deliveries'),
                $request->input('route_details'),
                $request->input('delivery_date')
            );

            if ($result['success']) {
                // Also export to WordPress for record keeping
                $exportResult = $this->wpGoMapsService->exportRouteForWordPress(
                    $request->input('deliveries'),
                    $request->input('route_details'),
                    $request->input('delivery_date')
                );

                return response()->json([
                    'success' => true,
                    'map_id' => $result['map_id'],
                    'shortcode' => $result['shortcode'],
                    'shareable_link' => $this->wpGoMapsService->createShareableMapLink($result['map_id']),
                    'wordpress_export' => $exportResult,
                    'message' => 'Shareable map created successfully'
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 400);
            }

        } catch (\Exception $e) {
            Log::error('Failed to create shareable map: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to create shareable map: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get WP Go Maps data for enhanced route planning
     */
    public function getWPGoMapsData(Request $request)
    {
        try {
            $customerEmail = $request->input('customer_email');
            
            if ($customerEmail) {
                // Get specific customer location data
                $locationData = $this->wpGoMapsService->getCustomerLocationData($customerEmail);
                return response()->json($locationData);
            } else {
                // Get all available maps
                $mapsData = $this->wpGoMapsService->getExistingMaps();
                return response()->json($mapsData);
            }

        } catch (\Exception $e) {
            Log::error('Failed to get WP Go Maps data: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Failed to get WP Go Maps data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract deliveries for a specific date
     */
    private function getDeliveriesForDate($scheduleData, $date)
    {
        if (!isset($scheduleData['data'][$date])) {
            return [];
        }

        $deliveries = $scheduleData['data'][$date]['deliveries'] ?? [];
        $collections = $scheduleData['data'][$date]['collections'] ?? [];
        
        // Combine deliveries and collections
        return array_merge($deliveries, $collections);
    }

    /**
     * Format delivery address for geocoding
     */
    private function formatDeliveryAddress($delivery)
    {
        if (isset($delivery['address']) && is_array($delivery['address'])) {
            return implode(', ', array_filter($delivery['address']));
        } elseif (isset($delivery['address']) && is_string($delivery['address'])) {
            return $delivery['address'];
        }
        
        return null;
    }

    /**
     * Format delivery address for routing display
     */
    private function formatDeliveryAddressForRouting($delivery)
    {
        // Try billing address first
        if (isset($delivery['billing']) && is_array($delivery['billing'])) {
            $address = $delivery['billing'];
            $parts = array_filter([
                $address['address_1'] ?? '',
                $address['address_2'] ?? '',
                $address['city'] ?? '',
                $address['state'] ?? '',
                $address['postcode'] ?? '',
                $address['country'] ?? ''
            ]);
            
            if (!empty($parts)) {
                return implode(', ', $parts);
            }
        }
        
        // Try shipping address as fallback
        if (isset($delivery['shipping']) && is_array($delivery['shipping'])) {
            $address = $delivery['shipping'];
            $parts = array_filter([
                $address['address_1'] ?? '',
                $address['address_2'] ?? '',
                $address['city'] ?? '',
                $address['state'] ?? '',
                $address['postcode'] ?? '',
                $address['country'] ?? ''
            ]);
            
            if (!empty($parts)) {
                return implode(', ', $parts);
            }
        }
        
        // Try direct address field
        if (isset($delivery['address'])) {
            if (is_string($delivery['address'])) {
                return $delivery['address'];
            } elseif (is_array($delivery['address'])) {
                return implode(', ', array_filter($delivery['address']));
            }
        }
        
        return 'Address not available';
    }
}
