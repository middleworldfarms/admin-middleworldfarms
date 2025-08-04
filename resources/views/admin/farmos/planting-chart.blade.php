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

    <!-- Temporary Debug Information -->
    @if(config('app.debug'))
        <div class="alert alert-info alert-sm mb-4">
            <strong>Debug Info:</strong> 
            Chart data keys: {{ implode(', ', array_keys($chartData ?? [])) }} |
            Locations count: {{ count($locations ?? []) }} |
            Crop types: {{ implode(', ', $cropTypes ?? []) }}
        </div>
    @endif

    <!-- Debug Data Display (temporary) -->
    @if(config('app.debug'))
        <div class="alert alert-info alert-sm mb-4">
            <h6>Debug Information:</h6>
            <p><strong>Chart Data Keys:</strong> {{ implode(', ', array_keys($chartData ?? [])) }}</p>
            <p><strong>Chart Data Count:</strong> {{ count($chartData ?? []) }}</p>
            <p><strong>Locations:</strong> {{ implode(', ', $locations ?? []) }}</p>
            <p><strong>Crop Types:</strong> {{ implode(', ', $cropTypes ?? []) }}</p>
            @if(isset($chartData) && count($chartData) > 0)
                <details>
                    <summary>First Chart Data Sample</summary>
                    <pre style="max-height: 200px; overflow-y: auto;">{{ json_encode(array_slice($chartData, 0, 2, true), JSON_PRETTY_PRINT) }}</pre>
                </details>
            @endif
        </div>
    @endif

    <!-- Planting Chart Container -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center" 
             style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; border-radius: 8px 8px 0 0;">
            <h5 class="mb-0">
                <i class="fas fa-chart-gantt me-2"></i>Timeline View by Block
            </h5>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-light btn-sm" id="viewWeeks">
                    <i class="fas fa-calendar-week"></i> Weeks
                </button>
                <button class="btn btn-light btn-sm active" id="viewMonths">
                    <i class="fas fa-calendar"></i> Months
                </button>
                <button class="btn btn-outline-light btn-sm" id="viewQuarters">
                    <i class="fas fa-calendar-plus"></i> Quarters
                </button>
            </div>
        </div>
        
        <div class="card-body">
            <!-- Block Tabs -->
            <ul class="nav nav-tabs mb-3" id="blockTabs" role="tablist">
                <!-- Tabs will be generated dynamically by JavaScript -->
            </ul>
            
            <!-- Loading State -->
            <div id="chartLoading" class="text-center py-5">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading chart data...</span>
                </div>
                <p class="text-muted mt-2">Loading planting timeline...</p>
            </div>
            
            <!-- Tab Content -->
            <div class="tab-content" id="blockTabContent" style="display: none;">
                <!-- Tab panes will be generated dynamically by JavaScript -->
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
                    <h5 class="card-title text-info" id="totalBeds">-</h5>
                    <p class="card-text text-muted">Total Beds</p>
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
    try {
        // Debug: Log the data we're working with
        console.log('=== Planting Chart Debug ===');
        console.log('Server Data:', serverData);
        console.log('Using FarmOS Data:', usingFarmOSData);
        console.log('Data type:', typeof serverData);
        console.log('Data keys:', Object.keys(serverData || {}));
        
        // Initialize with server data
        currentData = { data: serverData };
        
        // Check if serverData is valid and has content
        let hasValidData = false;
        if (serverData && typeof serverData === 'object') {
            if (Array.isArray(serverData)) {
                hasValidData = serverData.length > 0;
                console.log('Server data is an array with length:', serverData.length);
            } else {
                hasValidData = Object.keys(serverData).length > 0;
                console.log('Server data is an object with keys:', Object.keys(serverData));
            }
        }
        
        if (hasValidData) {
            console.log('Rendering timeline with server data...');
            renderTimelineChart(serverData);
            updateStats(serverData);
            showChart();
        } else {
            console.log('No valid server data, showing test data...');
            showTestData();
        }
        
        setupEventListeners();
    } catch (error) {
        console.error('Error initializing planting chart:', error);
        // Ensure loading is hidden even if there's an error
        showLoading(false);
        showError('Failed to initialize planting chart: ' + error.message);
    }
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
    
    // Setup horizontal scrolling for timeline
    setupTimelineScrolling();
}

