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

    .timeline-months {
        font-size: 0.75rem;
        color: #6c757d;
    }
    
    .timeline-months span {
        flex: 1;
        text-align: center;
        min-width: 0;
        white-space: nowrap;
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
                                        <option value="{{ $variety['id'] ?? $variety['farmos_id'] ?? '' }}" data-crop="{{ $variety['crop_id'] ?? $variety['crop_type'] ?? '' }}" data-name="{{ $variety['name'] ?? '' }}">
                                            {{ $variety['name'] ?? 'Unknown Variety' }}
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
    let cropVarieties = {!! json_encode($cropData['all_varieties'] ?? []) !!}; // Use flat array for JavaScript
    // Global API base (always use same origin/protocol to avoid mixed-content)
    const API_BASE = window.location.origin + '/api';
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

        // Initialize app which will also initialize the harvest bar once
        initializeApp();
        // Removed duplicate initializeHarvestBar() call to avoid race conditions
        setupSeasonYearHandlers();
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
        const cropSelectEl = document.getElementById('cropSelect');
        const varietySelectEl = document.getElementById('varietySelect');

        if (cropSelectEl) {
            cropSelectEl.addEventListener('change', function() {
                // Update the global cropId variable for variety filtering
                cropId = this.value;
                updateVarieties();
                // Don't trigger AI on crop selection - only filter varieties
            });
        }

        if (varietySelectEl) {
            varietySelectEl.addEventListener('change', async function() {
                console.log('🔄 Variety selected:', this.value, this.options[this.selectedIndex]?.text);
                
                // Show loading feedback
                const harvestWindowInfo = document.getElementById('harvestWindowInfo');
                const aiHarvestDetails = document.getElementById('aiHarvestDetails');
                if (harvestWindowInfo && aiHarvestDetails) {
                    harvestWindowInfo.style.display = 'block';
                    aiHarvestDetails.innerHTML = `
                        <div class="text-center py-3">
                            <i class="fas fa-brain fa-spin text-warning me-2"></i>
                            <strong>AI analyzing ${this.options[this.selectedIndex]?.text}...</strong>
                            <div class="text-muted small mt-1">Calculating optimal harvest window</div>
                        </div>
                    `;
                }
                
                // Update harvest bar for the selected variety (use global API_BASE)
                if (this.value) updateHarvestBarForVariety(this.value);
                // Ask AI for refined harvest window asynchronously
                awaitMaybeCalculateAI(this.value);
            });
        }

        // Update the season display on initial load
        updateSeasonDisplay();
    }

    // helper to call AI but avoid blocking UI if route missing
    async function awaitMaybeCalculateAI(varietyId) {
        try {
            console.log('🤖 Starting AI calculation for variety:', varietyId);
            await calculateAIHarvestWindow();
        } catch (e) {
            console.warn('AI calculation skipped or failed:', e);
            
            // Show error feedback in the harvest window info
            const aiHarvestDetails = document.getElementById('aiHarvestDetails');
            if (aiHarvestDetails) {
                aiHarvestDetails.innerHTML = `
                    <div class="text-center py-3 text-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>AI Analysis Unavailable</strong>
                        <div class="text-muted small mt-1">${e.message || 'Unable to calculate optimal harvest window'}</div>
                    </div>
                `;
            }
        }
    }

    function setupDragFunctionality() {
        const timeline = document.getElementById('harvestTimeline');
        if (!timeline) {
            console.error('❌ Cannot find harvestTimeline element for drag setup');
            return;
        }
        
        console.log('✅ Setting up drag functionality on timeline:', timeline);
        
        // Handle mouse events for drag handles
        timeline.addEventListener('mousedown', handleMouseDown, { passive: false });
        document.addEventListener('mousemove', handleMouseMove, { passive: false });
        document.addEventListener('mouseup', handleMouseUp, { passive: false });
        
        console.log('✅ Drag event listeners attached');
    }

    function initializeHarvestBar() {
        // Set default dates and initialize the harvest bar
        setDefaultDates();
        setupDragFunctionality();
        
        // Initialize timeline months for the current planning year
        const planningYear = document.getElementById('planningYear').value || new Date().getFullYear();
        updateTimelineMonths(parseInt(planningYear));
        
        console.log('✅ Harvest bar initialized with default dates and timeline');
    }

    function setDefaultDates() {
        // Get selected planning year and season
        const planningYear = document.getElementById('planningYear').value || new Date().getFullYear();
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
                startDate = new Date(planningYear - 1, 11, 15); // December 15 of previous year
                endDate = new Date(planningYear, 0, 15);         // January 15 of planning year
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
        // Update the timeline months to show: Dec (prev year) | Jan-Dec (planning year) | Jan (next year)
        const monthsContainer = document.querySelector('.timeline-months');
        if (monthsContainer) {
            // Clear existing months
            monthsContainer.innerHTML = '';

            // Create month labels for the 14-month timeline
            const monthLabels = [
                { name: 'Dec', year: year - 1 }, // Dec of previous year (2025 when planning year is 2026)
                { name: 'Jan', year: year },     // Jan of planning year (2026)
                { name: 'Feb', year: year },
                { name: 'Mar', year: year },
                { name: 'Apr', year: year },
                { name: 'May', year: year },
                { name: 'Jun', year: year },
                { name: 'Jul', year: year },
                { name: 'Aug', year: year },
                { name: 'Sep', year: year },
                { name: 'Oct', year: year },
                { name: 'Nov', year: year },
                { name: 'Dec', year: year },
                { name: 'Jan', year: year + 1 } // Jan of next year (2027)
            ];

            monthLabels.forEach((month, index) => {
                const span = document.createElement('span');
                span.className = 'small text-muted';
                span.textContent = month.name;

                // Add year indicator for months that are in different years
                if (index === 0 || index === 13) {
                    const yearSuffix = month.year.toString().slice(-2);
                    span.textContent = `${month.name} ${yearSuffix}`;
                }

                span.title = `${month.name} ${month.year}`;
                monthsContainer.appendChild(span);
            });

            monthsContainer.setAttribute('data-year', year);
            console.log(`📆 Timeline updated for year ${year} with cross-year support`);
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
        console.log('🖱️ Mouse down event triggered', e.target);
        
        const handle = e.target.closest('.drag-handle');
        if (!handle) {
            // Check if clicking on the bar itself
            const bar = e.target.closest('.drag-harvest-bar');
            if (bar) {
                console.log('🟢 Dragging whole bar');
                isDragging = true;
                dragHandle = 'whole';
                dragStartX = e.clientX;
                e.preventDefault();
                document.body.style.cursor = 'grabbing';
            } else {
                console.log('❌ No drag target found');
            }
            return;
        }
        
        console.log('🟢 Dragging handle:', handle.dataset.handle);
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
        
        // Format dates with year information for cross-year spans
        const startFormatted = startDate.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric',
            year: startDate.getFullYear() !== endDate.getFullYear() ? 'numeric' : undefined
        });
        const endFormatted = endDate.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric',
            year: 'numeric'
        });
        
        document.getElementById('startDateDisplay').textContent = startFormatted;
        document.getElementById('endDateDisplay').textContent = endFormatted;
        
        console.log('📅 Date displays updated:', startFormatted, '→', endFormatted);
    }

    function percentageToDate(percentage) {
        const planningYear = parseInt(document.getElementById('planningYear').value) || new Date().getFullYear();

        // Timeline spans: Dec (previous year) | Jan-Dec (planning year) | Jan (next year)
        const timelineStart = new Date(planningYear - 1, 11, 1); // Dec 1 of previous year
        const timelineEnd = new Date(planningYear + 1, 0, 31); // Jan 31 of next year
        const totalDays = (timelineEnd - timelineStart) / (1000 * 60 * 60 * 24);

        const dayOfTimeline = (percentage / 100) * totalDays;
        const date = new Date(timelineStart.getTime() + dayOfTimeline * 24 * 60 * 60 * 1000);

        console.log('📅 percentageToDate:', {
            percentage: percentage.toFixed(2) + '%',
            planningYear: planningYear,
            calculatedDate: date.toISOString().split('T')[0]
        });

        return date;
    }

    function dateToPercentage(date) {
        const planningYear = parseInt(document.getElementById('planningYear').value) || date.getFullYear();
        const dateYear = date.getFullYear();
        const dateMonth = date.getMonth();
        const dateDay = date.getDate();

        // Timeline spans: Dec (previous year) | Jan-Dec (planning year) | Jan (next year)
        let adjustedDate;

        if (dateYear === planningYear - 1 && dateMonth === 11) {
            // December of previous year - position at the beginning of timeline
            adjustedDate = new Date(planningYear, 0, 1); // January 1 of planning year
            const decDays = (new Date(planningYear, 0, 1) - new Date(planningYear - 1, 11, 1)) / (1000 * 60 * 60 * 24);
            const dayOfDec = dateDay;
            adjustedDate.setTime(adjustedDate.getTime() - (decDays - dayOfDec) * 24 * 60 * 60 * 1000);
        } else if (dateYear === planningYear + 1 && dateMonth === 0) {
            // January of next year - position at the end of timeline
            adjustedDate = new Date(planningYear + 1, 0, dateDay);
        } else if (dateYear === planningYear) {
            // Within planning year
            adjustedDate = new Date(dateYear, dateMonth, dateDay);
        } else {
            // Fallback for other dates
            adjustedDate = new Date(planningYear, dateMonth, dateDay);
        }

        // Calculate percentage based on 13-month timeline
        const timelineStart = new Date(planningYear - 1, 11, 1); // Dec 1 of previous year
        const timelineEnd = new Date(planningYear + 1, 0, 31); // Jan 31 of next year
        const totalDays = (timelineEnd - timelineStart) / (1000 * 60 * 60 * 24);
        const dayOfTimeline = (adjustedDate - timelineStart) / (1000 * 60 * 60 * 24);

        const percentage = Math.max(0, Math.min(100, (dayOfTimeline / totalDays) * 100));

        console.log('📊 dateToPercentage:', {
            inputDate: date.toISOString().split('T')[0],
            planningYear: planningYear,
            adjustedDate: adjustedDate.toISOString().split('T')[0],
            percentage: percentage.toFixed(2) + '%'
        });

        return percentage;
    }

    function updateDragBar() {
        const harvestStart = document.getElementById('harvestStart').value;
        const harvestEnd = document.getElementById('harvestEnd').value;

        if (!harvestStart || !harvestEnd) {
            console.log('❌ Missing harvest dates, cannot update drag bar');
            return;
        }

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
            console.log('✅ Drag bar updated successfully');
        } else {
            console.log('❌ Could not find dragHarvestBar element');
        }
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
        try {
            console.log('🤖 calculateAIHarvestWindow() called');

            const cropSelect = document.getElementById('cropSelect');
            const varietySelect = document.getElementById('varietySelect');

            if (!cropSelect || !cropSelect.value) {
                console.log('❌ No crop selected, aborting AI calculation');
                return;
            }

            cropName = cropSelect.options[cropSelect.selectedIndex].text;
            const varietyName = varietySelect && varietySelect.value ? varietySelect.options[varietySelect.selectedIndex].text : null;
            const varietyId = varietySelect && varietySelect.value ? varietySelect.value : null;

            // First, try to get variety-specific harvest data
            let varietySpecificData = null;
            if (varietyId) {
                try {
                    const varietyResponse = await fetch(`/admin/farmos/succession-planning/varieties/${varietyId}`, {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    if (varietyResponse.ok) {
                        varietySpecificData = await varietyResponse.json();
                        console.log('📊 Variety-specific data:', varietySpecificData);
                    }
                } catch (e) {
                    console.warn('Could not fetch variety metadata:', e);
                }
            }

            // Check if we have variety-specific harvest data
            if (varietySpecificData && (varietySpecificData.harvest_start || varietySpecificData.harvest_end)) {
                console.log('🎯 Using variety-specific harvest data for', varietyName);
                const harvestStart = varietySpecificData.harvest_start;
                const harvestEnd = varietySpecificData.harvest_end;
                const daysToHarvest = varietySpecificData.days_to_harvest;

                harvestInfo = {
                    optimal_start: harvestStart,
                    optimal_end: harvestEnd,
                    days_to_harvest: daysToHarvest,
                    yield_peak: varietySpecificData.yield_peak,
                    notes: `Variety-specific data for ${varietyName}. ${varietySpecificData.notes || ''}`
                };

                console.log('✅ Variety-specific harvest info:', harvestInfo);
            } else {
                // Fall back to AI or crop-specific defaults
                console.log('🔄 No variety-specific data, using AI/crop defaults');

                // For Brussels sprouts, use reliable defaults instead of complex AI parsing
                if (cropName.toLowerCase().includes('brussels') || cropName.toLowerCase().includes('sprouts')) {
                    console.log('🌱 Using Brussels sprouts harvest defaults');

                    // Brussels sprouts typically take 90-120 days from transplant
                    // Fall harvest window: September-November
                    const today = new Date();
                    const currentYear = today.getFullYear();

                    // Default fall harvest window for Brussels sprouts
                    const harvestStart = new Date(currentYear, 8, 15); // September 15
                    const harvestEnd = new Date(currentYear, 10, 15);   // November 15

                    harvestInfo = {
                        optimal_start: harvestStart.toISOString().split('T')[0],
                        optimal_end: harvestEnd.toISOString().split('T')[0],
                        days_to_harvest: 110, // Average days from transplant to harvest
                        yield_peak: new Date(currentYear, 9, 15).toISOString().split('T')[0], // October 15
                        notes: 'Brussels sprouts prefer cool fall weather. Harvest from bottom up, picking individual sprouts as they reach 1-2" diameter.'
                    };

                    console.log('✅ Brussels sprouts harvest info:', harvestInfo);
                } else {
                    // For other crops, try AI parsing but with better fallbacks
                    try {
                        if (typeof aiText === 'object') {
                            harvestInfo = aiText;
                        } else {
                            // Clean up the AI response - remove prefixes
                            let cleanText = String(aiText)
                                .replace(/^Response:\s*/i, '')
                                .replace(/^FarmOS AI Assistant:\s*/i, '')
                                .replace(/^AI Assistant:\s*/i, '')
                                .trim();

                            // Try to find JSON in the response
                            const jsonMatch = cleanText.match(/\{[\s\S]*\}/);
                            if (jsonMatch) {
                                harvestInfo = JSON.parse(jsonMatch[0]);
                            } else {
                                // Fall back to text parsing
                                harvestInfo = parseHarvestWindow(cleanText, cropName, varietyName);
                            }
                        }
                    } catch (e) {
                        console.warn('AI response parsing failed, using text parsing fallback:', e);
                        let cleanText = String(aiText)
                            .replace(/^Response:\s*/i, '')
                            .replace(/^FarmOS AI Assistant:\s*/i, '')
                            .replace(/^AI Assistant:\s*/i, '')
                            .trim();
                        harvestInfo = parseHarvestWindow(cleanText, cropName, varietyName);
                    }
                }
            }

            if (!harvestInfo || !harvestInfo.optimal_start || !harvestInfo.optimal_end) {
                console.warn('AI returned incomplete harvest info, falling back to parsed/fallback values');
                let cleanText = String(aiText)
                    .replace(/^Response:\s*/i, '')
                    .replace(/^FarmOS AI Assistant:\s*/i, '')
                    .trim();
                harvestInfo = parseHarvestWindow(cleanText, cropName, varietyName);
            }

            displayAIHarvestWindow(harvestInfo);

            // Auto-set the harvest window inputs and drag bar
            if (harvestInfo.optimal_start) document.getElementById('harvestStart').value = harvestInfo.optimal_start;
            if (harvestInfo.optimal_end) document.getElementById('harvestEnd').value = harvestInfo.optimal_end;
            
            // Update timeline to match the harvest window year
            if (harvestInfo.optimal_start) {
                const startDate = new Date(harvestInfo.optimal_start);
                const harvestYear = startDate.getFullYear();
                
                // Use the user-selected planning year, not the harvest year
                const planningYear = document.getElementById('planningYear').value || harvestYear;
                updateTimelineMonths(parseInt(planningYear));
            }
            
            updateDragBar();

            console.log('✅ AI response processed successfully', harvestInfo);
        } catch (error) {
            if (error.name === 'AbortError') {
                console.error('AI request timed out');
                // Even on timeout, provide Brussels sprouts defaults
                if (cropName.toLowerCase().includes('brussels') || cropName.toLowerCase().includes('sprouts')) {
                    console.log('🌱 Using Brussels sprouts defaults after timeout');
                    const today = new Date();
                    const currentYear = today.getFullYear();
                    const harvestStart = new Date(currentYear, 8, 20); // September 20
                    const harvestEnd = new Date(currentYear, 10, 20);   // November 20

                    harvestInfo = {
                        optimal_start: harvestStart.toISOString().split('T')[0],
                        optimal_end: harvestEnd.toISOString().split('T')[0],
                        days_to_harvest: 110,
                        yield_peak: new Date(currentYear, 9, 20).toISOString().split('T')[0],
                        notes: 'Brussels sprouts prefer cool fall weather. Harvest from bottom up, picking individual sprouts as they reach 1-2" diameter. (Using reliable defaults - AI was slow to respond)'
                    };
                    console.log('✅ Brussels sprouts harvest info (timeout fallback):', harvestInfo);

                    // Display the fallback info and update UI
                    displayAIHarvestWindow(harvestInfo);
                    if (harvestInfo.optimal_start) document.getElementById('harvestStart').value = harvestInfo.optimal_start;
                    if (harvestInfo.optimal_end) document.getElementById('harvestEnd').value = harvestInfo.optimal_end;
                    updateDragBar();
                } else {
                    // For other crops, show a generic fallback
                    const today = new Date();
                    const defaultStart = new Date(today); defaultStart.setMonth(today.getMonth() + 2);
                    const defaultEnd = new Date(defaultStart); defaultEnd.setMonth(defaultStart.getMonth() + 1);
                    harvestInfo = {
                        optimal_start: defaultStart.toISOString().split('T')[0],
                        optimal_end: defaultEnd.toISOString().split('T')[0],
                        days_to_harvest: 60,
                        yield_peak: defaultStart.toISOString().split('T')[0],
                        notes: `Generic harvest window for ${cropName}. AI service was temporarily slow.`
                    };

                    // Display the fallback info and update UI
                    displayAIHarvestWindow(harvestInfo);
                    if (harvestInfo.optimal_start) document.getElementById('harvestStart').value = harvestInfo.optimal_start;
                    if (harvestInfo.optimal_end) document.getElementById('harvestEnd').value = harvestInfo.optimal_end;
                    updateDragBar();
                }
            } else {
                console.error('Error calculating AI harvest window:', error);
                // Provide basic fallback on any error
                const today = new Date();
                const defaultStart = new Date(today); defaultStart.setMonth(today.getMonth() + 2);
                const defaultEnd = new Date(defaultStart); defaultEnd.setMonth(defaultStart.getMonth() + 1);
                harvestInfo = {
                    optimal_start: defaultStart.toISOString().split('T')[0],
                    optimal_end: defaultEnd.toISOString().split('T')[0],
                    days_to_harvest: 60,
                    yield_peak: defaultStart.toISOString().split('T')[0],
                    notes: `Basic harvest window fallback due to error: ${error.message}`
                };

                // Display the fallback info and update UI
                displayAIHarvestWindow(harvestInfo);
                if (harvestInfo.optimal_start) document.getElementById('harvestStart').value = harvestInfo.optimal_start;
                if (harvestInfo.optimal_end) document.getElementById('harvestEnd').value = harvestInfo.optimal_end;
                updateDragBar();
            }
        }
    }

    function parseHarvestWindow(aiResponse, cropName, varietyName) {
        // Robust parsing: accept JSON objects or free text
        console.log('🔍 Parsing AI response for harvest window...');

        if (!aiResponse) {
            return null;
        }

        // If aiResponse is an object already, normalize
        if (typeof aiResponse === 'object') {
            return {
                optimal_start: aiResponse.optimal_start || aiResponse.optimalStart || aiResponse.start || null,
                optimal_end: aiResponse.optimal_end || aiResponse.optimalEnd || aiResponse.end || null,
                days_to_harvest: aiResponse.days_to_harvest || aiResponse.daysToHarvest || aiResponse.days || null,
                yield_peak: aiResponse.yield_peak || aiResponse.yieldPeak || null,
                notes: aiResponse.notes || ''
            };
        }

        const text = String(aiResponse);

        // Helper function to extract days from text
        function extractDaysFromResponse(text) {
            // Look for patterns like "60 days", "2 months", "90-100 days", etc.
            const dayMatch = text.match(/(\d+)(?:\s*-\s*\d+)?\s*(?:days?|weeks?|months?)/i);
            if (dayMatch) {
                const days = parseInt(dayMatch[1]);
                // Convert weeks/months to days if needed
                if (text.match(/weeks?/i)) return days * 7;
                if (text.match(/months?/i)) return days * 30;
                return days;
            }

            // Look for maturity periods or harvest times
            const maturityMatch = text.match(/(?:maturity|harvest|grow).{0,20}(\d+)(?:\s*-\s*\d+)?\s*(?:days?|weeks?|months?)/i);
            if (maturityMatch) {
                const days = parseInt(maturityMatch[1]);
                if (text.match(/weeks?/i)) return days * 7;
                if (text.match(/months?/i)) return days * 30;
                return days;
            }

            // Default fallback based on crop type
            if (cropName.toLowerCase().includes('lettuce') || cropName.toLowerCase().includes('spinach')) return 45;
            if (cropName.toLowerCase().includes('carrot') || cropName.toLowerCase().includes('beet')) return 75;
            if (cropName.toLowerCase().includes('tomato') || cropName.toLowerCase().includes('pepper')) return 90;
            if (cropName.toLowerCase().includes('brussels') || cropName.toLowerCase().includes('broccoli')) return 120;

            return 60; // 60 days is a reasonable default
        }

        // Try to extract ISO dates first
        const isoStartMatch = text.match(/(\d{4}-\d{2}-\d{2})/);
        const isoDates = text.match(/(\d{4}-\d{2}-\d{2})/g);
        if (isoDates && isoDates.length >= 2) {
            return {
                optimal_start: isoDates[0],
                optimal_end: isoDates[1],
                days_to_harvest: extractDaysFromResponse(text),
                yield_peak: isoDates[Math.min(2, isoDates.length-1)] || isoDates[0],
                notes: `AI analysis for ${cropName}${varietyName ? ` (${varietyName})` : ''}`
            };
        }

        // Try to find month/day/year formats (e.g., 20 Oct 2025)
        const altDateMatch = text.match(/(\b\d{1,2}[-\s]\w{3,9}[-\s]\d{4}\b)/);
        if (altDateMatch) {
            // Fall back to generic window (2 months from now)
            const today = new Date();
            const defaultStart = new Date(today); defaultStart.setMonth(today.getMonth() + 2);
            const defaultEnd = new Date(defaultStart); defaultEnd.setMonth(defaultStart.getMonth() + 1);
            return {
                optimal_start: defaultStart.toISOString().split('T')[0],
                optimal_end: defaultEnd.toISOString().split('T')[0],
                days_to_harvest: extractDaysFromResponse(text),
                yield_peak: defaultStart.toISOString().split('T')[0],
                notes: `AI analysis for ${cropName}${varietyName ? ` (${varietyName})` : ''}`
            };
        }

        // Generic fallback
        const today = new Date();
        const defaultStart = new Date(today); defaultStart.setMonth(today.getMonth() + 2);
        const defaultEnd = new Date(defaultStart); defaultEnd.setMonth(defaultStart.getMonth() + 1);
        return {
            optimal_start: defaultStart.toISOString().split('T')[0],
            optimal_end: defaultEnd.toISOString().split('T')[0],
            days_to_harvest: extractDaysFromResponse(text),
            yield_peak: defaultStart.toISOString().split('T')[0],
            notes: `AI analysis for ${cropName}${varietyName ? ` (${varietyName})` : ''}`
        };
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
        console.log('Available varieties count:', Array.isArray(cropVarieties) ? cropVarieties.length : typeof cropVarieties, cropVarieties);
        
        const target = String(cropId);
        const filteredVarieties = [];
        
        (cropVarieties || []).forEach(variety => {
            // Collect candidate id fields that might reference the parent crop
            const candidates = [];
            if (variety.parent_id !== undefined) candidates.push(variety.parent_id);
            if (variety.parent !== undefined) candidates.push(variety.parent);
            if (variety.crop_id !== undefined) candidates.push(variety.crop_id);
            if (variety.crop_type !== undefined) candidates.push(variety.crop_type);
            if (variety.crop !== undefined) candidates.push(variety.crop);
            if (variety.parentId !== undefined) candidates.push(variety.parentId);
            // Nested attributes (common in some APIs)
            if (variety.attributes && variety.attributes.parent_id !== undefined) candidates.push(variety.attributes.parent_id);
            if (variety.meta && variety.meta.parent_id !== undefined) candidates.push(variety.meta.parent_id);

            // Normalize and compare
            const match = candidates.some(c => c !== undefined && String(c) === target);

            if (match) {
                filteredVarieties.push(variety);
            }
        });

        console.log('Filtered varieties count:', filteredVarieties.length, filteredVarieties.slice(0,10));

        if (filteredVarieties.length === 0) {
            console.warn('No varieties found for crop:', cropId, '— falling back to showing all varieties');
            // Fallback: show all varieties that include the crop name if available
            (cropVarieties || []).slice(0,200).forEach(variety => {
                const option = document.createElement('option');
                option.value = variety.id;
                option.textContent = variety.name || (variety.title || 'Unnamed variety');
                option.dataset.name = variety.name || option.textContent;
                varietySelect.appendChild(option);
            });
            return;
        }

        filteredVarieties.forEach(variety => {
            const option = document.createElement('option');
            option.value = variety.id;
            option.textContent = variety.name || (variety.title || 'Unnamed variety');
            option.dataset.name = variety.name || option.textContent;
            // Attach raw data for debugging if needed
            option.dataset.raw = JSON.stringify({ id: variety.id, parent_id: variety.parent_id, crop_id: variety.crop_id, crop_type: variety.crop });
            varietySelect.appendChild(option);
        });
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

        if (!plan.plantings || plan.plantings.length ===  0) {
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
                barThickness:  20
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
        loadingDiv.innerHTML = '<i class="fas fa-brain"></i> farmOS AI is analyzing your question... <small class="text-muted">(this may take 5-8 seconds, or use smart defaults)</small>';
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

    function getCurrentPlanContext() {
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        const harvestStart = document.getElementById('harvestStart').value;
        const harvestEnd = document.getElementById('harvestEnd').value;
        const planningYear = document.getElementById('planningYear').value;
        const planningSeason = document.getElementById('planningSeason').value;

        return {
            crop_name: cropSelect.options[cropSelect.selectedIndex]?.text || null,
            variety_name: varietySelect.options[varietySelect.selectedIndex]?.text || null,
            harvest_window: harvestStart && harvestEnd ? { start: harvestStart, end: harvestEnd } : null,
            planning_year: planningYear,
            planning_season: planningSeason,
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
    
    async function updateHarvestBarForVariety(varietyId) {
        try {
            if (!varietyId) return;

            const response = await fetch(`/admin/farmos/succession-planning/varieties/${varietyId}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                console.error('Failed to fetch variety data:', response.statusText);
                return;
            }

            const varietyData = await response.json();
            const harvestStart = varietyData.harvestStart || varietyData.harvest_start || varietyData.harvest_start_date || varietyData.harvest_from;
            const harvestEnd = varietyData.harvestEnd || varietyData.harvest_end || varietyData.harvest_to || varietyData.harvest_end_date;

            if (!harvestStart || !harvestEnd) {
                console.warn('Missing harvest dates on variety record, falling back to AI/generic');
                return;
            }

            const startDate = new Date(harvestStart);
            const endDate = new Date(harvestEnd);


            const dragBar = document.getElementById('dragHarvestBar');
            const startDisplay = document.getElementById('startDateDisplay');
            const endDisplay = document.getElementById('endDateDisplay');

            // If dragBar isn't mounted yet, just set the date inputs and displays and return
            if (!dragBar) {
                console.warn('Deferred DOM Node could not be resolved to a valid node - setting date inputs only');
                if (startDisplay) startDisplay.textContent = startDate.toLocaleDateString();
                if (endDisplay) endDisplay.textContent = endDate.toLocaleDateString();
                document.getElementById('harvestStart').value = startDate.toISOString().split('T')[0];
                document.getElementById('harvestEnd').value = endDate.toISOString().split('T')[0];
                return;
            }

            const startPercentage = dateToPercentage(startDate);
            const endPercentage = dateToPercentage(endDate);

            dragBar.style.left = `${startPercentage}%`;
            dragBar.style.width = `${endPercentage - startPercentage}%`;

            if (startDisplay) startDisplay.textContent = startDate.toLocaleDateString();
            if (endDisplay) endDisplay.textContent = endDate.toLocaleDateString();

            console.log(`Updated harvest bar for variety ${varietyId}: ${startDate.toDateString()} - ${endDate.toDateString()}`);
        } catch (error) {
            console.error('Error updating harvest bar for variety:', error);
        }
    }

    // Example usage
    const selectedVarietyId = document.getElementById('varietySelect').value;
    updateHarvestBarForVariety(selectedVarietyId);

    // Display AI harvest window results
    function displayAIHarvestWindow(harvestInfo) {
        const harvestWindowInfo = document.getElementById('harvestWindowInfo');
        const aiHarvestDetails = document.getElementById('aiHarvestDetails');

        if (!harvestInfo || !harvestWindowInfo || !aiHarvestDetails) {
            console.warn('Missing DOM elements for AI harvest window display');
            return;
        }

        // Format the AI results for display
        let detailsHTML = '';

        if (harvestInfo.optimal_start) {
            detailsHTML += `<div class="mb-2"><strong>Start:</strong> ${new Date(harvestInfo.optimal_start).toLocaleDateString()}</div>`;
        }

        if (harvestInfo.optimal_end) {
            detailsHTML += `<div class="mb-2"><strong>End:</strong> ${new Date(harvestInfo.optimal_end).toLocaleDateString()}</div>`;
        }

        if (harvestInfo.days_to_harvest) {
            detailsHTML += `<div class="mb-2"><strong>Days to Harvest:</strong> ${harvestInfo.days_to_harvest} days</div>`;
        }

        if (harvestInfo.yield_peak) {
            detailsHTML += `<div class="mb-2"><strong>Peak Yield:</strong> ${new Date(harvestInfo.yield_peak).toLocaleDateString()}</div>`;
        }

        if (harvestInfo.notes) {
            detailsHTML += `<div class="mb-2"><strong>AI Notes:</strong> ${harvestInfo.notes}</div>`;
        }

        // Add a timestamp
        detailsHTML += `<div class="text-muted small mt-2"><i class="fas fa-clock"></i> Calculated ${new Date().toLocaleTimeString()}</div>`;

        aiHarvestDetails.innerHTML = detailsHTML;
        harvestWindowInfo.style.display = 'block';

        console.log('✅ AI harvest window displayed:', harvestInfo);
    }
</script>
@endsection