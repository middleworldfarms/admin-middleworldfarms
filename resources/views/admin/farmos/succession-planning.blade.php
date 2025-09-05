@extends('layouts.app')

@section('title', 'farmOS Succession Planner - Revolutionary Backward Planning')

@section('page-title', 'farmOS Succession Planner')

@section('page-header')
    <p class="lead">Revolutionary backward planning from harvest windows • Real farmOS taxonomy • AI-powered intelligence</p>
@endsection

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

    .drag-harvest-bar:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(40, 167, 69, 0.5);
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

    .succession-tabs {
        background: white;
        border-radius: 1rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        overflow: hidden;
        margin-top: 2rem;
    }

    .tab-navigation {
        background: #f8f9fa;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        overflow-x: auto;
    }

    .tab-button {
        background: none;
        border: none;
        padding: 1rem 1.5rem;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.2s ease;
        white-space: nowrap;
        font-weight: 500;
        color: #6c757d;
    }

    .tab-button:hover {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }

    .tab-button.active {
        background: white;
        color: #28a745;
        border-bottom-color: #28a745;
    }

    .tab-button.completed {
        color: #198754;
    }

    .tab-button.completed::after {
        content: ' ✓';
        font-weight: bold;
    }

    .tab-content {
        min-height: 600px;
        background: white;
    }

    .tab-pane {
        display: none;
        padding: 2rem;
    }

    .tab-pane.active {
        display: block;
    }

    .quick-form-container {
        background: #f8f9fa;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .quick-form-iframe {
        width: 100%;
        height: 500px;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        background: white;
    }

    .succession-info {
        background: linear-gradient(45deg, #e3f2fd, #f3e5f5);
        border: 2px dashed #0dcaf0;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-bottom: 1rem;
    }

    .succession-info h5 {
        color: #0d6efd;
        margin-bottom: 0.5rem;
    }

    .succession-info p {
        margin-bottom: 0.25rem;
        color: #495057;
    }

    .loading-indicator {
        padding: 2rem;
        text-align: center;
        color: #6c757d;
        background: #f8f9fa;
        border-radius: 0.5rem;
        margin-bottom: 1rem;
    }

    .loading-indicator i {
        margin-right: 0.5rem;
    }

    .quick-form-error {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        border-radius: 0.5rem;
        padding: 2rem;
        text-align: center;
        color: #721c24;
    }

    .iframe-loading {
        position: relative;
    }

    .iframe-loading::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255, 255, 255, 0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
        border-radius: 0.5rem;
    }

    .iframe-loading::after {
        content: 'Loading farmOS Quick Form...';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        color: #6c757d;
        font-weight: 500;
        z-index: 11;
    }

    .tab-button.overdue {
        background: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .tab-button.overdue.active {
        background: #dc3545;
        color: white;
    }

    /* Responsive design for mobile */
    @media (max-width: 768px) {
        .tab-navigation {
            flex-wrap: wrap;
        }

        .tab-button {
            flex: 1 1 auto;
            min-width: 120px;
            font-size: 0.9rem;
            padding: 0.75rem 1rem;
        }

        .quick-form-iframe {
            height: 400px;
        }

        .succession-info {
            padding: 0.75rem;
        }
    }
</style>
@endsection

@section('content')
<div class="succession-planner-container">
    <!-- Cache buster for development -->
    <script>console.log('🔄 Cache buster: 1756750327-FIXED-' + Date.now() + ' - SIMPLIFIED TIMELINE - Clean succession planner');</script>
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">

    <!-- Loading overlay -->
    <div class="loading-overlay d-none" id="loadingOverlay">
        <div class="text-center">
            <div class="spinner-grow text-success" role="status" style="width: 3rem; height: 3rem;">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-3 text-muted">Processing with Holistic AI...</p>
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
                            <select class="form-select" id="planningYear" name="planningYear">
                                <option value="2024" {{ date('Y') == '2024' ? 'selected' : '' }}>2024</option>
                                <option value="2025" {{ date('Y') == '2025' ? 'selected' : '' }}>2025</option>
                                <option value="2026" {{ date('Y') == '2026' ? 'selected' : '' }}>2026</option>
                                <option value="2027" {{ date('Y') == '2027' ? 'selected' : '' }}>2027</option>
                                <option value="2028" {{ date('Y') == '2028' ? 'selected' : '' }}>2028</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="planningSeason" class="form-label">Primary Season</label>
                            <select class="form-select" id="planningSeason" name="planningSeason">
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
                            <select class="form-select" id="cropSelect" name="cropSelect" required>
                                <option value="">Select a crop type...</option>
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
                            <select class="form-select" id="varietySelect" name="varietySelect">
                                <option value="">Select a variety...</option>
                                @if(isset($cropData['varieties']) && count($cropData['varieties']) > 0)
                                    @foreach($cropData['varieties'] as $variety)
                                        <option value="{{ $variety['id'] }}" data-crop="{{ $variety['parent_id'] ?? '' }}" data-name="{{ $variety['name'] }}">
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
                                <input type="date" class="form-control" id="harvestStart" name="harvestStart" required>
                            </div>
                            <div class="col-md-6">
                                <label for="harvestEnd" class="form-label">
                                    <i class="fas fa-stop text-danger"></i>
                                    Harvest End Date
                                </label>
                                <input type="date" class="form-control" id="harvestEnd" name="harvestEnd" required>
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
                            <select class="form-select" id="bedSelect" name="bedSelect[]" multiple>
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
                                <button class="btn btn-outline-primary btn-sm" onclick="selectAllBeds()" aria-label="Select all beds">All Beds</button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="clearBedSelection()" aria-label="Clear bed selection">Clear</button>
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
                        <button class="btn btn-success btn-lg" id="calculateButton" onclick="calculateSuccessionPlan()" aria-label="Calculate succession plan">
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

                <!-- Quick Forms Tabs - This replaces the old summary cards -->
                <div id="quickFormTabsContainer" class="succession-tabs" style="display: none;">
                    <div class="tab-navigation" id="tabNavigation">
                        <!-- Tab buttons will be populated here -->
                    </div>
                    <div class="tab-content" id="tabContent">
                        <!-- Tab panes will be populated here -->
                    </div>
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
                                <br><small class="text-muted">💡 The AI now has context about your current harvest window and succession plan</small>
                            </label>
                            <textarea class="form-control ai-chat-input" id="aiChatInput" name="aiChatInput" rows="3" 
                                placeholder="e.g., 'What's the best succession interval for lettuce in August?'"></textarea>
                        </div>
                        
                        <div class="d-flex gap-2 mb-3">
                            <button class="btn btn-warning" onclick="askHolisticAI()" aria-label="Ask AI">
                                <i class="fas fa-paper-plane"></i>
                                Ask AI
                            </button>
                            <button class="btn btn-outline-warning" onclick="getQuickAdvice()" aria-label="Get quick tips">
                                <i class="fas fa-lightbulb"></i>
                                Quick Tips
                            </button>
                            <button class="btn btn-outline-info" onclick="askAIAboutPlan()" id="analyzePlanBtn" style="display: none;" aria-label="Analyze current plan">
                                <i class="fas fa-chart-line"></i>
                                Analyze Plan
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
                                <button id="refreshAIStatus" class="btn btn-sm btn-outline-secondary" style="padding: 2px 8px;" aria-label="Refresh AI status">
                                    <i class="fas fa-sync-alt"></i>
                                </button>
                            </div>
                        </div>
                        
                        <div id="aiResponseArea">
                        <!-- Current Plan Context for AI -->
                        <div id="aiPlanContext" class="mt-3 p-3 bg-light rounded" style="display: none;">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-seedling"></i> Current Succession Plan Context
                            </h6>
                            <div id="planContextDetails" class="small text-muted"></div>
                            <button class="btn btn-sm btn-primary mt-2" onclick="askAIAboutPlan()">
                                <i class="fas fa-brain"></i> AI Analyze This Plan
                            </button>
                        </div>
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

<!-- Cache busting version: 1756750327-FIXED -->
<script>
    // Force cache busting - this script loaded at: ' + new Date().toISOString()
    console.log('🔄 Succession Planner Loading - Version: 1756750327-FIXED-' + Date.now());
    
    // Force cache busting by adding timestamp to all dynamic requests
    const CACHE_BUSTER = Date.now();

    // Global variables - with proper fallbacks and error handling
    let cropTypes = [];
    let cropVarieties = [];
    let availableBeds = [];

    // Safely parse JSON data with fallbacks
    try {
        cropTypes = @json($cropData['types'] ?? []);
    } catch (e) {
        console.warn('Failed to parse cropTypes JSON, using fallback:', e);
        cropTypes = [
            {id: 'lettuce', name: 'Lettuce', label: 'Lettuce'},
            {id: 'carrot', name: 'Carrot', label: 'Carrot'},
            {id: 'radish', name: 'Radish', label: 'Radish'},
            {id: 'spinach', name: 'Spinach', label: 'Spinach'},
            {id: 'kale', name: 'Kale', label: 'Kale'},
            {id: 'arugula', name: 'Arugula', label: 'Arugula'},
            {id: 'beets', name: 'Beets', label: 'Beets'}
        ];
    }

    try {
        cropVarieties = @json($cropData['varieties'] ?? []);
    } catch (e) {
        console.warn('Failed to parse cropVarieties JSON, using fallback:', e);
        cropVarieties = [
            {id: 'carrot_nantes', name: 'Nantes', parent_id: 'carrot', crop_type: 'carrot'},
            {id: 'carrot_chantenay', name: 'Chantenay', parent_id: 'carrot', crop_type: 'carrot'},
            {id: 'lettuce_buttercrunch', name: 'Buttercrunch', parent_id: 'lettuce', crop_type: 'lettuce'},
            {id: 'lettuce_romaine', name: 'Romaine', parent_id: 'lettuce', crop_type: 'lettuce'}
        ];
    }

    try {
        availableBeds = @json($availableBeds ?? []);
    } catch (e) {
        console.warn('Failed to parse availableBeds JSON, using fallback:', e);
        availableBeds = [];
    }

    // Global API base (always use same origin/protocol to avoid mixed-content)
    // Keep base clean; add cache buster per-request
    const API_BASE = window.location.origin + '/admin/farmos/succession-planning';
    const FARMOS_BASE = "{{ config('services.farmos.url', '') }}";

    let currentSuccessionPlan = null;
    let timelineChart = null;
    let isDragging = false;
    let dragHandle = null;
    let dragStartX = 0;
    let cropId = null; // Track selected crop ID for variety filtering
    // Shared controllers to cancel stale AI requests
    let __aiCalcController = null;
    let __aiChatController = null;
    // Store last AI harvest info for overlay rendering
    let __lastAIHarvestInfo = null;

    // Initialize the application with real farmOS data
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Succession Planner Loading...');

    // Initialize app which will also initialize the harvest bar once
    initializeApp();
        // Removed duplicate initializeHarvestBar() call to avoid race conditions
        setupSeasonYearHandlers();
        
        // Debug: Log initial data
        console.log('🔍 Initial cropTypes:', cropTypes);
        console.log('🔍 Initial cropVarieties:', cropVarieties);

    // Ensure we have a favicon to avoid 404s on /favicon.ico (use site icon)
        (function ensureFavicon(){
            try {
                if (!document.querySelector('link[rel="icon"]')) {
                    const link = document.createElement('link');
            link.rel = 'icon';
            link.type = 'image/x-icon';
            link.href = '/middle_world_icon.ico';
            document.head.appendChild(link);
            // Add shortcut icon for broader compatibility
            const link2 = document.createElement('link');
            link2.rel = 'shortcut icon';
            link2.type = 'image/x-icon';
            link2.href = '/middle_world_icon.ico';
            document.head.appendChild(link2);
                }
            } catch (e) {
                console.warn('Favicon inject skipped:', e);
            }
        })();
    });

    async function initializeApp() {
        console.log('🌱 Initializing farmOS Succession Planner with real data...');

        // Test connections
        await testConnections();

    // Restore any saved state before initializing UI
    restorePlannerState();

    // Show the harvest bar immediately with default or restored dates
    initializeHarvestBar();

        // Set up AI status monitoring
        setupAIStatusMonitoring();

        // Initialize variety select to show "Select crop first..." if no crop is selected
        const cropSelectEl = document.getElementById('cropSelect');
        const varietySelectEl = document.getElementById('varietySelect');
        
        if (varietySelectEl && (!cropSelectEl || !cropSelectEl.value)) {
            varietySelectEl.innerHTML = '<option value="">Select crop first...</option>';
        }

        // Set up crop change listeners
        document.getElementById('cropSelect').addEventListener('change', function() {
            // Update the global cropId variable for variety filtering
            cropId = this.value;
            updateVarieties();
            savePlannerState();
            // Don't trigger AI on crop selection - only filter varieties
        });
        
        document.getElementById('varietySelect').addEventListener('change', function() {
            console.log('🔄 Variety selected:', this.value, this.options[this.selectedIndex]?.text);
            calculateAIHarvestWindow();
            savePlannerState();
        });

        // Persist season/year and harvest date inputs
        const yearEl = document.getElementById('planningYear');
        const seasonEl = document.getElementById('planningSeason');
        const hsEl = document.getElementById('harvestStart');
        const heEl = document.getElementById('harvestEnd');
        yearEl?.addEventListener('change', savePlannerState);
        seasonEl?.addEventListener('change', savePlannerState);
        hsEl?.addEventListener('change', savePlannerState);
        heEl?.addEventListener('change', savePlannerState);

        // Chat UX: Enter to send
        const chatInput = document.getElementById('aiChatInput');
        if (chatInput && !chatInput.__enterBound) {
            chatInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    askHolisticAI();
                }
            });
            chatInput.__enterBound = true;
        }
        // A11y: announce AI updates politely
        const aiDetails = document.getElementById('aiHarvestDetails');
        aiDetails?.setAttribute('aria-live', 'polite');
    }

    // Filter the variety dropdown by selected crop
    function updateVarieties() {
        const cropSelectEl = document.getElementById('cropSelect');
        const varietySelectEl = document.getElementById('varietySelect');
        if (!varietySelectEl) return;

        const selectedCropId = cropSelectEl?.value || null;
        const options = [];
        if (!selectedCropId) {
            options.push('<option value="">Select crop first...</option>');
        } else {
            // cropVarieties comes from server JSON; match by crop_id or parent_id / crop_type
            const filtered = (cropVarieties || []).filter(v =>
                v.crop_id === selectedCropId || v.parent_id === selectedCropId || v.crop_type === selectedCropId
            );
            if (filtered.length === 0) {
                options.push('<option value="">No varieties found for this crop</option>');
            } else {
                options.push('<option value="">Select a variety (optional)</option>');
                for (const v of filtered) {
                    const id = v.id || v.uuid || v.variety_id;
                    const name = v.name || v.title || 'Variety';
                    options.push(`<option value="${id}" data-crop="${selectedCropId}" data-name="${name}">${name}</option>`);
                }
            }
        }
        const prev = varietySelectEl.value;
        varietySelectEl.innerHTML = options.join('');
        if (prev) {
            const opt = Array.from(varietySelectEl.options).find(o => o.value === prev);
            if (opt) varietySelectEl.value = prev;
        }
    }

    // Quick select helpers for beds
    function selectAllBeds() {
        const sel = document.getElementById('bedSelect');
        if (!sel) return;
        for (const opt of sel.options) opt.selected = true;
        sel.dispatchEvent(new Event('change'));
    }

    function clearBedSelection() {
        const sel = document.getElementById('bedSelect');
        if (!sel) return;
        for (const opt of sel.options) opt.selected = false;
        sel.dispatchEvent(new Event('change'));
    }

    // helper to call AI but avoid blocking UI if route missing
    async function awaitMaybeCalculateAI(varietyId) {
        try {
            await calculateAIHarvestWindow();
        } catch (e) {
            console.warn('AI calculation skipped or failed:', e);
        }
    }

    function setupDragFunctionality() {
        const timeline = document.getElementById('harvestTimeline');
        if (!timeline) {
            console.error('❌ Cannot find harvestTimeline element for drag setup');
            return;
        }
        
        console.log('✅ Setting up drag functionality on timeline:', timeline);
        
        // Cache rect to reduce forced reflow
        let timelineRect = null;
        const computeRect = () => { timelineRect = timeline.getBoundingClientRect(); };
        computeRect();
        // Use ResizeObserver when available to avoid global resize/layout thrash
        if (window.ResizeObserver) {
            const ro = new ResizeObserver(() => computeRect());
            ro.observe(timeline);
        } else {
            window.addEventListener('resize', computeRect);
        }

        // rAF throttle for mousemove
        let pending = false;
        let lastEvent = null;
        const onMouseMove = (e) => {
            lastEvent = e;
            if (pending) return;
            pending = true;
            requestAnimationFrame(() => {
                pending = false;
                if (!lastEvent) return;
                handleMouseMove(lastEvent, timelineRect);
            });
        };

        // Handle mouse events for drag handles
        timeline.addEventListener('mousedown', handleMouseDown, { passive: false });
        document.addEventListener('mousemove', onMouseMove, { passive: false });
        document.addEventListener('mouseup', handleMouseUp, { passive: false });
        
        console.log('✅ Drag event listeners attached');
    }

    function initializeHarvestBar() {
        // Set default dates and initialize the harvest bar
        setDefaultDates();
        setupDragFunctionality();
        // Ensure AI max window overlay exists under the bar
        const timeline = document.getElementById('harvestTimeline');
        if (timeline && !document.getElementById('aiMaxWindowBand')) {
            const band = document.createElement('div');
            band.id = 'aiMaxWindowBand';
            band.className = 'position-absolute';
            band.style.top = '38px';
            band.style.height = '26px';
            band.style.left = '0%';
            band.style.width = '0%';
            band.style.borderRadius = '6px';
            band.style.background = 'rgba(33, 150, 243, 0.15)';
            band.style.border = '1px dashed rgba(33, 150, 243, 0.4)';
            band.style.pointerEvents = 'none';
            timeline.appendChild(band);
        }
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

    function handleMouseMove(e, cachedRect = null) {
        if (!isDragging || !dragHandle) return;
        const timeline = document.getElementById('harvestTimeline');
        const rect = cachedRect || timeline.getBoundingClientRect();
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
        
        const resultDate = new Date(yearStart);
        resultDate.setDate(yearStart.getDate() + Math.round(dayOfYear));
        
        return resultDate;
    }

    function dateToPercentage(date) {
        const planningYear = document.getElementById('planningYear').value || date.getFullYear();
        const yearStart = new Date(planningYear, 0, 1); // January 1st of planning year
        const yearEnd = new Date(planningYear, 11, 31); // December 31st of planning year
        
        // Handle dates that extend into the next year (for extended harvest windows)
        let adjustedDate = date;
        if (date.getFullYear() > planningYear) {
            // If date is in next year, treat it as December 31st of current year for percentage calculation
            adjustedDate = new Date(planningYear, 11, 31);
        } else if (date.getFullYear() < planningYear) {
            // If date is in previous year, treat it as January 1st of current year
            adjustedDate = new Date(planningYear, 0, 1);
        }
        
        const totalDays = (yearEnd - yearStart) / (1000 * 60 * 60 * 24);
        const dayOfYear = (adjustedDate - yearStart) / (1000 * 60 * 60 * 24);
        
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
            requestAnimationFrame(() => {
                dragBar.style.left = startPercentage + '%';
                dragBar.style.width = width + '%';
                dragBar.style.display = 'block';
                updateDateDisplays();
                updateDateInputsFromBar();
            });
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

    // Extend harvest window by maximum 20%
    function extendHarvestWindow() {
        const startInput = document.getElementById('harvestStart');
        const endInput = document.getElementById('harvestEnd');
        
        if (!startInput || !endInput || !startInput.value || !endInput.value) {
            console.warn('Cannot extend harvest window: missing date inputs');
            return;
        }
        
        const startDate = new Date(startInput.value);
        const endDate = new Date(endInput.value);
        const currentDuration = Math.floor((endDate - startDate) / (1000 * 60 * 60 * 24));
        const extensionDays = Math.min(Math.floor(currentDuration * 0.2), 30); // Max 20% or 30 days
        
        // Extend the end date
        const newEndDate = new Date(endDate);
        newEndDate.setDate(newEndDate.getDate() + extensionDays);
        
        endInput.value = newEndDate.toISOString().split('T')[0];
        updateDragBar();
        
        // Update display
        const aiHarvestDetails = document.getElementById('aiHarvestDetails');
        if (aiHarvestDetails) {
            let existingHTML = aiHarvestDetails.innerHTML;
            existingHTML = existingHTML.replace(
                /<div class="text-muted small mt-2">[\s\S]*?<\/div>/,
                `<div class="alert alert-warning small mt-2">
                    <i class="fas fa-exclamation-triangle"></i> Extended by ${extensionDays} days (increased risk of quality loss)
                </div>
                <div class="text-muted small mt-2">
                    <i class="fas fa-clock"></i> Extended ${new Date().toLocaleTimeString()}
                </div>`
            );
            aiHarvestDetails.innerHTML = existingHTML;
        }
        
        console.log(`📈 Extended harvest window by ${extensionDays} days`);
    }

    // Reduce harvest window to minimum 1 week
    function reduceHarvestWindow() {
        const startInput = document.getElementById('harvestStart');
        const endInput = document.getElementById('harvestEnd');
        
        if (!startInput || !endInput || !startInput.value) {
            console.warn('Cannot reduce harvest window: missing date inputs');
            return;
        }
        
        const startDate = new Date(startInput.value);
        const minEndDate = new Date(startDate);
        minEndDate.setDate(minEndDate.getDate() + 7); // Minimum 1 week
        
        endInput.value = minEndDate.toISOString().split('T')[0];
        updateDragBar();
        
        // Update display
        const aiHarvestDetails = document.getElementById('aiHarvestDetails');
        if (aiHarvestDetails) {
            let existingHTML = aiHarvestDetails.innerHTML;
            existingHTML = existingHTML.replace(
                /<div class="text-muted small mt-2">[\s\S]*?<\/div>/,
                `<div class="alert alert-info small mt-2">
                    <i class="fas fa-info-circle"></i> Reduced to minimum 1-week harvest window
                </div>
                <div class="text-muted small mt-2">
                    <i class="fas fa-clock"></i> Reduced ${new Date().toLocaleTimeString()}
                </div>`
            );
            aiHarvestDetails.innerHTML = existingHTML;
        }
        
        console.log('📉 Reduced harvest window to 1 week');
    }

    // Reset harvest window to AI-calculated maximum
    function resetHarvestWindow() {
        // Re-run the AI calculation to get the maximum window
        calculateAIHarvestWindow();
        console.log('🔄 Reset harvest window to AI maximum');
    }

    // Calculate AI harvest window - main function for getting maximum possible harvest
    async function calculateAIHarvestWindow() {
        try {
            console.log('🤖 calculateAIHarvestWindow() called');

            // Abort previous in-flight calculation before starting a new one
            if (__aiCalcController) {
                try { __aiCalcController.abort(); } catch (_) {}
            }
            __aiCalcController = new AbortController();

            const cropSelect = document.getElementById('cropSelect');
            const varietySelect = document.getElementById('varietySelect');

            if (!cropSelect || !cropSelect.value) {
                console.log('❌ No crop selected, aborting AI calculation');
                return;
            }

            const cropName = cropSelect.options[cropSelect.selectedIndex].text;
            const varietyName = varietySelect && varietySelect.value ? varietySelect.options[varietySelect.selectedIndex].text : null;
            const varietyId = varietySelect && varietySelect.value ? varietySelect.value : null;

            // Try to fetch full variety metadata from farmOS to pass to AI
            let varietyMeta = null;
            if (varietyId) {
                try {
                    const metaResp = await fetch(`${API_BASE}/varieties/${varietyId}?_cb=${CACHE_BUSTER}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, signal: __aiCalcController.signal });
                    if (metaResp.ok) varietyMeta = await metaResp.json();
                } catch (e) {
                    console.warn('Could not fetch variety metadata for AI context:', e);
                }
            }

            const contextPayload = {
                crop: cropName,
                variety: varietyName || null,
                variety_meta: varietyMeta,
                planning_year: document.getElementById('planningYear')?.value || new Date().getFullYear(),
                planning_season: document.getElementById('planningSeason')?.value || null,
                planted_on: new Date().toISOString().split('T')[0]
            };

            // Build a strict prompt requesting JSON output to make parsing deterministic
            const prompt = `You are an expert agronomist specializing in extended harvest techniques. Given the context JSON, return ONLY a JSON object with the exact keys: maximum_start (YYYY-MM-DD), maximum_end (YYYY-MM-DD), days_to_harvest (number), yield_peak (YYYY-MM-DD), notes (string), and extended_window (object with max_extension_days and risk_level). 

For crops like beets, carrots, and other root vegetables, consider the FULL POSSIBLE harvest period:
- Early harvesting (May-June) for baby/small sizes
- Main harvest (June-November) for mature sizes  
- Extended harvest (November-December) with protection techniques
- Total possible window: May to December (up to 221 days for beets)

Calculate the ABSOLUTE MAXIMUM possible harvest window for this crop variety, considering all harvesting techniques, succession planting, and storage methods. Do NOT include extra text.`;

            // Use same-origin Laravel route that exists in this app (chat endpoint)
            const chatUrl = window.location.origin + '/admin/farmos/succession-planning/chat';

            // Debug: log payload and endpoint
            console.log('🛰️ AI request ->', { chatUrl, prompt, context: contextPayload });

            // Timeout to abort long-running requests
            const timeoutId = setTimeout(() => { try { __aiCalcController.abort(); } catch(_){} }, 10000); // 10s timeout

            const response = await fetch(chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ question: prompt, context: contextPayload }),
                signal: __aiCalcController.signal
            });

            clearTimeout(timeoutId);

            console.log('🛰️ AI response status:', response.status);

            if (!response.ok) {
                // Try to read body for debugging
                let text = '';
                try { text = await response.text(); } catch (e) { text = `<failed to read body: ${e}>`; }
                console.error('Failed to fetch AI response:', response.status, response.statusText, text);
                return;
            }

            const data = await response.json();
            console.log('🛰️ AI raw response:', data);

            // Prefer structured harvest window from backend; else use AI answer parsing
            let harvestInfo = null;
            if (data && (data.maximum_start || data.optimal_window_days || data.peak_harvest_days)) {
                // Build a normalized harvestInfo object from structured backend response
                const maxStart = data.maximum_start || null;
                const duration = data.optimal_window_days || data.maximum_harvest_days || null;
                const peakDays = data.peak_harvest_days || null;
                let maxEnd = data.maximum_end || null;
                if (!maxEnd && maxStart && duration) {
                    const d = new Date(maxStart);
                    d.setDate(d.getDate() + Number(duration));
                    maxEnd = d.toISOString().split('T')[0];
                }
                harvestInfo = {
                    maximum_start: maxStart,
                    maximum_end: maxEnd,
                    days_to_harvest: peakDays, // display "Days to Harvest" as peak days to first harvest
                    extended_window: {
                        max_extension_days: Math.round((duration || peakDays || 0) * 0.2) || 14,
                        risk_level: 'moderate'
                    },
                    notes: Array.isArray(data.recommendations) ? data.recommendations.join('; ') : ''
                };
            } else {
                // Legacy path: parse AI free text answer
                try {
                    if (typeof data.answer === 'string') {
                        harvestInfo = JSON.parse(data.answer);
                        console.log('✅ Successfully parsed JSON from backend:', harvestInfo);
                    } else if (typeof data.answer === 'object') {
                        harvestInfo = data.answer;
                    } else {
                        throw new Error('Unexpected answer format');
                    }
                } catch (e) {
                    console.warn('Failed to parse JSON from backend, falling back to text parsing:', e);
                    const aiText = String(data.answer || data.wisdom || 'No response');
                    harvestInfo = parseHarvestWindow(aiText, cropName, varietyName);
                }
            }

            if (!harvestInfo || !harvestInfo.maximum_start || !harvestInfo.maximum_end) {
                console.warn('AI returned incomplete harvest info, using fallback');
                harvestInfo = parseHarvestWindow(String(data.answer || data.wisdom || ''), cropName, varietyName);
            }

            displayAIHarvestWindow(harvestInfo, cropName, varietyName);

            // Auto-set the harvest window inputs and drag bar to MAXIMUM possible
            if (harvestInfo.maximum_start) {
                document.getElementById('harvestStart').value = harvestInfo.maximum_start;
                console.log('📅 Set harvest start to maximum:', harvestInfo.maximum_start);
            }
            if (harvestInfo.maximum_end) {
                document.getElementById('harvestEnd').value = harvestInfo.maximum_end;
                console.log('📅 Set harvest end to maximum:', harvestInfo.maximum_end);
            }
            
            // Force update the drag bar and timeline
            setTimeout(() => {
                updateDragBar();
                updateTimelineMonths(document.getElementById('planningYear').value || new Date().getFullYear());
                console.log('🔄 Drag bar and timeline updated for maximum harvest window');
            }, 100);

            console.log('✅ AI response processed successfully', harvestInfo);
        } catch (error) {
            if (error.name === 'AbortError') {
                console.error('AI request timed out');
            } else {
                console.error('Error calculating AI harvest window:', error);
            }
        }
    }

    // Display AI harvest window information in the UI
    function displayAIHarvestWindow(harvestInfo, cropName, varietyName) {
        console.log('🎨 Displaying AI harvest window:', harvestInfo);
        
        const harvestWindowInfo = document.getElementById('harvestWindowInfo');
        const aiHarvestDetails = document.getElementById('aiHarvestDetails');
        
        if (!harvestWindowInfo || !aiHarvestDetails) {
            console.warn('AI harvest window display elements not found');
            return;
        }
        
        // Show the harvest window info section
        harvestWindowInfo.style.display = 'block';
        
        // Build the details HTML
        let detailsHTML = '';
        
        if (harvestInfo.maximum_start && harvestInfo.maximum_end) {
            const startDate = new Date(harvestInfo.maximum_start);
            const endDate = new Date(harvestInfo.maximum_end);
            const durationDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
            
            detailsHTML += `<div class="mb-2">
                <strong>Maximum Possible Harvest:</strong> ${startDate.toLocaleDateString()} - ${endDate.toLocaleDateString()}
                <br><small class="text-muted">Duration: ${durationDays} days</small>
            </div>`;
        }
        
        if (harvestInfo.days_to_harvest) {
            detailsHTML += `<div class="mb-2">
                <strong>Days to Harvest:</strong> ${harvestInfo.days_to_harvest} days
            </div>`;
        }
        
        if (harvestInfo.yield_peak) {
            const peakDate = new Date(harvestInfo.yield_peak);
            detailsHTML += `<div class="mb-2">
                <strong>Peak Yield:</strong> ${peakDate.toLocaleDateString()}
            </div>`;
        }
        
        if (harvestInfo.extended_window) {
            const extensionDays = harvestInfo.extended_window.max_extension_days || Math.floor((harvestInfo.days_to_harvest || 60) * 0.2);
            const riskLevel = harvestInfo.extended_window.risk_level || 'moderate';
            
            detailsHTML += `<div class="mb-2">
                <strong>Extension Options:</strong> Up to ${extensionDays} days (${riskLevel} risk)
            </div>`;
        }
        
        if (harvestInfo.notes) {
            detailsHTML += `<div class="mb-2">
                <strong>Notes:</strong> ${harvestInfo.notes}
            </div>`;
        }
        
        // Add harvest window controls
        detailsHTML += `<div class="mt-3">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-success" onclick="extendHarvestWindow()">
                    <i class="fas fa-plus"></i> Extend 20%
                </button>
                <button type="button" class="btn btn-outline-warning" onclick="reduceHarvestWindow()">
                    <i class="fas fa-minus"></i> Reduce to 1 Week
                </button>
                <button type="button" class="btn btn-outline-info" onclick="resetHarvestWindow()">
                    <i class="fas fa-undo"></i> Reset to Max
                </button>
            </div>
        </div>`;
        
        // Add a timestamp
        detailsHTML += `<div class="text-muted small mt-2">
            <i class="fas fa-clock"></i> Calculated ${new Date().toLocaleTimeString()}
        </div>`;
        
        aiHarvestDetails.innerHTML = detailsHTML;

        // Render AI max window overlay band behind the drag bar
        try {
            const band = document.getElementById('aiMaxWindowBand');
            if (band && harvestInfo.maximum_start && harvestInfo.maximum_end) {
                const sPct = dateToPercentage(new Date(harvestInfo.maximum_start));
                const ePct = dateToPercentage(new Date(harvestInfo.maximum_end));
                band.style.left = Math.max(0, Math.min(100, sPct)) + '%';
                band.style.width = Math.max(0, Math.min(100, ePct - sPct)) + '%';
                band.style.display = 'block';
            }
        } catch (_) {}
        
        // Update AI chat context with harvest window information
        updateAIChatContext(harvestInfo, cropName, varietyName);
        
        // Show the analyze plan button
        const analyzeBtn = document.getElementById('analyzePlanBtn');
        if (analyzeBtn) {
            analyzeBtn.style.display = 'inline-block';
        }
        
        console.log('✅ AI harvest window displayed successfully');
    }

    // Fallback parser: try to extract YYYY-MM-DD dates and numbers from a free-text answer
    function parseHarvestWindow(answerText, cropName, varietyName) {
        try {
            if (!answerText || typeof answerText !== 'string') return null;

            const text = answerText.replace(/\s+/g, ' ').trim();
            const dateRegex = /(20\d{2})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])/g; // YYYY-MM-DD
            const dates = [...text.matchAll(dateRegex)].map(m => m[0]);

            // Heuristics: pick first as start, last as end when at least 2 dates present
            let maximum_start = null;
            let maximum_end = null;
            if (dates.length >= 2) {
                maximum_start = dates[0];
                maximum_end = dates[dates.length - 1];
            }

            // Month name range detection (e.g., "June–November", "May to December")
            if (!maximum_start || !maximum_end) {
                const months = {
                    january: 0, february: 1, march: 2, april: 3, may: 4, june: 5,
                    july: 6, august: 7, september: 8, october: 9, november: 10, december: 11,
                    jan: 0, feb: 1, mar: 2, apr: 3, jun: 5, jul: 6, aug: 7, sep: 8, sept: 8, oct: 9, nov: 10, dec: 11
                };
                const monthPattern = /(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)/i;
                const rangePattern = new RegExp(`${monthPattern.source}\s*(?:-|–|—|to|through)\s*${monthPattern.source}`, 'i');
                const m = text.match(rangePattern);
                if (m && m[1] && m[2]) {
                    const year = parseInt(document.getElementById('planningYear')?.value || new Date().getFullYear(), 10);
                    const startMonth = months[m[1].toLowerCase()];
                    const endMonth = months[m[2].toLowerCase()];
                    if (startMonth != null && endMonth != null) {
                        const start = new Date(year, startMonth, 1);
                        const end = new Date(year, endMonth + 1, 0); // last day of end month
                        maximum_start = maximum_start || start.toISOString().split('T')[0];
                        maximum_end = maximum_end || end.toISOString().split('T')[0];
                    }
                }
            }

            // Handle phrases like "early/mid/late Month"
            if (!maximum_start || !maximum_end) {
                const emlPattern = /(early|mid|late)\s+(jan(?:uary)?|feb(?:ruary)?|mar(?:ch)?|apr(?:il)?|may|jun(?:e)?|jul(?:y)?|aug(?:ust)?|sep(?:t(?:ember)?)?|oct(?:ober)?|nov(?:ember)?|dec(?:ember)?)/ig;
                const monthsIdx = { jan:0,january:0,feb:1,february:1,mar:2,march:2,apr:3,april:3,may:4,jun:5,june:5,jul:6,july:6,aug:7,august:7,sep:8,sept:8,september:8,oct:9,october:9,nov:10,november:10,dec:11,december:11 };
                let match;
                const hits = [];
                while ((match = emlPattern.exec(text.toLowerCase())) !== null) {
                    const when = match[1];
                    const monKey = match[2];
                    const mIdx = monthsIdx[monKey] ?? monthsIdx[monKey.slice(0,3)];
                    if (mIdx != null) {
                        const year = parseInt(document.getElementById('planningYear')?.value || new Date().getFullYear(), 10);
                        let day = 15;
                        if (when === 'early') day = 5; else if (when === 'mid') day = 15; else if (when === 'late') day = 25;
                        const d = new Date(year, mIdx, day);
                        hits.push(d);
                    }
                }

                if (hits.length > 0) {
                    const sortedHits = hits.sort((a, b) => a - b);
                    maximum_start = maximum_start || sortedHits[0].toISOString().split('T')[0];
                    maximum_end = maximum_end || sortedHits[sortedHits.length - 1].toISOString().split('T')[0];
                }
            }

            // Extract numbers that might be days to harvest
            let dth = null;
            const numberRegex = /(\d{1,3})/g;
            const numbers = [...text.matchAll(numberRegex)].map(m => parseInt(m[1]));
            if (numbers.length > 0) {
                // Filter reasonable harvest days (30-300)
                const validNumbers = numbers.filter(n => n >= 30 && n <= 300);
                if (validNumbers.length > 0) {
                    dth = Math.min(...validNumbers); // Take the smallest reasonable number
                }
            }

            const yield_peak = maximum_start; // Assume peak is at start for simplicity
            const notes = text.length > 100 ? text.substring(0, 100) + '...' : text;

            const result = {
                maximum_start: maximum_start,
                maximum_end: maximum_end,
                days_to_harvest: dth || 60,
                yield_peak: yield_peak,
                notes: notes,
                extended_window: { max_extension_days: 30, risk_level: 'moderate' },
                crop: cropName || null,
                variety: varietyName || null
            };

            // Ensure at least something usable
            if (!result.maximum_start || !result.maximum_end) {
                // Provide a conservative synthetic window around the selected season defaults
                const year = parseInt(document.getElementById('planningYear')?.value || new Date().getFullYear(), 10);
                const start = new Date(year, 7, 1); // Aug 1
                const end = new Date(year, 10, 30); // Nov 30
                result.maximum_start = start.toISOString().split('T')[0];
                result.maximum_end = end.toISOString().split('T')[0];
                if (!result.days_to_harvest) result.days_to_harvest = 60;
            }

            return result;
        } catch (e) {
            console.warn('parseHarvestWindow failed:', e);
            return null;
        }
    }

    // Persist/restore state
    function savePlannerState() {
        try {
            const state = {
                // Don't save crop and variety selections - use placeholders instead
                // crop: document.getElementById('cropSelect')?.value || '',
                // variety: document.getElementById('varietySelect')?.value || '',
                year: document.getElementById('planningYear')?.value || '',
                season: document.getElementById('planningSeason')?.value || '',
                hStart: document.getElementById('harvestStart')?.value || '',
                hEnd: document.getElementById('harvestEnd')?.value || ''
            };
            localStorage.setItem('sp_state', JSON.stringify(state));
        } catch (_) {}
    }

    function restorePlannerState() {
        try {
            const raw = localStorage.getItem('sp_state');
            if (!raw) return;
            const s = JSON.parse(raw);
            if (s.year) document.getElementById('planningYear').value = s.year;
            if (s.season) document.getElementById('planningSeason').value = s.season;
            // Don't restore crop and variety selections - use placeholders instead
            // if (s.crop) {
            //     document.getElementById('cropSelect').value = s.crop;
            //     updateVarieties();
            //     if (s.variety) {
            //         const vSel = document.getElementById('varietySelect');
            //         const opt = Array.from(vSel.options).find(o => o.value === s.variety);
            //         if (opt) vSel.value = s.variety;
            //     }
            // }
            if (s.hStart) document.getElementById('harvestStart').value = s.hStart;
            if (s.hEnd) document.getElementById('harvestEnd').value = s.hEnd;
        } catch (_) {}
    }
    
    // Update AI chat context with current plan information
    function updateAIChatContext(harvestInfo, cropName, varietyName) {
        const contextDiv = document.getElementById('aiPlanContext');
        const detailsDiv = document.getElementById('planContextDetails');
        
        if (!contextDiv || !detailsDiv) return;
        
        let contextHTML = '<div class="mb-2">' +
            '<strong>Crop:</strong> ' + (cropName || 'Unknown') + '<br>' +
            '<strong>Variety:</strong> ' + (varietyName || 'Generic') +
        '</div>';
        
        if (harvestInfo.maximum_start && harvestInfo.maximum_end) {
            const startDate = new Date(harvestInfo.maximum_start);
            const endDate = new Date(harvestInfo.maximum_end);
            const durationDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));

            contextHTML += '<div class="mb-2">' +
                '<strong>Harvest Window:</strong> ' + startDate.toLocaleDateString() + ' - ' + endDate.toLocaleDateString() + '<br>' +
                '<small>Duration: ' + durationDays + ' days</small>' +
            '</div>';
        }

        if (harvestInfo.notes) {
            contextHTML += '<div class="mb-2">' +
                '<strong>AI Notes:</strong> <em>' + harvestInfo.notes + '</em>' +
            '</div>';
        }
        
        detailsDiv.innerHTML = contextHTML;
        contextDiv.style.display = 'block';
        
        console.log('📝 AI chat context updated with harvest window information');
    }
    
    // Test function to verify AI context integration
    window.testAIContext = function() {
        const context = getCurrentPlanContext();
        console.log('🧪 AI Context Test:', context);
        return context;
    };
    
    // Utility functions for notifications
    function showError(message) {
        console.error('❌ Error:', message);
        alert('Error: ' + message);
    }
    
    function showSuccess(message) {
        console.log('✅ Success:', message);
        alert('Success: ' + message);
    }
    
    function showLoading(show) {
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            if (show) {
                loadingOverlay.classList.remove('d-none');
            } else {
                loadingOverlay.classList.add('d-none');
            }
        }
    }
    
    function showToast(message, type = 'info') {
        console.log(`🍞 Toast (${type}):`, message);
        // For now, just use alert. You could enhance this with a proper toast system
        alert(`${type.toUpperCase()}: ${message}`);
    }

    // ----- Missing helpers (lightweight, safe fallbacks) -----
    // Quick connectivity check placeholder (non-blocking)
    async function testConnections() {
        try {
            updateAIStatus('checking', 'Verifying service…');
            // Optional: shallow ping to same-origin to avoid CORS; skip network to stay fast
            await new Promise(r => setTimeout(r, 100));
            updateAIStatus('online', 'AI ready');
        } catch (e) {
            console.warn('Connectivity check failed:', e);
            updateAIStatus('offline', 'Service unavailable');
        }
    }

    function setupAIStatusMonitoring() {
        // Initial status
        updateAIStatus('checking', 'Checking…');
        // Wire refresh button
        const btn = document.getElementById('refreshAIStatus');
        if (btn) btn.addEventListener('click', () => testConnections());
        // One initial check
        testConnections();
    }

    function updateAIStatus(status, details = '') {
        const light = document.getElementById('aiStatusLight');
        const text = document.getElementById('aiStatusText');
        const extra = document.getElementById('aiStatusDetails');
        if (light) {
            light.classList.remove('online', 'offline', 'checking');
            light.classList.add(status);
        }
        if (text) {
            text.textContent = status === 'online' ? 'AI Connected' : status === 'offline' ? 'AI Offline' : 'Checking AI service…';
        }
        if (extra) {
            extra.textContent = details || '';
        }
    }

    function getCurrentPlanContext() {
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        const harvestStart = document.getElementById('harvestStart');
        const harvestEnd = document.getElementById('harvestEnd');
        return {
            crop: cropSelect?.value || null,
            crop_name: cropSelect?.options[cropSelect.selectedIndex]?.text || null,
            variety: varietySelect?.value || null,
            variety_name: varietySelect?.options[varietySelect.selectedIndex]?.text || null,
            harvest_start: harvestStart?.value || null,
            harvest_end: harvestEnd?.value || null,
            plan: currentSuccessionPlan || null
        };
    }

    function addToastStyles() {
        // Stub: using alert-based toasts; no-op to avoid ReferenceError
        return;
    }

    function addKeyboardNavigation() {
        // Lightweight: number keys 1-9 switch tabs if present
        document.addEventListener('keydown', (e) => {
            if (e.altKey || e.ctrlKey || e.metaKey) return;
            const n = parseInt(e.key, 10);
            if (!isNaN(n) && n >= 1 && n <= 9) {
                const btns = document.querySelectorAll('.tab-button');
                const target = btns[n - 1];
                if (target) target.click?.();
            }
        });
    }

    function askQuickQuestion(type) {
        const input = document.getElementById('aiChatInput');
        if (!input) return;
        const context = getCurrentPlanContext();
        const cropName = context.crop_name || 'my crop';
        const topics = {
            'succession-timing': `What is the optimal succession timing for ${cropName}?`,
            'companion-plants': `What are good companion plants for ${cropName}?`,
            'lunar-timing': `Any lunar cycle timing tips for ${cropName}?`,
            'harvest-optimization': `How can I optimize the harvest window for ${cropName}?`
        };
        input.value = topics[type] || `Give me quick succession tips for ${cropName}`;
        askHolisticAI();
    }

    // Bed selection helpers for accessibility and UX
    function selectAllBeds() {
        const bedSelect = document.getElementById('bedSelect');
        if (!bedSelect) return;
        Array.from(bedSelect.options).forEach(opt => opt.selected = true);
        bedSelect.dispatchEvent(new Event('change'));
    }

    function clearBedSelection() {
        const bedSelect = document.getElementById('bedSelect');
        if (!bedSelect) return;
        Array.from(bedSelect.options).forEach(opt => opt.selected = false);
        bedSelect.dispatchEvent(new Event('change'));
    }

    // AI request state to prevent duplicate rapid sends
    let __aiInFlight = false;
    let __aiLastMsg = '';
    let __aiLastSentAt = 0;

    // Send message to AI chat (throttled + in-flight guard)
    async function askHolisticAI() {
        const chatInput = document.getElementById('aiChatInput');
        const message = chatInput.value.trim();
        
        if (!message) {
            console.warn('No message to send to AI');
            return;
        }

        const now = Date.now();
        if (__aiInFlight) {
            console.warn('AI request already in progress; skipped');
            return;
        }
        if (message === __aiLastMsg && (now - __aiLastSentAt) < 1500) {
            console.warn('Duplicate AI message throttled');
            return;
        }
        __aiInFlight = true;
        __aiLastMsg = message;
        __aiLastSentAt = now;
        
        console.log('🤖 Sending message to AI:', message);
        
        // Show loading state
        const loadingOverlay = document.getElementById('loadingOverlay');
        if (loadingOverlay) {
            loadingOverlay.classList.remove('d-none');
        }
        // Disable AI-related buttons while request is in-flight
        const aiButtons = Array.from(document.querySelectorAll('button[onclick="askHolisticAI()"], #analyzePlanBtn'));
        aiButtons.forEach(b => { try { b.disabled = true; } catch(_){} });
        
        // Abort previous chat request if any, then create a new controller
        if (__aiChatController) {
            try { __aiChatController.abort(); } catch(_){}
        }
        __aiChatController = new AbortController();
        const chatTimeoutId = setTimeout(() => { try { __aiChatController.abort(); } catch(_){} }, 10000);

        try {
            const response = await fetch(window.location.origin + '/admin/farmos/succession-planning/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ question: message }),
                signal: __aiChatController.signal
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('🤖 AI response:', data);
            
            // Handle the response (you might want to display it in a chat area)
            if (data.answer) {
                // For now, just log the response
                console.log('AI Answer:', data.answer);
                
                // You could display the response in a chat area or alert
                // alert('AI Response: ' + data.answer);
            }
            
        } catch (error) {
            if (error?.name === 'AbortError') {
                console.warn('AI chat aborted');
                return;
            }
            console.error('Error sending message to AI:', error);
            alert('Error communicating with AI: ' + error.message);
        } finally {
            clearTimeout(chatTimeoutId);
            __aiChatController = null;
            // Hide loading state
            if (loadingOverlay) {
                loadingOverlay.classList.add('d-none');
            }
            // Re-enable buttons and clear in-flight flag
            aiButtons.forEach(b => { try { b.disabled = false; } catch(_){} });
            __aiInFlight = false;
        }
    }

    // Calculate plan and render Quick Form tabs
    async function calculateSuccessionPlan() {
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        const hs = document.getElementById('harvestStart');
        const he = document.getElementById('harvestEnd');
        const beds = document.getElementById('bedSelect');

        if (!cropSelect?.value || !hs?.value || !he?.value) {
            showToast('Select crop and harvest dates first', 'warning');
            return;
        }

        const payload = {
            crop_id: cropSelect.value,
            variety_id: varietySelect?.value || null,
            harvest_start: hs.value,
            harvest_end: he.value,
            bed_ids: beds ? Array.from(beds.selectedOptions).map(o => o.value) : [],
            use_ai: true
        };

        showLoading(true);
        try {
            const resp = await fetch(`${API_BASE}/calculate?_cb=${Date.now()}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(payload)
            });
            const data = await resp.json();
            if (!resp.ok || !data.success) throw new Error(data.message || `HTTP ${resp.status}`);

            currentSuccessionPlan = data.succession_plan;
            renderSuccessionSummary(currentSuccessionPlan);
            renderQuickFormTabs(currentSuccessionPlan);
            document.getElementById('resultsSection').style.display = 'block';
            updateExportButton();
            // testQuickFormUrls(); // Function not defined
        } catch (e) {
            console.error('Failed to calculate plan:', e);
            showToast('Failed to calculate plan', 'error');
        } finally {
            showLoading(false);
        }
    }

    function renderSuccessionSummary(plan) {
        const container = document.getElementById('successionSummary');
        if (!container) return;
        const items = (plan.plantings || []).map((p, i) => {
            return `<div class="col-md-4">
                <div class="succession-card" onclick="switchTab(${i})" role="button" aria-label="Open succession ${i+1}">
                    <div class="d-flex justify-content-between">
                        <strong>Succession ${i+1}</strong>
                        <span class="badge bg-light text-dark">${p.bed_name || 'Unassigned'}</span>
                    </div>
                    <div class="mt-2 small text-muted">
                        Seeding: ${p.seeding_date || '-'}<br>
                        ${p.transplant_date ? 'Transplant: ' + p.transplant_date + '<br>' : ''}
                        Harvest: ${p.harvest_date}${p.harvest_end_date ? ' → ' + p.harvest_end_date : ''}
                    </div>
                </div>
            </div>`;
        });
        container.innerHTML = items.join('');
    }

    function renderQuickFormTabs(plan) {
        console.log('🔧 Rendering Quick Form tabs for plan:', plan);
        
        // Use the existing Quick Form tabs container
        const tabsWrap = document.getElementById('quickFormTabsContainer');
        if (!tabsWrap) {
            console.error('❌ Quick Form tabs container not found');
            return;
        }

        console.log('✅ Found tabs container, plantings:', plan.plantings);
        
        const nav = document.getElementById('tabNavigation');
        const content = document.getElementById('tabContent');
        
        if (!nav || !content) {
            console.error('❌ Tab navigation or content elements not found');
            return;
        }

        // Clear existing content
        nav.innerHTML = '';
        content.innerHTML = '';

        if (!plan.plantings || plan.plantings.length === 0) {
            console.warn('⚠️ No plantings found in plan');
            nav.innerHTML = '<div class="alert alert-warning">No succession plantings generated</div>';
            content.innerHTML = '';
            tabsWrap.style.display = 'block';
            return;
        }

        (plan.plantings || []).forEach((p, i) => {
            console.log(`🔄 Processing planting ${i+1}:`, p);
            
            // Button
            const btn = document.createElement('button');
            btn.className = 'tab-button' + (i === 0 ? ' active' : '');
            btn.type = 'button';
            btn.textContent = `Succession ${i+1}`;
            btn.addEventListener('click', () => switchTab(i));
            nav.appendChild(btn);

            // Pane
            const pane = document.createElement('div');
            pane.id = `tab-${i}`;
            pane.className = 'tab-pane' + (i === 0 ? ' active' : '');

            const info = document.createElement('div');
            info.className = 'succession-info';
            info.innerHTML = `<h5>Details</h5>
                <p><strong>Bed:</strong> ${p.bed_name || 'Unassigned'}</p>
                <p><strong>Seeding:</strong> ${p.seeding_date || '-'}</p>
                ${p.transplant_date ? `<p><strong>Transplant:</strong> ${p.transplant_date}</p>` : ''}
                <p><strong>Harvest:</strong> ${p.harvest_date}${p.harvest_end_date ? ' → ' + p.harvest_end_date : ''}</p>`;
            pane.appendChild(info);

            const qfu = p.quick_form_urls || {};
            console.log(`🔗 Quick Form URLs for planting ${i+1}:`, qfu);
            
            const forms = [
                { key: 'seeding', label: 'Seeding' },
                { key: 'transplant', label: 'Transplant' },
                { key: 'harvest', label: 'Harvest' }
            ];

            forms.forEach(f => {
                const wrap = document.createElement('div');
                wrap.className = 'quick-form-container';
                const url = qfu[f.key];
                if (!url) {
                    console.warn(`⚠️ No ${f.label} URL for planting ${i+1}`);
                    wrap.innerHTML = `<div class="quick-form-error"><i class=\"fas fa-exclamation-triangle\"></i> ${f.label} Quick Form URL not available.</div>`;
                } else {
                    console.log(`✅ ${f.label} URL for planting ${i+1}:`, url);
                    let crossOrigin = false;
                    try { crossOrigin = new URL(url).origin !== window.location.origin; } catch (_) {}
                    if (crossOrigin) {
                        wrap.innerHTML = `
                            <div class=\"d-flex align-items-center justify-content-between mb-2\">
                                <strong>${f.label} Quick Form</strong>
                                <span class=\"badge bg-secondary\">opens in farmOS</span>
                            </div>
                            <div class=\"alert alert-info\">This form opens in a new tab due to farmOS security settings (X-Frame-Options).</div>
                            <div class=\"d-flex gap-2\">
                                <a class=\"btn btn-primary btn-sm\" href=\"${url}\" target=\"_blank\" rel=\"noopener\"><i class=\"fas fa-external-link-alt\"></i> Open ${f.label}</a>
                                <button class=\"btn btn-outline-secondary btn-sm\" onclick=\"copyLink('${encodeURIComponent(url)}')\"><i class=\"fas fa-link\"></i> Copy link</button>
                            </div>
                        `;
                    } else {
                        const loading = document.createElement('div');
                        loading.className = 'loading-indicator';
                        loading.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading farmOS Quick Form...';
                        wrap.appendChild(loading);

                        const iframe = document.createElement('iframe');
                        iframe.className = 'quick-form-iframe';
                        iframe.src = url;
                        iframe.title = `${f.label} Quick Form`;
                        iframe.onload = () => loading.remove();
                        wrap.appendChild(iframe);

                        setTimeout(() => {
                            try {
                                const blocked = !iframe.contentDocument || iframe.contentDocument.body?.childElementCount === 0;
                                if (blocked) {
                                    wrap.innerHTML = `
                                        <div class=\"d-flex align-items-center justify-content-between mb-2\">
                                            <strong>${f.label} Quick Form</strong>
                                            <span class=\"badge bg-secondary\">opens in farmOS</span>
                                        </div>
                                        <div class=\"alert alert-info\">This form couldn\'t be embedded. Open it in farmOS instead.</div>
                                        <div class=\"d-flex gap-2\">
                                            <a class=\"btn btn-primary btn-sm\" href=\"${url}\" target=\"_blank\" rel=\"noopener\"><i class=\"fas fa-external-link-alt\"></i> Open ${f.label}</a>
                                            <button class=\"btn btn-outline-secondary btn-sm\" onclick=\"copyLink('${encodeURIComponent(url)}')\"><i class=\"fas fa-link\"></i> Copy link</button>
                                        </div>
                                    `;
                                }
                            } catch (_) {
                                wrap.innerHTML = `
                                    <div class=\"d-flex align-items-center justify-content-between mb-2\">
                                        <strong>${f.label} Quick Form</strong>
                                        <span class=\"badge bg-secondary\">opens in farmOS</span>
                                    </div>
                                    <div class=\"alert alert-info\">This form couldn\'t be embedded. Open it in farmOS instead.</div>
                                    <div class=\"d-flex gap-2\">
                                        <a class=\"btn btn-primary btn-sm\" href=\"${url}\" target=\"_blank\" rel=\"noopener\"><i class=\"fas fa-external-link-alt\"></i> Open ${f.label}</a>
                                        <button class=\"btn btn-outline-secondary btn-sm\" onclick=\"copyLink('${encodeURIComponent(url)}')\"><i class=\"fas fa-link\"></i> Copy link</button>
                                    </div>
                                `;
                            }
                        }, 1500);
                    }
                }
                pane.appendChild(wrap);
            });

            content.appendChild(pane);
        });

        // Show the tabs container
        console.log('✅ Showing tabs container');
        tabsWrap.style.display = 'block';
        // initializeTabs(); // Not needed - switchTab handles the logic
    }

    function switchTab(index) {
        const buttons = document.querySelectorAll('#tabNavigation .tab-button');
        const panes = document.querySelectorAll('#tabContent .tab-pane');
        buttons.forEach((b, i) => b.classList.toggle('active', i === index));
        panes.forEach((p, i) => p.classList.toggle('active', i === index));
    }

    function copyLink(encodedUrl) {
        const url = decodeURIComponent(encodedUrl);
        navigator.clipboard.writeText(url).then(() => {
            // Show a brief success message
            const notification = document.createElement('div');
            notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                <i class="fas fa-check"></i> Link copied to clipboard!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            
            // Auto-remove after 3 seconds
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        }).catch(err => {
            console.error('Failed to copy link:', err);
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = url;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            const notification = document.createElement('div');
            notification.className = 'alert alert-success alert-dismissible fade show position-fixed';
            notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
            notification.innerHTML = `
                <i class="fas fa-check"></i> Link copied to clipboard!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                if (notification.parentNode) {
                    notification.remove();
                }
            }, 3000);
        });
    }

    async function askAIAboutPlan() {
        if (!currentSuccessionPlan) {
            showToast('Please calculate a succession plan first', 'warning');
            return;
        }

        const planContext = buildPlanContextForAI();
        const prompt = `Analyze this succession planting plan and provide specific recommendations for optimization. Consider timing, spacing, resource allocation, and potential improvements.

Plan Details:
${planContext}

Please provide actionable insights for improving this succession plan.`;

        await askHolisticAI(prompt, 'succession_plan_analysis');
    }

    function buildPlanContextForAI() {
        if (!currentSuccessionPlan) return 'No plan available';

        const plan = currentSuccessionPlan;
        let context = `Crop: ${plan.crop?.name || 'Unknown'}
Variety: ${plan.variety?.name || 'Standard'}
Harvest Window: ${plan.harvest_start} to ${plan.harvest_end}
Total Successions: ${plan.total_successions || 0}

Plantings:`;

        if (plan.plantings && plan.plantings.length > 0) {
            plan.plantings.forEach((p, i) => {
                context += `\n${i+1}. Succession ${p.succession_number || i+1}
   - Bed: ${p.bed_name || 'Unassigned'}
   - Seeding: ${p.seeding_date || 'Not set'}
   - Transplant: ${p.transplant_date || 'Not set'}
   - Harvest: ${p.harvest_date || 'Not set'}${p.harvest_end_date ? ' to ' + p.harvest_end_date : ''}`;
            });
        } else {
            context += '\nNo plantings generated yet';
        }

        return context;
    }
</script>
@endsection