function setupTimelineScrolling() {
    // Add horizontal scrolling with mouse wheel
    document.addEventListener('wheel', function(e) {
        const timelineContainer = document.querySelector('.horizontal-timeline');
        if (timelineContainer && timelineContainer.contains(e.target)) {
            
            // Check if this should be timeline horizontal scrolling
            let shouldScrollTimeline = false;
            let scrollAmount = 0;
            
            // Use horizontal wheel (trackpad horizontal swipe)
            if (e.deltaX && Math.abs(e.deltaX) > 0) {
                shouldScrollTimeline = true;
                scrollAmount = e.deltaX > 0 ? 200 : -200;
            }
            // Use Shift + vertical wheel as alternative
            else if (e.shiftKey && e.deltaY && Math.abs(e.deltaY) > 0) {
                shouldScrollTimeline = true;
                scrollAmount = e.deltaY > 0 ? 200 : -200;
            }
            
            if (shouldScrollTimeline) {
                e.preventDefault();
                timelineContainer.scrollLeft += scrollAmount;
                console.log('Timeline horizontal scroll:', scrollAmount, 'scrollLeft:', timelineContainer.scrollLeft);
            }
            // Otherwise allow normal vertical scrolling
        }
    }, { passive: false });
    
    // Add keyboard navigation
    document.addEventListener('keydown', function(e) {
        const timelineContainer = document.querySelector('.horizontal-timeline');
        if (timelineContainer && (e.key === 'ArrowLeft' || e.key === 'ArrowRight')) {
            e.preventDefault();
            const scrollAmount = e.key === 'ArrowRight' ? 200 : -200;
            timelineContainer.scrollLeft += scrollAmount;
        }
    });
    
    // Add touch/drag scrolling for mobile
    let isScrolling = false;
    let startX = 0;
    let scrollLeft = 0;
    
    document.addEventListener('mousedown', function(e) {
        const timelineContainer = document.querySelector('.horizontal-timeline');
        if (timelineContainer && timelineContainer.contains(e.target)) {
            isScrolling = true;
            startX = e.pageX - timelineContainer.offsetLeft;
            scrollLeft = timelineContainer.scrollLeft;
            timelineContainer.style.cursor = 'grabbing';
        }
    });
    
    document.addEventListener('mousemove', function(e) {
        const timelineContainer = document.querySelector('.horizontal-timeline');
        if (!isScrolling || !timelineContainer) return;
        
        e.preventDefault();
        const x = e.pageX - timelineContainer.offsetLeft;
        const walk = (x - startX) * 3; // Increased scroll speed multiplier from 2 to 3
        timelineContainer.scrollLeft = scrollLeft - walk;
    });
    
    document.addEventListener('mouseup', function() {
        const timelineContainer = document.querySelector('.horizontal-timeline');
        if (timelineContainer) {
            isScrolling = false;
            timelineContainer.style.cursor = 'grab';
        }
    });
}

// Timeline navigation functions
function scrollToToday() {
    const timelineContainer = document.querySelector('.horizontal-timeline');
    if (!timelineContainer) return;
    
    // Find today's position on the timeline
    const today = new Date();
    const dateMarkers = document.querySelectorAll('.date-marker');
    
    if (dateMarkers.length > 0) {
        // Calculate approximate position for today
        const containerWidth = timelineContainer.scrollWidth;
        const now = new Date();
        const yearStart = new Date(now.getFullYear(), 0, 1);
        const dayOfYear = Math.floor((now - yearStart) / (24 * 60 * 60 * 1000));
        const position = (dayOfYear / 365) * containerWidth;
        
        timelineContainer.scrollTo({
            left: Math.max(0, position - timelineContainer.clientWidth / 2),
            behavior: 'smooth'
        });
    }
}

function scrollToStart() {
    const timelineContainer = document.querySelector('.horizontal-timeline');
    if (timelineContainer) {
        timelineContainer.scrollTo({ left: 0, behavior: 'smooth' });
    }
}

function scrollToEnd() {
    const timelineContainer = document.querySelector('.horizontal-timeline');
    if (timelineContainer) {
        timelineContainer.scrollTo({ 
            left: timelineContainer.scrollWidth, 
            behavior: 'smooth' 
        });
    }
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
    // Create tabs for blocks and organize data
    createBlockTabs(data);
    createBlockTabContent(data);
    
    // Show the tab content
    document.getElementById('blockTabContent').style.display = 'block';
}

function createBlockTabs(data) {
    const tabsContainer = document.getElementById('blockTabs');
    tabsContainer.innerHTML = '';
    
    // Extract blocks from data (anything starting with "Block")
    const blocks = [];
    Object.keys(data).forEach(location => {
        if (location.startsWith('Block ')) {
            blocks.push(location);
        }
    });
    
    // Sort blocks naturally
    blocks.sort((a, b) => {
        const aNum = parseInt(a.replace('Block ', ''));
        const bNum = parseInt(b.replace('Block ', ''));
        return aNum - bNum;
    });
    
    // If no blocks, create default tabs for Block 1-10
    if (blocks.length === 0) {
        for (let i = 1; i <= 10; i++) {
            blocks.push(`Block ${i}`);
        }
    }
    
    // Create tab navigation
    blocks.forEach((block, index) => {
        const tabId = block.replace(' ', '').toLowerCase(); // "block1", "block2", etc.
        const isActive = index === 0 ? 'active' : '';
        
        const tabHtml = `
            <li class="nav-item" role="presentation">
                <button class="nav-link ${isActive}" id="${tabId}-tab" data-bs-toggle="tab" 
                        data-bs-target="#${tabId}" type="button" role="tab" 
                        aria-controls="${tabId}" aria-selected="${index === 0 ? 'true' : 'false'}">
                    ${block}
                </button>
            </li>
        `;
        tabsContainer.innerHTML += tabHtml;
    });
}

