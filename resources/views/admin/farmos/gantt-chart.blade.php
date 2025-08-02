@extends('layouts.admin')

@section('title', 'Crop Planning Timeline')

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
                <i class="fas fa-chart-gantt"></i> Timeline View
            </li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-chart-gantt me-2"></i>Crop Planning Timeline
            </h1>
            <p class="text-muted mb-0">Visual timeline of crop cycles across all farm blocks</p>
        </div>
        
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm" id="zoomOut">
                <i class="fas fa-search-minus"></i> Zoom Out
            </button>
            <button class="btn btn-outline-primary btn-sm" id="zoomIn">
                <i class="fas fa-search-plus"></i> Zoom In
            </button>
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
                    <div class="legend-color" style="background-color: #28a745;"></div>
                    <small>Seeding</small>
                </div>
                <div class="d-flex align-items-center">
                    <div class="legend-color" style="background-color: #007bff;"></div>
                    <small>Growing</small>
                </div>
                <div class="d-flex align-items-center">
                    <div class="legend-color" style="background-color: #ffc107;"></div>
                    <small>Harvest</small>
                </div>
                <div class="d-flex align-items-center">
                    <div class="legend-color" style="background-color: #6c757d;"></div>
                    <small>Available</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Source Indicator -->
    @if($usingFarmOSData)
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

    <!-- Gantt Chart Container -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-calendar-alt me-2"></i>Timeline View
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
        
        <div class="card-body p-0">
            <!-- Loading State -->
            <div id="chartLoading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading chart data...</span>
                </div>
                <p class="text-muted mt-2">Loading crop timeline...</p>
            </div>
            
            <!-- Chart Canvas -->
            <div id="chartContainer" style="display: none;">
                <canvas id="ganttChart" style="height: 600px;"></canvas>
            </div>
            
            <!-- No Data State -->
            <div id="noDataMessage" class="text-center py-5" style="display: none;">
                <i class="fas fa-seedling fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Crop Data Available</h5>
                <p class="text-muted">No crop plans found for the selected date range and filters.</p>
                <button class="btn btn-primary" onclick="window.location.href='{{ route('admin.farmos.crop-plans') }}'">
                    <i class="fas fa-plus"></i> Add Crop Plans
                </button>
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
                    <h5 class="card-title text-info" id="totalLocations">{{ count($locations) }}</h5>
                    <p class="card-text text-muted">Farm Locations</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="cropDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crop Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="cropDetailContent">
                    <!-- Dynamic content will be loaded here -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="editCropPlan">
                    <i class="fas fa-edit"></i> Edit in farmOS
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.legend-color {
    width: 16px;
    height: 16px;
    border-radius: 3px;
    margin-right: 6px;
    border: 1px solid rgba(0,0,0,0.1);
}

.alert-sm {
    padding: 0.5rem 0.75rem;
    font-size: 0.875rem;
}

#chartContainer {
    position: relative;
    min-height: 600px;
}

.btn-group .btn.active {
    background-color: #007bff;
    border-color: #007bff;
    color: white;
}

.gantt-tooltip {
    position: absolute;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 10px;
    border-radius: 4px;
    font-size: 12px;
    pointer-events: none;
    z-index: 1000;
}

/* Responsive chart */
@media (max-width: 768px) {
    #chartContainer {
        min-height: 400px;
    }
    
    .card-body {
        padding: 1rem;
    }
}
</style>
@endpush

@push('scripts')
<!-- Chart.js and required plugins -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>

<script>
// Global variables
let ganttChart = null;
let currentData = null;
let currentView = 'months';

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeGanttChart();
    setupEventListeners();
});

function initializeGanttChart() {
    console.log('Initializing Gantt Chart...');
    loadChartData();
}

function setupEventListeners() {
    // Filter controls
    document.getElementById('applyFilters').addEventListener('click', applyFilters);
    document.getElementById('clearFilters').addEventListener('click', clearFilters);
    document.getElementById('refreshData').addEventListener('click', () => loadChartData());
    
    // View controls
    document.getElementById('viewWeeks').addEventListener('click', () => changeView('weeks'));
    document.getElementById('viewMonths').addEventListener('click', () => changeView('months'));
    document.getElementById('viewQuarters').addEventListener('click', () => changeView('quarters'));
    
    // Zoom controls
    document.getElementById('zoomIn').addEventListener('click', zoomIn);
    document.getElementById('zoomOut').addEventListener('click', zoomOut);
}

