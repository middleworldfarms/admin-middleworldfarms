@extends('layouts.app')

@section('title', 'Planting Chart - farmOS Integration')

@section('content')
<div class="container-fluid px-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('admin.farmos.dashboard') }}">
                    <i class="fas fa-tractor"></i> farmOS
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">
                <i class="fas fa-seedling"></i> Planting Chart
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-seedling me-2"></i>Planting Chart
            </h1>
            <p class="text-muted mb-0">Visual timeline of crop cycles across all farm blocks</p>
        </div>
        
        <div class="d-flex gap-2">
            <button class="btn btn-success btn-sm" id="refreshData">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label for="locationFilter" class="form-label">Location</label>
                    <select class="form-select form-select-sm" id="locationFilter">
                        <option value="">All Locations</option>
                        @foreach($locations as $location)
                            <option value="{{ $location }}">{{ $location }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="cropTypeFilter" class="form-label">Crop Type</label>
                    <select class="form-select form-select-sm" id="cropTypeFilter">
                        <option value="">All Crops</option>
                        @foreach($cropTypes as $cropType)
                            <option value="{{ $cropType }}">{{ ucfirst($cropType) }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div class="col-md-3">
                    <label for="startDate" class="form-label">Start Date</label>
                    <input type="date" class="form-control form-control-sm" id="startDate" 
                           value="{{ now()->subMonths(2)->format('Y-m-d') }}">
                </div>
                
                <div class="col-md-3">
                    <label for="endDate" class="form-label">End Date</label>
                    <input type="date" class="form-control form-control-sm" id="endDate" 
                           value="{{ now()->addMonths(4)->format('Y-m-d') }}">
                </div>
            </div>
            
            <div class="row mt-3">
                <div class="col-12">
                    <button class="btn btn-primary btn-sm" id="applyFilters">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <button class="btn btn-outline-secondary btn-sm ms-2" id="clearFilters">
                        <i class="fas fa-times"></i> Clear
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Legend Card -->
    <div class="card mb-4">
        <div class="card-body py-2">
            <div class="d-flex flex-wrap gap-3 align-items-center">
                <small class="text-muted fw-bold">Legend:</small>
                <div class="d-flex align-items-center">
                    <div class="legend-color" style="background-color: #28a745; width: 16px; height: 16px; border-radius: 3px; margin-right: 5px;"></div>
                    <small>Seeding</small>
                </div>
                <div class="d-flex align-items-center">
                    <div class="legend-color" style="background-color: #007bff; width: 16px; height: 16px; border-radius: 3px; margin-right: 5px;"></div>
                    <small>Growing</small>
                </div>
                <div class="d-flex align-items-center">
                    <div class="legend-color" style="background-color: #ffc107; width: 16px; height: 16px; border-radius: 3px; margin-right: 5px;"></div>
                    <small>Harvest</small>
                </div>
                <div class="d-flex align-items-center">
                    <div class="legend-color" style="background-color: #6c757d; width: 16px; height: 16px; border-radius: 3px; margin-right: 5px;"></div>
                    <small>Available</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Source Indicator -->
    @if(isset($usingFarmOSData) && $usingFarmOSData)
        <div class="alert alert-success alert-sm mb-4">
            <i class="fas fa-check-circle"></i>
            <strong>Live Data:</strong> Connected to farmOS - showing real crop planning data
        </div>
    @else
        <div class="alert alert-warning alert-sm mb-4">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Demo Mode:</strong> Using sample data - farmOS connection unavailable
        </div>
    @endif

    <!-- Planting Chart Container -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-chart-gantt me-2"></i>Timeline View
            </h5>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" id="viewWeeks">
                    <i class="fas fa-calendar-week"></i> Weeks
                </button>
                <button class="btn btn-outline-secondary btn-sm active" id="viewMonths">
                    <i class="fas fa-calendar"></i> Months
                </button>
                <button class="btn btn-outline-secondary btn-sm" id="viewQuarters">
                    <i class="fas fa-calendar-plus"></i> Quarters
                </button>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Loading State -->
            <div id="chartLoading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading chart data...</span>
                </div>
                <p class="text-muted mt-2">Loading planting timeline...</p>
            </div>
            
            <!-- Chart Container -->
            <div id="chartContainer" style="display: none; min-height: 500px;">
                <div id="plantingChart"></div>
            </div>
            
            <!-- No Data State -->
            <div id="noDataMessage" class="text-center py-5" style="display: none;">
                <i class="fas fa-seedling fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Planting Data Available</h5>
                <p class="text-muted">No crop plans found in farmOS yet. Create some plantings to see your timeline!</p>
                <div class="mt-4">
                    <button class="btn btn-primary me-2" onclick="window.location.href='{{ route('admin.farmos.crop-plans') }}'">
                        <i class="fas fa-plus"></i> Add Crop Plans
                    </button>
                    <button class="btn btn-outline-secondary" onclick="showTestData()">
                        <i class="fas fa-eye"></i> Show Demo Timeline
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Row -->
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title text-success" id="activePlantings">-</h5>
                    <p class="card-text text-muted">Active Plantings</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title text-primary" id="upcomingHarvests">-</h5>
                    <p class="card-text text-muted">Upcoming Harvests</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title text-warning" id="totalPlans">-</h5>
                    <p class="card-text text-muted">Total Plans</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title text-info" id="totalBlocks">{{ count($locations ?? []) }}</h5>
                    <p class="card-text text-muted">Farm Blocks</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Frappe Gantt Library from CDN -->
<script src="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/frappe-gantt@0.6.1/dist/frappe-gantt.css">

<script>
let gantt = null;
let currentData = null;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    if (typeof Gantt === 'undefined') {
        // Show error without Gantt library
        document.getElementById('chartLoading').style.display = 'none';
        document.getElementById('noDataMessage').style.display = 'block';
        document.getElementById('noDataMessage').querySelector('h5').textContent = 'Chart Library Error';
        document.getElementById('noDataMessage').querySelector('p').textContent = 'Frappe Gantt library failed to load. Showing demo data instead.';
        // Show demo data without Gantt
        setTimeout(function() {
            showTestDataWithoutGantt();
        }, 1000);
        return;
    }
    initializeChart();
    setupEventListeners();
});