function createBlockTabContent(data) {
    const contentContainer = document.getElementById('blockTabContent');
    contentContainer.innerHTML = '';
    
    // Extract blocks from data
    const blocks = [];
    Object.keys(data).forEach(location => {
        if (location.startsWith('Block ')) {
            blocks.push(location);
        }
    });
    
    // Sort blocks naturally
    blocks.sort((a, b) => {
        const aNum = parseInt(a.replace('Block ', ''));
        const bNum = parseInt(b.replace('Block ', ''));
        return aNum - bNum;
    });
    
    // If no blocks, create default tabs for Block 1-10
    if (blocks.length === 0) {
        for (let i = 1; i <= 10; i++) {
            blocks.push(`Block ${i}`);
        }
    }
    
    // Create content for each block
    blocks.forEach((block, index) => {
        const tabId = block.replace(' ', '').toLowerCase();
        const isActive = index === 0 ? 'show active' : '';
        
        // Get data for this block and related beds
        const blockData = getBlockData(data, block);
        const blockTimelineHtml = generateBlockTimeline(block, blockData);
        
        const tabContentHtml = `
            <div class="tab-pane fade ${isActive}" id="${tabId}" role="tabpanel" 
                 aria-labelledby="${tabId}-tab">
                <div class="block-timeline-container">
                    ${blockTimelineHtml}
                </div>
            </div>
        `;
        contentContainer.innerHTML += tabContentHtml;
    });
}

function getBlockData(data, targetBlock) {
    const blockData = {};
    
    // Get direct block data
    if (data[targetBlock]) {
        blockData[targetBlock] = data[targetBlock];
    }
    
    // Get beds that belong to this block (look for bed naming patterns)
    const blockNumber = targetBlock.replace('Block ', '');
    Object.keys(data).forEach(location => {
        // Look for beds with patterns like "1/1", "1/2" etc. or "Bed 1", "bed 2" etc.
        if (location.startsWith(`${blockNumber}/`) || 
            location.toLowerCase().includes(`bed `) ||
            location.toLowerCase().includes(`${targetBlock.toLowerCase()}`)) {
            blockData[location] = data[location];
        }
    });
    
    return blockData;
}

function generateBlockTimeline(blockName, blockData) {
    if (!blockData || Object.keys(blockData).length === 0) {
        return `
            <div class="empty-block-message text-center py-4">
                <i class="fas fa-seedling fa-2x text-muted mb-3"></i>
                <h6 class="text-muted">${blockName} - No Activities</h6>
                <p class="text-muted small">This block has no current plantings or activities.</p>
                <small class="text-muted">Beds: Ready for planting</small>
            </div>
        `;
    }
    
    return `
        <div class="timeline-container">
            <div class="timeline-header">
                <h6>${blockName} Timeline</h6>
                <p class="text-muted">Plantings and activities for this block</p>
            </div>
            <div class="timeline-content">
                ${generateTimelineItems(blockData)}
            </div>
        </div>
    `;
}

function generateTimelineItems(data) {
    if (!data || Object.keys(data).length === 0) {
        return '<div class="no-timeline-data">No planting activities found</div>';
    }

    // Create a horizontal timeline chart
    return generateHorizontalTimeline(data);
}

function generateHorizontalTimeline(data) {
    // Get date range for the timeline
    const { startDate, endDate, allActivities } = getTimelineData(data);
    
    if (allActivities.length === 0) {
        return '<div class="no-timeline-data">No activities with valid dates found</div>';
    }

    // Generate timeline HTML with scroll container
    return `
        <div class="horizontal-timeline">
            <div class="timeline-header-section">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6>Timeline: ${startDate.toLocaleDateString()} - ${endDate.toLocaleDateString()}</h6>
                        <p class="text-muted">Scroll horizontally to navigate through time • Use Shift+scroll, horizontal scroll, or arrow keys</p>
                    </div>
                    <div class="timeline-navigation">
                        <button class="btn btn-outline-success btn-sm" onclick="scrollToToday()">
                            <i class="fas fa-calendar-day"></i> Today
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="scrollToStart()">
                            <i class="fas fa-step-backward"></i> Start
                        </button>
                        <button class="btn btn-outline-success btn-sm" onclick="scrollToEnd()">
                            <i class="fas fa-step-forward"></i> End
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="timeline-scroll-container">
                <!-- Date Scale -->
                <div class="date-scale">
                    ${generateDateScale(startDate, endDate)}
                </div>
                
                <!-- Timeline Tracks -->
                <div class="timeline-tracks">
                    ${generateTimelineTracks(data, startDate, endDate)}
                </div>
            </div>
        </div>
    `;
}

function getTimelineData(data) {
    const allActivities = [];
    let earliestDate = new Date();
    let latestDate = new Date();
    
    // Collect all activities with dates
    Object.keys(data).forEach(location => {
        const activities = data[location] || [];
        // Ensure activities is an array before calling forEach
        if (Array.isArray(activities)) {
            activities.forEach(activity => {
                if (activity.start && activity.end) {
                    const start = new Date(activity.start);
                    const end = new Date(activity.end);
                    
                    if (!isNaN(start.getTime()) && !isNaN(end.getTime())) {
                        allActivities.push({
                            ...activity,
                            location: location,
                            startDate: start,
                            endDate: end
                        });
                        
                        if (start < earliestDate) earliestDate = start;
                        if (end > latestDate) latestDate = end;
                    }
                }
            });
        }
    });
    
    // Create a full year timeline regardless of activities
    const now = new Date();
    const startDate = new Date(now.getFullYear(), 0, 1); // January 1st of current year
    const endDate = new Date(now.getFullYear(), 11, 31); // December 31st of current year
    
    // If we have activities, extend the range to include them
    if (allActivities.length > 0) {
        if (earliestDate < startDate) {
            startDate.setFullYear(earliestDate.getFullYear(), 0, 1);
        }
        if (latestDate > endDate) {
            endDate.setFullYear(latestDate.getFullYear(), 11, 31);
        }
    }
    
    return { startDate, endDate, allActivities };
}

