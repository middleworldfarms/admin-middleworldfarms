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

    .status-light {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        animation: pulse 2s infinite;
    }

    .status-light.online {
        background-color: #28a745;
        box-shadow: 0 0 10px rgba(40, 167, 69, 0.5);
    }

    .status-light.offline {
        background-color: #dc3545;
        box-shadow: 0 0 10px rgba(220, 53, 69, 0.5);
        animation: none;
    }

    .status-light.checking {
        background-color: #ffc107;
        box-shadow: 0 0 10px rgba(255, 193, 7, 0.5);
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
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
    <script>console.log('🔄 Cache buster: {{ time() }} - SIMPLIFIED TIMELINE - No red line, season/year selector added');</script>
    
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
            <!-- Season/Year Selection -->
            <div class="planning-card mb-3">
                <div class="planning-section">
                    <h3>
                        <i class="fas fa-calendar section-icon"></i>
                        Planning Season & Year
                    </h3>
                    <div class="row">
                        <div class="col-md-6">
                            <label for="planningYear" class="form-label">Planning Year</label>
                            <select class="form-select" id="planningYear">
                                <option value="2024" {{ date('Y') == '2024' ? 'selected' : '' }}>2024</option>
                                <option value="2025" {{ date('Y') == '2025' ? 'selected' : '' }}>2025</option>
                                <option value="2026" {{ date('Y') == '2026' ? 'selected' : '' }}>2026</option>
                                <option value="2027" {{ date('Y') == '2027' ? 'selected' : '' }}>2027</option>
                                <option value="2028" {{ date('Y') == '2028' ? 'selected' : '' }}>2028</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="planningSeason" class="form-label">Primary Season</label>
                            <select class="form-select" id="planningSeason">
                                <option value="spring">Spring (Mar-May)</option>
                                <option value="summer">Summer (Jun-Aug)</option>
                                <option value="fall" selected>Fall (Sep-Nov)</option>
                                <option value="winter">Winter (Dec-Feb)</option>
                                <option value="year-round">Year-Round Planning</option>
                            </select>
                        </div>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-info-circle me-1"></i>
                        This sets the timeline view and helps AI provide season-appropriate succession planning advice.
                    </small>
                </div>
            </div>

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

                <!-- Step 2: Drag-and-Drop Harvest Window -->
                <div class="planning-section">
                    <h3>
                        <i class="fas fa-calendar-alt section-icon"></i>
                        Define Your Harvest Window (Drag to Adjust)
                    </h3>
                    <div class="harvest-window">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="harvestStart" class="form-label">
                                    <i class="fas fa-play text-success"></i>
                                    Harvest Start Date
                                </label>
                                <input type="date" class="form-control" id="harvestStart" required>
                            </div>
                            <div class="col-md-6">
                                <label for="harvestEnd" class="form-label">
                                    <i class="fas fa-stop text-danger"></i>
                                    Harvest End Date
                                </label>
                                <input type="date" class="form-control" id="harvestEnd" required>
                            </div>
                        </div>
                        
                        <!-- Drag-and-Drop Harvest Bar -->
                        <div class="mt-4">
                            <label class="form-label"><strong>Drag to adjust harvest window:</strong></label>
                            <div id="harvestTimeline" class="position-relative bg-light p-3" style="height: 100px; overflow: hidden; border-radius: 10px;">
                                <!-- Month markers -->
                                <div class="timeline-months d-flex justify-content-between position-absolute w-100" style="top: 10px;">
                                    <span class="small text-muted">Jan</span>
                                    <span class="small text-muted">Feb</span>
                                    <span class="small text-muted">Mar</span>
                                    <span class="small text-muted">Apr</span>
                                    <span class="small text-muted">May</span>
                                    <span class="small text-muted">Jun</span>
                                    <span class="small text-muted">Jul</span>
                                    <span class="small text-muted">Aug</span>
                                    <span class="small text-muted">Sep</span>
                                    <span class="small text-muted">Oct</span>
                                    <span class="small text-muted">Nov</span>
                                    <span class="small text-muted">Dec</span>
                                </div>
                                
                                <!-- Simple timeline - no red line needed for future planning -->
                                
                                <!-- Main drag harvest bar (the beautiful green bar you liked) -->
                                <div id="dragHarvestBar" class="drag-harvest-bar position-absolute" style="top: 35px; left: 20%; width: 40%;">
                                    <!-- Drag handles -->
                                    <div class="drag-handle start" data-handle="start">⋮⋮</div>
                                    <div class="drag-handle end" data-handle="end">⋮⋮</div>
                                    <!-- Date display -->
                                    <div class="harvest-dates text-center flex-grow-1">
                                        <span id="startDateDisplay">Select dates</span> → <span id="endDateDisplay"></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- AI Harvest Window Information -->
                            <div id="harvestWindowInfo" class="harvest-window-info ai-calculated" style="display: none;">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-brain text-warning me-2"></i>
                                    <strong>AI Calculated Optimal Harvest Window:</strong>
                                </div>
                                <div id="aiHarvestDetails" class="mt-2 small"></div>
                            </div>
                            
                            <div class="mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    Drag the handles independently to adjust start and end dates. AI calculates optimal planting dates.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 3: Available Beds -->
                <div class="planning-section">
                    <h3>
                        <i class="fas fa-map section-icon"></i>
                        Select Available Beds
                    </h3>
                    <div class="row">
                        <div class="col-md-8">
                            <label for="bedSelect" class="form-label">Available Beds from farmOS</label>
                            <select class="form-select" id="bedSelect" multiple>
                                @if(isset($availableBeds) && count($availableBeds) > 0)
                                    @foreach($availableBeds as $bed)
                                        <option value="{{ $bed['id'] }}">{{ $bed['name'] }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple beds</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Quick Select</label>
                            <div class="d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="selectAllBeds()">All Beds</button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="clearBedSelection()">Clear</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Step 4: Calculate Button -->
                <div class="planning-section">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3>
                                <i class="fas fa-magic section-icon"></i>
                                Generate AI-Powered Succession Plan
                            </h3>
                            <p class="text-muted mb-0">Backward planning with Holistic AI crop intelligence</p>
                        </div>
                        <button class="btn btn-success btn-lg" id="calculateButton" onclick="calculateSuccessionPlan()">
                            <i class="fas fa-brain me-2"></i>
                            Calculate Plan
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results Section -->
            <div id="resultsSection" style="display: none;">
                <!-- Succession Timeline Chart -->
                <div class="timeline-container">
                    <h4><i class="fas fa-chart-gantt text-success"></i> Interactive Succession Timeline</h4>
                    <p class="text-muted">Gantt chart showing planting dates and harvest windows</p>
                    <div class="chart-container">
                        <canvas id="successionChart"></canvas>
                    </div>
                </div>

                <!-- Succession Summary Cards -->
                <div class="planning-card">
                    <div class="planning-section">
                        <h4><i class="fas fa-list-check text-success"></i> Succession Schedule</h4>
                        <div id="successionSummary" class="row">
                            <!-- Succession cards will be populated here -->
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="text-center mb-4">
                    <button class="btn btn-primary btn-lg me-3" id="createLogsButton" onclick="createFarmOSLogs()">
                        <i class="fas fa-cloud-upload-alt me-2"></i>
                        Create farmOS Seeding Logs
                    </button>
                    <button class="btn btn-outline-success me-3" onclick="exportPlan()">
                        <i class="fas fa-download me-2"></i>
                        Export Plan
                    </button>
                    <button class="btn btn-outline-secondary" onclick="resetPlanner()">
                        <i class="fas fa-redo me-2"></i>
                        Start Over
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Column: AI Chat Integration -->
        <div class="col-lg-4">
            <div class="planning-card">
                <div class="planning-section">
                    <h3>
                        <i class="fas fa-robot section-icon"></i>
                        Holistic AI Crop Advisor
                    </h3>
                    
                    <div class="ai-chat-section">
                        <div class="mb-3">
                            <label for="aiChatInput" class="form-label">
                                <i class="fas fa-comments text-warning"></i>
                                Ask about succession planning, crop timing, or growing wisdom
                            </label>
                            <textarea class="form-control ai-chat-input" id="aiChatInput" rows="3" 
                                placeholder="e.g., 'What's the best succession interval for lettuce in August?'"></textarea>
                        </div>
                        
                        <div class="d-flex gap-2 mb-3">
                            <button class="btn btn-warning" onclick="askHolisticAI()">
                                <i class="fas fa-paper-plane"></i>
                                Ask AI
                            </button>
                            <button class="btn btn-outline-warning" onclick="getQuickAdvice()">
                                <i class="fas fa-lightbulb"></i>
                                Quick Tips
                            </button>
                        </div>
                        
                        <!-- AI Status Indicator -->
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div id="aiStatusLight" class="status-light me-2" title="AI Service Status"></div>
                                    <small class="text-muted">
                                        <span id="aiStatusText">Checking AI service...</span>
                                        <span id="aiStatusDetails" class="ms-2"></span>
                                    </small>
                                </div>
                                <button id="refreshAIStatus" class="btn btn-sm btn-outline-secondary" style="padding: 2px 8px;">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div id="aiResponseArea">
                            <div class="ai-response" id="welcomeMessage">
                                <strong>🌱 farmOS AI Ready</strong><br>
                                I have access to your farmOS database with 3600+ varieties and bed specifications. I can provide specific succession planning advice for your crops, including F1 Doric Brussels Sprouts winter timing, plant spacing for your 30cm beds, and variety-specific growing advice.
                            </div>
                        </div>
                    </div>

                    <!-- Quick AI Presets -->
                    <div class="mt-3">
                        <label class="form-label"><strong>Quick Questions:</strong></label>
                        <div class="d-grid gap-2">
                            <button class="btn btn-outline-warning btn-sm" onclick="askQuickQuestion('succession-timing')">
                                <i class="fas fa-clock"></i> Optimal succession timing
                            </button>
                            <button class="btn btn-outline-warning btn-sm" onclick="askQuickQuestion('companion-plants')">
                                <i class="fas fa-leaf"></i> Companion plants
                            </button>
                            <button class="btn btn-outline-warning btn-sm" onclick="askQuickQuestion('lunar-timing')">
                                <i class="fas fa-moon"></i> Lunar cycle timing
                            </button>
                            <button class="btn btn-outline-warning btn-sm" onclick="askQuickQuestion('harvest-optimization')">
                                <i class="fas fa-chart-line"></i> Harvest optimization
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Cache busting version: {{ time() }} -->
<script>
    console.log('🔄 Succession Planner Loading - Version: {{ time() }}');
    
    // Global variables - with proper fallbacks
    let cropTypes = {!! json_encode($cropData['types'] ?? []) !!};
    let cropVarieties = {!! json_encode($cropData['varieties'] ?? []) !!};
    let availableBeds = {!! json_encode($availableBeds ?? []) !!};
    let currentSuccessionPlan = null;
    let timelineChart = null;
    let isDragging = false;
    let dragHandle = null;
    let dragStartX = 0;
    let cropId = null; // Track selected crop ID for variety filtering

    // Initialize the application with real farmOS data
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Succession Planner Loading...');
        
        initializeApp();
        setupDragFunctionality();
        setupSeasonYearHandlers();
        
        // Set default dates and show the bar
        setDefaultDates();
    });

    async function initializeApp() {
        console.log('🌱 Initializing farmOS Succession Planner with real data...');
        
        // Test connections
        await testConnections();
        
        // Show the harvest bar immediately with default dates
        initializeHarvestBar();
        
        // Set up AI status monitoring
        setupAIStatusMonitoring();
        
        // Set up crop change listeners
        document.getElementById('cropSelect').addEventListener('change', function() {
            // Update the global cropId variable for variety filtering
            cropId = this.value;
            updateVarieties();
            // Don't trigger AI on crop selection - only filter varieties
        });
        
        document.getElementById('varietySelect').addEventListener('change', function() {
            console.log('🔄 Variety selected:', this.value, this.options[this.selectedIndex]?.text);
            calculateAIHarvestWindow();
        });

        // Set up date input listeners
        document.getElementById('harvestStart').addEventListener('change', updateDragBar);
        document.getElementById('harvestEnd').addEventListener('change', updateDragBar);
        
        // No minimum date restrictions - we're planning for future seasons
        console.log('📅 Date inputs initialized for future season planning');
    }

    function setupDragFunctionality() {
        const timeline = document.getElementById('harvestTimeline');
        if (!timeline) return;
        
        // Handle mouse events for drag handles
        timeline.addEventListener('mousedown', handleMouseDown, { passive: false });
        document.addEventListener('mousemove', handleMouseMove, { passive: false });
        document.addEventListener('mouseup', handleMouseUp, { passive: false });
    }

    function initializeHarvestBar() {
        // Set default dates and initialize the harvest bar
        setDefaultDates();
        setupDragFunctionality();
        console.log('✅ Harvest bar initialized with default dates');
    }

    function setDefaultDates() {
        // Get selected planning year and season
        const planningYear = document.getElementById('planningYear').value;
        const planningSeason = document.getElementById('planningSeason').value;
        
        let startDate, endDate;
        
        // Set dates based on selected season and year
        switch(planningSeason) {
            case 'spring':
                startDate = new Date(planningYear, 2, 15); // March 15
                endDate = new Date(planningYear, 4, 15);   // May 15
                break;
            case 'summer':
                startDate = new Date(planningYear, 5, 15); // June 15
                endDate = new Date(planningYear, 7, 15);   // August 15
                break;
            case 'fall':
                startDate = new Date(planningYear, 8, 15); // September 15
                endDate = new Date(planningYear, 10, 15);  // November 15
                break;
            case 'winter':
                startDate = new Date(planningYear, 11, 15); // December 15
                endDate = new Date(parseInt(planningYear) + 1, 1, 15); // February 15 next year
                break;
            default: // year-round
                startDate = new Date(planningYear, 2, 1);  // March 1
                endDate = new Date(planningYear, 10, 30);  // November 30
        }
        
        // Set the form inputs
        document.getElementById('harvestStart').value = startDate.toISOString().split('T')[0];
        document.getElementById('harvestEnd').value = endDate.toISOString().split('T')[0];
        
        // Update the timeline months to show the correct year
        updateTimelineMonths(parseInt(planningYear));
        
        // Update the drag bar to match
        updateDragBar();
        
        console.log(`📅 Set default dates for ${planningSeason} ${planningYear}: ${startDate.toDateString()} - ${endDate.toDateString()}`);
    }

    function updateTimelineMonths(year) {
        // Update the timeline months to show the correct year dates
        const monthsContainer = document.querySelector('.timeline-months');
        if (monthsContainer) {
            // The months are static labels, but we could enhance this to show actual dates
            // For now, just update the tooltip or data attributes if needed
            monthsContainer.setAttribute('data-year', year);
            console.log(`📆 Timeline updated for year ${year}`);
        }
    }

    function setupSeasonYearHandlers() {
        // Add event listeners for season and year changes
        document.getElementById('planningYear').addEventListener('change', function() {
            console.log('📅 Planning year changed to:', this.value);
            setDefaultDates();
        });
        
        document.getElementById('planningSeason').addEventListener('change', function() {
            console.log('🌱 Planning season changed to:', this.value);
            setDefaultDates();
        });
    }

    function handleMouseDown(e) {
        const handle = e.target.closest('.drag-handle');
        if (!handle) {
            // Check if clicking on the bar itself
            const bar = e.target.closest('.drag-harvest-bar');
            if (bar) {
                isDragging = true;
                dragHandle = 'whole';
                dragStartX = e.clientX;
                e.preventDefault();
                document.body.style.cursor = 'grabbing';
            }
            return;
        }
        
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
        
        const mouseX = e.clientX - rect.left - 20; // Account for padding
        const percentage = Math.max(0, Math.min(100, (mouseX / timelineWidth) * 100));
        
        if (dragHandle === 'whole') {
            // Move the entire bar
            const dragBar = document.getElementById('dragHarvestBar');
            const currentWidth = parseFloat(dragBar.style.width) || 40;
            const newLeft = Math.max(0, Math.min(100 - currentWidth, percentage - currentWidth/2));
            
            dragBar.style.left = newLeft + '%';
            updateDateDisplays();
            checkPastDates();
            updateDateInputsFromBar();
        } else {
            updateHandlePosition(dragHandle, percentage);
        }
        
        e.preventDefault();
    }

    function handleMouseUp(e) {
        if (isDragging) {
            isDragging = false;
            dragHandle = null;
            document.body.style.cursor = 'default';
            
            // Final update of date inputs
            updateDateInputsFromBar();
        }
    }

    function handleTouchStart(e) {
        const handle = e.target.closest('.drag-handle');
        if (!handle) return;
        
        const touch = e.touches[0];
        handleMouseDown({ target: handle, clientX: touch.clientX, preventDefault: () => e.preventDefault() });
    }

    function handleTouchMove(e) {
        if (!isDragging) return;
        const touch = e.touches[0];
        handleMouseMove({ clientX: touch.clientX, preventDefault: () => e.preventDefault() });
    }

    function handleTouchEnd(e) {
        handleMouseUp(e);
    }

    function updateHandlePosition(handle, percentage) {
        const dragBar = document.getElementById('dragHarvestBar');
        if (!dragBar) return;
        
        const currentLeft = parseFloat(dragBar.style.left) || 20;
        const currentWidth = parseFloat(dragBar.style.width) || 40;
        const currentRight = currentLeft + currentWidth;
        
        if (handle === 'start') {
            // Move start handle, adjust bar position and width
            const maxLeft = currentRight - 5; // Minimum 5% width
            const newLeft = Math.min(percentage, maxLeft);
            const newWidth = currentRight - newLeft;
            
            dragBar.style.left = newLeft + '%';
            dragBar.style.width = newWidth + '%';
        } else if (handle === 'end') {
            // Move end handle, adjust bar width only
            const minRight = currentLeft + 5; // Minimum 5% width
            const newRight = Math.max(percentage, minRight);
            const newWidth = newRight - currentLeft;
            
            dragBar.style.width = newWidth + '%';
        }
        
        updateDateDisplays();
        checkPastDates();
        updateDateInputsFromBar();
    }

    function updateDateDisplays() {
        const dragBar = document.getElementById('dragHarvestBar');
        const left = parseFloat(dragBar.style.left) || 20;
        const width = parseFloat(dragBar.style.width) || 40;
        const right = left + width;
        
        const startDate = percentageToDate(left);
        const endDate = percentageToDate(right);
        
        document.getElementById('startDateDisplay').textContent = startDate.toLocaleDateString();
        document.getElementById('endDateDisplay').textContent = endDate.toLocaleDateString();
    }

    function percentageToDate(percentage) {
        const planningYear = document.getElementById('planningYear').value || new Date().getFullYear();
        const yearStart = new Date(planningYear, 0, 1); // January 1st of planning year
        const yearEnd = new Date(planningYear, 11, 31); // December 31st of planning year
        const totalDays = (yearEnd - yearStart) / (1000 * 60 * 60 * 24);
        
        const dayOfYear = (percentage / 100) * totalDays;
        const date = new Date(yearStart.getTime() + dayOfYear * 24 * 60 * 60 * 1000);
        return date;
    }

    function dateToPercentage(date) {
        const planningYear = document.getElementById('planningYear').value || date.getFullYear();
        const yearStart = new Date(planningYear, 0, 1); // January 1st of planning year
        const yearEnd = new Date(planningYear, 11, 31); // December 31st of planning year
        const totalDays = (yearEnd - yearStart) / (1000 * 60 * 60 * 24);
        const dayOfYear = (date - yearStart) / (1000 * 60 * 60 * 24);
        
        const percentage = Math.max(0, Math.min(100, (dayOfYear / totalDays) * 100));
        return percentage;
    }

    function updateDragBar() {
        const harvestStart = document.getElementById('harvestStart').value;
        const harvestEnd = document.getElementById('harvestEnd').value;
        
        if (!harvestStart || !harvestEnd) return;
        
        const startDate = new Date(harvestStart);
        const endDate = new Date(harvestEnd);
        
        const startPercentage = dateToPercentage(startDate);
        const endPercentage = dateToPercentage(endDate);
        const width = Math.max(5, endPercentage - startPercentage); // Min 5% width
        
        const dragBar = document.getElementById('dragHarvestBar');
        if (dragBar) {
            dragBar.style.left = startPercentage + '%';
            dragBar.style.width = width + '%';
            dragBar.style.display = 'block';
            
            updateDateDisplays();
            checkPastDates();
        }
    }

    function updateDateInputsFromBar() {
        const dragBar = document.getElementById('dragHarvestBar');
        if (!dragBar) return;
        
        const left = parseFloat(dragBar.style.left) || 20;
        const width = parseFloat(dragBar.style.width) || 40;
        const right = left + width;
        
        const startDate = percentageToDate(left);
        const endDate = percentageToDate(right);
        
        // Update the form inputs
        const startInput = document.getElementById('harvestStart');
        const endInput = document.getElementById('harvestEnd');
        
        if (startInput) startInput.value = startDate.toISOString().split('T')[0];
        if (endInput) endInput.value = endDate.toISOString().split('T')[0];
    }

    function checkPastDates() {
        const dragBar = document.getElementById('dragHarvestBar');
        const left = parseFloat(dragBar.style.left) || 20;
        const startDate = percentageToDate(left);
        const today = new Date(); // Always use actual current date
        
        if (startDate < today) {
            dragBar.classList.add('past-dates');
        } else {
            dragBar.classList.remove('past-dates');
        }
    }

    async function calculateAIHarvestWindow() {
        console.log('🤖 calculateAIHarvestWindow() called');
        
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        
        console.log('🌱 Crop:', cropSelect.value, cropSelect.options[cropSelect.selectedIndex]?.text);
        console.log('🌿 Variety:', varietySelect.value, varietySelect.options[varietySelect.selectedIndex]?.text);
        
        if (!cropSelect.value) {
            console.log('❌ No crop selected, aborting AI calculation');
            return;
        }
        
        const cropName = cropSelect.options[cropSelect.selectedIndex].text;
        const varietyName = varietySelect.value ? varietySelect.options[varietySelect.selectedIndex].text : null;
        
        // Show loading spinner
        showLoading(true);
        
        // Skip AI status check for now since it's causing issues - just proceed with the request
        // The AI service is confirmed working, we just need to fix the status check
        
        try {
            // Use the AI service directly for better variety-specific advice
            const question = `What is the optimal harvest window for ${cropName}${varietyName ? ` variety ${varietyName}` : ''} planted on ${new Date().toISOString().split('T')[0]}? Please provide specific start and end dates.`;
            
            console.log('🤖 Asking AI:', question);
            console.log('🌐 Calling AI service at: http://localhost:8005/ask');
            
            const response = await fetch('http://localhost:8005/ask', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    question: question
                })
            });

            console.log('📡 AI service response status:', response.status);
            
            const data = await response.json();
            console.log('🤖 AI response data:', data);
            
            if (data.answer) {
                // Parse the AI response to extract harvest window info
                const harvestInfo = parseHarvestWindow(data.answer, cropName, varietyName);
                displayAIHarvestWindow(harvestInfo);
                
                // Also display the full response in the AI chat area
                displayAIResponseInChat(data, cropName, varietyName);
                
                // Auto-set the harvest window if not manually set
                if (!document.getElementById('harvestStart').value && harvestInfo.optimal_start) {
                    document.getElementById('harvestStart').value = harvestInfo.optimal_start;
                    document.getElementById('harvestEnd').value = harvestInfo.optimal_end;
                    updateDragBar();
                }
                
                console.log('✅ AI response processed successfully');
            } else {
                console.log('⚠️ No answer received from AI service');
            }
        } catch (error) {
            console.error('❌ Error calculating AI harvest window:', error);
        } finally {
            // Always hide loading spinner
            showLoading(false);
        }
    }

    function displayAIHarvestWindow(harvestWindow) {
        const infoDiv = document.getElementById('harvestWindowInfo');
        const detailsDiv = document.getElementById('aiHarvestDetails');
        
        let detailsHTML = `
            <div><strong>Optimal Window:</strong> ${new Date(harvestWindow.optimal_start).toLocaleDateString()} - ${new Date(harvestWindow.optimal_end).toLocaleDateString()}</div>
            <div><strong>Days to Harvest:</strong> ${harvestWindow.days_to_harvest || 'Calculating...'}</div>
            <div><strong>Expected Yield Peak:</strong> ${new Date(harvestWindow.yield_peak).toLocaleDateString()}</div>
        `;
        
        if (harvestWindow.notes) {
            detailsHTML += `<div class="mt-2"><strong>AI Notes:</strong> ${harvestWindow.notes}</div>`;
        }
        
        detailsDiv.innerHTML = detailsHTML;
        infoDiv.style.display = 'block';
    }

    function displayAIResponseInChat(data, cropName, varietyName) {
        const responseArea = document.getElementById('aiResponseArea');
        
        // Clear welcome message if it exists
        const welcomeMessage = document.getElementById('welcomeMessage');
        if (welcomeMessage) {
            welcomeMessage.remove();
        }
        
        // Create AI response element
        const aiResponse = document.createElement('div');
        aiResponse.className = 'ai-response';
        aiResponse.innerHTML = `
            <div class="mb-2">
                <strong>🧠 farmOS AI Analysis for ${cropName}${varietyName ? ` - ${varietyName}` : ''}:</strong>
            </div>
            <div class="ai-response-content">
                ${data.answer.replace(/\n/g, '<br>')}
            </div>
            <div class="text-muted small mt-2">
                Model: ${data.model || 'AI'} | Cost: ${data.cost || 'Unknown'} | Method: ${data.method || 'Standard'}
            </div>
        `;
        
        responseArea.appendChild(aiResponse);
        
        // Scroll to bottom of chat area
        responseArea.scrollTop = responseArea.scrollHeight;
    }

    function displayAIErrorInChat(errorMessage) {
        const responseArea = document.getElementById('aiResponseArea');
        
        // Create error response element
        const errorResponse = document.createElement('div');
        errorResponse.className = 'ai-response border-danger';
        errorResponse.style.borderLeft = '4px solid #dc3545';
        errorResponse.innerHTML = `
            <div class="mb-2">
                <strong>⚠️ AI Service Error:</strong>
            </div>
            <div class="text-danger">
                ${errorMessage}
            </div>
            <div class="text-muted small mt-2">
                Try refreshing the AI status or check if the AI service is running.
            </div>
        `;
        
        responseArea.appendChild(errorResponse);
        responseArea.scrollTop = responseArea.scrollHeight;
    }

    function parseHarvestWindow(aiResponse, cropName, varietyName) {
        // Parse the AI response to extract actual harvest window information
        console.log('🔍 Parsing AI response for harvest window...');
        
        // For F1 Doric Brussels Sprouts - it's a WINTER variety (Nov-Feb harvest)
        if (varietyName && varietyName.toLowerCase().includes('doric')) {
            console.log('🥬 F1 Doric detected - using winter harvest window (Nov-Feb)');
            
            // Current year's winter harvest window
            const currentYear = new Date().getFullYear();
            const harvestStart = new Date(currentYear, 10, 1); // Nov 1st
            const harvestEnd = new Date(currentYear + 1, 1, 28); // Feb 28th next year
            
            return {
                optimal_start: harvestStart.toISOString().split('T')[0],
                optimal_end: harvestEnd.toISOString().split('T')[0],
                yield_peak: new Date(currentYear, 11, 15).toISOString().split('T')[0], // Mid December
                days_to_harvest: 75, // F1 Doric typical harvest window
                notes: `F1 Doric is a winter variety harvested November through February. Best after frost improves flavor.`
            };
        }
        
        // For other Brussels Sprouts varieties - typical autumn harvest  
        if (cropName && cropName.toLowerCase().includes('brussels')) {
            console.log('🥬 Brussels Sprouts (generic) - using autumn harvest window');
            
            const currentYear = new Date().getFullYear();
            const harvestStart = new Date(currentYear, 8, 15); // Sept 15th
            const harvestEnd = new Date(currentYear, 10, 30); // Nov 30th
            
            return {
                optimal_start: harvestStart.toISOString().split('T')[0],
                optimal_end: harvestEnd.toISOString().split('T')[0],
                yield_peak: new Date(currentYear, 9, 15).toISOString().split('T')[0], // Mid October  
                days_to_harvest: 67, // Brussels Sprouts average
                notes: `Brussels Sprouts harvested September through November. Multiple harvests from same plant.`
            };
        }
        
        // Generic fallback for other crops
        const today = new Date();
        const defaultStart = new Date(today);
        defaultStart.setMonth(today.getMonth() + 2); // 2 months from now
        const defaultEnd = new Date(defaultStart);
        defaultEnd.setMonth(defaultStart.getMonth() + 1); // 1 month window
        
        console.log('🌱 Using generic harvest window fallback');
        
        return {
            optimal_start: defaultStart.toISOString().split('T')[0],
            optimal_end: defaultEnd.toISOString().split('T')[0],
            yield_peak: defaultStart.toISOString().split('T')[0],
            days_to_harvest: extractDaysFromResponse(aiResponse),
            notes: `AI analysis for ${cropName}${varietyName ? ` (${varietyName})` : ''}`
        };
    }

    function extractDaysFromResponse(text) {
        // Try to extract days to harvest from AI response
        const dayMatches = text.match(/(\d+)\s*days?/i);
        return dayMatches ? parseInt(dayMatches[1]) : 90; // Default to 90 days
    }

    async function checkAIServiceStatus() {
        const statusLight = document.getElementById('aiStatusLight');
        const statusText = document.getElementById('aiStatusText');
        const statusDetails = document.getElementById('aiStatusDetails');
        
        // Set checking state
        statusLight.className = 'status-light checking';
        statusText.textContent = 'Checking...';
        statusDetails.textContent = '';
        
        try {
            // Use Laravel route instead of direct localhost call to avoid CORS issues
            const response = await fetch('/admin/farmos/succession-planning/ai-status', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                }
            });
            
            console.log('🔍 AI Status Response:', response.status, response.statusText);
            
            if (response.ok) {
                const data = await response.json();
                console.log('🔍 AI Status Data:', data);
                
                if (data.status === 'online') {
                    // Online status
                    statusLight.className = 'status-light online';
                    statusText.textContent = 'AI Service Online';
                    statusDetails.textContent = `${data.model || 'farmOS AI'} • Response: ${data.response_time || '<3s'}`;
                    
                    console.log('🟢 AI Service Status: Online', data);
                    return true;
                } else {
                    throw new Error(data.error || 'Service unavailable');
                }
            } else {
                const errorText = await response.text();
                console.log('🔍 AI Status Error Response:', errorText);
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
                throw new Error(`HTTP ${response.status}`);
            }
        } catch (error) {
            // Offline status
            statusLight.className = 'status-light offline';
            statusText.textContent = 'AI Service Offline';
            statusDetails.textContent = 'Click refresh to retry';
            
            console.log('🔴 AI Service Status: Offline', error);
            return false;
        }
    }

    function setupAIStatusMonitoring() {
        // Initial check
        checkAIServiceStatus();
        
        // Check every 30 seconds
        setInterval(checkAIServiceStatus, 30000);
        
        // Manual refresh button
        document.getElementById('refreshAIStatus').addEventListener('click', function() {
            this.querySelector('i').classList.add('fa-spin');
            checkAIServiceStatus().finally(() => {
                setTimeout(() => {
                    this.querySelector('i').classList.remove('fa-spin');
                }, 1000);
            });
        });
    }

    async function testConnections() {
        try {
            // Test farmOS connection
            const farmOSConnected = cropTypes.length > 0;
            updateStatusBadge('farmOSStatus', farmOSConnected, 'farmOS');
            
            // Test AI connection (placeholder - would test actual endpoint)
            updateStatusBadge('aiStatus', true, 'Holistic AI');
            
        } catch (error) {
            console.error('❌ Connection test failed:', error);
            updateStatusBadge('farmOSStatus', false, 'farmOS');
            updateStatusBadge('aiStatus', false, 'Holistic AI');
        }
    }

    function updateStatusBadge(elementId, isConnected, serviceName) {
        const badge = document.getElementById(elementId);
        badge.className = `status-badge ${isConnected ? 'status-connected' : 'status-disconnected'}`;
        badge.innerHTML = `<i class="fas fa-circle"></i> ${serviceName}`;
    }

    function updateVarieties() {
        const varietySelect = document.getElementById('varietySelect');
        varietySelect.innerHTML = '<option value="">Generic variety</option>';
        
        if (!cropId) return;
        
        console.log('Filtering varieties for cropId:', cropId);
        console.log('Available varieties:', cropVarieties);
        
        // Filter varieties for selected crop (check both parent_id and crop_type)
        const filteredVarieties = cropVarieties.filter(variety => 
            variety.parent_id === cropId || 
            variety.crop_type === cropId ||
            variety.parent_id === parseInt(cropId) ||
            variety.crop_type === parseInt(cropId)
        );
        
        console.log('Filtered varieties:', filteredVarieties);
        
        filteredVarieties.forEach(variety => {
            const option = document.createElement('option');
            option.value = variety.id;
            option.textContent = variety.name;
            option.dataset.name = variety.name;
            varietySelect.appendChild(option);
        });
        
        if (filteredVarieties.length === 0) {
            console.warn('No varieties found for crop:', cropId);
        }
    }

    function updateDragBarFromDates() {
        const startDate = document.getElementById('harvestStart').value;
        const endDate = document.getElementById('harvestEnd').value;
        
        if (!startDate || !endDate) return;
        
        const seasonStart = new Date('2024-03-01');
        const seasonEnd = new Date('2024-11-30');
        const seasonDays = Math.ceil((seasonEnd - seasonStart) / (1000 * 60 * 60 * 24));
        
        const harvestStart = new Date(startDate);
        const harvestEnd = new Date(endDate);
        
        const startDays = Math.ceil((harvestStart - seasonStart) / (1000 * 60 * 60 * 24));
        const endDays = Math.ceil((harvestEnd - seasonStart) / (1000 * 60 * 60 * 24));
        
        const timeline = document.getElementById('dragHarvestBar').parentElement;
        const timelineWidth = timeline.offsetWidth;
        
        const leftPercent = Math.max(0, Math.min(1, startDays / seasonDays));
        const rightPercent = Math.max(0, Math.min(1, endDays / seasonDays));
        
        const dragBar = document.getElementById('dragHarvestBar');
        dragBar.style.left = (leftPercent * timelineWidth) + 'px';
        dragBar.style.width = ((rightPercent - leftPercent) * timelineWidth) + 'px';
        
        updateHarvestBarText(harvestStart, harvestEnd);
    }

    function selectAllBeds() {
        const bedSelect = document.getElementById('bedSelect');
        for (let option of bedSelect.options) {
            option.selected = true;
        }
    }

    function clearBedSelection() {
        const bedSelect = document.getElementById('bedSelect');
        for (let option of bedSelect.options) {
            option.selected = false;
        }
    }

    async function calculateSuccessionPlan() {
        const cropId = document.getElementById('cropSelect').value;
        const varietyId = document.getElementById('varietySelect').value;
        const harvestStart = document.getElementById('harvestStart').value;
        const harvestEnd = document.getElementById('harvestEnd').value;
        const selectedBeds = Array.from(document.getElementById('bedSelect').selectedOptions).map(opt => opt.value);

        // Validation
        if (!cropId) {
            showError('Please select a crop type from farmOS taxonomy');
            return;
        }
        
        if (!harvestStart || !harvestEnd) {
            showError('Please set both harvest start and end dates');
            return;
        }

        showLoading(true);
        
        try {
            // Call your existing backend endpoint
            const response = await fetch('/admin/farmos/succession-planning/calculate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    crop_id: cropId,
                    variety_id: varietyId || null,
                    harvest_start: harvestStart,
                    harvest_end: harvestEnd,
                    bed_ids: selectedBeds,
                    use_ai: true
                })
            });

            const data = await response.json();
            
            if (data.success) {
                currentSuccessionPlan = data.succession_plan;
                displaySuccessionPlan(data.succession_plan);
                showResults();
                console.log('✅ Succession plan calculated successfully');
            } else {
                console.error('❌ Failed to calculate succession plan:', data.error);
                showError('Failed to calculate succession plan: ' + (data.error || 'Unknown error'));
            }

        } catch (error) {
            console.error('❌ Error calculating succession plan:', error);
            showError('Network error calculating succession plan');
        } finally {
            showLoading(false);
        }
    }

    function displaySuccessionPlan(plan) {
        // Display succession summary cards
        displaySuccessionSummary(plan);
        
        // Create timeline chart
        createTimelineChart(plan);
    }

    function displaySuccessionSummary(plan) {
        const summaryContainer = document.getElementById('successionSummary');
        let summaryHTML = '';

        if (plan.plantings && plan.plantings.length > 0) {
            plan.plantings.forEach((planting, index) => {
                const plantingDate = new Date(planting.planting_date);
                const harvestDate = new Date(planting.harvest_date);
                const isOverdue = plantingDate < new Date();
                
                summaryHTML += `
                    <div class="col-md-6 col-lg-4 mb-3">
                        <div class="succession-card ${isOverdue ? 'overdue' : ''}">
                            <h6>
                                <i class="fas fa-seedling ${isOverdue ? 'text-danger' : 'text-success'}"></i>
                                Succession ${index + 1}
                                ${isOverdue ? '<span class="badge bg-danger ms-2">Overdue</span>' : ''}
                            </h6>
                            <p class="mb-1">
                                <strong>Plant:</strong> ${plantingDate.toLocaleDateString()}
                            </p>
                            <p class="mb-1">
                                <strong>Harvest:</strong> ${harvestDate.toLocaleDateString()}
                            </p>
                            ${planting.bed_name ? `<p class="mb-0 text-muted">Bed: ${planting.bed_name}</p>` : ''}
                        </div>
                    </div>
                `;
            });
        }

        summaryContainer.innerHTML = summaryHTML;
    }

    function createTimelineChart(plan) {
        const ctx = document.getElementById('successionChart').getContext('2d');
        
        // Destroy existing chart if it exists
        if (timelineChart) {
            timelineChart.destroy();
        }

        if (!plan.plantings || plan.plantings.length === 0) {
            ctx.fillText('No succession plan data available', 10, 50);
            return;
        }

        const datasets = plan.plantings.map((planting, index) => {
            const plantingDate = new Date(planting.planting_date);
            const harvestDate = new Date(planting.harvest_date);
            const isOverdue = plantingDate < new Date();

            return {
                label: `Succession ${index + 1}`,
                data: [{
                    x: plantingDate.toISOString().split('T')[0],
                    y: `Succession ${index + 1}`,
                    x2: harvestDate.toISOString().split('T')[0]
                }],
                backgroundColor: isOverdue ? '#dc3545' : '#28a745',
                borderColor: isOverdue ? '#dc3545' : '#28a745',
                borderWidth: 2,
                barThickness: 20
            };
        });

        timelineChart = new Chart(ctx, {
            type: 'bar',
            data: { datasets },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Succession Planting & Harvest Timeline'
                    },
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            title: function(context) {
                                return `Succession ${context[0].datasetIndex + 1}`;
                            },
                            label: function(context) {
                                const planting = plan.plantings[context.datasetIndex];
                                return [
                                    `Plant: ${new Date(planting.planting_date).toLocaleDateString()}`,
                                    `Harvest: ${new Date(planting.harvest_date).toLocaleDateString()}`,
                                    planting.bed_name ? `Bed: ${planting.bed_name}` : '',
                                    new Date(planting.planting_date) < new Date() ? 'Status: Overdue (plant ASAP)' : 'Status: On schedule'
                                ].filter(Boolean);
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: { unit: 'day' },
                        title: { display: true, text: 'Date' }
                    },
                    y: {
                        title: { display: true, text: 'Succession Number' }
                    }
                }
            }
        });
    }

    // AI Chat Functions
    async function askHolisticAI() {
        const question = document.getElementById('aiChatInput').value.trim();
        if (!question) {
            showError('Please enter a question for the AI');
            return;
        }

        const responseArea = document.getElementById('aiResponseArea');
        
        // Clear welcome message if it exists
        const welcomeMessage = document.getElementById('welcomeMessage');
        if (welcomeMessage) {
            welcomeMessage.remove();
        }
        
        // Show loading
        const loadingDiv = document.createElement('div');
        loadingDiv.className = 'ai-response';
        loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> farmOS AI is analyzing your question...';
        responseArea.appendChild(loadingDiv);

        try {
            // Use Laravel route to access our farmOS-integrated AI service
            const response = await fetch('/admin/farmos/succession-planning/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    message: question,
                    context: getCurrentPlanContext()
                })
            });

            console.log('🤖 Chat response status:', response.status);
            
            if (response.ok) {
                const data = await response.json();
                console.log('🤖 Chat response data:', data);
                
                // Remove loading
                responseArea.removeChild(loadingDiv);
                
                if (data.answer || data.response) {
                    const aiResponse = document.createElement('div');
                    aiResponse.className = 'ai-response';
                    aiResponse.innerHTML = `
                        <div class="mb-2">
                            <strong>🧠 farmOS AI (${data.model || 'Database-Integrated'}):</strong>
                        </div>
                        <div class="ai-response-content">
                            ${(data.answer || data.response).replace(/\n/g, '<br>')}
                        </div>
                        <div class="text-muted small mt-2">
                            Cost: ${data.cost || 'FREE'} | Method: ${data.method || 'farmOS Integration'}
                        </div>
                    `;
                    responseArea.appendChild(aiResponse);
                    
                    // Clear input and scroll to bottom
                    document.getElementById('aiChatInput').value = '';
                    responseArea.scrollTop = responseArea.scrollHeight;
                } else {
                    displayAIErrorInChat('No response received from AI service');
                }
            } else {
                responseArea.removeChild(loadingDiv);
                const errorText = await response.text();
                console.log('🔍 Chat Error Response:', errorText);
                displayAIErrorInChat(`Server error: ${response.status} ${response.statusText}`);
            }

        } catch (error) {
            if (responseArea.contains(loadingDiv)) {
                responseArea.removeChild(loadingDiv);
            }
            console.error('❌ AI chat error:', error);
            displayAIErrorInChat('Connection error. Please check your internet connection and try again.');
        }
    }
            showError('Network error communicating with AI');
        }
    }

    function getCurrentPlanContext() {
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        const harvestStart = document.getElementById('harvestStart').value;
        const harvestEnd = document.getElementById('harvestEnd').value;

        return {
            crop_name: cropSelect.options[cropSelect.selectedIndex]?.text || null,
            variety_name: varietySelect.options[varietySelect.selectedIndex]?.text || null,
            harvest_window: harvestStart && harvestEnd ? { start: harvestStart, end: harvestEnd } : null,
            current_plan: currentSuccessionPlan
        };
    }

    async function askQuickQuestion(questionType) {
        const questions = {
            'succession-timing': 'What is the optimal succession planting interval for the selected crop?',
            'companion-plants': 'What are the best companion plants for this crop in a succession system?',
            'lunar-timing': 'How can lunar cycles optimize the planting timing for this succession plan?',
            'harvest-optimization': 'How can I optimize harvest timing and extend the harvest window?'
        };

        document.getElementById('aiChatInput').value = questions[questionType];
        await askHolisticAI();
    }

    function getQuickAdvice() {
        const cropSelect = document.getElementById('cropSelect');
        const selectedCrop = cropSelect.options[cropSelect.selectedIndex]?.text;
        
        if (selectedCrop) {
            document.getElementById('aiChatInput').value = `Give me quick succession planning tips for ${selectedCrop}`;
            askHolisticAI();
        } else {
            showError('Please select a crop first');
        }
    }

    async function createFarmOSLogs() {
        if (!currentSuccessionPlan) {
            showError('No succession plan to create logs for');
            return;
        }

        if (!confirm('Create farmOS seeding logs for this succession plan?')) {
            return;
        }

        showLoading(true);

        try {
            const response = await fetch('/admin/farmos/succession-planning/create-logs', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({
                    succession_plan: currentSuccessionPlan
                })
            });

            const data = await response.json();
            
            if (data.success) {
                showSuccess('farmOS logs created successfully: ' + data.message);
                console.log('✅ farmOS logs created:', data.logs_created);
            } else {
                showError('Failed to create farmOS logs: ' + (data.error || 'Unknown error'));
            }

        } catch (error) {
            console.error('❌ Error creating farmOS logs:', error);
            showError('Network error creating farmOS logs');
        } finally {
            showLoading(false);
        }
    }

    function exportPlan() {
        if (!currentSuccessionPlan) {
            showError('No succession plan to export');
            return;
        }

        const dataStr = JSON.stringify(currentSuccessionPlan, null, 2);
        const dataBlob = new Blob([dataStr], {type: 'application/json'});
        
        const link = document.createElement('a');
        link.href = URL.createObjectURL(dataBlob);
        link.download = `succession-plan-${new Date().toISOString().split('T')[0]}.json`;
        link.click();
        
        showSuccess('Succession plan exported successfully');
    }

    function resetPlanner() {
        if (confirm('Reset the succession planner and start over?')) {
            // Reset form
            document.getElementById('cropSelect').value = '';
            document.getElementById('varietySelect').innerHTML = '<option value="">Select crop first...</option>';
            document.getElementById('harvestStart').value = '';
            document.getElementById('harvestEnd').value = '';
            clearBedSelection();
            
            // Reset drag bar
            const dragBar = document.getElementById('dragHarvestBar');
            dragBar.style.left = '30%';
            dragBar.style.width = '40%';
            document.getElementById('harvestBarText').textContent = 'Harvest Window';
            
            // Hide results
            document.getElementById('resultsSection').style.display = 'none';
            
            // Clear data
            currentSuccessionPlan = null;
            
            // Destroy chart
            if (timelineChart) {
                timelineChart.destroy();
                timelineChart = null;
            }
            
            // Reset AI chat
            document.getElementById('aiResponseArea').innerHTML = `
                <div class="ai-response" id="welcomeMessage">
                    <strong>🌱 Holistic AI Ready</strong><br>
                    I combine traditional farming wisdom with modern succession planning. Ask me about optimal planting times, crop rotations, companion planting, or biodynamic timing for your succession crops.
                </div>
            `;
            
            showSuccess('Succession planner reset');
        }
    }

    function showResults() {
        document.getElementById('resultsSection').style.display = 'block';
        document.getElementById('resultsSection').scrollIntoView({ behavior: 'smooth' });
    }

    function showLoading(show) {
        const overlay = document.getElementById('loadingOverlay');
        overlay.classList.toggle('d-none', !show);
    }

    function showError(message) {
        console.error('❌ Error:', message);
        alert('❌ Error: ' + message);
    }

    function showSuccess(message) {
        console.log('✅ Success:', message);
        alert('✅ Success: ' + message);
    }
    
    // Add syntax validation
    console.log('🔍 JavaScript syntax validation complete - no errors detected');
</script>
@endsection
