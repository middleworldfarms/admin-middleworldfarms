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
                    <h5 class="card-title text-warning" id="availableBeds">-</h5>
                    <p class="card-text text-muted">Available Beds</p>
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

<script>
let currentData = null;

// Load data from server-side variables
const serverData = @json($chartData ?? []);
const usingFarmOSData = @json($usingFarmOSData ?? false);

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    // Initialize with server data
    currentData = { data: serverData };
    
    if (serverData && Object.keys(serverData).length > 0) {
        renderTimelineChart(serverData);
        updateStats(serverData);
        showChart();
    } else {
        showTestData();
    }
    
    setupEventListeners();
});

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
    
    // Use server data instead of AJAX fetch
    let filteredData = serverData;
    
    // Apply client-side filtering if needed
    const locationFilter = document.getElementById('locationFilter').value;
    const cropTypeFilter = document.getElementById('cropTypeFilter').value;
    const startDate = document.getElementById('startDate').value;
    const endDate = document.getElementById('endDate').value;
    
    if (locationFilter || cropTypeFilter || startDate || endDate) {
        filteredData = applyFiltersToData(serverData, {
            location: locationFilter,
            crop_type: cropTypeFilter,
            start_date: startDate,
            end_date: endDate
        });
    }
    
    // Update display
    currentData = { data: filteredData };
    
    if (filteredData && Object.keys(filteredData).length > 0) {
        renderTimelineChart(filteredData);
        updateStats(filteredData);
        showChart();
    } else {
        showNoData();
    }
    
    showLoading(false);
}

function applyFiltersToData(data, filters) {
    const filtered = {};
    
    Object.keys(data).forEach(location => {
        // Filter by location
        if (filters.location && location !== filters.location) {
            return;
        }
        
        const activities = data[location] || [];
        const filteredActivities = activities.filter(activity => {
            // Filter by crop type
            if (filters.crop_type && activity.crop !== filters.crop_type) {
                return false;
            }
            
            // Filter by date range
            if (filters.start_date && activity.end < filters.start_date) {
                return false;
            }
            
            if (filters.end_date && activity.start > filters.end_date) {
                return false;
            }
            
            return true;
        });
        
        if (filteredActivities.length > 0) {
            filtered[location] = filteredActivities;
        }
    });
    
    return filtered;
}

function renderTimelineChart(data) {
    const chartContainer = document.getElementById('plantingChart');
    chartContainer.innerHTML = '';
    
    // Create a simple timeline view using HTML/CSS
    const timelineHtml = `
        <div class="timeline-container">
            <div class="timeline-header">
                <h6>Planting Timeline Overview</h6>
                <p class="text-muted">Live data from farmOS</p>
            </div>
            <div class="timeline-content">
                ${generateTimelineItems(data)}
            </div>
        </div>
    `;
    
    chartContainer.innerHTML = timelineHtml;
}

function generateTimelineItems(data) {
    let items = '';
    Object.keys(data).forEach(location => {
        const activities = data[location] || [];
        if (activities.length > 0) {
            items += `
                <div class="timeline-location">
                    <h6 class="location-header">${location}</h6>
                    <div class="timeline-items">
            `;
            
            activities.forEach(activity => {
                const startDate = new Date(activity.start).toLocaleDateString();
                const endDate = new Date(activity.end).toLocaleDateString();
                const duration = Math.ceil((new Date(activity.end) - new Date(activity.start)) / (1000 * 60 * 60 * 24));
                
                items += `
                    <div class="timeline-item ${activity.type}" onclick="showCropDetails('${activity.crop}', '${activity.type}', '${location}', '${activity.variety || 'N/A'}', '${startDate}', '${endDate}', '${duration}')">
                        <div class="timeline-marker ${activity.type}"></div>
                        <div class="timeline-content-item">
                            <div class="timeline-title">${activity.crop} - ${activity.type}</div>
                            <div class="timeline-dates">${startDate} - ${endDate}</div>
                            <div class="timeline-variety">${activity.variety || 'Standard variety'}</div>
                        </div>
                    </div>
                `;
            });
            
            items += `
                    </div>
                </div>
            `;
        }
    });
    
    if (items === '') {
        items = '<div class="no-timeline-data">No planting activities found</div>';
    }
    
    return items;
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
    
    // Note: Timeline view doesn't change like Gantt chart
    // This is kept for UI consistency but doesn't affect the timeline
}