function generateDateScale(startDate, endDate) {
    const totalDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
    const dayWidth = 10; // 10px per day
    const totalWidth = totalDays * dayWidth;
    
    const markers = [];
    
    // Create daily markers
    let currentDate = new Date(startDate);
    let dayIndex = 0;
    
    while (currentDate <= endDate) {
        const leftPosition = dayIndex * dayWidth;
        const isFirstOfMonth = currentDate.getDate() === 1;
        const isMonday = currentDate.getDay() === 1;
        
        // Month markers (major)
        if (isFirstOfMonth) {
            const monthName = currentDate.toLocaleDateString('en-US', { 
                month: 'short', 
                year: currentDate.getFullYear() !== new Date().getFullYear() ? 'numeric' : undefined
            });
            
            markers.push(`
                <div class="date-marker month-marker" style="left: ${leftPosition}px;">
                    <div class="date-line major-line"></div>
                    <div class="date-label month-label">${monthName}</div>
                </div>
            `);
        }
        // Week markers (minor) - show on Mondays
        else if (isMonday) {
            const weekLabel = currentDate.getDate();
            markers.push(`
                <div class="date-marker week-marker" style="left: ${leftPosition}px;">
                    <div class="date-line minor-line"></div>
                    <div class="date-label week-label">${weekLabel}</div>
                </div>
            `);
        }
        // Day markers (minimal) - just a small tick
        else {
            markers.push(`
                <div class="date-marker day-marker" style="left: ${leftPosition}px;">
                    <div class="date-line day-line"></div>
                </div>
            `);
        }
        
        // Move to next day
        currentDate.setDate(currentDate.getDate() + 1);
        dayIndex++;
    }
    
    return markers.join('');
}

function generateTimelineTracks(data, startDate, endDate) {
    const tracks = [];
    const totalDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
    const dayWidth = 10; // 10px per day
    
    Object.keys(data).forEach(location => {
        const activities = data[location] || [];
        // Ensure activities is an array before calling filter
        const validActivities = Array.isArray(activities) ? activities.filter(activity => 
            activity.start && activity.end && 
            !isNaN(new Date(activity.start).getTime()) && 
            !isNaN(new Date(activity.end).getTime())
        ) : [];
        
        if (validActivities.length > 0 || true) { // Show all locations
            tracks.push(`
                <div class="timeline-track">
                    <div class="track-label">
                        <span class="location-name">${location}</span>
                        <small class="activity-count">${validActivities.length} activities</small>
                    </div>
                    <div class="track-timeline">
                        ${generateTrackBars(validActivities, startDate, endDate, totalDays, dayWidth)}
                    </div>
                </div>
            `);
        }
    });
    
    return tracks.join('');
}

function generateTrackBars(activities, startDate, endDate, totalDays, dayWidth) {
    if (activities.length === 0) {
        return '<div class="empty-track">No activities scheduled</div>';
    }
    
    return activities.map(activity => {
        const activityStart = new Date(activity.start);
        const activityEnd = new Date(activity.end);
        
        // Calculate days from start date
        const startDayOffset = Math.floor((activityStart - startDate) / (1000 * 60 * 60 * 24));
        const activityDays = Math.ceil((activityEnd - activityStart) / (1000 * 60 * 60 * 24));
        
        // Convert to pixels
        const leftPx = Math.max(0, startDayOffset * dayWidth);
        const widthPx = Math.max(dayWidth, activityDays * dayWidth);
        
        const activityClass = `activity-bar activity-${activity.type}`;
        const duration = Math.ceil((activityEnd - activityStart) / (1000 * 60 * 60 * 24));
        
        return `
            <div class="${activityClass}" 
                 style="left: ${leftPx}px; width: ${widthPx}px"
                 onclick="showCropDetails('${activity.crop}', '${activity.type}', '${activity.location || 'Unknown'}', '${activity.variety || 'N/A'}', '${activityStart.toLocaleDateString()}', '${activityEnd.toLocaleDateString()}', '${duration}')"
                 title="${activity.crop} - ${activity.type} (${activityStart.toLocaleDateString()} - ${activityEnd.toLocaleDateString()})">
                <div class="activity-content">
                    <span class="activity-name">${activity.crop}</span>
                    <span class="activity-type">${activity.type}</span>
                </div>
            </div>
        `;
    }).join('');
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
    let totalBeds = 0;
    const now = new Date();
    const twoWeeksFromNow = new Date(now.getTime() + 14 * 24 * 60 * 60 * 1000);
    
    Object.keys(data).forEach(location => {
        const activities = data[location] || [];
        totalBeds++; // Count each location/bed
        
        // Ensure activities is an array before calling forEach
        if (Array.isArray(activities)) {
            activities.forEach(activity => {
                const startDate = new Date(activity.start);
                const endDate = new Date(activity.end);
                if (activity.type === 'growing' && startDate <= now && endDate >= now) {
                    activePlantings++;
                }
                if (activity.type === 'harvest' && startDate >= now && startDate <= twoWeeksFromNow) {
                    upcomingHarvests++;
                }
            });
        }
    });
    
    document.getElementById('activePlantings').textContent = activePlantings;
    document.getElementById('upcomingHarvests').textContent = upcomingHarvests;
    document.getElementById('totalBeds').textContent = totalBeds;
}