function showTestDataWithoutGantt() {
    document.getElementById('chartContainer').innerHTML = `
        <div class="alert alert-info">
            <h5>Demo Planting Timeline Data</h5>
            <p>Chart library couldn't load, but here's what your planting timeline would show:</p>
            <ul>
                <li><strong>Block 1 - Lettuce:</strong> Seeding: Jul 20, Transplant: Aug 5, Harvest: Sep 10-20</li>
                <li><strong>Block 2 - Tomato:</strong> Seeding: Jul 1, Transplant: Jul 15, Harvest: Sep 1-30</li>
                <li><strong>Block 3 - Carrot:</strong> Seeding: Aug 15, Harvest: Nov 1-15</li>
            </ul>
            <p><small>Try refreshing the page or check your internet connection to load the interactive timeline.</small></p>
        </div>
    `;
    document.getElementById('chartContainer').style.display = 'block';
    document.getElementById('noDataMessage').style.display = 'none';
}

function setupEventListeners() {
    // Filter controls
    const applyBtn = document.getElementById('applyFilters');
    const clearBtn = document.getElementById('clearFilters');
    const refreshBtn = document.getElementById('refreshData');
    if (applyBtn) applyBtn.addEventListener('click', applyFilters);
    if (clearBtn) clearBtn.addEventListener('click', clearFilters);
    if (refreshBtn) refreshBtn.addEventListener('click', initializeChart);
    // View controls
    const viewWeeks = document.getElementById('viewWeeks');
    const viewMonths = document.getElementById('viewMonths');
    const viewQuarters = document.getElementById('viewQuarters');
    if (viewWeeks) viewWeeks.addEventListener('click', () => changeView('Week'));
    if (viewMonths) viewMonths.addEventListener('click', () => changeView('Month'));
    if (viewQuarters) viewQuarters.addEventListener('click', () => changeView('Quarter Year'));
}

