<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FarmOSApiService;
use App\Models\HarvestLog;
use App\Models\StockItem;
use App\Models\CropPlan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class FarmOSDataController extends Controller
{
    protected $farmOSApi;

    public function __construct(FarmOSApiService $farmOSApi)
    {
        $this->farmOSApi = $farmOSApi;
        
        // Debug: Verify service is properly injected
        if (!method_exists($this->farmOSApi, 'getCropPlanningData')) {
            Log::error('FarmOSApiService is missing getCropPlanningData method. Service class: ' . get_class($this->farmOSApi));
            // Re-instantiate the service as fallback
            $this->farmOSApi = new FarmOSApiService();
        }
    }

    /**
     * Display the main FarmOS dashboard
     */
    public function index()
    {
        try {
            // Debug: Check service instance and method
            Log::info('FarmOS Controller Debug - Service class: ' . get_class($this->farmOSApi));
            Log::info('FarmOS Controller Debug - getCropPlanningData method exists: ' . (method_exists($this->farmOSApi, 'getCropPlanningData') ? 'Yes' : 'No'));
            
            // Get real farmOS data
            $farmOSCropPlans = $this->farmOSApi->getCropPlanningData();
            $farmOSHarvests = $this->farmOSApi->getHarvestLogs();
            
            // Use farmOS data for statistics
            $cropPlans = collect($farmOSCropPlans);
            
            $stats = [
                'recent_harvests' => 0, // farmOS harvests would need date filtering
                'unsynced_harvests' => 0, // Not applicable for farmOS direct integration
                'total_stock_items' => 0, // Would need to fetch stock from farmOS
                'low_stock_items' => 0, // Would need stock levels from farmOS
                'active_crop_plans' => $cropPlans->whereNotIn('status', ['completed', 'cancelled'])->count(),
            ];

            // Use farmOS crop plans for upcoming harvests
            $upcomingHarvests = $cropPlans->filter(function($plan) {
                return !empty($plan['planned_harvest_start']) && 
                       !in_array($plan['status'], ['completed', 'cancelled']) &&
                       strtotime($plan['planned_harvest_start']) >= time() &&
                       strtotime($plan['planned_harvest_start']) <= strtotime('+14 days');
            })->take(10);

            // Use farmOS data but format it for the dashboard
            $recentHarvests = collect($farmOSHarvests)->map(function($harvest) {
                // Convert farmOS harvest data to expected format
                if (is_array($harvest)) {
                    return (object) [
                        'crop_name' => $harvest['crop_name'] ?? $harvest['name'] ?? 'Unknown Crop',
                        'crop_type' => $harvest['crop_type'] ?? null,
                        'formatted_quantity' => $harvest['formatted_quantity'] ?? $harvest['quantity'] ?? 'N/A',
                        'harvest_date' => isset($harvest['harvest_date']) ? 
                            (is_string($harvest['harvest_date']) ? date('Y-m-d', strtotime($harvest['harvest_date'])) : $harvest['harvest_date']) :
                            date('Y-m-d'),
                        'is_today' => isset($harvest['harvest_date']) ? 
                            date('Y-m-d', strtotime($harvest['harvest_date'])) === date('Y-m-d') : false,
                        'synced_to_stock' => $harvest['synced_to_stock'] ?? false
                    ];
                }
                return $harvest; // If already an object, return as-is
            })->take(10);
            $lowStockItems = collect([]);

            // Check if we have live farmOS data
            $hasRealData = !empty($farmOSCropPlans) || !empty($farmOSHarvests);
            $hasTestData = false; // No test data - we want live farmOS data
            $usingFarmOSData = $hasRealData;

            return view('admin.farmos.dashboard', compact(
                'stats',
                'recentHarvests', 
                'lowStockItems',
                'upcomingHarvests',
                'hasTestData',
                'usingFarmOSData'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to fetch farmOS dashboard data: ' . $e->getMessage());
            
            // Fallback to local database if farmOS fails
            $hasTestData = HarvestLog::where('crop_name', 'LIKE', 'TEST -%')->exists() ||
                          StockItem::where('crop_name', 'LIKE', 'TEST -%')->exists() ||
                          CropPlan::where('crop_name', 'LIKE', 'TEST -%')->exists();

            $stats = [
                'recent_harvests' => HarvestLog::where('harvest_date', '>=', now()->subDays(7))->count(),
                'unsynced_harvests' => HarvestLog::where('synced_to_stock', false)->count(),
                'total_stock_items' => StockItem::count(),
                'low_stock_items' => StockItem::whereRaw('current_stock <= minimum_stock')->count(),
                'active_crop_plans' => CropPlan::whereNotIn('status', ['completed', 'cancelled'])->count(),
            ];

            $recentHarvests = HarvestLog::orderBy('harvest_date', 'desc')
                ->limit(10)
                ->get();

            $lowStockItems = StockItem::whereRaw('current_stock <= minimum_stock')
                ->orderBy('current_stock', 'asc')
                ->limit(10)
                ->get();

            $upcomingHarvests = CropPlan::whereNotIn('status', ['completed', 'cancelled'])
                ->where('planned_harvest_start', '>=', now())
                ->where('planned_harvest_start', '<=', now()->addDays(14))
                ->orderBy('planned_harvest_start', 'asc')
                ->limit(10)
                ->get();

            $usingFarmOSData = false;

            return view('admin.farmos.dashboard', compact(
                'stats',
                'recentHarvests', 
                'lowStockItems',
                'upcomingHarvests',
                'hasTestData',
                'usingFarmOSData'
            ));
        }
    }

    /**
     * Display harvest logs
     */
    public function harvests(Request $request)
    {
        try {
            // Get real farmOS harvest logs
            $farmOSHarvests = $this->farmOSApi->getHarvestLogs();
            
            // Convert to collection for filtering
            $harvestLogs = collect();
            if (isset($farmOSHarvests['data']) && is_array($farmOSHarvests['data'])) {
                foreach ($farmOSHarvests['data'] as $harvestData) {
                    $harvestLogs->push((object)[
                        'id' => $harvestData['id'],
                        'farmos_id' => $harvestData['id'],
                        'crop_name' => $this->extractCropName($harvestData),
                        'crop_type' => $this->extractCropType($harvestData),
                        'quantity' => $this->extractQuantity($harvestData),
                        'units' => $this->extractUnits($harvestData),
                        'harvest_date' => \Carbon\Carbon::parse($harvestData['attributes']['timestamp'] ?? now()),
                        'location' => $this->extractLocation($harvestData),
                        'notes' => $harvestData['attributes']['notes']['value'] ?? '',
                        'status' => $harvestData['attributes']['status'] ?? 'done',
                        'synced_to_stock' => false, // farmOS logs aren't synced to local stock
                        'farmos_data' => $harvestData,
                        'formatted_quantity' => $this->extractQuantity($harvestData) . ' ' . $this->extractUnits($harvestData),
                        'is_today' => \Carbon\Carbon::parse($harvestData['attributes']['timestamp'] ?? now())->isToday()
                    ]);
                }
            }

            // Apply filters
            if ($request->filled('crop_type')) {
                $harvestLogs = $harvestLogs->where('crop_type', $request->crop_type);
            }

            if ($request->filled('date_from')) {
                $harvestLogs = $harvestLogs->filter(function($harvest) use ($request) {
                    return $harvest->harvest_date->gte(\Carbon\Carbon::parse($request->date_from));
                });
            }

            if ($request->filled('date_to')) {
                $harvestLogs = $harvestLogs->filter(function($harvest) use ($request) {
                    return $harvest->harvest_date->lte(\Carbon\Carbon::parse($request->date_to));
                });
            }

            // Sort by harvest date descending
            $harvestLogs = $harvestLogs->sortByDesc('harvest_date');

            // Get unique crop types for filter dropdown (from farmOS)
            $cropTypes = $this->farmOSApi->getAvailableCropTypes();

            $farmosBaseUrl = config('farmos.url', 'https://farmos.middleworldfarms.org');
            $usingFarmOSData = true;

            // Convert to paginated-like structure for view compatibility
            // Since it's a collection, we'll slice it manually for pagination
            $perPage = 50;
            $currentPage = $request->get('page', 1);
            $total = $harvestLogs->count();
            $offset = ($currentPage - 1) * $perPage;
            $paginatedHarvests = $harvestLogs->slice($offset, $perPage)->values();

            // Create a mock paginator-like object
            $harvestLogs = new \Illuminate\Pagination\LengthAwarePaginator(
                $paginatedHarvests,
                $total,
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'pageName' => 'page']
            );

            return view('admin.farmos.harvests', compact(
                'harvestLogs', 
                'cropTypes', 
                'farmosBaseUrl', 
                'usingFarmOSData'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to fetch farmOS harvest logs: ' . $e->getMessage());
            
            // Fallback to local database
            $query = HarvestLog::with('stockItem');

            if ($request->filled('crop_type')) {
                $query->where('crop_type', $request->crop_type);
            }

            if ($request->filled('date_from')) {
                $query->where('harvest_date', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->where('harvest_date', '<=', $request->date_to);
            }

            $harvestLogs = $query->orderBy('harvest_date', 'desc')
                ->paginate(50);

            $cropTypes = HarvestLog::distinct()
                ->pluck('crop_type')
                ->filter()
                ->sort()
                ->values();

            $farmosBaseUrl = config('farmos.url', 'https://farmos.middleworldfarms.org');
            $usingFarmOSData = false;

            return view('admin.farmos.harvests', compact(
                'harvestLogs', 
                'cropTypes', 
                'farmosBaseUrl', 
                'usingFarmOSData'
            ));
        }
    }

    /**
     * Display stock management
     */
    public function stock(Request $request)
    {
        try {
            // For now, stock management still uses local database since farmOS doesn't have a direct stock API
            // But we'll get crop types and locations from farmOS for consistency
            $farmOSCropTypes = $this->farmOSApi->getAvailableCropTypes();
            $farmOSLocations = $this->farmOSApi->getAvailableLocations();

            $query = StockItem::query();

            if ($request->filled('crop_type')) {
                $query->where('crop_type', $request->crop_type);
            }

            if ($request->filled('status')) {
                switch ($request->status) {
                    case 'in_stock':
                        $query->where('current_stock', '>', 0);
                        break;
                    case 'low_stock':
                        $query->whereRaw('current_stock <= minimum_stock AND current_stock > 0');
                        break;
                    case 'out_of_stock':
                        $query->where('current_stock', '<=', 0);
                        break;
                }
            }

            if ($request->filled('location')) {
                $query->where('location', $request->location);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('crop_type', 'like', "%{$search}%")
                      ->orWhere('variety', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            $stockItems = $query->orderBy('crop_type')
                ->paginate(50);

            // Use farmOS data for dropdowns, fallback to local if needed
            $cropTypes = !empty($farmOSCropTypes) ? $farmOSCropTypes : 
                StockItem::distinct()->pluck('crop_type')->filter()->sort()->values();
            
            $locations = !empty($farmOSLocations) ? $farmOSLocations :
                StockItem::distinct()->pluck('storage_location')->filter()->sort()->values();

            // Calculate stock statistics
            $stockStats = [
                'total_items' => StockItem::count(),
                'items_in_stock' => StockItem::where('current_stock', '>', 0)->count(),
                'low_stock_items' => StockItem::whereRaw('current_stock <= minimum_stock AND current_stock > 0')->count(),
                'out_of_stock_items' => StockItem::where('current_stock', '<=', 0)->count(),
            ];

            $usingFarmOSData = !empty($farmOSCropTypes) && !empty($farmOSLocations);

            return view('admin.farmos.stock', compact(
                'stockItems', 
                'cropTypes', 
                'locations', 
                'stockStats',
                'usingFarmOSData'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to fetch farmOS data for stock management: ' . $e->getMessage());
            
            // Complete fallback to local database
            $query = StockItem::query();

            if ($request->filled('crop_type')) {
                $query->where('crop_type', $request->crop_type);
            }

            if ($request->filled('status')) {
                switch ($request->status) {
                    case 'in_stock':
                        $query->where('current_stock', '>', 0);
                        break;
                    case 'low_stock':
                        $query->whereRaw('current_stock <= minimum_stock AND current_stock > 0');
                        break;
                    case 'out_of_stock':
                        $query->where('current_stock', '<=', 0);
                        break;
                }
            }

            if ($request->filled('location')) {
                $query->where('location', $request->location);
            }

            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('crop_type', 'like', "%{$search}%")
                      ->orWhere('variety', 'like', "%{$search}%")
                      ->orWhere('notes', 'like', "%{$search}%");
                });
            }

            $stockItems = $query->orderBy('crop_type')
                ->paginate(50);

            $cropTypes = StockItem::distinct()
                ->pluck('crop_type')
                ->filter()
                ->sort()
                ->values();

            $locations = StockItem::distinct()
                ->pluck('storage_location')
                ->filter()
                ->sort()
                ->values();

            // Calculate stock statistics
            $stockStats = [
                'total_items' => StockItem::count(),
                'items_in_stock' => StockItem::where('current_stock', '>', 0)->count(),
                'low_stock_items' => StockItem::whereRaw('current_stock <= minimum_stock AND current_stock > 0')->count(),
                'out_of_stock_items' => StockItem::where('current_stock', '<=', 0)->count(),
            ];

            $usingFarmOSData = false;

            return view('admin.farmos.stock', compact(
                'stockItems', 
                'cropTypes', 
                'locations', 
                'stockStats', 
                'usingFarmOSData'
            ));
        }
    }

    /**
     * Store a new stock item
     */
    public function storeStock(Request $request)
    {
        $request->validate([
            'crop_type' => 'required|string|max:255',
            'current_stock' => 'required|numeric|min:0',
            'unit' => 'required|string|max:50',
            'minimum_stock' => 'nullable|numeric|min:0',
            'max_quantity' => 'nullable|numeric|min:0',
            'location' => 'nullable|string|max:255',
            'quality_grade' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
        ]);

        $stockItem = StockItem::create($request->all());

        return response()->json([
            'success' => true,
            'message' => 'Stock item created successfully',
            'data' => $stockItem
        ]);
    }

    /**
     * Display crop planning
     */
    public function cropPlans(Request $request)
    {
        try {
            // Get real farmOS data instead of local database
            $farmOSCropPlans = $this->farmOSApi->getCropPlanningData();
            $farmOSCropTypes = $this->farmOSApi->getAvailableCropTypes();
            $farmOSLocations = $this->farmOSApi->getAvailableLocations();

            // Convert farmOS data to collection for easier filtering
            $cropPlans = collect($farmOSCropPlans);

            // Apply filters
            if ($request->filled('crop_type')) {
                $cropPlans = $cropPlans->where('crop_type', $request->crop_type);
            }

            if ($request->filled('status')) {
                $cropPlans = $cropPlans->where('status', $request->status);
            }

            if ($request->filled('location')) {
                $cropPlans = $cropPlans->where('location', $request->location);
            }

            // Handle calendar format request
            if ($request->format === 'calendar') {
                $events = [];
                
                foreach ($cropPlans as $plan) {
                    if (!empty($plan['planned_harvest_start'])) {
                        $events[] = [
                            'title' => "Harvest Start: {$plan['crop_type']}",
                            'start' => $plan['planned_harvest_start'],
                            'color' => '#007bff',
                            'extendedProps' => ['type' => 'harvest_start', 'plan_id' => $plan['farmos_asset_id']]
                        ];
                    }
                    
                    if (!empty($plan['planned_transplant_date'])) {
                        $events[] = [
                            'title' => "Transplant: {$plan['crop_type']}",
                            'start' => $plan['planned_transplant_date'],
                            'color' => '#28a745',
                            'extendedProps' => ['type' => 'transplant', 'plan_id' => $plan['farmos_asset_id']]
                        ];
                    }
                    
                    if (!empty($plan['planned_harvest_end'])) {
                        $events[] = [
                            'title' => "Harvest: {$plan['crop_type']}",
                            'start' => $plan['planned_harvest_end'],
                            'color' => '#ffc107',
                            'extendedProps' => ['type' => 'harvest', 'plan_id' => $plan['farmos_asset_id']]
                        ];
                    }
                }
                
                return response()->json($events);
            }

            // Convert back to paginated collection-like structure for view compatibility
            // Create a proper paginator for the view
            $perPage = 50;
            $currentPage = $request->get('page', 1);
            $total = $cropPlans->count();
            $offset = ($currentPage - 1) * $perPage;
            $paginatedPlans = $cropPlans->slice($offset, $perPage)->values();

            // Create a mock paginator-like object
            $cropPlans = new \Illuminate\Pagination\LengthAwarePaginator(
                $paginatedPlans,
                $total,
                $perPage,
                $currentPage,
                ['path' => request()->url(), 'pageName' => 'page']
            );

            // Use farmOS data for dropdowns
            $cropTypes = $farmOSCropTypes;
            $locations = $farmOSLocations;

            // Calculate planning statistics from farmOS data (use original collection before pagination)
            $allPlans = collect($farmOSCropPlans);
            $planningStats = [
                'total_plans' => $allPlans->count(),
                'planned' => $allPlans->where('status', 'planned')->count(),
                'in_progress' => $allPlans->whereIn('status', ['seeded', 'transplanted', 'growing'])->count(),
                'completed' => $allPlans->where('status', 'completed')->count(),
                'cancelled' => $allPlans->where('status', 'cancelled')->count(),
                'overdue' => 0, // Would need date logic for farmOS data
            ];

            // Add farmOS data source indicator
            $hasTestData = false; // We're now using real farmOS data
            $usingFarmOSData = true;

            return view('admin.farmos.crop-plans', compact(
                'cropPlans', 
                'cropTypes', 
                'locations', 
                'planningStats',
                'hasTestData',
                'usingFarmOSData'
            ));

        } catch (\Exception $e) {
            Log::error('Failed to fetch farmOS crop planning data: ' . $e->getMessage());
            
            // Fallback to local database if farmOS fails
            $query = CropPlan::query();

            if ($request->filled('season')) {
                $query->where('season', $request->season);
            }

            if ($request->filled('crop_type')) {
                $query->where('crop_type', $request->crop_type);
            }

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('location')) {
                $query->where('location', $request->location);
            }

            if ($request->filled('sort')) {
                switch ($request->sort) {
                    case 'planned_harvest_start':
                        $query->orderBy('planned_harvest_start', 'asc');
                        break;
                    case 'expected_harvest_date':
                        $query->orderBy('expected_harvest_date', 'asc');
                        break;
                    default:
                        $query->orderBy('planned_harvest_start', 'asc');
                }
            } else {
                $query->orderBy('planned_harvest_start', 'asc');
            }

            // Handle calendar format request (fallback)
            if ($request->format === 'calendar') {
                $events = [];
                $plans = $query->get();
                
                foreach ($plans as $plan) {
                    if ($plan->planned_harvest_start) {
                        $events[] = [
                            'title' => "Harvest Start: {$plan->crop_type}",
                            'start' => $plan->planned_harvest_start->toDateString(),
                            'color' => '#007bff',
                            'extendedProps' => ['type' => 'harvest_start', 'plan_id' => $plan->id]
                        ];
                    }
                }
                
                return response()->json($events);
            }

            $cropPlans = $query->paginate(50);

            $cropTypes = CropPlan::distinct()->pluck('crop_type')->filter()->sort()->values();
            $locations = CropPlan::distinct()->pluck('location')->filter()->sort()->values();

            // Calculate planning statistics (fallback)
            $planningStats = [
                'total_plans' => CropPlan::count(),
                'planned' => CropPlan::where('status', 'planned')->count(),
                'in_progress' => CropPlan::whereIn('status', ['seeded', 'transplanted', 'growing'])->count(),
                'completed' => CropPlan::where('status', 'completed')->count(),
                'cancelled' => CropPlan::where('status', 'cancelled')->count(),
                'overdue' => CropPlan::where('planned_harvest_start', '<', now())
                                     ->whereNotIn('status', ['completed', 'cancelled'])
                                     ->count(),
            ];

            $hasTestData = true; // Using fallback local database
            $usingFarmOSData = false;

            return view('admin.farmos.crop-plans', compact(
                'cropPlans', 
                'cropTypes', 
                'locations', 
                'planningStats',
                'hasTestData',
                'usingFarmOSData'
            ));
        }
    }

    /**
     * Store a new crop plan
     */
    public function storeCropPlan(Request $request)
    {
        $request->validate([
            'crop_type' => 'required|string|max:255',
            'season' => 'required|in:spring,summer,fall,winter',
            'year' => 'required|integer|min:' . date('Y') . '|max:' . (date('Y') + 2),
            'planned_seed_date' => 'nullable|date',
            'planned_transplant_date' => 'nullable|date',
            'expected_harvest_date' => 'nullable|date',
            'expected_yield' => 'nullable|numeric|min:0',
            'yield_unit' => 'nullable|string|max:50',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $cropPlan = CropPlan::create(array_merge($request->all(), [
            'status' => 'planned'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Crop plan created successfully',
            'data' => $cropPlan
        ]);
    }

    /**
     * Sync harvest logs from FarmOS
     */
    public function syncHarvests()
    {
        try {
            $since = HarvestLog::max('updated_at') ?? Carbon::now()->subDays(30);
            $farmOSHarvests = $this->farmOSApi->getHarvestLogs($since->toISOString());

            $synced = 0;
            if (isset($farmOSHarvests['data'])) {
                foreach ($farmOSHarvests['data'] as $harvestData) {
                    $this->processHarvestLog($harvestData);
                    $synced++;
                }
            }

            return response()->json([
                'success' => true,
                'message' => "Synced {$synced} harvest logs from FarmOS",
                'synced_count' => $synced
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync harvest logs: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync harvest logs: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process a single harvest log from FarmOS
     */
    private function processHarvestLog($harvestData)
    {
        $attributes = $harvestData['attributes'] ?? [];
        $relationships = $harvestData['relationships'] ?? [];

        // Extract basic harvest info
        $harvestLog = HarvestLog::updateOrCreate(
            ['farmos_id' => $harvestData['id']],
            [
                'crop_name' => $this->extractCropName($harvestData),
                'crop_type' => $this->extractCropType($harvestData),
                'quantity' => $this->extractQuantity($harvestData),
                'units' => $this->extractUnits($harvestData),
                'harvest_date' => Carbon::parse($attributes['timestamp'] ?? now()),
                'location' => $this->extractLocation($harvestData),
                'notes' => $attributes['notes']['value'] ?? '',
                'status' => $attributes['status'] ?? 'done',
                'farmos_data' => $harvestData
            ]
        );

        // Auto-sync to stock if enabled
        if (!$harvestLog->synced_to_stock) {
            $this->syncHarvestToStock($harvestLog);
        }

        return $harvestLog;
    }

    /**
     * Sync harvest to stock items
     */
    public function syncHarvestToStock(HarvestLog $harvestLog)
    {
        try {
            // Find or create stock item
            $stockItem = StockItem::firstOrCreate(
                ['name' => $harvestLog->crop_name],
                [
                    'crop_type' => $harvestLog->crop_type,
                    'units' => $harvestLog->units,
                    'current_stock' => 0,
                    'reserved_stock' => 0,
                    'available_stock' => 0,
                    'is_active' => true,
                    'track_stock' => true,
                    'description' => "Auto-created from harvest log"
                ]
            );

            // Add harvest quantity to stock
            $stockItem->addHarvestStock($harvestLog->quantity, $harvestLog->harvest_date);

            // Mark harvest as synced
            $harvestLog->markAsSynced();

            return response()->json([
                'success' => true,
                'message' => "Added {$harvestLog->formatted_quantity} to {$stockItem->name} stock"
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to sync harvest to stock: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync to stock: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Extract crop name from FarmOS data
     */
    private function extractCropName($harvestData)
    {
        $attributes = $harvestData['attributes'] ?? [];
        return $attributes['name'] ?? 'Unknown Crop';
    }

    /**
     * Extract crop type from FarmOS data
     */
    private function extractCropType($harvestData)
    {
        // This would need to be extracted from plant asset relationships
        return 'vegetable'; // Default for now
    }

    /**
     * Extract quantity from FarmOS data
     */
    private function extractQuantity($harvestData)
    {
        $relationships = $harvestData['relationships'] ?? [];
        $quantity = $relationships['quantity']['data'][0] ?? null;
        return $quantity['attributes']['value']['decimal'] ?? 0;
    }

    /**
     * Extract units from FarmOS data
     */
    private function extractUnits($harvestData)
    {
        $relationships = $harvestData['relationships'] ?? [];
        $units = $relationships['quantity']['data'][0]['relationships']['units']['data'] ?? null;
        return $units['attributes']['name'] ?? 'kg';
    }

    /**
     * Extract location from FarmOS data
     */
    private function extractLocation($harvestData)
    {
        $relationships = $harvestData['relationships'] ?? [];
        $location = $relationships['location']['data'][0] ?? null;
        return $location['attributes']['name'] ?? 'Unknown';
    }

    /**
     * Display the Gantt chart page
     */
    public function ganttChart(Request $request)
    {
        try {
            // Get locations and crop types for filters
            $locations = $this->farmOSApi->getAvailableLocations();
            $cropTypes = $this->farmOSApi->getAvailableCropTypes();
            
            $usingFarmOSData = true;
            
            return view('admin.farmos.gantt-chart', compact(
                'locations',
                'cropTypes', 
                'usingFarmOSData'
            ));
            
        } catch (\Exception $e) {
            Log::error('Failed to load Gantt chart page: ' . $e->getMessage());
            
            // Fallback to local data
            $locations = ['Block 1', 'Block 2', 'Block 3', 'Block 4', 'Block 5'];
            $cropTypes = ['lettuce', 'tomato', 'carrot', 'cabbage', 'potato'];
            $usingFarmOSData = false;
            
            return view('admin.farmos.gantt-chart', compact(
                'locations',
                'cropTypes',
                'usingFarmOSData'
            ));
        }
    }

    /**
     * Get Gantt chart data as JSON
     */
    public function ganttData(Request $request)
    {
        try {
            // Get farmOS data
            $cropPlans = $this->farmOSApi->getCropPlanningData();
            $harvestLogs = $this->farmOSApi->getHarvestLogs();
            $locations = $this->farmOSApi->getAvailableLocations();
            
            // Apply filters
            if ($request->filled('location')) {
                $cropPlans = array_filter($cropPlans, function($plan) use ($request) {
                    return $plan['location'] === $request->location;
                });
            }
            
            if ($request->filled('crop_type')) {
                $cropPlans = array_filter($cropPlans, function($plan) use ($request) {
                    return $plan['crop_type'] === $request->crop_type;
                });
            }
            
            // Filter by date range
            $startDate = $request->get('start_date', now()->subMonths(2)->toDateString());
            $endDate = $request->get('end_date', now()->addMonths(4)->toDateString());
            
            // Transform data for Gantt chart
            $ganttData = $this->transformToGanttData($cropPlans, $harvestLogs, $locations, $startDate, $endDate);
            
            return response()->json([
                'success' => true,
                'data' => $ganttData,
                'locations' => $locations,
                'date_range' => ['start' => $startDate, 'end' => $endDate]
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to get Gantt data: ' . $e->getMessage());
            
            // Return fallback/test data
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'data' => $this->getFallbackGanttData(),
                'locations' => ['Block 1', 'Block 2', 'Block 3', 'Block 4', 'Block 5']
            ]);
        }
    }

    /**
     * Transform farmOS data into Gantt chart format
     */
    private function transformToGanttData($cropPlans, $harvestLogs, $locations, $startDate, $endDate)
    {
        $ganttData = [];
        
        // Ensure we have locations array
        if (empty($locations)) {
            $locations = ['Block 1', 'Block 2', 'Block 3', 'Block 4', 'Block 5'];
        }
        
        // Group crop plans by location
        $plansByLocation = [];
        foreach ($cropPlans as $plan) {
            $location = $plan['location'] ?? 'Unknown';
            if (!isset($plansByLocation[$location])) {
                $plansByLocation[$location] = [];
            }
            $plansByLocation[$location][] = $plan;
        }
        
        // Create gantt bars for each location
        foreach ($locations as $location) {
            $locationPlans = $plansByLocation[$location] ?? [];
            $ganttData[$location] = $this->createLocationGanttBars($locationPlans, $startDate, $endDate);
        }
        
        // If no real data, add at least some demo data for visualization
        if (empty(array_filter($ganttData))) {
            $ganttData = $this->getFallbackGanttData();
        }
        
        return $ganttData;
    }

    /**
     * Create Gantt bars for a specific location
     */
    private function createLocationGanttBars($plans, $startDate, $endDate)
    {
        $bars = [];
        
        foreach ($plans as $plan) {
            // Seeding period
            if (!empty($plan['planned_seeding_date'])) {
                $seedingStart = $plan['planned_seeding_date'];
                $seedingEnd = $plan['planned_transplant_date'] ?? 
                             date('Y-m-d', strtotime($seedingStart . ' + 3 weeks'));
                
                $bars[] = [
                    'id' => $plan['farmos_asset_id'] . '_seeding',
                    'type' => 'seeding',
                    'crop' => $plan['crop_type'],
                    'variety' => $plan['variety'] ?? '',
                    'start' => $seedingStart,
                    'end' => $seedingEnd,
                    'color' => '#28a745', // Green
                    'label' => $plan['crop_type'] . ' (Seeding)',
                    'details' => $plan
                ];
            }
            
            // Growing period
            if (!empty($plan['planned_transplant_date']) || !empty($plan['planned_seeding_date'])) {
                $growingStart = $plan['planned_transplant_date'] ?? $plan['planned_seeding_date'];
                $growingEnd = $plan['planned_harvest_start'] ?? 
                             date('Y-m-d', strtotime($growingStart . ' + 8 weeks'));
                
                if ($growingStart && $growingEnd) {
                    $bars[] = [
                        'id' => $plan['farmos_asset_id'] . '_growing',
                        'type' => 'growing',
                        'crop' => $plan['crop_type'],
                        'variety' => $plan['variety'] ?? '',
                        'start' => $growingStart,
                        'end' => $growingEnd,
                        'color' => '#007bff', // Blue
                        'label' => $plan['crop_type'] . ' (Growing)',
                        'details' => $plan
                    ];
                }
            }
            
            // Harvest period
            if (!empty($plan['planned_harvest_start'])) {
                $harvestStart = $plan['planned_harvest_start'];
                $harvestEnd = $plan['planned_harvest_end'] ?? 
                             date('Y-m-d', strtotime($harvestStart . ' + 2 weeks'));
                
                $bars[] = [
                    'id' => $plan['farmos_asset_id'] . '_harvest',
                    'type' => 'harvest',
                    'crop' => $plan['crop_type'],
                    'variety' => $plan['variety'] ?? '',
                    'start' => $harvestStart,
                    'end' => $harvestEnd,
                    'color' => '#ffc107', // Yellow/Orange
                    'label' => $plan['crop_type'] . ' (Harvest)',
                    'details' => $plan
                ];
            }
        }
        
        return $bars;
    }

    /**
     * Get fallback Gantt data for testing
     */
    private function getFallbackGanttData()
    {
        $now = now();
        
        return [
            'Block 1' => [
                [
                    'id' => 'test_lettuce_seeding',
                    'type' => 'seeding',
                    'crop' => 'lettuce',
                    'variety' => 'Butter Lettuce',
                    'start' => $now->subDays(10)->toDateString(),
                    'end' => $now->addDays(5)->toDateString(),
                    'color' => '#28a745',
                    'label' => 'Lettuce (Seeding)'
                ],
                [
                    'id' => 'test_lettuce_growing',
                    'type' => 'growing',
                    'crop' => 'lettuce',
                    'variety' => 'Butter Lettuce',
                    'start' => $now->addDays(5)->toDateString(),
                    'end' => $now->addDays(40)->toDateString(),
                    'color' => '#007bff',
                    'label' => 'Lettuce (Growing)'
                ],
                [
                    'id' => 'test_lettuce_harvest',
                    'type' => 'harvest',
                    'crop' => 'lettuce',
                    'variety' => 'Butter Lettuce',
                    'start' => $now->addDays(40)->toDateString(),
                    'end' => $now->addDays(50)->toDateString(),
                    'color' => '#ffc107',
                    'label' => 'Lettuce (Harvest)'
                ]
            ],
            'Block 2' => [
                [
                    'id' => 'test_tomato_growing',
                    'type' => 'growing',
                    'crop' => 'tomato',
                    'variety' => 'Cherry Tomato',
                    'start' => $now->subDays(30)->toDateString(),
                    'end' => $now->addDays(60)->toDateString(),
                    'color' => '#007bff',
                    'label' => 'Tomato (Growing)'
                ],
                [
                    'id' => 'test_tomato_harvest',
                    'type' => 'harvest',
                    'crop' => 'tomato',
                    'variety' => 'Cherry Tomato',
                    'start' => $now->addDays(60)->toDateString(),
                    'end' => $now->addDays(90)->toDateString(),
                    'color' => '#ffc107',
                    'label' => 'Tomato (Harvest)'
                ]
            ],
            'Block 3' => [
                [
                    'id' => 'test_carrot_seeding',
                    'type' => 'seeding',
                    'crop' => 'carrot',
                    'variety' => 'Nantes',
                    'start' => $now->addDays(7)->toDateString(),
                    'end' => $now->addDays(14)->toDateString(),
                    'color' => '#28a745',
                    'label' => 'Carrot (Seeding)'
                ],
                [
                    'id' => 'test_carrot_growing',
                    'type' => 'growing',
                    'crop' => 'carrot',
                    'variety' => 'Nantes',
                    'start' => $now->addDays(14)->toDateString(),
                    'end' => $now->addDays(84)->toDateString(),
                    'color' => '#007bff',
                    'label' => 'Carrot (Growing)'
                ]
            ],
            'Block 4' => [
                [
                    'id' => 'test_spinach_completed',
                    'type' => 'harvest',
                    'crop' => 'spinach',
                    'variety' => 'Space Spinach',
                    'start' => $now->subDays(20)->toDateString(),
                    'end' => $now->subDays(5)->toDateString(),
                    'color' => '#6c757d',
                    'label' => 'Spinach (Completed)'
                ],
                [
                    'id' => 'test_kale_seeding',
                    'type' => 'seeding',
                    'crop' => 'kale',
                    'variety' => 'Curly Kale',
                    'start' => $now->addDays(15)->toDateString(),
                    'end' => $now->addDays(22)->toDateString(),
                    'color' => '#28a745',
                    'label' => 'Kale (Seeding)'
                ]
            ],
            'Block 5' => [
                [
                    'id' => 'test_herbs_succession',
                    'type' => 'growing',
                    'crop' => 'basil',
                    'variety' => 'Sweet Basil',
                    'start' => $now->subDays(15)->toDateString(),
                    'end' => $now->addDays(45)->toDateString(),
                    'color' => '#007bff',
                    'label' => 'Basil (Growing)'
                ],
                [
                    'id' => 'test_herbs_harvest',
                    'type' => 'harvest',
                    'crop' => 'basil',
                    'variety' => 'Sweet Basil',
                    'start' => $now->addDays(30)->toDateString(),
                    'end' => $now->addDays(75)->toDateString(),
                    'color' => '#ffc107',
                    'label' => 'Basil (Harvest)'
                ]
            ]
        ];
    }
}