function showCropDetails(crop, phase, location, variety, startDate, endDate, duration) {
    alert(`Crop Details:\n\nCrop: ${crop}\nPhase: ${phase}\nLocation: ${location}\nVariety: ${variety}\nStart: ${startDate}\nEnd: ${endDate}\nDuration: ${duration} days`);
}

function showLoading(show) {
    document.getElementById('chartLoading').style.display = show ? 'block' : 'none';
}

function showChart() {
    document.getElementById('blockTabContent').style.display = 'block';
    document.getElementById('blockTabs').style.display = 'flex';
    document.getElementById('noDataMessage').style.display = 'none';
    document.getElementById('chartLoading').style.display = 'none';
}

function showNoData() {
    document.getElementById('noDataMessage').style.display = 'block';
    document.getElementById('blockTabContent').style.display = 'none';
    document.getElementById('blockTabs').style.display = 'none';
    document.getElementById('chartLoading').style.display = 'none';
}

function showError(message) {
    document.getElementById('noDataMessage').style.display = 'block';
    document.getElementById('noDataMessage').querySelector('h5').textContent = 'Error Loading Data';
    document.getElementById('noDataMessage').querySelector('p').textContent = 'Error: ' + message;
    document.getElementById('blockTabContent').style.display = 'none';
    document.getElementById('blockTabs').style.display = 'none';
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
                start: '2025-03-15',
                end: '2025-04-01',
                color: '#28a745',
                label: 'Lettuce (Seeding)'
            },
            {
                id: 'test_lettuce_growing',
                type: 'growing', 
                crop: 'lettuce',
                variety: 'Butter Lettuce',
                start: '2025-04-01',
                end: '2025-05-15',
                color: '#007bff',
                label: 'Lettuce (Growing)'
            },
            {
                id: 'test_lettuce_harvest',
                type: 'harvest',
                crop: 'lettuce',
                variety: 'Butter Lettuce',
                start: '2025-05-15',
                end: '2025-05-30',
                color: '#ffc107',
                label: 'Lettuce (Harvest)'
            }
        ],
        '1/1': [
            {
                id: 'bed_1_1_spinach',
                type: 'seeding',
                crop: 'spinach',
                variety: 'Space Spinach',
                start: '2025-02-15',
                end: '2025-03-01',
                color: '#28a745',
                label: 'Spinach (Seeding)'
            },
            {
                id: 'bed_1_1_spinach_growing',
                type: 'growing',
                crop: 'spinach',
                variety: 'Space Spinach',
                start: '2025-03-01',
                end: '2025-04-15',
                color: '#007bff',
                label: 'Spinach (Growing)'
            }
        ],
        '1/2': [
            {
                id: 'bed_1_2_radish',
                type: 'seeding',
                crop: 'radish',
                variety: 'Cherry Belle',
                start: '2025-03-01',
                end: '2025-03-10',
                color: '#28a745',
                label: 'Radish (Seeding)'
            }
        ],
        '1/3': [
            {
                id: 'bed_1_3_kale',
                type: 'seeding',
                crop: 'kale',
                variety: 'Red Russian',
                start: '2025-04-01',
                end: '2025-04-15',
                color: '#28a745',
                label: 'Kale (Seeding)'
            },
            {
                id: 'bed_1_3_kale_growing',
                type: 'growing',
                crop: 'kale',
                variety: 'Red Russian',
                start: '2025-04-15',
                end: '2025-07-01',
                color: '#007bff',
                label: 'Kale (Growing)'
            }
        ],
        '1/4': [],
        '1/5': [
            {
                id: 'bed_1_5_peas',
                type: 'seeding',
                crop: 'peas',
                variety: 'Sugar Snap',
                start: '2025-03-15',
                end: '2025-03-25',
                color: '#28a745',
                label: 'Peas (Seeding)'
            }
        ],
        '1/6': [],
        '1/7': [
            {
                id: 'bed_1_7_arugula',
                type: 'seeding',
                crop: 'arugula',
                variety: 'Wild Rocket',
                start: '2025-05-01',
                end: '2025-05-10',
                color: '#28a745',
                label: 'Arugula (Seeding)'
            }
        ],
        '1/8': [],
        '1/9': [
            {
                id: 'bed_1_9_beets',
                type: 'seeding',
                crop: 'beets',
                variety: 'Detroit Dark Red',
                start: '2025-06-01',
                end: '2025-06-15',
                color: '#28a745',
                label: 'Beets (Seeding)'
            }
        ],
        '1/10': [],
        '1/11': [],
        '1/12': [
            {
                id: 'bed_1_12_cilantro',
                type: 'seeding',
                crop: 'cilantro',
                variety: 'Slow Bolt',
                start: '2025-04-15',
                end: '2025-04-25',
                color: '#28a745',
                label: 'Cilantro (Seeding)'
            }
        ],
        '1/13': [],
        '1/14': [],
        '1/15': [
            {
                id: 'bed_1_15_chard',
                type: 'seeding',
                crop: 'chard',
                variety: 'Rainbow',
                start: '2025-07-01',
                end: '2025-07-15',
                color: '#28a745',
                label: 'Chard (Seeding)'
            }
        ],
        '1/16': [],
        'Block 2': [
            {
                id: 'test_tomato_seeding',
                type: 'seeding',
                crop: 'tomato',
                variety: 'Cherry Tomato',
                start: '2025-02-01',
                end: '2025-02-20',
                color: '#28a745',
                label: 'Tomato (Seeding)'
            },
            {
                id: 'test_tomato_growing',
                type: 'growing',
                crop: 'tomato',
                variety: 'Cherry Tomato',
                start: '2025-02-20',
                end: '2025-07-30',
                color: '#007bff',
                label: 'Tomato (Growing)'
            },
            {
                id: 'test_tomato_harvest',
                type: 'harvest',
                crop: 'tomato',
                variety: 'Cherry Tomato',
                start: '2025-07-30',
                end: '2025-10-15',
                color: '#ffc107',
                label: 'Tomato (Harvest)'
            }
        ],
        '2/1': [
            {
                id: 'bed_2_1_basil',
                type: 'seeding',
                crop: 'basil',
                variety: 'Genovese',
                start: '2025-04-15',
                end: '2025-05-01',
                color: '#28a745',
                label: 'Basil (Seeding)'
            }
        ],
        '2/2': [
            {
                id: 'bed_2_2_peppers',
                type: 'seeding',
                crop: 'peppers',
                variety: 'Bell Pepper',
                start: '2025-03-01',
                end: '2025-03-20',
                color: '#28a745',
                label: 'Peppers (Seeding)'
            },
            {
                id: 'bed_2_2_peppers_growing',
                type: 'growing',
                crop: 'peppers',
                variety: 'Bell Pepper',
                start: '2025-03-20',
                end: '2025-08-15',
                color: '#007bff',
                label: 'Peppers (Growing)'
            }
        ],
        '2/3': [],
        '2/4': [
            {
                id: 'bed_2_4_eggplant',
                type: 'seeding',
                crop: 'eggplant',
                variety: 'Black Beauty',
                start: '2025-03-15',
                end: '2025-04-01',
                color: '#28a745',
                label: 'Eggplant (Seeding)'
            }
        ],
        '2/5': [],
        '2/6': [],
        '2/7': [
            {
                id: 'bed_2_7_cucumbers',
                type: 'seeding',
                crop: 'cucumbers',
                variety: 'Boston Pickling',
                start: '2025-05-01',
                end: '2025-05-15',
                color: '#28a745',
                label: 'Cucumbers (Seeding)'
            }
        ],
        '2/8': [],
        '2/9': [],
        '2/10': [
            {
                id: 'bed_2_10_zucchini',
                type: 'seeding',
                crop: 'zucchini',
                variety: 'Black Beauty',
                start: '2025-05-15',
                end: '2025-06-01',
                color: '#28a745',
                label: 'Zucchini (Seeding)'
            }
        ],
        '2/11': [],
        '2/12': [],
        '2/13': [
            {
                id: 'bed_2_13_herbs',
                type: 'seeding',
                crop: 'oregano',
                variety: 'Greek',
                start: '2025-04-01',
                end: '2025-04-15',
                color: '#28a745',
                label: 'Oregano (Seeding)'
            }
        ],
        '2/14': [],
        '2/15': [],
        '2/16': [
            {
                id: 'bed_2_16_parsley',
                type: 'seeding',
                crop: 'parsley',
                variety: 'Flat Leaf',
                start: '2025-03-15',
                end: '2025-03-30',
                color: '#28a745',
                label: 'Parsley (Seeding)'
            }
        ],
        'Block 3': [
            {
                id: 'test_carrot_seeding',
                type: 'seeding',
                crop: 'carrot',
                variety: 'Nantes',
                start: '2025-06-01',
                end: '2025-06-15',
                color: '#28a745',
                label: 'Carrot (Seeding)'
            },
            {
                id: 'test_carrot_growing',
                type: 'growing',
                crop: 'carrot',
                variety: 'Nantes',
                start: '2025-06-15',
                end: '2025-09-15',
                color: '#007bff',
                label: 'Carrot (Growing)'
            }
        ],
        '3/1': [],
        '3/2': [],
        '3/3': [],
        '3/4': [],
        '3/5': [],
        '3/6': [],
        '3/7': [],
        '3/8': [],
        '3/9': [],
        '3/10': [],
        '3/11': [],
        '3/12': [],
        '3/13': [],
        '3/14': [],
        '3/15': [],
        '3/16': [],
        'Block 4': [
            {
                id: 'test_winter_prep',
                type: 'seeding',
                crop: 'winter cover',
                variety: 'Rye Grass',
                start: '2025-10-01',
                end: '2025-10-15',
                color: '#28a745',
                label: 'Winter Cover (Seeding)'
            },
            {
                id: 'test_winter_growing',
                type: 'growing',
                crop: 'winter cover',
                variety: 'Rye Grass',
                start: '2025-10-15',
                end: '2025-12-31',
                color: '#007bff',
                label: 'Winter Cover (Growing)'
            }
        ],
        '4/1': [],
        '4/2': [],
        '4/3': [],
        '4/4': [],
        '4/5': [],
        '4/6': [],
        '4/7': [],
        '4/8': [],
        '4/9': [],
        '4/10': [],
        '4/11': [],
        '4/12': [],
        '4/13': [],
        '4/14': [],
        '4/15': [],
        '4/16': [],
        'Block 5': [],
        'Block 6': [],
        'Block 7': [],
        'Block 8': [],
        'Block 9': [],
        'Block 10': []
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