function initializeChart() {
    showLoading(true);
    
    try {
        // Use the crop plans data passed from the controller instead of fetching
        const cropPlansData = @json($cropPlans ?? []);
        
        if (cropPlansData && cropPlansData.length > 0) {
            renderPlantingChart(cropPlansData);
            updateStats(cropPlansData);
            showChart();
        } else {
            showNoData();
        }
    } catch (error) {
        showError(`Failed to load chart data: ${error.message}`);
    } finally {
        showLoading(false);
    }
}

function renderPlantingChart(cropPlans) {
    const chartContainer = document.getElementById('plantingChart');
    chartContainer.innerHTML = '';
    
    if (!cropPlans || cropPlans.length === 0) {
        showNoData();
        return;
    }
    
    // Create a simple timeline chart using crop plans data
    const tasks = [];
    let taskId = 1;
    
    cropPlans.forEach(plan => {
        // Create tasks for different phases of the crop plan
        if (plan.planned_seed_date) {
            tasks.push({
                id: `task-${taskId++}`,
                name: `${plan.crop_type || plan.crop_name || 'Unknown'} - Seeding`,
                start: plan.planned_seed_date,
                end: plan.planned_transplant_date || plan.planned_seed_date,
                progress: 25,
                custom_class: 'gantt-bar-seeding',
                location: plan.location || 'Unknown',
                crop: plan.crop_type || plan.crop_name || 'Unknown',
                variety: plan.variety || 'N/A',
                phase: 'seeding'
            });
        }
        
        if (plan.planned_transplant_date) {
            tasks.push({
                id: `task-${taskId++}`,
                name: `${plan.crop_type || plan.crop_name || 'Unknown'} - Transplanting`,
                start: plan.planned_transplant_date,
                end: plan.planned_harvest_start || plan.planned_transplant_date,
                progress: 50,
                custom_class: 'gantt-bar-transplant',
                location: plan.location || 'Unknown',
                crop: plan.crop_type || plan.crop_name || 'Unknown',
                variety: plan.variety || 'N/A',
                phase: 'transplanting'
            });
        }
        
        if (plan.planned_harvest_start) {
            tasks.push({
                id: `task-${taskId++}`,
                name: `${plan.crop_type || plan.crop_name || 'Unknown'} - Harvest`,
                start: plan.planned_harvest_start,
                end: plan.planned_harvest_end || plan.planned_harvest_start,
                progress: 100,
                custom_class: 'gantt-bar-harvest',
                location: plan.location || 'Unknown',
                crop: plan.crop_type || plan.crop_name || 'Unknown',
                variety: plan.variety || 'N/A',
                phase: 'harvest'
            });
        }
    });
    
    if (tasks.length === 0) {
        showNoData();
        return;
    }
    
    // Use Frappe Gantt to display the timeline
    try {
        gantt = new Gantt('#plantingChart', tasks, {
            view_mode: 'Month',
            date_format: 'YYYY-MM-DD',
            language: 'en',
            custom_popup_html: function(task) {
                const startDate = new Date(task.start).toLocaleDateString();
                const endDate = new Date(task.end).toLocaleDateString();
                const duration = Math.ceil((new Date(task.end) - new Date(task.start)) / (1000 * 60 * 60 * 24));
                return `
                    <div class="gantt-popup">
                        <h6>${task.crop} - ${task.phase}</h6>
                        <p><strong>Location:</strong> ${task.location}</p>
                        <p><strong>Variety:</strong> ${task.variety}</p>
                        <p><strong>Start:</strong> ${startDate}</p>
                        <p><strong>End:</strong> ${endDate}</p>
                        <p><strong>Duration:</strong> ${duration} days</p>
                    </div>
                `;
            },
            on_click: function(task) {
                showCropDetails(task);
            },
            on_date_change: function(task, start, end) {},
            on_progress_change: function(task, progress) {},
            on_view_change: function(mode) {}
        });
    } catch (error) {
        console.error('Error creating Gantt chart:', error);
        showError('Failed to create chart visualization');
    }
}

