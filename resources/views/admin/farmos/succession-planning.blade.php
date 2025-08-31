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
                        <div class="alert alert-info">
                            <h6><i class="fas fa-lightbulb text-primary"></i> Quick Start Guide</h6>
                            <ul class="mb-0 small">
                                <li><strong>Click tabs</strong> to switch between successions</li>
                                <li><strong>Review forms</strong> in each tab (Seeding → Transplant → Harvest)</li>
                                <li><strong>Submit to farmOS</strong> using the embedded Quick Forms</li>
                                <li><strong>Keyboard shortcuts:</strong> Number keys (1-9) or arrow keys to navigate</li>
                                <li><strong>Track progress:</strong> Completed tabs show a green checkmark</li>
                            </ul>
                        </div>
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
                                <br><small class="text-muted">💡 The AI now has context about your current harvest window and succession plan</small>
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
                            <button class="btn btn-outline-info" onclick="askAIAboutPlan()" id="analyzePlanBtn" style="display: none;">
                                <i class="fas fa-chart-line"></i>
                                Analyze Plan
                            </button>
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

<!-- Cache busting version: {{ time() }} -->
<script>
    console.log('🔄 Succession Planner Loading - Version: {{ time() }}');
    
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
    const API_BASE = window.location.origin + '/admin/farmos/succession-planning';

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
        
        // Debug: Log initial data
        console.log('🔍 Initial cropTypes:', cropTypes);
        console.log('🔍 Initial cropVarieties:', cropVarieties);
    });

    async function initializeApp() {
        console.log('🌱 Initializing farmOS Succession Planner with real data...');

        // Test connections
        await testConnections();

        // Show the harvest bar immediately with default dates
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
            // Don't trigger AI on crop selection - only filter varieties
        });
        
        document.getElementById('varietySelect').addEventListener('change', function() {
            console.log('🔄 Variety selected:', this.value, this.options[this.selectedIndex]?.text);
            calculateAIHarvestWindow();
        });
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
                    const metaResp = await fetch(`${API_BASE}/varieties/${varietyId}`, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
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

            // Use AbortController to timeout requests that hang
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10s timeout

            const response = await fetch(chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ message: prompt, context: contextPayload }),
                signal: controller.signal
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

            // data.answer may be plain text containing JSON or already parsed
            const aiText = (typeof data.answer === 'string') ? data.answer.trim() : data.answer;

            // Try to parse guaranteed JSON response first
            let harvestInfo = null;
            try {
                if (typeof aiText === 'object') {
                    harvestInfo = aiText;
                } else {
                    // Sometimes AI wraps JSON in markdown fences - try to find JSON substring
                    const jsonMatch = String(aiText).match(/\{[\s\S]*\}/);
                    const candidate = jsonMatch ? jsonMatch[0] : String(aiText);
                    harvestInfo = JSON.parse(candidate);
                }
            } catch (e) {
                console.warn('AI did not return strict JSON or parsing failed, falling back to text parsing:', e);
                harvestInfo = parseHarvestWindow(String(aiText), cropName, varietyName);
            }

            if (!harvestInfo || !harvestInfo.maximum_start || !harvestInfo.maximum_end) {
                console.warn('AI returned incomplete harvest info, falling back to parsed/fallback values');
                harvestInfo = parseHarvestWindow(String(aiText), cropName, varietyName);
            }

            displayAIHarvestWindow(harvestInfo);

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
    function displayAIHarvestWindow(harvestInfo) {
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
        
        // Update AI chat context with harvest window information
        updateAIChatContext(harvestInfo, cropName, varietyName);
        
        // Show the analyze plan button
        const analyzeBtn = document.getElementById('analyzePlanBtn');
        if (analyzeBtn) {
            analyzeBtn.style.display = 'inline-block';
        }
        
        console.log('✅ AI harvest window displayed successfully');
    
    // Update AI chat context with current plan information
    function updateAIChatContext(harvestInfo, cropName, varietyName) {
        const contextDiv = document.getElementById('aiPlanContext');
        const detailsDiv = document.getElementById('planContextDetails');
        
        if (!contextDiv || !detailsDiv) return;
        
        let contextHTML = '<div class="mb-2">
            <strong>Crop:</strong> ' + (cropName || 'Unknown') + '<br>
            <strong>Variety:</strong> ' + (varietyName || 'Generic') + '
        </div>');
        
        if (harvestInfo.maximum_start && harvestInfo.maximum_end) {
            const startDate = new Date(harvestInfo.maximum_start);
            const endDate = new Date(harvestInfo.maximum_end);
            const durationDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
            
            contextHTML += '<div class="mb-2">
                <strong>Harvest Window:</strong> ' + startDate.toLocaleDateString() + ' - ' + endDate.toLocaleDateString() + '<br>
                <small>Duration: ' + durationDays + ' days</small>
            </div>');
        }
        
        if (harvestInfo.notes) {
            contextHTML += '<div class="mb-2">
                <strong>AI Notes:</strong> <em>' + harvestInfo.notes + '</em>
            </div>');
        }
        
        detailsDiv.innerHTML = contextHTML;
        contextDiv.style.display = 'block');
        
        console.log('📝 AI chat context updated with harvest window information');
    
    // Test function to verify AI context integration
    window.testAIContext = function() {
        const context = getCurrentPlanContext();
        console.log('🧪 AI Context Test:', context);
        return context;
    };
    }
    
    // Ask AI to analyze the current succession plan
    function askAIAboutPlan() {
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        const harvestStart = document.getElementById('harvestStart');
        const harvestEnd = document.getElementById('harvestEnd');
        
        const cropName = cropSelect.options[cropSelect.selectedIndex]?.text || 'selected crop';
        const varietyName = varietySelect.value ? varietySelect.options[varietySelect.selectedIndex]?.text : 'any variety';
        const startDate = harvestStart.value ? new Date(harvestStart.value).toLocaleDateString() : 'not set';
        const endDate = harvestEnd.value ? new Date(harvestEnd.value).toLocaleDateString() : 'not set';
        
        const question = `Please analyze this succession plan for ${cropName} (${varietyName}) with harvest window ${startDate} to ${endDate}. What are your recommendations for optimal succession intervals, planting dates, and any potential issues I should consider?`;
        
        document.getElementById('aiChatInput').value = question;
        askHolisticAI();
        
        console.log('🤖 AI analyzing current succession plan');
    }
        
        console.log('✅ AI harvest window displayed successfully');
    
    // Update AI chat context with current plan information
    function updateAIChatContext(harvestInfo, cropName, varietyName) {
        const contextDiv = document.getElementById('aiPlanContext');
        const detailsDiv = document.getElementById('planContextDetails');
        
        if (!contextDiv || !detailsDiv) return;
        
        let contextHTML = '<div class="mb-2">
            <strong>Crop:</strong> ' + (cropName || 'Unknown') + '<br>
            <strong>Variety:</strong> ' + (varietyName || 'Generic') + '
        </div>');
        
        if (harvestInfo.maximum_start && harvestInfo.maximum_end) {
            const startDate = new Date(harvestInfo.maximum_start);
            const endDate = new Date(harvestInfo.maximum_end);
            const durationDays = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));
            
            contextHTML += '<div class="mb-2">
                <strong>Harvest Window:</strong> ' + startDate.toLocaleDateString() + ' - ' + endDate.toLocaleDateString() + '<br>
                <small>Duration: ' + durationDays + ' days</small>
            </div>');
        }
        
        if (harvestInfo.notes) {
            contextHTML += '<div class="mb-2">
                <strong>AI Notes:</strong> <em>' + harvestInfo.notes + '</em>
            </div>');
        }
        
        detailsDiv.innerHTML = contextHTML;
        contextDiv.style.display = 'block');
        
        console.log('📝 AI chat context updated with harvest window information');
    
    // Test function to verify AI context integration
    window.testAIContext = function() {
        const context = getCurrentPlanContext();
        console.log('🧪 AI Context Test:', context);
        return context;
    };
    }
    
    // Ask AI to analyze the current succession plan
    function askAIAboutPlan() {
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        const harvestStart = document.getElementById('harvestStart');
        const harvestEnd = document.getElementById('harvestEnd');
        
        const cropName = cropSelect.options[cropSelect.selectedIndex]?.text || 'selected crop';
        const varietyName = varietySelect.value ? varietySelect.options[varietySelect.selectedIndex]?.text : 'any variety';
        const startDate = harvestStart.value ? new Date(harvestStart.value).toLocaleDateString() : 'not set';
        const endDate = harvestEnd.value ? new Date(harvestEnd.value).toLocaleDateString() : 'not set';
        
        const question = `Please analyze this succession plan for ${cropName} (${varietyName}) with harvest window ${startDate} to ${endDate}. What are your recommendations for optimal succession intervals, planting dates, and any potential issues I should consider?`;
        
        document.getElementById('aiChatInput').value = question;
        askHolisticAI();
        
        console.log('🤖 AI analyzing current succession plan');
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
            ai_harvest_window: harvestWindowInfo,
                maximum_start: aiResponse.maximum_start || aiResponse.maximumStart || aiResponse.optimal_start || aiResponse.optimalStart || aiResponse.start || null,
                maximum_end: aiResponse.maximum_end || aiResponse.maximumEnd || aiResponse.optimal_end || aiResponse.optimalEnd || aiResponse.end || null,
                days_to_harvest: aiResponse.days_to_harvest || aiResponse.daysToHarvest || aiResponse.days || null,
                yield_peak: aiResponse.yield_peak || aiResponse.yieldPeak || null,
                notes: aiResponse.notes || '',
                extended_window: aiResponse.extended_window || {
                    max_extension_days: Math.floor((aiResponse.days_to_harvest || 60) * 0.2), // 20% extension
                    risk_level: 'moderate'
                }
            };
        }

        const text = String(aiResponse);

        // Try to extract ISO dates first
        const isoStartMatch = text.match(/(\d{4}-\d{2}-\d{2})/);
        const isoDates = text.match(/(\d{4}-\d{2}-\d{2})/g);
        if (isoDates && isoDates.length >= 2) {
            return {
            ai_harvest_window: harvestWindowInfo,
                maximum_start: isoDates[0],
                maximum_end: isoDates[1],
                days_to_harvest: extractDaysFromResponse(text),
                yield_peak: isoDates[Math.min(2, isoDates.length-1)] || isoDates[0],
                notes: `AI analysis for ${cropName}${varietyName ? ` (${varietyName})` : ''}`,
                extended_window: {
                    max_extension_days: Math.floor(extractDaysFromResponse(text) * 0.2),
                    risk_level: 'moderate'
                }
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
            ai_harvest_window: harvestWindowInfo,
                maximum_start: defaultStart.toISOString().split('T')[0],
                maximum_end: defaultEnd.toISOString().split('T')[0],
                days_to_harvest: extractDaysFromResponse(text),
                yield_peak: defaultStart.toISOString().split('T')[0],
                notes: `AI analysis for ${cropName}${varietyName ? ` (${varietyName})` : ''}`,
                extended_window: {
                    max_extension_days: Math.floor(extractDaysFromResponse(text) * 0.2),
                    risk_level: 'moderate'
                }
            };
        }

        // Generic fallback with crop-specific logic
        const today = new Date();
        const cropNameLower = cropName.toLowerCase();
        
        let defaultStart, defaultEnd, defaultDays, notes;
        
        // Special handling for beets and root vegetables
        if (cropNameLower.includes('beet')) {
            // Beets: May to December (up to 221 days as user mentioned)
            defaultStart = new Date(today.getFullYear(), 4, 1); // May 1st
            defaultEnd = new Date(today.getFullYear(), 11, 31); // December 31st
            defaultDays = 221; // May to December
            notes = `Maximum beet harvest window: May-December (${defaultDays} days). Young beets from May, mature from June-November, can extend to January with protection.`;
        } else if (cropNameLower.includes('carrot') || cropNameLower.includes('radish') || cropNameLower.includes('turnip')) {
            // Other root vegetables: also have extended harvest periods
            defaultStart = new Date(today.getFullYear(), 5, 1); // June 1st
            defaultEnd = new Date(today.getFullYear(), 10, 30); // November 30th
            defaultDays = 153; // June to November
            notes = `Extended root vegetable harvest: June-November (${defaultDays} days). Can extend with row covers.`;
        } else if (cropNameLower.includes('lettuce') || cropNameLower.includes('spinach')) {
            // Leafy greens: shorter harvest periods
            defaultStart = new Date(today);
            defaultEnd = new Date(today);
            defaultEnd.setMonth(defaultEnd.getMonth() + 2);
            defaultDays = 60;
            notes = `Leafy green harvest window: ${defaultDays} days. Multiple successions recommended.`;
        } else {
            // Generic fallback for other crops
            defaultStart = new Date(today); defaultStart.setMonth(today.getMonth() + 2);
            defaultEnd = new Date(defaultStart); defaultEnd.setMonth(defaultStart.getMonth() + 1);
            defaultDays = 60;
            notes = `Standard harvest window: ${defaultDays} days for ${cropName}.`;
        }
        
        return {
            ai_harvest_window: harvestWindowInfo,
            maximum_start: defaultStart.toISOString().split('T')[0],
            maximum_end: defaultEnd.toISOString().split('T')[0],
            days_to_harvest: defaultDays,
            yield_peak: new Date((defaultStart.getTime() + defaultEnd.getTime()) / 2).toISOString().split('T')[0],
            notes: notes,
            extended_window: {
                max_extension_days: Math.floor(defaultDays * 0.2),
                risk_level: cropNameLower.includes('beet') || cropNameLower.includes('carrot') ? 'low' : 'moderate'
            }
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
        const seasonDays = Math.ceil((seasonEnd - seasonStart) / (1000 * 60 * 60 * 60 * 24));
        
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

        // Initialize tab functionality
        setTimeout(() => {
            initializeTabs();
            updateExportButton();
        }, 100);
    }

    function displaySuccessionSummary(plan) {
        const summaryContainer = document.getElementById('successionSummary');

        if (!plan.plantings || plan.plantings.length === 0) {
            summaryContainer.innerHTML = '<div class="alert alert-warning">No succession plantings found.</div>';
            return;
        }

        let tabsHTML = `
            <div class="succession-tabs">
                <div class="tab-navigation">
        `;

        let contentHTML = '<div class="tab-content">';

        plan.plantings.forEach((planting, index) => {
            const plantingDate = new Date(planting.planting_date || planting.seeding_date);
            const harvestDate = new Date(planting.harvest_date);
            const isOverdue = plantingDate < new Date();
            const successionNumber = planting.succession_number || (index + 1);

            // Tab button
            tabsHTML += `
                <button class="tab-button ${index === 0 ? 'active' : ''} ${isOverdue ? 'overdue' : ''}"
                        onclick="switchTab(${index})">
                    <i class="fas fa-seedling"></i>
                    Succession ${successionNumber}
                    ${isOverdue ? '<span class="badge bg-danger ms-1">Overdue</span>' : ''}
                </button>
            `;

            // Tab content with Quick Form
            contentHTML += `
                <div class="tab-pane ${index === 0 ? 'active' : ''}" id="tab-${index}">
                    <div class="succession-info">
                        <h5><i class="fas fa-info-circle text-primary"></i> Succession ${successionNumber} Details</h5>
                        <p><strong>Seeding:</strong> ${plantingDate.toLocaleDateString()}</p>
                        <p><strong>Harvest:</strong> ${harvestDate.toLocaleDateString()}</p>
                        ${planting.bed_name ? `<p><strong>Bed:</strong> ${planting.bed_name}</p>` : ''}
                        ${planting.quantity ? `<p><strong>Quantity:</strong> ${planting.quantity} plants</p>` : ''}
                    </div>

                    <div class="quick-form-container">
                        <h6><i class="fas fa-file-alt text-success"></i> Quick Form - Seeding Log</h6>
                        <p class="text-muted small">Review and submit this seeding log to farmOS</p>
                        ${planting.quick_form_urls?.seeding ?
                            `<iframe class="quick-form-iframe iframe-loading"
                                    src="${planting.quick_form_urls.seeding}"
                                    id="iframe-seeding-${index}"
                                    onload="onIframeLoad(${index}, 'seeding')">
                            </iframe>` :
                            `<div class="quick-form-error">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Quick Form Unavailable</strong>
                                <br>
                                <small>farmOS Quick Form integration not configured</small>
                            </div>`
                        }
                    </div>

                    <div class="quick-form-container">
                        <h6><i class="fas fa-spa text-warning"></i> Quick Form - Transplant Log</h6>
                        <p class="text-muted small">Review and submit this transplant log to farmOS</p>
                        ${planting.quick_form_urls?.transplant ?
                            `<iframe class="quick-form-iframe iframe-loading"
                                    src="${planting.quick_form_urls.transplant}"
                                    id="iframe-transplant-${index}"
                                    onload="onIframeLoad(${index}, 'transplant')">
                            </iframe>` :
                            `<div class="quick-form-error">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Quick Form Unavailable</strong>
                                <br>
                                <small>farmOS Quick Form integration not configured</small>
                            </div>`
                        }
                    </div>

                    <div class="quick-form-container">
                        <h6><i class="fas fa-leaf text-danger"></i> Quick Form - Harvest Log</h6>
                        <p class="text-muted small">Review and submit this harvest log to farmOS</p>
                        ${planting.quick_form_urls?.harvest ?
                            `<iframe class="quick-form-iframe iframe-loading"
                                    src="${planting.quick_form_urls.harvest}"
                                    id="iframe-harvest-${index}"
                                    onload="onIframeLoad(${index}, 'harvest')">
                            </iframe>` :
                            `<div class="quick-form-error">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>Quick Form Unavailable</strong>
                                <br>
                                <small>farmOS Quick Form integration not configured</small>
                            </div>`
                        }
                    </div>
                </div>
            `;
        });

        tabsHTML += '</div>';
        contentHTML += '</div></div>';

        summaryContainer.innerHTML = tabsHTML + contentHTML;

        // Initialize tab functionality
        initializeTabs();
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
            ai_harvest_window: harvestWindowInfo,
                label: `Succession ${index + 1}`,
                data: [{

                    x: plantingDate.toISOString().split('T')[0],
                    y: `Succession ${index + 1}`,
                    x2: harvestDate.toISOString().split('T')[0]
                }],
                backgroundColor: isOverdue ? '#dc3545' : '#28a745',
                borderColor: isOverdue ? '#dc3545' : '#28a745',
                borderWidth: 2,
                barThickness:   20
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

    function getCurrentPlanContext() {
        // Get AI harvest window information from the UI
        const aiHarvestDetails = document.getElementById('aiHarvestDetails');
        let harvestWindowInfo = null;
        
        if (aiHarvestDetails && aiHarvestDetails.innerHTML) {
            // Extract harvest window information from the displayed text
            const harvestText = aiHarvestDetails.innerText || aiHarvestDetails.textContent;
            harvestWindowInfo = {
                ai_calculated_details: harvestText,
                has_ai_context: true
            };
        }
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        const harvestStart = document.getElementById('harvestStart').value;
        const harvestEnd = document.getElementById('harvestEnd').value;
        const planningYear = document.getElementById('planningYear').value;
        const planningSeason = document.getElementById('planningSeason').value;

        return {
            ai_harvest_window: harvestWindowInfo,
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
        exportSuccessionData();
    }

    // Export succession plan summary
    function exportSuccessionPlan() {
        if (!currentSuccessionPlan) {
            showToast('No succession plan to export', 'warning');
            return;
        }

        const plan = currentSuccessionPlan;
        let exportText = `farmOS Succession Plan Export\n`;
        exportText += `Generated: ${new Date().toLocaleString()}\n\n`;

        if (plan.crop) {
            exportText += `Crop: ${plan.crop.name || plan.crop.label}\n`;
        }

        if (plan.variety) {
            exportText += `Variety: ${plan.variety.name || plan.variety.title}\n`;
        }

        exportText += `Harvest Window: ${plan.harvest_start} to ${plan.harvest_end}\n\n`;

        exportText += `Successions:\n`;
        exportText += `==========\n\n`;

        plan.plantings.forEach((planting, index) => {
            exportText += `Succession ${index + 1}:\n`;
            exportText += `- Seeding: ${planting.seeding_date || planting.planting_date}\n`;
            if (planting.transplant_date) {
                exportText += `- Transplant: ${planting.transplant_date}\n`;
            }
            exportText += `- Harvest: ${planting.harvest_date}`;
            if (planting.harvest_end_date) {
                exportText += ` to ${planting.harvest_end_date}`;
            }
            exportText += `\n`;
            if (planting.bed_name) {
                exportText += `- Bed: ${planting.bed_name}\n`;
            }
            if (planting.quantity) {
                exportText += `- Quantity: ${planting.quantity}\n`;
            }
            exportText += `\n`;
        });

        // Create and download file
        const blob = new Blob([exportText], { type: 'text/plain' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `succession-plan-${new Date().toISOString().split('T')[0]}.txt`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);

        showToast('Succession plan exported successfully!', 'success');
    }

    // Add global functions for iframe communication
    window.farmOSFormSubmitted = function(successionIndex, formType, success) {
        handleFormSubmission(successionIndex, formType, success);
    };

    window.farmOSFormError = function(successionIndex, formType, error) {
        console.error(`Form error for Succession ${successionIndex + 1} ${formType}:`, error);
        showToast(`Error submitting ${formType} form: ${error}`, 'error');
    };

    function handleFormSubmission(successionIndex, formType, success = true) {
        if (success) {
            markSuccessionComplete(successionIndex, formType);

            // Check if all forms for this succession are completed
            const successionCompleted = checkSuccessionCompletion(successionIndex);
            if (successionCompleted) {
                showToast(`Succession ${successionIndex + 1} completed! All logs submitted to farmOS.`, 'success');

                // Auto-advance to next tab if available
                const nextTabIndex = successionIndex + 1;
                const nextTabButton = document.querySelectorAll('.tab-button')[nextTabIndex];
                if (nextTabButton) {
                    setTimeout(() => {
                        switchTab(nextTabIndex);
                    }, 2000); // Wait 2 seconds before auto-advancing
                }
            }
        } else {
            showToast(`Failed to submit ${formType} log for Succession ${successionIndex + 1}`, 'error');
        }
    }

    function checkSuccessionCompletion(successionIndex) {
        // This would ideally check with farmOS API to see if all logs exist
        // For now, we'll use a simple local check
        const tabButton = document.querySelectorAll('.tab-button')[successionIndex];
        return tabButton && tabButton.classList.contains('completed');
    }

    function refreshTabData(successionIndex) {
        // Refresh the data for a specific tab (useful after form submissions)
        const tabPane = document.getElementById(`tab-${successionIndex}`);
        if (tabPane) {
            // Add a subtle refresh animation
            tabPane.style.opacity = '0.7';
            setTimeout(() => {
                tabPane.style.opacity = '1';
            }, 500);

            console.log(`🔄 Refreshed data for Succession ${successionIndex + 1}`);
        }
    }

    function exportSuccessionData() {
        const plan = currentSuccessionPlan;
        if (!plan) {
            showToast('No succession plan to export', 'warning');
            return;
        }

        // Create export data
        const exportData = {
            plan: plan,
            exported_at: new Date().toISOString(),
            total_successions: plan.plantings?.length || 0,
            forms_generated: plan.plantings?.filter(p => p.quick_form_urls).length || 0
        };

        // Create and download JSON file
        const dataStr = JSON.stringify(exportData, null, 2);
        const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);

        const exportFileDefaultName = `succession-plan-${plan.crop?.name || 'unknown'}-${new Date().toISOString().split('T')[0]}.json`;

        const linkElement = document.createElement('a');
        linkElement.setAttribute('href', dataUri);
        linkElement.setAttribute('download', exportFileDefaultName);
        linkElement.click();

        showToast('Succession plan exported successfully!', 'success');
    }

    function updateExportButton() {
        const exportBtn = document.getElementById('exportPlanBtn');
        if (exportBtn && currentSuccessionPlan) {
            exportBtn.style.display = 'inline-block';
            exportBtn.onclick = exportSuccessionData;
        }
    }

    function testQuickFormUrls() {
        if (!currentSuccessionPlan || !currentSuccessionPlan.plantings) {
            console.warn('No succession plan available for testing');
            return;
        }

        console.log('🧪 Testing Quick Form URLs:');
        currentSuccessionPlan.plantings.forEach((planting, index) => {
            console.log(`Succession ${index + 1}:`, {
                seeding: planting.quick_form_urls?.seeding || 'Not available',
                transplant: planting.quick_form_urls?.transplant || 'Not available',
                harvest: planting.quick_form_urls?.harvest || 'Not available'
            });
        });
    }

    // Add to window for debugging
    window.testQuickFormUrls = testQuickFormUrls;

    function initializeTabs() {
        // Add loading indicators to iframes
        const iframes = document.querySelectorAll('.quick-form-iframe');
        iframes.forEach(iframe => {
            const container = iframe.closest('.quick-form-container');
            if (container && !container.querySelector('.loading-indicator')) {
                const loadingDiv = document.createElement('div');
                loadingDiv.className = 'loading-indicator text-center text-muted';
                loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading farmOS Quick Form...';
                container.insertBefore(loadingDiv, iframe);
            }
        });

        // Add error handling to iframes
        addIframeErrorHandling();

        // Add toast notification styles
        addToastStyles();

        // Add keyboard navigation
        addKeyboardNavigation();

        console.log('🔧 Tab functionality initialized with error handling, notifications, and keyboard navigation');
    }

    function addIframeErrorHandling() {
        const iframes = document.querySelectorAll('.quick-form-iframe');
        iframes.forEach((iframe, index) => {
            iframe.addEventListener('error', function() {
                const container = iframe.closest('.quick-form-container');
                if (container) {
                    container.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Unable to load farmOS Quick Form. Please check your connection and try again.
                            <br><small class="text-muted">If the problem persists, try refreshing the page.</small>
                        </div>
                    `;
                }
            });

            // Add load timeout handling
            setTimeout(() => {
                if (!iframe.contentWindow || iframe.contentWindow.length === 0) {
                    const container = iframe.closest('.quick-form-container');
                    if (container && container.querySelector('.loading-indicator')) {
                        container.querySelector('.loading-indicator').innerHTML =
                            '<i class="fas fa-clock"></i> Form is taking longer than expected to load...';
                    }
                }
            }, 10000); // 10 second timeout
        });
    }
</script>
@endsection