/* Block Tab Styles - Folder Tab Design */
.nav-tabs {
    border-bottom: 3px solid #28a745;
    margin-bottom: 0;
    background: linear-gradient(to bottom, #f8f9fa 0%, #e9ecef 100%);
    padding: 0 15px;
    border-radius: 8px 8px 0 0;
}

.nav-tabs .nav-item {
    margin-bottom: -3px;
    margin-right: 3px;
}

.nav-tabs .nav-link {
    color: #6c757d;
    background: linear-gradient(to bottom, #f1f3f4 0%, #e2e6ea 100%);
    border: 2px solid #dee2e6;
    border-bottom: none;
    border-radius: 12px 12px 0 0;
    padding: 12px 20px 15px 20px;
    font-weight: 600;
    font-size: 0.95rem;
    position: relative;
    transition: all 0.3s ease;
    box-shadow: 0 -2px 5px rgba(0,0,0,0.1);
    margin-right: 2px;
}

.nav-tabs .nav-link:hover {
    background: linear-gradient(to bottom, #fff 0%, #f8f9fa 100%);
    border-color: #28a745;
    color: #28a745;
    transform: translateY(-2px);
    box-shadow: 0 -4px 8px rgba(40, 167, 69, 0.2);
}

.nav-tabs .nav-link.active {
    color: #fff;
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-color: #28a745;
    border-bottom: 3px solid #28a745;
    transform: translateY(-3px);
    box-shadow: 0 -6px 12px rgba(40, 167, 69, 0.3);
    z-index: 10;
    position: relative;
}

.nav-tabs .nav-link.active::before {
    content: '';
    position: absolute;
    bottom: -3px;
    left: 0;
    right: 0;
    height: 3px;
    background: #28a745;
    border-radius: 0 0 3px 3px;
}

.nav-tabs .nav-link.active::after {
    content: '📁';
    margin-right: 8px;
    font-size: 1.1em;
}

.nav-tabs .nav-link:not(.active)::after {
    content: '📂';
    margin-right: 8px;
    font-size: 1.1em;
    opacity: 0.6;
}

/* Tab Content Styling */
.tab-content {
    background: #fff;
    border: 2px solid #28a745;
    border-top: none;
    border-radius: 0 0 8px 8px;
    min-height: 400px;
    box-shadow: 0 4px 12px rgba(40, 167, 69, 0.1);
}

.block-timeline-container {
    min-height: 400px;
    padding: 25px;
    background: linear-gradient(135deg, #f8fff9 0%, #ffffff 100%);
}

/* Enhanced Empty Block Message */
.empty-block-message {
    border: 2px dashed #28a745;
    border-radius: 12px;
    margin: 20px 0;
    background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);
    position: relative;
    overflow: hidden;
}

.empty-block-message::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: repeating-linear-gradient(
        45deg,
        transparent,
        transparent 10px,
        rgba(40, 167, 69, 0.05) 10px,
        rgba(40, 167, 69, 0.05) 20px
    );
    animation: shimmer 20s linear infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-50%) translateY(-50%) rotate(0deg); }
    100% { transform: translateX(-50%) translateY(-50%) rotate(360deg); }
}