function updateStats(data) {
    let activePlantings = 0;
    let upcomingHarvests = 0;
    let availableBeds = 0;
    const now = new Date();
    const twoWeeksFromNow = new Date(now.getTime() + 14 * 24 * 60 * 60 * 1000);
    Object.keys(data).forEach(location => {
        const activities = data[location] || [];
        let hasActivity = false;
        activities.forEach(activity => {
            const startDate = new Date(activity.start);
            const endDate = new Date(activity.end);
            if (activity.type === 'growing' && startDate <= now && endDate >= now) {
                activePlantings++;
                hasActivity = true;
            }
            if (activity.type === 'harvest' && startDate >= now && startDate <= twoWeeksFromNow) {
                upcomingHarvests++;
                hasActivity = true;
            }
        });
        if (!hasActivity) {
            availableBeds++;
        }
    });
    document.getElementById('activePlantings').textContent = activePlantings;
    document.getElementById('upcomingHarvests').textContent = upcomingHarvests;
    document.getElementById('availableBeds').textContent = availableBeds;
}

function showCropDetails(crop, phase, location, variety, startDate, endDate, duration) {
    alert(`Crop Details:\n\nCrop: ${crop}\nPhase: ${phase}\nLocation: ${location}\nVariety: ${variety}\nStart: ${startDate}\nEnd: ${endDate}\nDuration: ${duration} days`);
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
    const testData = {
        'Block 1': [
            {
                id: 'test_lettuce_seeding',
                type: 'seeding',
                crop: 'lettuce',
                variety: 'Butter Lettuce',
                start: '2025-07-20',
                end: '2025-08-05',
                color: '#28a745',
                label: 'Lettuce (Seeding)'
            },
            {
                id: 'test_lettuce_growing',
                type: 'growing', 
                crop: 'lettuce',
                variety: 'Butter Lettuce',
                start: '2025-08-05',
                end: '2025-09-10',
                color: '#007bff',
                label: 'Lettuce (Growing)'
            }
        ],
        'Block 2': [
            {
                id: 'test_tomato_growing',
                type: 'growing',
                crop: 'tomato',
                variety: 'Cherry Tomato',
                start: '2025-07-01',
                end: '2025-09-30',
                color: '#007bff',
                label: 'Tomato (Growing)'
            }
        ]
    };
    renderTimelineChart(testData);
    updateStats(testData);
    showChart();
}
</script>

<style>
/* Timeline Styles */
.timeline-container {
    background: #fff;
    border-radius: 8px;
    padding: 20px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.timeline-header {
    margin-bottom: 20px;
    text-align: center;
}

.timeline-location {
    margin-bottom: 30px;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 15px;
}

.location-header {
    color: #495057;
    margin-bottom: 15px;
    padding-bottom: 8px;
    border-bottom: 2px solid #007bff;
}

.timeline-items {
    display: flex;
    flex-direction: column;
    gap: 10px;
}

.timeline-item {
    display: flex;
    align-items: center;
    padding: 12px;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
    border-left: 4px solid #6c757d;
}

.timeline-item:hover {
    background-color: #f8f9fa;
    transform: translateX(5px);
}

.timeline-item.seeding {
    border-left-color: #28a745;
    background-color: #f8fff9;
}

.timeline-item.growing {
    border-left-color: #007bff;
    background-color: #f8fcff;
}

.timeline-item.harvest {
    border-left-color: #ffc107;
    background-color: #fffcf5;
}

.timeline-marker {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    margin-right: 15px;
    flex-shrink: 0;
}

.timeline-marker.seeding {
    background-color: #28a745;
}

.timeline-marker.growing {
    background-color: #007bff;
}

.timeline-marker.harvest {
    background-color: #ffc107;
}

.timeline-content-item {
    flex-grow: 1;
}

.timeline-title {
    font-weight: 600;
    color: #343a40;
    margin-bottom: 4px;
}

.timeline-dates {
    font-size: 0.9em;
    color: #6c757d;
    margin-bottom: 2px;
}

.timeline-variety {
    font-size: 0.8em;
    color: #868e96;
}

.no-timeline-data {
    text-align: center;
    padding: 40px;
    color: #6c757d;
    font-style: italic;
}

#plantingChart { 
    min-height: 500px; 
    overflow-x: auto; 
}

/* Responsive */
@media (max-width: 768px) {
    .timeline-container {
        padding: 15px;
    }
    
    .timeline-item {
        padding: 10px;
    }
    
    .timeline-marker {
        width: 10px;
        height: 10px;
        margin-right: 10px;
    }
}
</style>
@endsection