function loadChartData() {
    showLoading(true);
    
    const params = new URLSearchParams({
        location: document.getElementById('locationFilter').value,
        crop_type: document.getElementById('cropTypeFilter').value,
        start_date: document.getElementById('startDate').value,
        end_date: document.getElementById('endDate').value
    });
    
    fetch(`{{ route('admin.farmos.gantt-data') }}?${params}`)
        .then(response => response.json())
        .then(data => {
            console.log('Gantt data received:', data);
            currentData = data;
            
            if (data.success && data.data && Object.keys(data.data).length > 0) {
                renderGanttChart(data.data);
                updateStats(data.data);
                showChart(true);
            } else {
                showNoData(true);
            }
        })
        .catch(error => {
            console.error('Error loading gantt data:', error);
            showNoData(true);
        })
        .finally(() => {
            showLoading(false);
        });
}

function renderGanttChart(data) {
    const ctx = document.getElementById('ganttChart').getContext('2d');
    
    // Destroy existing chart
    if (ganttChart) {
        ganttChart.destroy();
    }
    
    // Transform data for Chart.js
    const chartData = transformDataForChart(data);
    
    ganttChart = new Chart(ctx, {
        type: 'bar',
        data: chartData,
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'point'
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: {
                            size: 12
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleColor: '#fff',
                    bodyColor: '#fff',
                    borderColor: 'rgba(255, 255, 255, 0.1)',
                    borderWidth: 1,
                    cornerRadius: 6,
                    displayColors: false,
                    callbacks: {
                        title: function(context) {
                            const data = context[0].raw;
                            return `${data.crop || 'Unknown Crop'} - ${data.phase || 'Unknown Phase'}`;
                        },
                        label: function(context) {
                            const data = context.raw;
                            const startDate = new Date(data.start).toLocaleDateString();
                            const endDate = new Date(data.end).toLocaleDateString();
                            return [
                                `Location: ${data.y}`,
                                `Variety: ${data.variety || 'N/A'}`,
                                `Start: ${startDate}`,
                                `End: ${endDate}`,
                                `Duration: ${data.duration} days`
                            ];
                        }
                    }
                }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: currentView === 'weeks' ? 'week' : 
                              currentView === 'months' ? 'month' : 'quarter',
                        displayFormats: {
                            week: 'MMM dd',
                            month: 'MMM yyyy',
                            quarter: 'QQQ yyyy'
                        },
                        tooltipFormat: 'MMM dd, yyyy'
                    },
                    title: {
                        display: true,
                        text: 'Timeline',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    grid: {
                        display: true,
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Farm Locations',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    },
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                }
            },
            onClick: (event, elements) => {
                if (elements.length > 0) {
                    const element = elements[0];
                    const datasetIndex = element.datasetIndex;
                    const index = element.index;
                    const clickedData = ganttChart.data.datasets[datasetIndex].data[index];
                    showCropDetails(clickedData);
                }
            }
        }
    });
}

function transformDataForChart(data) {
    const datasets = [];
    const labels = Object.keys(data);
    
    // Enhanced color mapping for different crop phases
    const colorMap = {
        'seeding': {
            background: 'rgba(40, 167, 69, 0.8)',
            border: '#28a745'
        },
        'growing': {
            background: 'rgba(0, 123, 255, 0.8)', 
            border: '#007bff'
        },
        'harvest': {
            background: 'rgba(255, 193, 7, 0.8)',
            border: '#ffc107'
        }
    };
    
    const defaultColor = {
        background: 'rgba(108, 117, 125, 0.8)',
        border: '#6c757d'
    };
    
    // Create separate datasets for each phase type for better legend control
    const phases = ['seeding', 'growing', 'harvest'];
    const dataByPhase = {};
    
    phases.forEach(phase => {
        dataByPhase[phase] = [];
    });
    
    labels.forEach((location) => {
        const locationData = data[location] || [];
        
        locationData.forEach((item) => {
            const startDate = new Date(item.start);
            const endDate = new Date(item.end);
            const duration = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
            
            const phase = item.type || 'other';
            const colors = colorMap[phase] || defaultColor;
            
            const barData = {
                x: [startDate, endDate],
                y: location,
                crop: item.crop,
                variety: item.variety,
                start: item.start,
                end: item.end,
                duration: duration,
                label: item.label,
                phase: phase,
                details: item.details
            };
            
            if (dataByPhase[phase]) {
                dataByPhase[phase].push(barData);
            }
        });
    });
    
    // Create datasets for each phase
    phases.forEach(phase => {
        if (dataByPhase[phase].length > 0) {
            const colors = colorMap[phase] || defaultColor;
            datasets.push({
                label: phase.charAt(0).toUpperCase() + phase.slice(1),
                data: dataByPhase[phase],
                backgroundColor: colors.background,
                borderColor: colors.border,
                borderWidth: 2,
                barPercentage: 0.8,
                categoryPercentage: 0.9
            });
        }
    });
    
    return {
        labels: labels,
        datasets: datasets
    };
}