.empty-block-message i {
    color: #28a745;
}

.empty-block-message h6 {
    color: #155724;
    font-weight: 600;
}

.empty-block-message p,
.empty-block-message small {
    color: #155724;
}

/* Enhanced Timeline Styling */
.timeline-header h6 {
    color: #28a745;
    font-weight: 700;
    font-size: 1.2rem;
    margin-bottom: 8px;
}

.timeline-header p {
    color: #6c757d;
    font-style: italic;
}

.timeline-container {
    background: linear-gradient(135deg, #ffffff 0%, #f8fff9 100%);
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.1);
    border: 1px solid rgba(40, 167, 69, 0.2);
}

.timeline-location {
    margin-bottom: 25px;
    border: 1px solid rgba(40, 167, 69, 0.3);
    border-radius: 8px;
    padding: 20px;
    background: linear-gradient(135deg, #fff 0%, #f8fff9 100%);
    box-shadow: 0 2px 6px rgba(40, 167, 69, 0.1);
}

.location-header {
    color: #155724;
    margin-bottom: 15px;
    padding-bottom: 10px;
    border-bottom: 2px solid #28a745;
    font-weight: 700;
}

/* Horizontal Timeline Styles */
.horizontal-timeline {
    background: linear-gradient(135deg, #ffffff 0%, #f8fff9 100%);
    border-radius: 12px;
    padding: 25px;
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.1);
    border: 1px solid rgba(40, 167, 69, 0.2);
    margin: 20px 0;
    overflow-x: auto;
    overflow-y: hidden;
    min-width: 100%;
    position: relative;
    scroll-behavior: smooth;
    cursor: grab;
}

.horizontal-timeline:active {
    cursor: grabbing;
}

/* Custom scrollbar */
.horizontal-timeline::-webkit-scrollbar {
    height: 12px;
}

.horizontal-timeline::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 6px;
}

.horizontal-timeline::-webkit-scrollbar-thumb {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-radius: 6px;
    border: 2px solid #f1f1f1;
}

.horizontal-timeline::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
}

.timeline-scroll-container {
    min-width: 3650px; /* 365 days * 10px = 3650px for a full year */
    width: max-content;
    position: relative;
}

.timeline-header-section {
    margin-bottom: 30px;
    text-align: center;
    border-bottom: 2px solid #28a745;
    padding-bottom: 15px;
}

.timeline-header-section h6 {
    color: #28a745;
    font-weight: 700;
    font-size: 1.3rem;
    margin-bottom: 5px;
}