function applyFilters() {
    initializeChart();
}

function clearFilters() {
    document.getElementById('locationFilter').value = '';
    document.getElementById('cropTypeFilter').value = '';
    document.getElementById('startDate').value = '{{ now()->subMonths(2)->format('Y-m-d') }}';
    document.getElementById('endDate').value = '{{ now()->addMonths(4)->format('Y-m-d') }}';
    initializeChart();
}

function changeView(view) {
    document.querySelectorAll('[id^="view"]').forEach(btn => btn.classList.remove('active'));
    if (view === 'Week') document.getElementById('viewWeeks').classList.add('active');
    else if (view === 'Month') document.getElementById('viewMonths').classList.add('active');
    else if (view === 'Quarter Year') document.getElementById('viewQuarters').classList.add('active');
    if (gantt) {
        gantt.change_view_mode(view);
    }
}

function updateStats(cropPlans) {
    let activePlantings = 0;
    let upcomingHarvests = 0;
    let totalPlans = 0;
    const now = new Date();
    const twoWeeksFromNow = new Date(now.getTime() + 14 * 24 * 60 * 60 * 1000);
    
    if (cropPlans && Array.isArray(cropPlans)) {
        totalPlans = cropPlans.length;
        
        cropPlans.forEach(plan => {
            // Count active plantings (those currently growing)
            if (plan.status === 'growing' || plan.status === 'active' || plan.status === 'in_progress') {
                activePlantings++;
            }
            
            // Count upcoming harvests (planned within 2 weeks)
            if (plan.planned_harvest_start) {
                const harvestDate = new Date(plan.planned_harvest_start);
                if (harvestDate >= now && harvestDate <= twoWeeksFromNow) {
                    upcomingHarvests++;
                }
            }
        });
    }
    
    // Update stats display
    document.getElementById('activePlantings').textContent = activePlantings;
    document.getElementById('upcomingHarvests').textContent = upcomingHarvests;
    document.getElementById('totalPlans').textContent = totalPlans;
    
    // Update total blocks (locations) from the locations data
    const totalLocations = @json(count($locations ?? []));
    document.getElementById('totalBlocks').textContent = totalLocations;
}

function showCropDetails(task) {
    const startDate = new Date(task.start).toLocaleDateString();
    const endDate = new Date(task.end).toLocaleDateString();
    const duration = Math.ceil((new Date(task.end) - new Date(task.start)) / (1000 * 60 * 60 * 24));
    alert(`Crop Details:\n\nCrop: ${task.crop}\nPhase: ${task.phase}\nLocation: ${task.location}\nVariety: ${task.variety}\nStart: ${startDate}\nEnd: ${endDate}\nDuration: ${duration} days`);
}

function showLoading(show) {
    document.getElementById('chartLoading').style.display = show ? 'block' : 'none';
}

function showChart() {
    document.getElementById('chartContainer').style.display = 'block';
    document.getElementById('noDataMessage').style.display = 'none';
    document.getElementById('chartLoading').style.display = 'none';
}

function showNoData() {
    document.getElementById('noDataMessage').style.display = 'block';
    document.getElementById('chartContainer').style.display = 'none';
    document.getElementById('chartLoading').style.display = 'none';
}

function showError(message) {
    document.getElementById('noDataMessage').style.display = 'block';
    document.getElementById('noDataMessage').querySelector('h5').textContent = 'Error Loading Data';
    document.getElementById('noDataMessage').querySelector('p').textContent = 'Error: ' + message;
    document.getElementById('chartContainer').style.display = 'none';
    document.getElementById('chartLoading').style.display = 'none';
}