function showCropDetails(data) {
    const modal = new bootstrap.Modal(document.getElementById('cropDetailModal'));
    const content = document.getElementById('cropDetailContent');
    
    content.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h6>Basic Information</h6>
                <ul class="list-unstyled">
                    <li><strong>Crop:</strong> ${data.crop || 'N/A'}</li>
                    <li><strong>Variety:</strong> ${data.variety || 'N/A'}</li>
                    <li><strong>Location:</strong> ${data.y || 'N/A'}</li>
                    <li><strong>Phase:</strong> ${data.label || 'N/A'}</li>
                </ul>
            </div>
            <div class="col-md-6">
                <h6>Timeline</h6>
                <ul class="list-unstyled">
                    <li><strong>Start:</strong> ${data.start || 'N/A'}</li>
                    <li><strong>End:</strong> ${data.end || 'N/A'}</li>
                    <li><strong>Duration:</strong> ${data.duration || 0} days</li>
                </ul>
            </div>
        </div>
    `;
    
    modal.show();
}

function updateStats(data) {
    let activePlantings = 0;
    let upcomingHarvests = 0;
    let totalItems = 0;
    
    Object.values(data).forEach(locationData => {
        locationData.forEach(item => {
            totalItems++;
            
            if (item.type === 'growing') {
                activePlantings++;
            }
            
            if (item.type === 'harvest') {
                const harvestDate = new Date(item.start);
                const now = new Date();
                const twoWeeksFromNow = new Date(now.getTime() + (14 * 24 * 60 * 60 * 1000));
                
                if (harvestDate >= now && harvestDate <= twoWeeksFromNow) {
                    upcomingHarvests++;
                }
            }
        });
    });
    
    document.getElementById('activePlantings').textContent = activePlantings;
    document.getElementById('upcomingHarvests').textContent = upcomingHarvests;
    document.getElementById('availableBeds').textContent = Object.keys(data).length;
}

function changeView(view) {
    currentView = view;
    
    // Update button states
    document.querySelectorAll('[id^="view"]').forEach(btn => btn.classList.remove('active'));
    document.getElementById(`view${view.charAt(0).toUpperCase() + view.slice(1)}`).classList.add('active');
    
    // Re-render chart with new view
    if (currentData && currentData.data) {
        renderGanttChart(currentData.data);
    }
}

function applyFilters() {
    loadChartData();
}

function clearFilters() {
    document.getElementById('locationFilter').value = '';
    document.getElementById('cropTypeFilter').value = '';
    document.getElementById('startDate').value = '{{ now()->subMonths(2)->format('Y-m-d') }}';
    document.getElementById('endDate').value = '{{ now()->addMonths(4)->format('Y-m-d') }}';
    loadChartData();
}

function zoomIn() {
    if (ganttChart && ganttChart.options.scales.x.time) {
        // Implement zoom functionality
        console.log('Zoom in not yet implemented');
    }
}

function zoomOut() {
    if (ganttChart && ganttChart.options.scales.x.time) {
        // Implement zoom functionality
        console.log('Zoom out not yet implemented');
    }
}

function showLoading(show) {
    document.getElementById('chartLoading').style.display = show ? 'block' : 'none';
}

function showChart(show) {
    document.getElementById('chartContainer').style.display = show ? 'block' : 'none';
    document.getElementById('noDataMessage').style.display = 'none';
}

function showNoData(show) {
    document.getElementById('noDataMessage').style.display = show ? 'block' : 'none';
    document.getElementById('chartContainer').style.display = 'none';
}
</script>
@endpush
