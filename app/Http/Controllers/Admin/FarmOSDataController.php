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
    }

    /**
     * Display the main FarmOS dashboard
     */
    public function index()
    {
        // Show test data notice if we're using test data
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

        return view('admin.farmos.dashboard', compact(
            'stats',
            'recentHarvests', 
            'lowStockItems',
            'upcomingHarvests',
            'hasTestData'
        ));
    }

    /**
     * Display harvest logs
     */
    public function harvests(Request $request)
    {
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

        return view('admin.farmos.harvests', compact('harvestLogs', 'cropTypes', 'farmosBaseUrl'));
    }

    /**
     * Display stock management
     */
    public function stock(Request $request)
    {
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

        return view('admin.farmos.stock', compact('stockItems', 'cropTypes', 'locations', 'stockStats'));
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

        // Handle calendar format request
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
                
                if ($plan->planned_transplant_date) {
                    $events[] = [
                        'title' => "Transplant: {$plan->crop_type}",
                        'start' => $plan->planned_transplant_date->toDateString(),
                        'color' => '#28a745',
                        'extendedProps' => ['type' => 'transplant', 'plan_id' => $plan->id]
                    ];
                }
                
                if ($plan->expected_harvest_date) {
                    $events[] = [
                        'title' => "Harvest: {$plan->crop_type}",
                        'start' => $plan->expected_harvest_date->toDateString(),
                        'color' => '#ffc107',
                        'extendedProps' => ['type' => 'harvest', 'plan_id' => $plan->id]
                    ];
                }
            }
            
            return response()->json($events);
        }

        $cropPlans = $query->paginate(50);

        $cropTypes = CropPlan::distinct()
            ->pluck('crop_type')
            ->filter()
            ->sort()
            ->values();

        $locations = CropPlan::distinct()
            ->pluck('location')
            ->filter()
            ->sort()
            ->values();

        // Calculate planning statistics
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

        return view('admin.farmos.crop-plans', compact('cropPlans', 'cropTypes', 'locations', 'planningStats'));
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
}