function showTestData() {
    const testData = [
        {
            id: 1,
            crop_type: 'lettuce',
            crop_name: 'Butter Lettuce',
            variety: 'Butter Lettuce',
            location: 'Block 1',
            status: 'growing',
            planned_seed_date: '2025-07-20',
            planned_transplant_date: '2025-08-05',
            planned_harvest_start: '2025-09-10',
            planned_harvest_end: '2025-09-20'
        },
        {
            id: 2,
            crop_type: 'tomato',
            crop_name: 'Cherry Tomato',
            variety: 'Cherry Tomato',
            location: 'Block 2',
            status: 'active',
            planned_seed_date: '2025-07-01',
            planned_transplant_date: '2025-07-15',
            planned_harvest_start: '2025-09-01',
            planned_harvest_end: '2025-09-30'
        },
        {
            id: 3,
            crop_type: 'carrot',
            crop_name: 'Rainbow Carrots',
            variety: 'Rainbow Mix',
            location: 'Block 3',
            status: 'planned',
            planned_seed_date: '2025-08-15',
            planned_harvest_start: '2025-11-01',
            planned_harvest_end: '2025-11-15'
        }
    ];
    renderPlantingChart(testData);
    updateStats(testData);
    showChart();
}
</script>

<style>
.gantt .grid-background { fill: none; }
.gantt .grid-header { fill: #ffffff; stroke: #e0e0e0; stroke-width: 1.4; }
.gantt .grid-row { fill: #ffffff; }
.gantt .grid-row:nth-child(even) { fill: #f5f5f5; }
.gantt .row-line { stroke: #ebeff2; }
.gantt .tick { stroke: #e0e0e0; stroke-width: 0.2; }
.gantt .tick.thick { stroke: #c0c0c0; stroke-width: 0.4; }
.gantt .today-highlight { fill: #fcf8e3; }
.gantt .arrow { fill: none; stroke: #666; stroke-width: 1.4; }
.gantt .bar { fill: #b8c2cc; stroke: #8d99a6; stroke-width: 0; cursor: pointer; }
.gantt .bar.active { fill: #5eb9f3; stroke: #4aa8e8; }
.gantt .bar-progress { fill: #a3a3a3; }
.gantt .bar-invalid { fill: #e53e3e; }
.gantt .bar-invalid~.bar-label { fill: #fff; }
.gantt .bar-label { fill: #fff; dominant-baseline: central; text-anchor: middle; font-size: 12px; font-weight: 400; }
.gantt .lower-text { font-size: 16px; fill: #777; text-anchor: middle; }
.gantt .upper-text { font-size: 12px; fill: #555; text-anchor: middle; }
.gantt .handle.left { cursor: w-resize; }
.gantt .handle.right { cursor: e-resize; }
.gantt .handle { fill: #ddd; cursor: ew-resize; opacity: 0; }
.gantt .handle.active { opacity: 1; }
.gantt .popup-wrapper { position: absolute; top: 0; left: 0; background: rgba(0, 0, 0, 0.8); height: 100%; width: 100%; display: flex; align-items: center; justify-content: center; }
.gantt .popup { background: white; padding: 10px; border-radius: 3px; box-shadow: 0 4px 8px 0 rgba(0, 0, 0, 0.2); }
.gantt-bar-seeding { fill: #28a745 !important; }
.gantt-bar-growing { fill: #007bff !important; }
.gantt-bar-harvest { fill: #ffc107 !important; }
.gantt-popup { padding: 15px; min-width: 200px; }
.gantt-popup h6 { margin: 0 0 10px 0; color: #333; font-weight: bold; }
.gantt-popup p { margin: 5px 0; font-size: 14px; color: #666; }
#plantingChart { min-height: 500px; overflow-x: auto; }
</style>
@endsection