.timeline-header-section p {
    color: #6c757d;
    font-style: italic;
    margin-bottom: 0;
}

.date-scale {
    position: relative;
    height: 60px;
    margin-bottom: 30px;
    border-bottom: 3px solid #28a745;
    background: linear-gradient(to bottom, transparent 80%, rgba(40, 167, 69, 0.1) 100%);
    min-width: 3650px; /* 365 days * 10px */
    width: 100%;
}

.date-marker {
    position: absolute;
    top: 0;
    height: 100%;
}

/* Month markers (major) */
.month-marker .major-line {
    width: 3px;
    height: 50px;
    background-color: #28a745;
    margin: 0 auto;
    border-radius: 1px;
}

.month-label {
    position: absolute;
    top: 45px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.9rem;
    color: #28a745;
    font-weight: 700;
    background: #fff;
    padding: 3px 8px;
    border-radius: 4px;
    border: 1px solid #28a745;
    white-space: nowrap;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

/* Week markers (minor) */
.week-marker .minor-line {
    width: 2px;
    height: 30px;
    background-color: #6c757d;
    margin: 0 auto;
    border-radius: 1px;
}

.week-label {
    position: absolute;
    top: 35px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.7rem;
    color: #6c757d;
    font-weight: 500;
    background: #f8f9fa;
    padding: 1px 4px;
    border-radius: 2px;
    white-space: nowrap;
}

/* Day markers (minimal) */
.day-marker .day-line {
    width: 1px;
    height: 15px;
    background-color: #dee2e6;
    margin: 0 auto;
}

.date-label {
    position: absolute;
    top: 45px;
    left: 50%;
    transform: translateX(-50%);
    font-size: 0.8rem;
    color: #6c757d;
    font-weight: 600;
    background: #fff;
    padding: 2px 6px;
    border-radius: 3px;
    border: 1px solid #dee2e6;
    white-space: nowrap;
}

.timeline-tracks {
    display: flex;
    flex-direction: column;
    gap: 15px;
    min-width: 3650px; /* 365 days * 10px */
    width: 100%;
}

.timeline-track {
    display: flex;
    align-items: center;
    min-height: 50px;
    border-bottom: 1px solid #e9ecef;
    padding: 10px 0;
}

.track-label {
    width: 200px;
    flex-shrink: 0;
    padding-right: 20px;
    text-align: right;
    border-right: 2px solid #28a745;
    margin-right: 20px;
}

.location-name {
    display: block;
    font-weight: 600;
    color: #495057;
    font-size: 0.95rem;
}

.activity-count {
    display: block;
    color: #6c757d;
    font-size: 0.75rem;
    margin-top: 2px;
}

.track-timeline {
    flex-grow: 1;
    position: relative;
    height: 40px;
    background: linear-gradient(to right, #f8f9fa 0%, #ffffff 50%, #f8f9fa 100%);
    border-radius: 20px;
    border: 1px solid #dee2e6;
    overflow: hidden;
}

.empty-track {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #adb5bd;
    font-style: italic;
    font-size: 0.85rem;
}

.activity-bar {
    position: absolute;
    top: 2px;
    height: 36px;
    border-radius: 18px;
    cursor: pointer;
    display: flex;
    align-items: center;
    padding: 0 10px;
    color: white;
    font-size: 0.8rem;
    font-weight: 600;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
    transition: all 0.3s ease;
    border: 2px solid rgba(255,255,255,0.3);
    box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    min-width: 60px;
    overflow: hidden;
}

.activity-bar:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    z-index: 10;
}

.activity-bar.activity-seeding {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-color: rgba(255,255,255,0.4);
}

.activity-bar.activity-growing {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border-color: rgba(255,255,255,0.4);
}

.activity-bar.activity-harvest {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
    border-color: rgba(255,255,255,0.4);
    color: #212529;
}

.activity-content {
    display: flex;
    flex-direction: column;
    width: 100%;
    min-width: 0;
}

.activity-name {
    font-weight: 700;
    font-size: 0.85rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.activity-type {
    font-size: 0.7rem;
    opacity: 0.9;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    text-transform: capitalize;
}

/* Responsive */
@media (max-width: 768px) {
    .timeline-container {
        padding: 15px;
    }
    
    .horizontal-timeline {
        padding: 15px;
    }
    
    .timeline-track {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
    
    .track-label {
        width: 100%;
        text-align: left;
        border-right: none;
        border-bottom: 1px solid #28a745;
        padding-bottom: 10px;
        margin-right: 0;
        margin-bottom: 10px;
    }
    
    .track-timeline {
        width: 100%;
    }
    
    .timeline-item {
        padding: 10px;
    }
    
    .timeline-marker {
        width: 10px;
        height: 10px;
        margin-right: 10px;
    }
    
    .block-timeline-container {
        padding: 10px;
    }
    
    .nav-tabs {
        flex-wrap: wrap;
    }
    
    .nav-tabs .nav-link {
        font-size: 0.875rem;
        padding: 0.5rem 0.75rem;
    }
    
    .date-label {
        font-size: 0.7rem;
        padding: 1px 4px;
    }
    
    .activity-bar {
        min-width: 40px;
        padding: 0 6px;
    }
    
    .activity-name {
        font-size: 0.75rem;
    }
    
    .activity-type {
        font-size: 0.65rem;
    }
}
</style>
@endsection
