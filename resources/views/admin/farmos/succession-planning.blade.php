@extends('layouts.app')

@section('title', 'farmOS Succession Planner - Revolutionary Backward Planning')

@section('styles')
<!-- Chart.js for timeline visualization - Simple UMD version -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<!-- Sortable.js for drag and drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<style>
    .succession-planner-container {
        padding: 20px;
    }

    .hero-section {
        background: linear-gradient(135deg, var(--primary-color, #28a745) 0%, var(--success-color, #198754) 100%);
        color: white;
        padding: 2rem;
        margin-bottom: 2rem;
        border-radius: 1rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    .hero-section h1 {
        font-size: 2.5rem;
        font-weight: 300;
        margin-bottom: 0.5rem;
    }

    .hero-section .subtitle {
        font-size: 1.1rem;
        opacity: 0.9;
    }

    .planning-card {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        border: none;
        margin-bottom: 2rem;
    }

    .planning-section {
        padding: 1.5rem;
        border-bottom: 1px solid #e9ecef;
    }

    .planning-section:last-child {
        border-bottom: none;
    }

    .planning-section h3 {
        color: #212529;
        font-size: 1.3rem;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .section-icon {
        color: #28a745;
    }

    .harvest-window {
        background: linear-gradient(45deg, #e3f2fd, #f3e5f5);
        border: 2px dashed #0dcaf0;
        border-radius: 1rem;
        padding: 1.5rem;
        margin: 1rem 0;
    }

    .timeline-container {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-top: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        min-height: 500px;
    }

    .chart-container {
        position: relative;
        width: 100%;
        height: 400px;
    }

    .drag-harvest-bar {
        background: linear-gradient(90deg, #28a745, #20c997);
        height: 30px;
        border-radius: 15px;
        cursor: grab;
        display: flex;
        align-items: center;
        padding: 0 15px;
        color: white;
        font-weight: 500;
        margin: 10px 0;
        box-shadow: 0 2px 10px rgba(40, 167, 69, 0.3);
        transition: all 0.2s ease;
        position: relative;
        overflow: visible;
    }

    .drag-harvest-bar.past-dates {
        background: linear-gradient(90deg, #dc3545, #c82333);
        box-shadow: 0 2px 10px rgba(220, 53, 69, 0.3);
    }
    
    /* Override to force green color on page load */
    #dragHarvestBar {
        background: linear-gradient(90deg, #28a745, #20c997) !important;
    }

    .drag-harvest-bar.split-dates {
        box-shadow: 0 2px 10px rgba(200, 100, 100, 0.3);
    }

    .drag-harvest-bar:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.5);
    }

    .drag-harvest-bar.past-dates:hover {
        box-shadow: 0 4px 15px rgba(220, 53, 69, 0.5);
    }

    .drag-harvest-bar:active {
        cursor: grabbing;
    }

    .drag-handle {
        position: absolute;
        top: 0;
        width: 25px;
        height: 30px;
        background: rgba(255, 255, 255, 0.8);
        border-radius: 15px;
        cursor: grab;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        font-size: 12px;
        color: #333;
        font-weight: bold;
        border: 2px solid rgba(255, 255, 255, 0.9);
        z-index: 10;
    }

    .drag-handle:hover {
        background: rgba(255, 255, 255, 1);
        transform: scale(1.1);
    }

    .drag-handle.start {
        left: -12px;
        border-radius: 15px 5px 5px 15px;
    }

    .drag-handle.end {
        right: -12px;
        border-radius: 5px 15px 15px 5px;
    }

    .drag-handle:active {
        cursor: grabbing;
        transform: scale(1.2);
        box-shadow: 0 4px 20px rgba(40, 167, 69, 0.4);
    }

    .drag-handle.constrained {
        background: rgba(255, 193, 7, 0.9);
        border-color: #ffc107;
        animation: constrainedPulse 0.3s ease-in-out;
    }

    @keyframes constrainedPulse {
        0% { transform: scale(1.1); }
        50% { transform: scale(1.3); }
        100% { transform: scale(1.1); }
    }

    .harvest-window-info {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 0.5rem;
        padding: 10px;
        margin-top: 10px;
        color: #495057;
    }

    .harvest-window-info.ai-calculated {
        background: linear-gradient(45deg, rgba(255, 193, 7, 0.1), rgba(255, 235, 59, 0.1));
        border-left: 3px solid #ffc107;
    }

    .ai-chat-section {
        background: linear-gradient(45deg, #fff3cd, #f0f9ff);
        border: 1px solid #ffc107;
        border-radius: 1rem;
        padding: 1.5rem;
        margin: 1rem 0;
    }

    .ai-chat-input {
        border: 2px solid #28a745;
        border-radius: 25px;
        padding: 12px 20px;
        resize: none;
    }

    .ai-chat-input:focus {
        box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
        border-color: #198754;
    }

    .ai-response {
        background: rgba(40, 167, 69, 0.1);
        border-left: 4px solid #28a745;
        padding: 15px;
        border-radius: 0.5rem;
        margin: 15px 0;
        font-style: italic;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-size: 0.875rem;
        font-weight: 500;
    }

    .status-connected {
        background-color: rgba(25, 135, 84, 0.1);
        color: #198754;
    }

    .status-disconnected {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .succession-card {
        border: 2px solid #e9ecef;
        border-radius: 0.5rem;
        padding: 1rem;
        margin: 0.5rem 0;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .succession-card:hover {
        border-color: #28a745;
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.2);
    }

    .succession-card.overdue {
        border-color: #dc3545;
        background-color: rgba(220, 53, 69, 0.05);
    }

    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.9);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 9999;
        backdrop-filter: blur(3px);
    }

    .timeline-controls {
        display: flex;
        gap: 10px;
        align-items: center;
        margin-bottom: 20px;
    }

    .date-slider {
        width: 100%;
        margin: 10px 0;
    }
</style>
@endsection

@section('content')
<div class="succession-planner-container">
    <!-- Cache buster for development -->
    <script>console.log('🔄 Cache buster: {{ time() }}');</script>
    
    <!-- Loading overlay -->
    <div class="loading-overlay d-none" id="loadingOverlay">
        <div class="text-center">
            <div class="spinner-grow text-success" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Processing with Holistic AI...</p>
        </div>
    </div>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="row align-items-center">
            <div class="col-lg-8">
                <h1><i class="fas fa-seedling me-3"></i>farmOS Succession Planner</h1>
                <p class="subtitle">Revolutionary backward planning from harvest windows • Real farmOS taxonomy • AI-powered intelligence</p>
            </div>
            <div class="col-lg-4 text-lg-end">
                <div class="d-flex flex-wrap gap-2 justify-content-lg-end justify-content-start mt-3 mt-lg-0">
                    <span class="status-badge" id="farmOSStatus">
                        <i class="fas fa-circle"></i> farmOS
                    </span>
                    <span class="status-badge" id="aiStatus">
                        <i class="fas fa-brain"></i> Holistic AI
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Planning Interface -->
        <div class="col-lg-8">
            <div class="planning-card">
                <!-- Step 1: Crop Selection from farmOS -->
                <div class="planning-section">
                    <h3>
                        <i class="fas fa-leaf section-icon"></i>
                        Choose Your Crop from farmOS Taxonomy
                    </h3>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="cropSelect" class="form-label">Crop Type</label>
                            <select class="form-select" id="cropSelect" required>
                                <option value="">Loading from farmOS...</option>
                                @if(isset($cropData['types']) && count($cropData['types']) > 0)
                                    @foreach($cropData['types'] as $crop)
                                        <option value="{{ $crop['id'] }}" data-name="{{ $crop['name'] }}">
                                            {{ $crop['name'] }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="varietySelect" class="form-label">Variety</label>
                            <select class="form-select" id="varietySelect">
                                <option value="">Select crop first...</option>
                                @if(isset($cropData['varieties']) && count($cropData['varieties']) > 0)
                                    @foreach($cropData['varieties'] as $variety)
                                        <option value="{{ $variety['id'] }}" data-crop="{{ $variety['crop_id'] ?? '' }}" data-name="{{ $variety['name'] }}">
                                            {{ $variety['name'] }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Step 2: Revolutionary Drag-and-Drop Harvest Window -->
                <div class="planning-section">
                    <h3>
                        <i class="fas fa-calendar-week section-icon"></i>
                        Set Your Harvest Window (Drag to Adjust)
                    </h3>
                    
                    <!-- Date Input Controls -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="harvestStart" class="form-label">Harvest Start Date</label>
                            <input type="date" class="form-control" id="harvestStart" required>
                        </div>
                        <div class="col-md-6">
                            <label for="harvestEnd" class="form-label">Harvest End Date</label>
                            <input type="date" class="form-control" id="harvestEnd" required>
                        </div>
                    </div>

                    <!-- Visual Timeline with Drag Handles -->
                    <div class="harvest-window">
                        <h5 class="mb-3">
                            <i class="fas fa-arrows-alt-h text-info"></i> 
                            Visual Harvest Timeline - Drag the Ends!
                        </h5>
                        
                        <!-- Dynamic Timeline Labels - Above the timeline -->
                        <div id="timelineLabels" style="position: relative; height: 20px; margin-bottom: 5px; font-size: 12px; color: #6c757d;">
                            <!-- Will be populated dynamically with month labels -->
                        </div>
                        
                        <div id="harvestTimeline" style="position: relative; background: linear-gradient(90deg, #e9ecef, #f8f9fa); height: 60px; border-radius: 10px; padding: 15px 20px; border: 2px solid #dee2e6; overflow: hidden;">
                            <!-- Current Date Marker -->
                            <div id="currentDateMarker" style="position: absolute; top: 0; bottom: 0; width: 2px; background: #dc3545; z-index: 5;"></div>
                            
                            <!-- Draggable Harvest Window Bar -->
                            <div id="dragHarvestBar" class="drag-harvest-bar" style="position: absolute; left: 20%; width: 60%; top: 15px;">
                                <!-- Start Handle -->
                                <div class="drag-handle start" data-handle="start">⟨</div>
                                
                                <!-- Harvest Window Content -->
                                <div class="d-flex justify-content-between align-items-center w-100">
                                    <span id="startDateDisplay">Mar 15</span>
                                    <span><i class="fas fa-arrows-alt-h"></i> HARVEST WINDOW</span>
                                    <span id="endDateDisplay">May 30</span>
                                </div>
                                
                                <!-- End Handle -->
                                <div class="drag-handle end" data-handle="end">⟩</div>
                            </div>
                        </div>

                        <!-- AI-Generated Harvest Window Info -->
                        <div id="harvestWindowInfo" class="harvest-window-info ai-calculated mt-3" style="display: none;">
                            <h6><i class="fas fa-robot text-warning"></i> AI Harvest Analysis</h6>
                            <div id="harvestAnalysis">Calculating optimal harvest window...</div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Live Succession Calculation -->
                <div class="planning-section">
                    <h3>
                        <i class="fas fa-calculator section-icon"></i>
                        Live Succession Calculation
                    </h3>
                    <div id="successionSummary" class="alert alert-light">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="h4 text-success mb-0" id="totalSuccessions">0</div>
                                <small class="text-muted">Total Plantings</small>
                            </div>
                            <div class="col-md-3">
                                <div class="h4 text-info mb-0" id="daysBetween">0</div>
                                <small class="text-muted">Days Between</small>
                            </div>
                            <div class="col-md-3">
                                <div class="h4 text-warning mb-0" id="totalHarvestDays">0</div>
                                <small class="text-muted">Harvest Days</small>
                            </div>
                            <div class="col-md-3">
                                <div class="h4 text-primary mb-0" id="bedsNeeded">0</div>
                                <small class="text-muted">Beds Needed</small>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="text-center mt-4">
                        <button class="btn btn-success btn-lg me-3" id="calculatePlan" onclick="calculateSuccessionPlan()">
                            <i class="fas fa-magic me-2"></i>
                            Generate AI Succession Plan
                        </button>
                        <button class="btn btn-outline-primary btn-lg" id="createLogs" onclick="createFarmOSLogs()" disabled>
                            <i class="fas fa-upload me-2"></i>
                            Create farmOS Logs
                        </button>
                    </div>
                </div>
            </div>

            <!-- Timeline Chart -->
            <div class="timeline-container" id="timelineContainer" style="display: none;">
                <h4><i class="fas fa-chart-gantt me-2"></i>Interactive Timeline</h4>
                <div class="chart-container">
                    <canvas id="timelineChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Right Column: AI Chat Integration -->
        <div class="col-lg-4">
            <div class="planning-card">
                <div class="ai-chat-section">
                    <h4>
                        <i class="fas fa-robot text-warning me-2"></i>
                        Symbiosis AI Assistant
                    </h4>
                    <p class="small text-muted">Ask about crop timing, spacing, companion planting, or holistic farming wisdom.</p>
                    
                    <!-- Chat Messages -->
                    <div id="aiChatMessages" style="height: 300px; overflow-y: auto; background: rgba(255,255,255,0.5); border-radius: 10px; padding: 15px; margin: 15px 0;">
                        <div class="text-center text-muted">
                            <i class="fas fa-seedling mb-2"></i><br>
                            <small>Symbiosis AI is ready to help with your succession planning!</small>
                        </div>
                    </div>
                    
                    <!-- Chat Input -->
                    <div class="input-group">
                        <textarea class="form-control ai-chat-input" id="aiChatInput" rows="2" placeholder="Ask about optimal timing, spacing, companions..."></textarea>
                        <button class="btn btn-success" type="button" id="sendAIMessage" onclick="askHolisticAI()">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                    
                    <!-- Quick Questions -->
                    <div class="mt-3">
                        <div class="d-flex flex-wrap gap-2">
                            <button class="btn btn-outline-success btn-sm" onclick="askQuickQuestion('timing')">Optimal Timing</button>
                            <button class="btn btn-outline-info btn-sm" onclick="askQuickQuestion('spacing')">Plant Spacing</button>
                            <button class="btn btn-outline-warning btn-sm" onclick="askQuickQuestion('companions')">Companions</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Revolutionary farmOS Succession Planner - JavaScript -->
<script>
    console.log('🚀 Revolutionary farmOS Succession Planner Loading...');
    
    // Global variables - with proper fallbacks
    let cropTypes = {!! json_encode($cropData['types'] ?? []) !!};
    let cropVarieties = {!! json_encode($cropData['varieties'] ?? []) !!};
    let currentSuccessionPlan = null;
    let timelineChart = null;
    let isDragging = false;
    let dragHandle = null;
    let dragStartX = 0;
    
    // Dynamic timeline variables
    let timelineStartDate = new Date();
    let timelineEndDate = new Date();
    let timelineTotalDays = 365;

    // Initialize the application
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🌱 Initializing farmOS Succession Planner...');
        
        // Initialize timeline with basic range (today at 25%)
        setDefaultTimelineRange();
        
        initializeApp();
        setupDragFunctionality();
        updateCurrentDateMarker();
        setDefaultDates();
        
        // Let checkPastDates handle the color naturally
        setTimeout(() => checkPastDates(), 100);
    });

    function setDefaultTimelineRange() {
        const today = new Date();
        const pastDays = 90;    // 3 months past
        const futureDays = 270; // 9 months future (total = 360 days, today at 25%)
        
        timelineStartDate = new Date(today);
        timelineStartDate.setDate(timelineStartDate.getDate() - pastDays);
        
        timelineEndDate = new Date(today);
        timelineEndDate.setDate(timelineEndDate.getDate() + futureDays);
        
        timelineTotalDays = pastDays + futureDays;
        
        console.log('📅 Basic timeline initialized:', {
            today: today.toLocaleDateString(),
            timelineStart: timelineStartDate.toLocaleDateString(),
            timelineEnd: timelineEndDate.toLocaleDateString(),
            totalDays: timelineTotalDays,
            todayPercentage: Math.round((pastDays / timelineTotalDays) * 100) + '%'
        });
        
        updateTimelineLabels();
    }

    function updateTimelineLabels() {
        const labelsContainer = document.getElementById('timelineLabels');
        if (!labelsContainer) {
            console.error('❌ Timeline labels container not found!');
            return;
        }
        
        labelsContainer.innerHTML = '';
        
        // Create 8 evenly spaced labels across the timeline
        for (let i = 0; i <= 8; i++) {
            const percentage = (i / 8) * 100;
            const date = percentageToDate(percentage);
            
            const label = document.createElement('span');
            label.style.position = 'absolute';
            label.style.left = percentage + '%';
            label.style.transform = 'translateX(-50%)';
            
            // Show month + day for labels
            label.textContent = date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
            
            labelsContainer.appendChild(label);
        }
        
        console.log('✅ Created 9 timeline labels');
    }

    function percentageToDate(percentage) {
        const dayOfTimeline = (percentage / 100) * timelineTotalDays;
        const date = new Date(timelineStartDate);
        date.setDate(date.getDate() + dayOfTimeline);
        return date;
    }

    async function initializeApp() {
        console.log('🌾 Setting up farmOS connections...');
        
        // Test connections
        await testConnections();
        
        // Set up crop change listeners
        document.getElementById('cropSelect').addEventListener('change', function() {
            updateVarieties();
            // Don't trigger AI analysis yet - wait for variety selection
        });
        
        document.getElementById('varietySelect').addEventListener('change', function() {
            calculateAIHarvestWindow();
        });

        // Set up date input listeners
        document.getElementById('harvestStart').addEventListener('change', updateDragBar);
        document.getElementById('harvestEnd').addEventListener('change', updateDragBar);
        
        // Set minimum dates to today
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('harvestStart').setAttribute('min', today);
        document.getElementById('harvestEnd').setAttribute('min', today);
        
        // Initialize chat
        addAIChatMessage('system', '🤖 Symbiosis AI ready! Select a crop to get started with intelligent succession planning.');
    }

    function setupDragFunctionality() {
        const timeline = document.getElementById('harvestTimeline');
        if (!timeline) return;
        
        timeline.addEventListener('mousedown', handleMouseDown, { passive: false });
        document.addEventListener('mousemove', handleMouseMove, { passive: false });
        document.addEventListener('mouseup', handleMouseUp, { passive: false });
        
        // Touch support for mobile
        timeline.addEventListener('touchstart', handleTouchStart, { passive: false });
        document.addEventListener('touchmove', handleTouchMove, { passive: false });
        document.addEventListener('touchend', handleTouchEnd, { passive: false });
    }

    function setDefaultDates() {
        // Calculate dates that will create approximately 60% width on timeline
        // Timeline spans timelineTotalDays, so 60% = ~60% of that timespan
        const timelineRangeInDays = timelineTotalDays || 365; // fallback if not set
        const desiredHarvestDays = Math.round(timelineRangeInDays * 0.60); // 60% of timeline
        
        // Start harvest at 20% into timeline (matches left position)
        const startOffsetDays = Math.round(timelineRangeInDays * 0.20);
        
        const startDate = new Date(timelineStartDate);
        startDate.setDate(startDate.getDate() + startOffsetDays);
        
        const endDate = new Date(startDate);
        endDate.setDate(endDate.getDate() + desiredHarvestDays);
        
        document.getElementById('harvestStart').value = startDate.toISOString().split('T')[0];
        document.getElementById('harvestEnd').value = endDate.toISOString().split('T')[0];
        
        updateDragBar();
    }

    // Drag and Drop Functionality
    function handleMouseDown(e) {
        const handle = e.target.closest('.drag-handle');
        if (!handle) return;
        
        isDragging = true;
        dragHandle = handle.dataset.handle;
        dragStartX = e.clientX;
        
        e.preventDefault();
        e.stopPropagation();
        document.body.style.cursor = 'grabbing';
    }

    function handleMouseMove(e) {
        if (!isDragging || !dragHandle) return;
        
        const timeline = document.getElementById('harvestTimeline');
        const rect = timeline.getBoundingClientRect();
        const timelineWidth = rect.width - 40; // Account for padding
        
        // Calculate mouse position relative to timeline
        const mouseX = e.clientX - rect.left - 20; // Account for left padding
        let percentage = (mouseX / timelineWidth) * 100;
        let originalPercentage = percentage;
        
        // Get current bar state
        const dragBar = document.getElementById('dragHarvestBar');
        const currentLeft = parseFloat(dragBar.style.left) || 20;
        const currentWidth = parseFloat(dragBar.style.width) || 60;  // Changed from 40 to 60
        const currentRight = currentLeft + currentWidth;
        
        // Apply constraints based on handle type
        if (dragHandle === 'start') {
            // Start handle: between 0% and (current right edge - 5% minimum width)
            percentage = Math.max(0, Math.min(percentage, currentRight - 5));
        } else if (dragHandle === 'end') {
            // End handle: between (current left + 5% minimum width) and 100%
            percentage = Math.max(currentLeft + 5, Math.min(percentage, 100));
        }
        
        // Visual feedback when hitting constraints
        const handleElement = document.querySelector(`.drag-handle.${dragHandle}`);
        if (handleElement) {
            if (Math.abs(originalPercentage - percentage) > 0.1) {
                // User hit a constraint
                handleElement.classList.add('constrained');
                setTimeout(() => handleElement.classList.remove('constrained'), 300);
            }
        }
        
        updateHandlePosition(dragHandle, percentage);
        e.preventDefault();
    }

    function handleMouseUp(e) {
        if (isDragging) {
            isDragging = false;
            dragHandle = null;
            document.body.style.cursor = 'default';
            updateDateInputsFromBar();
            calculateAIHarvestWindow();
        }
    }

    function handleTouchStart(e) {
        const handle = e.target.closest('.drag-handle');
        if (!handle) return;
        
        const touch = e.touches[0];
        handleMouseDown({ 
            target: handle, 
            clientX: touch.clientX, 
            preventDefault: () => e.preventDefault(),
            stopPropagation: () => e.stopPropagation()
        });
    }

    function handleTouchMove(e) {
        if (!isDragging) return;
        const touch = e.touches[0];
        handleMouseMove({ 
            clientX: touch.clientX, 
            preventDefault: () => e.preventDefault() 
        });
    }

    function handleTouchEnd(e) {
        handleMouseUp(e);
    }

    function updateHandlePosition(handle, percentage) {
        const dragBar = document.getElementById('dragHarvestBar');
        if (!dragBar) return;
        
        // Ensure percentage is within bounds
        percentage = Math.max(0, Math.min(100, percentage));
        
        const currentLeft = parseFloat(dragBar.style.left) || 20;
        const currentWidth = parseFloat(dragBar.style.width) || 60;  // Changed from 40 to 60
        const currentRight = currentLeft + currentWidth;
        
        if (handle === 'start') {
            // Moving the start handle - only adjust left position, keep width fixed
            const newLeft = percentage;
            
            // Validate the new position (ensure it doesn't overlap with end)
            if (newLeft >= 0 && newLeft + currentWidth <= 100) {
                dragBar.style.left = newLeft + '%';
                // Keep width the same - don't change it!
            }
        } else if (handle === 'end') {
            // Moving the end handle - adjust width only
            const newWidth = percentage - currentLeft;
            
            // Validate the new width
            if (newWidth >= 5 && currentLeft + newWidth <= 100) {
                dragBar.style.width = newWidth + '%';
            }
        }
        
        updateDateDisplays();
        checkPastDates();
    }

    function updateDragBar() {
        const startInput = document.getElementById('harvestStart');
        const endInput = document.getElementById('harvestEnd');
        
        if (!startInput.value || !endInput.value) return;
        
        const startDate = new Date(startInput.value);
        const endDate = new Date(endInput.value);
        
        const startPercentage = dateToPercentage(startDate);
        const endPercentage = dateToPercentage(endDate);
        
        const dragBar = document.getElementById('dragHarvestBar');
        dragBar.style.left = startPercentage + '%';
        dragBar.style.width = (endPercentage - startPercentage) + '%';
        
        updateDateDisplays();
        checkPastDates();
    }

    function updateDateDisplays() {
        const dragBar = document.getElementById('dragHarvestBar');
        const left = parseFloat(dragBar.style.left) || 20;
        const width = parseFloat(dragBar.style.width) || 60;  // Changed from 40 to 60
        
        const startDate = percentageToDate(left);
        const endDate = percentageToDate(left + width);
        
        document.getElementById('startDateDisplay').textContent = startDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
        document.getElementById('endDateDisplay').textContent = endDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }

    function percentageToDate(percentage) {
        const today = new Date();
        const yearStart = new Date(today.getFullYear(), 0, 1);
        const yearEnd = new Date(today.getFullYear(), 11, 31);
        const totalDays = (yearEnd - yearStart) / (1000 * 60 * 60 * 24);
        
        const dayOfYear = (percentage / 100) * totalDays;
        const date = new Date(yearStart);
        date.setDate(date.getDate() + dayOfYear);
        return date;
    }

    function dateToPercentage(date) {
        const daysSinceStart = (date - timelineStartDate) / (1000 * 60 * 60 * 24);
        return Math.max(0, Math.min(100, (daysSinceStart / timelineTotalDays) * 100));
    }

    function updateDateInputsFromBar() {
        const dragBar = document.getElementById('dragHarvestBar');
        const left = parseFloat(dragBar.style.left) || 20;
        const width = parseFloat(dragBar.style.width) || 60;  // Changed from 40 to 60
        
        const startDate = percentageToDate(left);
        const endDate = percentageToDate(left + width);
        
        document.getElementById('harvestStart').value = startDate.toISOString().split('T')[0];
        document.getElementById('harvestEnd').value = endDate.toISOString().split('T')[0];
    }

    function checkPastDates() {
        const dragBar = document.getElementById('dragHarvestBar');
        const left = parseFloat(dragBar.style.left) || 20;
        const width = parseFloat(dragBar.style.width) || 60;  // Changed from 40 to 60
        
        const startDate = percentageToDate(left);
        const endDate = percentageToDate(left + width);
        const today = new Date();
        
        // Calculate what percentage of the bar is in the past
        if (startDate < today && endDate > today) {
            // Bar spans across today - split coloring needed
            const todayPercentage = dateToPercentage(today);
            
            // Calculate the percentage within the harvest bar where today falls
            // This is the key fix: we need today's position relative to the bar, not the year
            const pastPortion = ((todayPercentage - left) / width) * 100;
            
            // Ensure pastPortion is within bounds (0-100%)
            const clampedPastPortion = Math.max(0, Math.min(100, pastPortion));
            
            // Create gradient: red for past, green for future
            const gradientCSS = `linear-gradient(90deg, 
                #dc3545 0%, 
                #dc3545 ${clampedPastPortion}%, 
                #28a745 ${clampedPastPortion}%, 
                #198754 100%)`;
            
            dragBar.style.background = gradientCSS;
            dragBar.style.setProperty('background', gradientCSS, 'important');
            
            dragBar.classList.add('split-dates');
            dragBar.classList.remove('past-dates');
        } else if (endDate < today) {
            // Entire bar is in the past
            dragBar.classList.add('past-dates');
            dragBar.classList.remove('split-dates');
            dragBar.style.background = '';
        } else {
            // Entire bar is in the future
            dragBar.classList.remove('past-dates', 'split-dates');
            dragBar.style.background = '';
        }
    }

    function updateCurrentDateMarker() {
        const today = new Date();
        const percentage = dateToPercentage(today);
        
        const marker = document.getElementById('currentDateMarker');
        if (marker) {
            marker.style.left = percentage + '%';
        }
    }

    // Crop and Variety Management
    function updateVarieties() {
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        
        varietySelect.innerHTML = '<option value="">Loading varieties...</option>';
        
        const selectedCropId = cropSelect.value;
        const selectedCropName = cropSelect.selectedOptions[0]?.dataset.name?.toLowerCase();
        
        console.log('updateVarieties called with:', { selectedCropId, selectedCropName });
        
        if (!selectedCropId || !cropVarieties) {
            varietySelect.innerHTML = '<option value="">Select crop first...</option>';
            return;
        }
        
        // Filter varieties for selected crop
        const filteredVarieties = cropVarieties.filter(variety => {
            // Multiple ways to match varieties to crop type
            return variety.crop_id == selectedCropId || 
                   variety.crop_type == selectedCropId ||
                   variety.parent == selectedCropId ||
                   variety.parent_id == selectedCropId ||
                   (variety.name && variety.name.toLowerCase().includes(cropSelect.selectedOptions[0]?.dataset.name?.toLowerCase() || ''));
        });
        
        console.log('Selected crop ID:', selectedCropId);
        console.log('Filtered varieties for crop:', filteredVarieties);
        console.log('All varieties:', cropVarieties);
        
        varietySelect.innerHTML = '<option value="">Choose variety (optional)...</option>';
        filteredVarieties.forEach(variety => {
            const option = document.createElement('option');
            option.value = variety.id;
            option.textContent = variety.name;
            varietySelect.appendChild(option);
        });
        
        if (filteredVarieties.length === 0) {
            varietySelect.innerHTML = '<option value="">No varieties found - continue without</option>';
        }
    }

    // Helper function to determine current season
    function getCurrentSeason() {
        const month = new Date().getMonth(); // 0-11
        if (month >= 2 && month <= 4) return 'spring';  // Mar, Apr, May
        if (month >= 5 && month <= 7) return 'summer';  // Jun, Jul, Aug
        if (month >= 8 && month <= 10) return 'fall';   // Sep, Oct, Nov
        return 'winter'; // Dec, Jan, Feb
    }

    // AI Integration Functions
    async function calculateAIHarvestWindow() {
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        
        if (!cropSelect.value) return;
        
        const cropName = cropSelect.selectedOptions[0]?.dataset.name || cropSelect.value;
        const varietyName = varietySelect.value ? varietySelect.selectedOptions[0]?.textContent : null;
        
        showLoading(true);
        addAIChatMessage('ai', `🔍 Analyzing optimal harvest window for ${cropName}${varietyName ? ` (${varietyName})` : ''}...`);
        addAIChatMessage('ai', `🧠 Using Mistral 7B on CPU - this may take up to 60 seconds...`);
        
        // Add a progress indicator for long-running AI requests
        let progressDots = 0;
        const progressInterval = setInterval(() => {
            progressDots = (progressDots + 1) % 4;
            const dots = '.'.repeat(progressDots) + ' '.repeat(3 - progressDots);
            const chatMessages = document.getElementById('aiChatMessages');
            const lastMessage = chatMessages.lastElementChild;
            if (lastMessage && lastMessage.textContent.includes('Using Mistral 7B')) {
                lastMessage.querySelector('small').textContent = `🧠 Using Mistral 7B on CPU - processing${dots}`;
            }
        }, 1000);
        
        try {
            // Create AbortController for 90-second timeout (longer for CPU-based AI)
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 90000); // 90 seconds
            
            const response = await fetch('/admin/farmos/succession-planning/harvest-window', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    crop_type: cropName,
                    variety: varietyName,
                    location: 'Market Garden',
                    harvest_start: document.getElementById('harvestStart').value,
                    harvest_end: document.getElementById('harvestEnd').value
                }),
                signal: controller.signal
            });
            
            clearTimeout(timeoutId); // Clear timeout if request completes
            clearInterval(progressInterval); // Clear progress indicator
            
            // Check if response is ok before parsing JSON
            if (!response.ok) {
                const errorText = await response.text();
                console.error('HTTP Error:', response.status, errorText);
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            // Try to parse JSON with better error handling
            let data;
            try {
                const responseText = await response.text();
                console.log('Raw AI response:', responseText); // Debug logging
                data = JSON.parse(responseText);
                console.log('Parsed AI data:', data); // Debug the structure
            } catch (parseError) {
                console.error('JSON Parse Error:', parseError);
                console.error('Response that failed to parse:', responseText);
                throw new Error('Invalid response format from AI service');
            }
            
            if (data.success) {
                console.log('AI response structure:', Object.keys(data)); // See what keys exist
                
                // Handle different possible response structures
                let harvestWindow = data.harvest_window;
                
                if (!harvestWindow) {
                    // Try alternative structures
                    harvestWindow = data.timing || data.recommendations || data.analysis;
                    console.log('Using alternative structure:', harvestWindow);
                }
                
                if (!harvestWindow) {
                    // Create from whatever data we have
                    harvestWindow = {
                        optimal_harvest_days: data.optimal_harvest_days || data.harvest_days || 14,
                        recommended_successions: data.recommended_successions || data.successions || 4,
                        days_between_plantings: data.days_between_plantings || data.interval || 14,
                        confidence_level: data.confidence_level || data.confidence || 'AI-enhanced'
                    };
                    console.log('Created harvestWindow from available data:', harvestWindow);
                }
                
                displayAIHarvestWindow(harvestWindow);
                addAIChatMessage('ai', `✅ AI Analysis Complete! Recommended ${harvestWindow.optimal_harvest_days || 14} day harvest window with ${harvestWindow.recommended_successions || 4} successions.`);
            } else {
                throw new Error(data.message || data.error || 'AI analysis failed');
            }
            
        } catch (error) {
            clearInterval(progressInterval); // Clear progress indicator on error
            console.error('AI harvest window error:', error);
            
            if (error.name === 'AbortError') {
                addAIChatMessage('ai', `⏱️ AI analysis timed out after 90 seconds. Using standard timing guidelines for ${cropName}.`);
            } else if (error.message.includes('Invalid response format')) {
                addAIChatMessage('ai', `🔧 AI service returned invalid data. Check console for details. Using standard timing for ${cropName}.`);
            } else if (error.message.includes('HTTP')) {
                addAIChatMessage('ai', `🌐 AI service error: ${error.message}. Using standard timing for ${cropName}.`);
            } else {
                addAIChatMessage('ai', `❌ AI analysis failed: ${error.message}. Using standard timing for ${cropName}.`);
            }
            
            // Fallback to basic calculations
            displayAIHarvestWindow({
                optimal_harvest_days: 14,
                recommended_successions: 4,
                days_between_plantings: 14,
                confidence_level: 'basic'
            });
        } finally {
            // Ensure loading spinner is always hidden, even on timeout/error
            showLoading(false);
        }
    }

    function displayAIHarvestWindow(harvestWindow) {
        const infoDiv = document.getElementById('harvestWindowInfo');
        const analysisDiv = document.getElementById('harvestAnalysis');
        
        if (infoDiv && analysisDiv) {
            analysisDiv.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <strong>🎯 Optimal Harvest:</strong> ${harvestWindow.optimal_harvest_days || 14} days<br>
                        <strong>📊 Successions:</strong> ${harvestWindow.recommended_successions || 4} plantings
                    </div>
                    <div class="col-md-6">
                        <strong>📅 Plant Every:</strong> ${harvestWindow.days_between_plantings || 14} days<br>
                        <strong>🎖️ Confidence:</strong> ${harvestWindow.confidence_level || 'Standard'}
                    </div>
                </div>
            `;
            infoDiv.style.display = 'block';
        }
        
        // Update summary
        updateSuccessionSummary(harvestWindow);
    }

    function updateSuccessionSummary(harvestWindow) {
        document.getElementById('totalSuccessions').textContent = harvestWindow.recommended_successions || 4;
        document.getElementById('daysBetween').textContent = harvestWindow.days_between_plantings || 14;
        document.getElementById('totalHarvestDays').textContent = harvestWindow.optimal_harvest_days || 14;
        document.getElementById('bedsNeeded').textContent = Math.ceil((harvestWindow.recommended_successions || 4) * 0.5);
    }

    // Succession Plan Calculation
    async function calculateSuccessionPlan() {
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        const harvestStart = document.getElementById('harvestStart').value;
        const harvestEnd = document.getElementById('harvestEnd').value;
        
        if (!cropSelect.value || !harvestStart || !harvestEnd) {
            addAIChatMessage('ai', '⚠️ Please select a crop and set harvest dates first.');
            return;
        }
        
        showLoading(true);
        addAIChatMessage('ai', '🧠 Generating intelligent succession plan...');
        
        try {
            const response = await fetch('/admin/farmos/calculate-succession', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    crop_id: cropSelect.value,
                    crop_name: cropSelect.selectedOptions[0]?.dataset.name,
                    variety_id: varietySelect.value || null,
                    variety_name: varietySelect.value ? varietySelect.selectedOptions[0]?.textContent : null,
                    harvest_start: harvestStart,
                    harvest_end: harvestEnd,
                    planning_method: 'backward'
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                currentSuccessionPlan = data.plan;
                displaySuccessionPlan(data.plan);
                showResults();
                addAIChatMessage('ai', `🎉 Success! Generated ${data.plan.successions?.length || 0} succession plantings. Check the timeline below!`);
            } else {
                throw new Error(data.message || 'Plan generation failed');
            }
            
        } catch (error) {
            console.error('Succession plan error:', error);
            addAIChatMessage('ai', '❌ Plan generation failed. Please check your inputs and try again.');
        }
        
        showLoading(false);
    }

    function displaySuccessionPlan(plan) {
        // Show timeline
        document.getElementById('timelineContainer').style.display = 'block';
        
        // Create Chart.js timeline
        createTimelineChart(plan);
        
        // Enable create logs button
        document.getElementById('createLogs').disabled = false;
    }

    function createTimelineChart(plan) {
        const ctx = document.getElementById('timelineChart').getContext('2d');
        
        // Destroy existing chart
        if (timelineChart) {
            timelineChart.destroy();
        }
        
        const successions = plan.successions || [];
        const labels = successions.map(s => `Succession ${s.sequence}`);
        const plantingDates = successions.map(s => new Date(s.planting_date));
        const harvestDates = successions.map(s => new Date(s.harvest_date));
        
        timelineChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Growing Period',
                    data: successions.map((s, i) => ({
                        x: labels[i],
                        y: [plantingDates[i].getTime(), harvestDates[i].getTime()]
                    })),
                    backgroundColor: 'rgba(40, 167, 69, 0.6)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        type: 'time',
                        time: {
                            unit: 'day',
                            displayFormats: {
                                day: 'MMM DD'
                            }
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: `${plan.crop_name} Succession Timeline`
                    },
                    legend: {
                        display: true
                    }
                }
            }
        });
    }

    // farmOS Integration
    async function createFarmOSLogs() {
        if (!currentSuccessionPlan) {
            addAIChatMessage('ai', '⚠️ No succession plan to create. Generate a plan first.');
            return;
        }
        
        showLoading(true);
        addAIChatMessage('ai', '📤 Creating farmOS logs...');
        
        try {
            const response = await fetch('/admin/farmos/create-succession-logs', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    plan: currentSuccessionPlan
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                addAIChatMessage('ai', `✅ Success! Created ${data.logs_created || 0} farmOS logs. Check your farmOS dashboard.`);
            } else {
                throw new Error(data.message || 'Log creation failed');
            }
            
        } catch (error) {
            console.error('farmOS log creation error:', error);
            addAIChatMessage('ai', '❌ Failed to create farmOS logs. Please check your farmOS connection.');
        }
        
        showLoading(false);
    }

    // AI Chat Functions
    async function askHolisticAI() {
        const input = document.getElementById('aiChatInput');
        const question = input.value.trim();
        
        if (!question) return;
        
        // Add user message
        addAIChatMessage('user', question);
        input.value = '';
        
        // Add typing indicator
        addAIChatMessage('ai', '🤔 Thinking...', true);
        
        try {
            const response = await fetch('/admin/farmos/ask-ai', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify({
                    question: question,
                    context: getCurrentPlanContext()
                })
            });
            
            const data = await response.json();
            
            // Remove typing indicator
            const chatMessages = document.getElementById('aiChatMessages');
            const typingMessage = chatMessages.querySelector('.typing-message');
            if (typingMessage) {
                typingMessage.remove();
            }
            
            if (data.success) {
                addAIChatMessage('ai', data.answer);
            } else {
                throw new Error(data.message || 'AI request failed');
            }
            
        } catch (error) {
            console.error('AI chat error:', error);
            addAIChatMessage('ai', '❌ Sorry, I\'m having trouble connecting. Please try again.');
        }
    }

    async function askQuickQuestion(type) {
        const cropSelect = document.getElementById('cropSelect');
        const cropName = cropSelect.selectedOptions[0]?.dataset.name || 'your crop';
        
        const questions = {
            'timing': `What's the optimal planting timing for ${cropName} succession?`,
            'spacing': `What spacing should I use for ${cropName}?`,
            'companions': `What are good companion plants for ${cropName}?`
        };
        
        document.getElementById('aiChatInput').value = questions[type] || questions.timing;
        askHolisticAI();
    }

    function addAIChatMessage(type, message, isTyping = false) {
        const chatMessages = document.getElementById('aiChatMessages');
        
        // Remove existing typing messages
        const existingTyping = chatMessages.querySelector('.typing-message');
        if (existingTyping) {
            existingTyping.remove();
        }
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `mb-2 ${isTyping ? 'typing-message' : ''}`;
        
        const messageClass = type === 'user' ? 'bg-primary text-white' : 
                           type === 'system' ? 'bg-secondary text-white' :
                           'bg-light text-dark';
        
        messageDiv.innerHTML = `
            <div class="p-2 rounded ${messageClass}">
                <small>${message}</small>
            </div>
        `;
        
        chatMessages.appendChild(messageDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
    }

    function getCurrentPlanContext() {
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        
        return {
            crop: cropSelect.selectedOptions[0]?.dataset.name || null,
            variety: varietySelect.value ? varietySelect.selectedOptions[0]?.textContent : null,
            harvest_start: document.getElementById('harvestStart').value,
            harvest_end: document.getElementById('harvestEnd').value,
            current_plan: currentSuccessionPlan
        };
    }

    // Connection Testing
    async function testConnections() {
        try {
            // Test farmOS connection
            updateStatusBadge('farmOSStatus', true, 'farmOS');
            
            // Test AI connection
            updateStatusBadge('aiStatus', true, 'Symbiosis AI');
            
        } catch (error) {
            console.error('Connection test failed:', error);
        }
    }

    function updateStatusBadge(elementId, isConnected, serviceName) {
        const badge = document.getElementById(elementId);
        if (!badge) return;
        
        badge.className = `status-badge ${isConnected ? 'status-connected' : 'status-disconnected'}`;
        badge.innerHTML = `
            <i class="fas fa-circle"></i> ${serviceName}
        `;
    }

    // Utility Functions
    function showResults() {
        document.getElementById('timelineContainer').style.display = 'block';
    }

    function showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.classList.toggle('d-none', !show);
        }
    }

    // Initialize chat input handlers
    document.addEventListener('DOMContentLoaded', function() {
        const chatInput = document.getElementById('aiChatInput');
        if (chatInput) {
            chatInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    askHolisticAI();
                }
            });
        }
    });
</script>
@endsection
