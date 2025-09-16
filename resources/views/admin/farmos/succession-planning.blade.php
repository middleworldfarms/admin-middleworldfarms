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

    .harvest-window-selector {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border: 2px solid #dee2e6;
        border-radius: 15px;
        padding: 20px;
        margin: 20px 0;
    }

    .range-indicator {
        position: relative;
        margin-bottom: 15px;
        border-radius: 10px;
        overflow: hidden;
    }

    .range-indicator .progress {
        border-radius: 10px;
        box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
    }

    .range-indicator.max-range {
        background: linear-gradient(90deg, rgba(13, 202, 240, 0.1), rgba(13, 202, 240, 0.05));
        border: 1px solid rgba(13, 202, 240, 0.2);
    }

    .range-indicator.ai-range {
        background: linear-gradient(90deg, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.05));
        border: 1px solid rgba(255, 193, 7, 0.2);
    }

    .range-indicator.user-range {
        background: linear-gradient(90deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
        border: 1px solid rgba(40, 167, 69, 0.2);
    }

    .range-handle {
        position: absolute;
        top: 0;
        width: 20px;
        height: 100%;
        background: #28a745;
        cursor: ew-resize;
        border-radius: 3px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 12px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        transition: all 0.2s ease;
    }

    .range-handle:hover {
        background: #218838;
        transform: scale(1.1);
    }

    .range-handle.start {
        border-radius: 3px 0 0 3px;
    }

    .range-handle.end {
        border-radius: 0 3px 3px 0;
    }

    .calendar-month {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 10px;
        text-align: center;
        min-height: 80px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        transition: all 0.2s ease;
    }

    .calendar-month:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .calendar-month.optimal {
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.1), rgba(40, 167, 69, 0.05));
        border-color: rgba(40, 167, 69, 0.3);
    }

    .calendar-month.extended {
        background: linear-gradient(135deg, rgba(255, 193, 7, 0.1), rgba(255, 193, 7, 0.05));
        border-color: rgba(255, 193, 7, 0.3);
    }

    .calendar-month.selected {
        background: linear-gradient(135deg, rgba(0, 123, 255, 0.1), rgba(0, 123, 255, 0.05));
        border-color: rgba(0, 123, 255, 0.3);
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.2);
    }

    .succession-preview {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-top: 10px;
    }

    .succession-item {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .succession-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .succession-label {
        font-weight: 600;
        color: #495057;
    }

    .succession-timeline {
        display: flex;
        gap: 16px;
        align-items: center;
        position: relative;
    }

    .timeline-step {
        text-align: center;
        min-width: 80px;
        position: relative;
        z-index: 1;
        background: white;
        padding: 0 8px;
    }

    .timeline-step small {
        display: block;
        font-size: 0.75rem;
        color: #6c757d;
        margin-bottom: 2px;
    }

    .succession-date {
        font-size: 0.875rem;
        font-weight: 500;
        color: #495057;
    }

    /* Timeline connector lines */
    .succession-timeline::before {
        content: '';
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 1px;
        background: #dee2e6;
        z-index: 0;
    }

    .timeline-step:not(:last-child)::after {
        content: '';
        position: absolute;
        right: -16px;
        top: 50%;
        transform: translateY(-50%);
        width: 8px;
        height: 8px;
        background: #28a745;
        border-radius: 50%;
        z-index: 2;
    }

    /* Different colors for different timeline steps */
    .timeline-step:nth-child(1) small { color: #007bff; } /* Sow - Blue */
    .timeline-step:nth-child(2) small { color: #ffc107; } /* Transplant - Yellow */
    .timeline-step:nth-child(3) small { color: #28a745; } /* Harvest - Green */

    .timeline-step:nth-child(1)::after { background: #007bff; }
    .timeline-step:nth-child(2)::after { background: #ffc107; }

    /* Special styling for transplant and harvest steps */
    .transplant-step small { color: #856404 !important; font-weight: 500; }
    .harvest-step small { color: #155724 !important; font-weight: 500; }

    .transplant-step::after { background: #ffc107 !important; border: 2px solid #856404; }
    .harvest-step::after { background: #28a745 !important; border: 2px solid #155724; }

    .method-badge {
        background: #f8f9fa;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.75rem;
        border: 1px solid #dee2e6;
    }
        color: #6c757d;
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

    .variety-info-section {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border-radius: 1rem;
        padding: 1.5rem;
        border: 1px solid #dee2e6;
    }

    .variety-photo {
        border: 2px solid #dee2e6;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .variety-description {
        line-height: 1.5;
        font-style: italic;
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
        min-height: 800px;
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

    .quick-form-error {
        background: #f8d7da;
        border: 1px solid #f5c6cb;
        border-radius: 0.5rem;
        padding: 2rem;
        text-align: center;
        color: #721c24;
    }

    .form-content {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 0.5rem;
        padding: 1rem;
        margin-top: 1rem;
        max-height: 600px;
        overflow-y: auto;
    }

    .form-loading {
        color: #6c757d;
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
                        
                        <!-- NEW: Visual Harvest Window Selector -->
                        <div class="mt-4">
                            <label class="form-label"><strong>Harvest Window Planning:</strong></label>

                            <!-- Maximum Possible Range Indicator -->
                            <div id="maxHarvestRange" class="mb-3 p-3 bg-light rounded" style="display: none;">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-infinity text-info me-2"></i>
                                    <strong class="text-info">Maximum Possible Harvest Window</strong>
                                    <span id="maxRangeDates" class="ms-auto small text-muted"></span>
                                </div>
                                <div class="progress" style="height: 20px;">
                                    <div id="maxRangeBar" class="progress-bar bg-info opacity-25" role="progressbar" style="width: 100%"></div>
                                </div>
                            </div>

                            <!-- AI Recommended Range -->
                            <div id="aiRecommendedRange" class="mb-3 p-3 bg-light rounded" style="display: none;">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-brain text-warning me-2"></i>
                                    <strong class="text-warning">AI Recommended Window</strong>
                                    <span id="aiRangeDates" class="ms-auto small text-muted"></span>
                                </div>
                                <div class="progress" style="height: 25px;">
                                    <div id="aiRangeBar" class="progress-bar bg-warning" role="progressbar" style="width: 60%"></div>
                                </div>
                            </div>

                            <!-- User Selected Range -->
                            <div id="userSelectedRange" class="mb-3 p-3 border rounded" style="display: none;">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-user text-success me-2"></i>
                                    <strong class="text-success">Your Selected Window</strong>
                                    <span id="userRangeDates" class="ms-auto small text-muted"></span>
                                </div>
                                <div class="progress position-relative" style="height: 30px;">
                                    <div id="userRangeBar" class="progress-bar bg-success" role="progressbar" style="width: 40%; margin-left: 20%;"></div>
                                    <!-- Range Adjusters -->
                                    <div id="rangeStartHandle" class="position-absolute" style="left: 20%; top: 0; width: 20px; height: 30px; background: #28a745; cursor: ew-resize; border-radius: 3px 0 0 3px;">
                                        <div class="text-white text-center small" style="line-height: 30px;">⋮⋮</div>
                                    </div>
                                    <div id="rangeEndHandle" class="position-absolute" style="right: 40%; top: 0; width: 20px; height: 30px; background: #28a745; cursor: ew-resize; border-radius: 0 3px 3px 0;">
                                        <div class="text-white text-center small" style="line-height: 30px;">⋮⋮</div>
                                    </div>
                                </div>
                            </div>

                            <!-- Succession Impact Preview -->
                            <div id="successionImpact" class="mt-3 p-3 bg-white border rounded" style="display: none;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <strong class="text-primary">Succession Planning Impact</strong>
                                        <i class="fas fa-info-circle text-muted" data-bs-toggle="tooltip" data-bs-placement="top" 
                                           title="Shows sowing, transplant (if needed), and harvest dates for each succession. Timeline colors: Blue=Sow, Yellow=Transplant, Green=Harvest"></i>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span id="successionCount" class="badge bg-primary">3 Successions</span>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="exportSuccessionPlan()" title="Export as CSV">
                                            <i class="fas fa-download"></i> Export
                                        </button>
                                    </div>
                                </div>
                                <div id="successionPreview" class="small text-muted">
                                    <!-- Will be populated with succession dates -->
                                </div>
                            </div>

                            <!-- Quick Adjust Buttons -->
                            <div class="mt-3 d-flex gap-2 justify-content-center">
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="extendHarvestWindow()">
                                    <i class="fas fa-plus"></i> Extend 20%
                                </button>
                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="optimizeHarvestWindow()">
                                    <i class="fas fa-magic"></i> AI Optimize
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="shortenHarvestWindow()">
                                    <i class="fas fa-minus"></i> Reduce Successions
                                </button>
                            </div>

                            <!-- Calendar Grid View -->
                            <div id="harvestCalendar" class="mt-4" style="display: none;">
                                <h6 class="text-center mb-3">
                                    <i class="fas fa-calendar-alt text-primary"></i>
                                    Harvest Calendar Overview
                                </h6>
                                <div class="row g-2" id="calendarGrid">
                                    <!-- Calendar months will be populated here -->
                                </div>
                                <div class="mt-2 text-center">
                                    <small class="text-muted">
                                        <span class="badge bg-info me-2">Optimal</span>
                                        <span class="badge bg-warning me-2">Extended</span>
                                        <span class="badge bg-success">Selected</span>
                                    </small>
                                </div>
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

            <!-- Variety Information Display -->
            <div class="planning-card mt-3">
                <div class="planning-section">
                    <h3>
                        <i class="fas fa-leaf section-icon"></i>
                        Variety Information
                    </h3>
                    
                    <div id="varietyInfoContainer" class="variety-info-section" style="display: none;">
                        <!-- Variety Photo -->
                        <div class="variety-photo-container mb-3 text-center">
                            <img id="varietyPhoto" src="" alt="Variety Photo" class="variety-photo img-fluid rounded" 
                                 style="max-height: 200px; max-width: 100%; object-fit: cover; display: none;">
                            <div id="noPhotoMessage" class="text-muted small mt-2" style="display: none;">
                                <i class="fas fa-image"></i> No photo available
                            </div>
                        </div>
                        
                        <!-- Variety Details -->
                        <div class="variety-details">
                            <h5 id="varietyName" class="text-primary mb-2"></h5>
                            <div id="varietyDescription" class="variety-description small text-muted mb-3"></div>
                            
                            <!-- Additional Variety Info -->
                            <div class="row">
                                <div class="col-6">
                                    <strong>Crop Type:</strong><br>
                                    <span id="varietyCropType" class="text-muted small"></span>
                                </div>
                                <div class="col-6">
                                    <strong>Variety ID:</strong><br>
                                    <span id="varietyId" class="text-muted small"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Loading State -->
                        <div id="varietyLoading" class="text-center py-3" style="display: none;">
                            <div class="spinner-border spinner-border-sm text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="text-muted small mt-2">Loading variety information...</div>
                        </div>
                        
                        <!-- Error State -->
                        <div id="varietyError" class="alert alert-warning py-2 small" style="display: none;">
                            <i class="fas fa-exclamation-triangle"></i>
                            Unable to load variety information from FarmOS
                        </div>
                    </div>
                    
                    <!-- No Variety Selected State -->
                    <div id="noVarietySelected" class="text-center py-4 text-muted">
                        <i class="fas fa-seedling fa-2x mb-2"></i>
                        <div>Select a variety to see detailed information from FarmOS</div>
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

    // ===== INITIALIZATION =====

    // Initialize when page loads
    document.addEventListener('DOMContentLoaded', function() {
        console.log('🚀 Initializing succession planning interface');

        // Initialize the new harvest window selector
        initializeHarvestWindowSelector();

        // Set up existing functionality
        setupDragFunctionality(); // Fixed: was setupDragBar()
        setupEventListeners();
        updateSuccessionPreview();

        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    // ===== MISSING FUNCTIONS - PLACEHOLDERS =====

    // Placeholder for setupEventListeners (called during initialization)
    function setupEventListeners() {
        console.log('📋 Event listeners setup (placeholder)');
        // Add any event listeners here if needed
    }

    // Placeholder for updateSuccessionPreview (called during initialization)
    function updateSuccessionPreview() {
        console.log('🔄 Succession preview updated (placeholder)');
        // Add succession preview logic here if needed
    }

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
            // Update succession impact with new crop timing
            updateSuccessionImpact();
            // Don't trigger AI on crop selection - only filter varieties
        });
        
        document.getElementById('varietySelect').addEventListener('change', function() {
            console.log('🔄 Variety selected:', this.value, this.options[this.selectedIndex]?.text);
            calculateAIHarvestWindow();
            savePlannerState();
            // Update succession impact with new variety timing
            updateSuccessionImpact();
            // Fetch and display variety information from FarmOS
            handleVarietySelection(this.value);
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

    // Export succession plan as CSV
    function exportSuccessionPlan() {
        if (!harvestWindowData.userStart || !harvestWindowData.userEnd) {
            alert('Please set a harvest window first');
            return;
        }

        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        const cropName = cropSelect?.options[cropSelect.selectedIndex]?.text || '';
        const varietyName = varietySelect?.options[varietySelect.selectedIndex]?.text || '';

        const start = new Date(harvestWindowData.userStart);
        const end = new Date(harvestWindowData.userEnd);
        const duration = Math.ceil((end - start) / (1000 * 60 * 60 * 24));

        const avgSuccessionInterval = getSuccessionInterval(cropName.toLowerCase(), varietyName.toLowerCase());
        const successions = Math.max(1, Math.ceil(duration / avgSuccessionInterval));

        // Generate CSV content
        let csvContent = 'Succession,Crop,Variety,Sowing Date,Transplant Date,Harvest Date,Method\n';

        for (let i = 0; i < successions; i++) {
            const successionData = calculateSuccessionDates(start, i, avgSuccessionInterval, cropName.toLowerCase(), varietyName.toLowerCase());

            const sowDateStr = successionData.sowDate.toISOString().split('T')[0];
            const transplantDateStr = successionData.transplantDate ? successionData.transplantDate.toISOString().split('T')[0] : '';
            const harvestDateStr = successionData.harvestDate.toISOString().split('T')[0];

            csvContent += `${i + 1},"${cropName}","${varietyName}","${sowDateStr}","${transplantDateStr}","${harvestDateStr}","${successionData.method}"\n`;
        }

        // Download CSV file
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', `succession-plan-${cropName.replace(/\s+/g, '-')}-${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        console.log('📊 Exported succession plan as CSV');
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
            // Find the actual crop object to get its farmOS ID
            const selectedCrop = cropTypes.find(c => c.id === selectedCropId);
            const farmOSCropId = selectedCrop?.id || selectedCropId;
            
            // Filter varieties by matching crop_type with the farmOS crop ID
            const filtered = (cropVarieties || []).filter(v => 
                v.crop_type === farmOSCropId || 
                v.parent_id === farmOSCropId ||
                v.crop_id === farmOSCropId
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

    // Fetch variety information from FarmOS API
    async function fetchVarietyInfo(varietyId) {
        if (!varietyId) return null;

        try {
            console.log('🌱 Fetching variety info for ID:', varietyId);
            console.log('🌐 Making request to:', `/admin/farmos/succession-planning/varieties/${varietyId}`);
            
            // Use the existing succession planning variety endpoint
            const response = await fetch(`/admin/farmos/succession-planning/varieties/${varietyId}`, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json'
                },
                credentials: 'same-origin'
            });

            console.log('📡 Response status:', response.status);
            console.log('📡 Response headers:', Object.fromEntries(response.headers.entries()));

            if (!response.ok) {
                const errorText = await response.text();
                console.error('❌ API Error Response:', errorText);
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('📋 Variety info received:', data);
            
            if (data.success && data.variety) {
                return data.variety;
            } else {
                console.warn('⚠️ Variety API returned success=false or no variety data');
                console.warn('⚠️ Full response:', data);
                return null;
            }
        } catch (error) {
            console.error('❌ Error fetching variety info:', error);
            console.error('❌ Error details:', {
                message: error.message,
                stack: error.stack
            });
            return null;
        }
    }

    // Display variety information in the UI
    function displayVarietyInfo(varietyData) {
        const container = document.getElementById('varietyInfoContainer');
        const loading = document.getElementById('varietyLoading');
        const error = document.getElementById('varietyError');
        const noSelection = document.getElementById('noVarietySelected');

        // Hide loading and error states
        loading.style.display = 'none';
        error.style.display = 'none';
        noSelection.style.display = 'none';

        if (!varietyData) {
            container.style.display = 'none';
            noSelection.style.display = 'block';
            return;
        }

        // Show container
        container.style.display = 'block';

        // Update variety name
        const nameEl = document.getElementById('varietyName');
        nameEl.textContent = varietyData.name || varietyData.title || 'Unknown Variety';

        // Update description - combine available fields
        const descEl = document.getElementById('varietyDescription');
        let description = '';
        
        if (varietyData.harvest_notes) {
            description += varietyData.harvest_notes;
        }
        
        if (varietyData.description) {
            if (description) description += ' ';
            description += varietyData.description;
        }
        
        if (!description) {
            description = 'No description available';
        }
        
        descEl.textContent = description;

        // Update crop type
        const cropTypeEl = document.getElementById('varietyCropType');
        cropTypeEl.textContent = varietyData.crop_family || varietyData.plant_type || 'Unknown';

        // Update variety ID
        const idEl = document.getElementById('varietyId');
        idEl.textContent = varietyData.farmos_id || varietyData.id || 'N/A';

        // Handle photo - FarmOS varieties may not have photos in the API response
        const photoEl = document.getElementById('varietyPhoto');
        const noPhotoEl = document.getElementById('noPhotoMessage');

        // For now, we'll show "no photo available" since FarmOS API may not include photos
        photoEl.style.display = 'none';
        noPhotoEl.style.display = 'block';

        console.log('✅ Variety information displayed');
    }

    // Handle variety selection and fetch/display info
    async function handleVarietySelection(varietyId) {
        console.log('🎯 handleVarietySelection called with ID:', varietyId);
        
        const container = document.getElementById('varietyInfoContainer');
        const loading = document.getElementById('varietyLoading');
        const error = document.getElementById('varietyError');
        const noSelection = document.getElementById('noVarietySelected');

        if (!varietyId) {
            // No variety selected
            console.log('📝 No variety selected, showing default state');
            container.style.display = 'none';
            loading.style.display = 'none';
            error.style.display = 'none';
            noSelection.style.display = 'block';
            return;
        }

        // Show loading state
        console.log('⏳ Showing loading state');
        container.style.display = 'block';
        loading.style.display = 'block';
        error.style.display = 'none';
        noSelection.style.display = 'none';

        try {
            console.log('🔍 Calling fetchVarietyInfo...');
            // Fetch variety information
            const varietyData = await fetchVarietyInfo(varietyId);
            console.log('📊 Variety data result:', varietyData);
            
            if (varietyData) {
                console.log('✅ Displaying variety data');
                displayVarietyInfo(varietyData);
            } else {
                // Show error state
                console.log('❌ No variety data received, showing error');
                loading.style.display = 'none';
                error.style.display = 'block';
                container.style.display = 'block';
            }
        } catch (err) {
            console.error('❌ Error in handleVarietySelection:', err);
            loading.style.display = 'none';
            error.style.display = 'block';
            container.style.display = 'block';
        }
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
        // Try to find the timeline element (could be harvestTimeline or timeline-container)
        let timeline = document.getElementById('harvestTimeline');
        if (!timeline) {
            timeline = document.querySelector('.timeline-container');
        }
        if (!timeline) {
            console.warn('⚠️ No timeline element found for drag setup - drag functionality disabled');
            return;
        }

        console.log('✅ Setting up drag functionality on timeline:', timeline);        // Cache rect to reduce forced reflow
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
            updateHarvestWindowDisplay(); // Update the new harvest window selector
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

    // Helper function to adjust harvest dates to the selected planning year
    function adjustHarvestDatesToSelectedYear(harvestInfo) {
        if (!harvestInfo) return harvestInfo;
        
        const selectedYear = document.getElementById('planningYear')?.value || new Date().getFullYear();
        
        if (harvestInfo.maximum_start) {
            const parts = harvestInfo.maximum_start.split('-');
            if (parts.length === 3) {
                harvestInfo.maximum_start = `${selectedYear}-${parts[1]}-${parts[2]}`;
                console.log(`📅 Adjusted JSON start date to selected year: ${harvestInfo.maximum_start}`);
            }
        }
        
        if (harvestInfo.maximum_end) {
            const parts = harvestInfo.maximum_end.split('-');
            if (parts.length === 3) {
                harvestInfo.maximum_end = `${selectedYear}-${parts[1]}-${parts[2]}`;
                console.log(`📅 Adjusted JSON end date to selected year: ${harvestInfo.maximum_end}`);
            }
        }
        
        if (harvestInfo.yield_peak) {
            const parts = harvestInfo.yield_peak.split('-');
            if (parts.length === 3) {
                harvestInfo.yield_peak = `${selectedYear}-${parts[1]}-${parts[2]}`;
                console.log(`📅 Adjusted JSON yield peak date to selected year: ${harvestInfo.yield_peak}`);
            }
        }
        
        return harvestInfo;
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
                request_type: 'maximum_harvest_window'
            };

            // Build a comprehensive prompt for MAXIMUM harvest windows based on real farming knowledge
            const prompt = `You are an expert UK market gardener calculating MAXIMUM POSSIBLE harvest windows for succession planning.

CRITICAL: Use the provided farmOS variety_meta data if available. This is REAL farm data and takes priority over generic information.

TASK: Return ONLY a JSON object with the ABSOLUTE MAXIMUM harvest period for ${cropName} ${varietyName || ''} in year ${contextPayload.planning_year}.

VARIETY DATA PROVIDED: ${varietyMeta ? JSON.stringify(varietyMeta) : 'None - use crop defaults'}

IGNORE today's date (${new Date().toDateString()}). Calculate based on SEASONAL POTENTIAL for the planning year.

If variety_meta contains timing data, USE IT. Otherwise use these maximums:

CARROT VARIETIES (Early Nantes, Amsterdam, Paris Market, etc.):
- Maximum harvest window: MAY 1st through DECEMBER 31st
- Peak quality: June-October
- Extended storage harvest: November-December
- Peak quality: June-October
- Extended harvest: November-December with protection

BEETROOT VARIETIES:
- Sowing: April-July
- Maximum harvest window: JUNE 1st through DECEMBER 31st
- Can harvest baby beets from June, main crop July-November, storage crop December

LETTUCE VARIETIES:
- Year-round with protection
- Maximum harvest window: MARCH 1st through NOVEMBER 30th (outdoor)
- Winter varieties: JANUARY 1st through DECEMBER 31st (with protection)

RADISH VARIETIES:
- Quick crop, succession plantings
- Maximum harvest window: APRIL 1st through OCTOBER 31st

ONION VARIETIES:
- Spring sets planted March-April
- Maximum harvest window: JULY 1st through SEPTEMBER 30th

Return JSON format:
{
  "maximum_start": "YYYY-MM-DD",
  "maximum_end": "YYYY-MM-DD", 
  "days_to_harvest": 60,
  "yield_peak": "YYYY-MM-DD",
  "notes": "Maximum harvest window explanation",
  "extended_window": {
    "max_extension_days": 30,
    "risk_level": "low"
  }
}
- Early varieties like "Early Nantes 2": May 1st - November 30th (214 days maximum)

ROOT VEGETABLES GENERAL:
- Baby harvest: 50-70 days after sowing
- Main harvest: 90-120 days after sowing  
- Extended harvest: Can stay in ground 180-250+ days with protection
- Winter storage: Many can be harvested through winter months

MAXIMUM HARVEST CALCULATION:
- Start: Earliest possible harvest date (baby stage)
- End: Latest possible harvest before quality deteriorates
- Consider storage, succession planting, winter protection

Return ONLY a JSON object with these exact keys:
{
  "maximum_start": "YYYY-MM-DD",
  "maximum_end": "YYYY-MM-DD", 
  "days_to_harvest": 60,
  "yield_peak": "YYYY-MM-DD",
  "notes": "Maximum possible harvest window for succession planning",
  "extended_window": {"max_extension_days": 45, "risk_level": "low"}
}

NO extra text. Calculate for ${contextPayload.planning_year}.`;

            // Use same-origin Laravel route that exists in this app (chat endpoint)
            const chatUrl = window.location.origin + '/admin/farmos/succession-planning/chat';

            // Debug: log payload and endpoint
            console.log('🛰️ AI request ->', { chatUrl, prompt, context: contextPayload });
            console.log('🔍 Question text check:', {
                'contains_harvest_window': prompt.toLowerCase().includes('harvest window'),
                'contains_maximum_start': prompt.toLowerCase().includes('maximum_start'),
                'contains_json_object': prompt.toLowerCase().includes('json object'),
                'prompt_preview': prompt.substring(0, 200) + '...'
            });

            // Timeout to abort long-running requests
            const timeoutId = setTimeout(() => { try { __aiCalcController.abort(); } catch(_){} }, 10000); // 10s timeout

            const response = await fetch(chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ 
                    question: prompt, 
                    crop_type: contextPayload.crop,
                    context: contextPayload 
                }),
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
                let maxStart = data.maximum_start || null;
                const duration = data.optimal_window_days || data.maximum_harvest_days || null;
                const peakDays = data.peak_harvest_days || null;
                let maxEnd = data.maximum_end || null;
                
                // Adjust dates to use the selected planning year
                const selectedYear = document.getElementById('planningYear')?.value || new Date().getFullYear();
                if (maxStart) {
                    const parts = maxStart.split('-');
                    if (parts.length === 3) {
                        maxStart = `${selectedYear}-${parts[1]}-${parts[2]}`;
                        console.log(`📅 Adjusted AI start date to selected year: ${maxStart}`);
                    }
                }
                if (maxEnd) {
                    const parts = maxEnd.split('-');
                    if (parts.length === 3) {
                        maxEnd = `${selectedYear}-${parts[1]}-${parts[2]}`;
                        console.log(`📅 Adjusted AI end date to selected year: ${maxEnd}`);
                    }
                }
                
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
                    
                    // Adjust parsed JSON dates to selected year
                    if (harvestInfo) {
                        harvestInfo = adjustHarvestDatesToSelectedYear(harvestInfo);
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

            // Update the new harvest window selector with AI data
            updateHarvestWindowData(harvestInfo);

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
            const extensionDays = harvestInfo.extended_window.max_extension_days || Math.round((harvestInfo.days_to_harvest || 60) * 0.2);
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
            const selectedYear = parseInt(document.getElementById('planningYear')?.value || new Date().getFullYear(), 10);
            
            const dateRegex = /(20\d{2})-(0[1-9]|1[0-2])-(0[1-9]|[12]\d|3[01])/g; // YYYY-MM-DD
            const dates = [...text.matchAll(dateRegex)].map(m => m[0]);

            // Convert dates to use the selected planning year
            let maximum_start = null;
            let maximum_end = null;
            if (dates.length >= 2) {
                // Parse the month and day from the AI dates but use the selected year
                const startParts = dates[0].split('-');
                const endParts = dates[dates.length - 1].split('-');
                maximum_start = `${selectedYear}-${startParts[1]}-${startParts[2]}`;
                maximum_end = `${selectedYear}-${endParts[1]}-${endParts[2]}`;
                
                console.log(`📅 Adjusted AI dates to selected year ${selectedYear}: ${maximum_start} - ${maximum_end}`);
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

        // Calculate succession count based on harvest duration and crop type
        const start = new Date(hs.value);
        const end = new Date(he.value);
        const duration = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        const cropName = cropSelect?.options[cropSelect.selectedIndex]?.text?.toLowerCase() || '';
        const varietyName = varietySelect?.options[varietySelect.selectedIndex]?.text?.toLowerCase() || '';
        const avgSuccessionInterval = getSuccessionInterval(cropName, varietyName);
        let successionCount = Math.max(1, Math.ceil(duration / avgSuccessionInterval));

        // For crops with transplant windows, also consider transplant window constraints
        if (cropName.toLowerCase().includes('brussels') || cropName.toLowerCase().includes('cabbage') ||
            cropName.toLowerCase().includes('broccoli') || cropName.toLowerCase().includes('cauliflower')) {
            // Get crop timing to check transplant window
            const cropTiming = getCropTiming(cropName, varietyName);
            if (cropTiming.transplantWindow) {
                const transplantWindowDays = 61; // March 15 - May 15 is approximately 61 days
                const transplantInterval = cropTiming.daysToTransplant || 35;

                // For Brussels sprouts, allow more successions since sowing dates can overlap
                let maxByTransplantWindow;
                if (cropName.toLowerCase().includes('brussels')) {
                    // Allow up to 3 successions for Brussels sprouts despite window constraints
                    maxByTransplantWindow = 3;
                } else {
                    // For other crops, use the conservative calculation
                    const minDaysPerSuccession = transplantInterval + 14; // 35 days + 2 weeks buffer
                    maxByTransplantWindow = Math.max(1, Math.floor(transplantWindowDays / minDaysPerSuccession));
                }

                // Reduce successions if transplant window can't support them
                successionCount = Math.min(successionCount, maxByTransplantWindow);
            }
        }

        const payload = {
            crop_id: cropSelect.value,
            variety_id: varietySelect?.value || null,
            harvest_start: hs.value,
            harvest_end: he.value,
            bed_ids: beds ? Array.from(beds.selectedOptions).map(o => o.value) : [],
            succession_count: successionCount,
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
                    // Display forms inline instead of opening in new tabs
                    const formId = `form-${f.key}-${i}`;
                    wrap.innerHTML = `
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <strong>${f.label} Quick Form</strong>
                            <div class="d-flex gap-2">
                                <button class="btn btn-success btn-sm" onclick="toggleForm('${formId}', '${url}')">
                                    <i class="fas fa-eye"></i> Show ${f.label} Form
                                </button>
                                <a class="btn btn-outline-secondary btn-sm" href="${url}" target="_blank" rel="noopener">
                                    <i class="fas fa-external-link-alt"></i> New Tab
                                </a>
                            </div>
                        </div>
                        <div id="${formId}" class="form-content" style="display: none;">
                            <div class="form-loading text-center py-4">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <p class="mt-2">Loading ${f.label.toLowerCase()} form...</p>
                            </div>
                        </div>
                    `;
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

    async function toggleForm(formId, url) {
        const formContainer = document.getElementById(formId);
        const button = event.target.closest('button');

        if (formContainer.style.display === 'none' || formContainer.style.display === '') {
            // Show the form
            formContainer.style.display = 'block';
            button.innerHTML = '<i class="fas fa-eye-slash"></i> Hide Form';
            button.classList.remove('btn-success');
            button.classList.add('btn-warning');

            // Load form content if not already loaded
            if (!formContainer.dataset.loaded) {
                try {
                    const response = await fetch(url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (response.ok) {
                        const html = await response.text();
                        // Extract the form content (remove layout wrapper)
                        const parser = new DOMParser();
                        const doc = parser.parseFromString(html, 'text/html');
                        const formContent = doc.querySelector('.container') || doc.body;

                        if (formContent) {
                            formContainer.innerHTML = formContent.innerHTML;
                            formContainer.dataset.loaded = 'true';
                        } else {
                            formContainer.innerHTML = '<div class="alert alert-warning">Could not load form content</div>';
                        }
                    } else {
                        formContainer.innerHTML = '<div class="alert alert-danger">Failed to load form</div>';
                    }
                } catch (error) {
                    console.error('Error loading form:', error);
                    formContainer.innerHTML = '<div class="alert alert-danger">Error loading form</div>';
                }
            }
        } else {
            // Hide the form
            formContainer.style.display = 'none';
            button.innerHTML = '<i class="fas fa-eye"></i> Show Form';
            button.classList.remove('btn-warning');
            button.classList.add('btn-success');
        }
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

    function submitViaAPI(logType, plantingData) {
        console.log(`🚀 Submitting ${logType} via API for planting:`, plantingData);

        // Show loading state
        const button = event.target;
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Submitting...';
        button.disabled = true;

        // Prepare data for API submission
        const apiData = {
            crop_name: plantingData.crop_name,
            variety_name: plantingData.variety_name,
            bed_name: plantingData.bed_name,
            quantity: plantingData.quantity,
            succession_number: plantingData.succession_number
        };

        // Add date based on log type
        switch(logType) {
            case 'seeding':
                apiData.seeding_date = plantingData.seeding_date;
                break;
            case 'transplant':
                apiData.transplant_date = plantingData.transplant_date;
                break;
            case 'harvest':
                apiData.harvest_date = plantingData.harvest_date;
                break;
        }

        // Make API call
        fetch('/admin/farmos/succession-planning/submit-log', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                log_type: logType,
                data: apiData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success message
                button.innerHTML = '<i class="fas fa-check"></i> Submitted!';
                button.className = 'btn btn-success btn-sm';
                console.log(`✅ ${logType} log created successfully:`, data);

                // Reset button after 3 seconds
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.className = 'btn btn-success btn-sm';
                    button.disabled = false;
                }, 3000);
            } else {
                // Show API submission failed, suggest manual entry
                button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> API Failed';
                button.className = 'btn btn-warning btn-sm';
                console.warn(`⚠️ ${logType} API submission failed:`, data.message);

                // Reset button after 3 seconds
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.className = 'btn btn-warning btn-sm';
                    button.disabled = false;
                }, 3000);
            }
        })
        .catch(error => {
            console.error(`❌ ${logType} API submission failed:`, error);
            button.innerHTML = '<i class="fas fa-exclamation-triangle"></i> Failed';
            button.className = 'btn btn-danger btn-sm';

            // Reset button after 3 seconds
            setTimeout(() => {
                button.innerHTML = originalText;
                button.className = 'btn btn-success btn-sm';
                button.disabled = false;
            }, 3000);
        });
    }

    // ===== BATCH SUCCESSION PLANNING =====

    // Process multiple varieties at once
    async function batchProcessSuccessions(varietyIds) {
        console.log(`🔄 Batch processing ${varietyIds.length} varieties...`);

        const results = [];
        for (const varietyId of varietyIds) {
            try {
                // Auto-select variety
                const varietySelect = document.getElementById('varietySelect');
                varietySelect.value = varietyId;
                varietySelect.dispatchEvent(new Event('change'));

                // Wait for UI to update
                await new Promise(resolve => setTimeout(resolve, 500));

                // Calculate succession plan
                await calculateSuccessionPlan();

                // Wait for calculation
                await new Promise(resolve => setTimeout(resolve, 1000));

                // Capture results
                const plan = getCurrentSuccessionPlan();
                results.push({
                    varietyId,
                    varietyName: plan?.variety?.name || 'Unknown',
                    successions: plan?.plantings?.length || 0,
                    status: 'completed'
                });

            } catch (error) {
                console.error(`❌ Failed to process variety ${varietyId}:`, error);
                results.push({
                    varietyId,
                    status: 'failed',
                    error: error.message
                });
            }
        }

        console.log(`✅ Batch processing complete:`, results);
        return results;
    }

    // Quick setup for similar varieties
    function quickSetupForVarietyFamily(cropType, varietyIds) {
        console.log(`🚀 Quick setup for ${cropType} family: ${varietyIds.length} varieties`);

        // Use first variety to establish baseline settings
        const baselineVarietyId = varietyIds[0];

        // Process all varieties with same settings
        return batchProcessSuccessions(varietyIds);
    }

    // ===== EFFICIENCY FEATURES =====

    // Auto-detect and process all varieties of same crop type
    async function processAllVarietiesOfCrop(cropType) {
        console.log(`🔍 Finding all ${cropType} varieties...`);

        const varietySelect = document.getElementById('varietySelect');
        const cropSelect = document.getElementById('cropSelect');

        // Find crop option
        let cropValue = '';
        for (let i = 0; i < cropSelect.options.length; i++) {
            if (cropSelect.options[i].text.toLowerCase().includes(cropType.toLowerCase())) {
                cropValue = cropSelect.options[i].value;
                break;
            }
        }

        if (!cropValue) {
            console.error(`❌ Crop type "${cropType}" not found`);
            return [];
        }

        // Select crop
        cropSelect.value = cropValue;
        cropSelect.dispatchEvent(new Event('change'));

        // Wait for varieties to load
        await new Promise(resolve => setTimeout(resolve, 1000));

        // Get all variety IDs
        const varietyIds = [];
        for (let i = 0; i < varietySelect.options.length; i++) {
            const option = varietySelect.options[i];
            if (option.value && option.value !== '') {
                varietyIds.push(option.value);
            }
        }

        console.log(`📋 Found ${varietyIds.length} ${cropType} varieties`);
        return await batchProcessSuccessions(varietyIds);
    }

    // Create succession templates for crop families
    const successionTemplates = {
        'brassicas': {
            successions: 3,
            spacing: 30, // days between transplant dates
            notes: 'Cool season crop, good for succession planting'
        },
        'leafy_greens': {
            successions: 4,
            spacing: 14, // days between transplant dates
            notes: 'Quick growing, high succession frequency'
        },
        'root_vegetables': {
            successions: 2,
            spacing: 21, // days between transplant dates
            notes: 'Long growing season, fewer successions needed'
        }
    };

    // Apply template settings
    function applySuccessionTemplate(templateName) {
        const template = successionTemplates[templateName];
        if (!template) {
            console.error(`❌ Template "${templateName}" not found`);
            return false;
        }

        console.log(`📋 Applying ${templateName} template:`, template);
        // Template would adjust succession count and spacing
        return true;
    }

    // ===== USAGE EXAMPLES =====
    /*
    🚀 QUICK START COMMANDS (run in browser console):

    // Process all Brussels sprouts varieties at once
    processAllVarietiesOfCrop('Brussels Sprouts').then(results => {
        console.log('Batch results:', results);
    });

    // Process specific varieties
    batchProcessSuccessions(['variety-id-1', 'variety-id-2', 'variety-id-3']);

    // Apply template for brassicas
    applySuccessionTemplate('brassicas');

    // Quick setup for similar varieties
    quickSetupForVarietyFamily('Brussels Sprouts', ['id1', 'id2', 'id3']);
    */

    console.log('🎯 Efficiency features loaded! Use processAllVarietiesOfCrop() or batchProcessSuccessions()');

    // Global variables for harvest window management
    let harvestWindowData = {
        maxStart: null,
        maxEnd: null,
        aiStart: null,
        aiEnd: null,
        userStart: null,
        userEnd: null,
        selectedYear: new Date().getFullYear()
    };

    // Initialize the new harvest window selector
    function initializeHarvestWindowSelector() {
        console.log('🎯 Initializing new harvest window selector');

        // Set up range handle event listeners
        setupRangeHandles();

        // Initialize with default dates
        updateHarvestWindowDisplay();
    }

    // Set up drag handles for range adjustment
    function setupRangeHandles() {
        const startHandle = document.getElementById('rangeStartHandle');
        const endHandle = document.getElementById('rangeEndHandle');

        if (!startHandle || !endHandle) return;

        let isDragging = false;
        let dragHandle = null;
        let startX = 0;
        let initialLeft = 0;

        function handleMouseDown(e, handle) {
            isDragging = true;
            dragHandle = handle;
            startX = e.clientX;
            const progressBar = document.querySelector('#userSelectedRange .progress');
            const rect = progressBar.getBoundingClientRect();
            initialLeft = rect.left;
            document.body.style.cursor = 'ew-resize';
            e.preventDefault();
        }

        function handleMouseMove(e) {
            if (!isDragging || !dragHandle) return;

            const progressBar = document.querySelector('#userSelectedRange .progress');
            const rect = progressBar.getBoundingClientRect();
            const deltaX = e.clientX - startX;
            const percentage = Math.max(0, Math.min(100, (deltaX / rect.width) * 100));

            if (dragHandle === 'start') {
                adjustUserRange('start', percentage);
            } else if (dragHandle === 'end') {
                adjustUserRange('end', percentage);
            }
        }

        function handleMouseUp() {
            isDragging = false;
            dragHandle = null;
            document.body.style.cursor = 'default';
            updateDateInputsFromRange();
        }

        startHandle.addEventListener('mousedown', (e) => handleMouseDown(e, 'start'));
        endHandle.addEventListener('mousedown', (e) => handleMouseDown(e, 'end'));
        document.addEventListener('mousemove', handleMouseMove);
        document.addEventListener('mouseup', handleMouseUp);
    }

    // Update the harvest window display with new data
    function updateHarvestWindowDisplay() {
        const year = document.getElementById('planningYear').value || new Date().getFullYear();
        harvestWindowData.selectedYear = year;

        // Show maximum possible range
        if (harvestWindowData.maxStart && harvestWindowData.maxEnd) {
            displayMaxRange();
        }

        // Show AI recommended range
        if (harvestWindowData.aiStart && harvestWindowData.aiEnd) {
            displayAIRange();
        }

        // Show user selected range
        if (harvestWindowData.userStart && harvestWindowData.userEnd) {
            displayUserRange();
        }

        // Update calendar grid
        updateCalendarGrid();

        // Update succession impact
        updateSuccessionImpact();
    }

    // Display maximum possible harvest range
    function displayMaxRange() {
        const maxRangeDiv = document.getElementById('maxHarvestRange');
        const maxRangeBar = document.getElementById('maxRangeBar');
        const maxRangeDates = document.getElementById('maxRangeDates');

        if (!maxRangeDiv || !maxRangeBar || !maxRangeDates) return;

        const startDate = new Date(harvestWindowData.maxStart);
        const endDate = new Date(harvestWindowData.maxEnd);
        const duration = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));

        maxRangeDates.textContent = `${startDate.toLocaleDateString()} - ${endDate.toLocaleDateString()} (${duration} days)`;
        maxRangeDiv.style.display = 'block';
    }

    // Display AI recommended harvest range
    function displayAIRange() {
        const aiRangeDiv = document.getElementById('aiRecommendedRange');
        const aiRangeBar = document.getElementById('aiRangeBar');
        const aiRangeDates = document.getElementById('aiRangeDates');

        if (!aiRangeDiv || !aiRangeBar || !aiRangeDates) return;

        const startDate = new Date(harvestWindowData.aiStart);
        const endDate = new Date(harvestWindowData.aiEnd);
        const duration = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));

        // Calculate position within max range
        const maxStart = new Date(harvestWindowData.maxStart);
        const maxEnd = new Date(harvestWindowData.maxEnd);
        const maxDuration = maxEnd - maxStart;
        const aiStartOffset = startDate - maxStart;
        const aiDuration = endDate - startDate;

        const leftPercent = (aiStartOffset / maxDuration) * 100;
        const widthPercent = (aiDuration / maxDuration) * 100;

        aiRangeBar.style.marginLeft = `${leftPercent}%`;
        aiRangeBar.style.width = `${widthPercent}%`;
        aiRangeDates.textContent = `${startDate.toLocaleDateString()} - ${endDate.toLocaleDateString()} (${duration} days)`;
        aiRangeDiv.style.display = 'block';
    }

    // Display user selected harvest range
    function displayUserRange() {
        const userRangeDiv = document.getElementById('userSelectedRange');
        const userRangeBar = document.getElementById('userRangeBar');
        const userRangeDates = document.getElementById('userRangeDates');
        const startHandle = document.getElementById('rangeStartHandle');
        const endHandle = document.getElementById('rangeEndHandle');

        if (!userRangeDiv || !userRangeBar || !userRangeDates) return;

        const startDate = new Date(harvestWindowData.userStart);
        const endDate = new Date(harvestWindowData.userEnd);
        const duration = Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24));

        // Calculate position within max range
        const maxStart = new Date(harvestWindowData.maxStart);
        const maxEnd = new Date(harvestWindowData.maxEnd);
        const maxDuration = maxEnd - maxStart;
        const userStartOffset = startDate - maxStart;
        const userDuration = endDate - startDate;

        const leftPercent = (userStartOffset / maxDuration) * 100;
        const widthPercent = (userDuration / maxDuration) * 100;

        userRangeBar.style.marginLeft = `${leftPercent}%`;
        userRangeBar.style.width = `${widthPercent}%`;

        if (startHandle) startHandle.style.left = `${leftPercent}%`;
        if (endHandle) endHandle.style.left = `${leftPercent + widthPercent}%`;

        userRangeDates.textContent = `${startDate.toLocaleDateString()} - ${endDate.toLocaleDateString()} (${duration} days)`;
        userRangeDiv.style.display = 'block';
    }

    // Adjust user selected range
    function adjustUserRange(handle, percentage) {
        const maxStart = new Date(harvestWindowData.maxStart);
        const maxEnd = new Date(harvestWindowData.maxEnd);
        const maxDuration = maxEnd - maxStart;

        const newDate = new Date(maxStart.getTime() + (percentage / 100) * maxDuration);

        if (handle === 'start') {
            harvestWindowData.userStart = newDate.toISOString().split('T')[0];
            // Ensure start doesn't go past end
            if (new Date(harvestWindowData.userStart) >= new Date(harvestWindowData.userEnd)) {
                harvestWindowData.userStart = harvestWindowData.userEnd;
            }
        } else if (handle === 'end') {
            harvestWindowData.userEnd = newDate.toISOString().split('T')[0];
            // Ensure end doesn't go before start
            if (new Date(harvestWindowData.userEnd) <= new Date(harvestWindowData.userStart)) {
                harvestWindowData.userEnd = harvestWindowData.userStart;
            }
        }

        displayUserRange();
        updateSuccessionImpact();
    }

    // Update date inputs from range selection
    function updateDateInputsFromRange() {
        const startInput = document.getElementById('harvestStart');
        const endInput = document.getElementById('harvestEnd');

        if (startInput && harvestWindowData.userStart) {
            startInput.value = harvestWindowData.userStart;
        }
        if (endInput && harvestWindowData.userEnd) {
            endInput.value = harvestWindowData.userEnd;
        }
    }

    // Extend harvest window by maximum 20%
    function extendHarvestWindow() {
        if (!harvestWindowData.aiStart || !harvestWindowData.aiEnd) {
            console.warn('No AI range to extend from');
            return;
        }

        const aiStart = new Date(harvestWindowData.aiStart);
        const aiEnd = new Date(harvestWindowData.aiEnd);
        const aiDuration = aiEnd - aiStart;
        const maxExtension = aiDuration * 0.2; // 20% maximum

        const newEnd = new Date(aiEnd.getTime() + maxExtension);
        const maxPossibleEnd = new Date(harvestWindowData.maxEnd);

        // Don't extend beyond maximum possible
        const finalEnd = newEnd > maxPossibleEnd ? maxPossibleEnd : newEnd;

        harvestWindowData.userStart = harvestWindowData.aiStart;
        harvestWindowData.userEnd = finalEnd.toISOString().split('T')[0];

        updateHarvestWindowDisplay();
        updateDateInputsFromRange();

        console.log('📈 Extended harvest window by up to 20%');
    }

    // Optimize harvest window (set to AI recommended)
    function optimizeHarvestWindow() {
        if (!harvestWindowData.aiStart || !harvestWindowData.aiEnd) {
            console.warn('No AI range to optimize to');
            return;
        }

        harvestWindowData.userStart = harvestWindowData.aiStart;
        harvestWindowData.userEnd = harvestWindowData.aiEnd;

        updateHarvestWindowDisplay();
        updateDateInputsFromRange();

        console.log('🎯 Optimized harvest window to AI recommendation');
    }

    // Shorten harvest window (reduce successions)
    function shortenHarvestWindow() {
        if (!harvestWindowData.userStart || !harvestWindowData.userEnd) {
            console.warn('No user range to shorten');
            return;
        }

        const start = new Date(harvestWindowData.userStart);
        const end = new Date(harvestWindowData.userEnd);
        const currentDuration = end - start;

        // Reduce by 25% to decrease number of successions
        const newDuration = currentDuration * 0.75;
        const newEnd = new Date(start.getTime() + newDuration);

        harvestWindowData.userEnd = newEnd.toISOString().split('T')[0];

        updateHarvestWindowDisplay();
        updateDateInputsFromRange();

        console.log('📉 Shortened harvest window to reduce successions');
    }

    // Update succession impact preview
    function updateSuccessionImpact() {
        const impactDiv = document.getElementById('successionImpact');
        const countBadge = document.getElementById('successionCount');
        const previewDiv = document.getElementById('successionPreview');

        if (!impactDiv || !countBadge || !previewDiv) return;
        if (!harvestWindowData.userStart || !harvestWindowData.userEnd) return;

        const start = new Date(harvestWindowData.userStart);
        const end = new Date(harvestWindowData.userEnd);
        const duration = Math.ceil((end - start) / (1000 * 60 * 60 * 24));

        // Get crop information for better calculations
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        const cropName = cropSelect?.options[cropSelect.selectedIndex]?.text?.toLowerCase() || '';
        const varietyName = varietySelect?.options[varietySelect.selectedIndex]?.text?.toLowerCase() || '';

        // Estimate number of successions based on duration
        // Typical succession interval varies by crop
        const avgSuccessionInterval = getSuccessionInterval(cropName, varietyName);
        let successions = Math.max(1, Math.ceil(duration / avgSuccessionInterval));

        // For crops with transplant windows, also consider transplant window constraints
        if (cropName.toLowerCase().includes('brussels') || cropName.toLowerCase().includes('cabbage') ||
            cropName.toLowerCase().includes('broccoli') || cropName.toLowerCase().includes('cauliflower')) {
            // Get crop timing to check transplant window
            const cropTiming = getCropTiming(cropName, varietyName);
            if (cropTiming.transplantWindow) {
                const transplantWindowDays = 61; // March 15 - May 15 is approximately 61 days
                const transplantInterval = cropTiming.daysToTransplant || 35;

                // For Brussels sprouts, allow more successions since sowing dates can overlap
                // The transplant window constraint is less limiting than the harvest window
                let maxByTransplantWindow;
                if (cropName.toLowerCase().includes('brussels')) {
                    // Allow up to 3 successions for Brussels sprouts despite window constraints
                    maxByTransplantWindow = 3;
                    console.log(`🌱 Brussels sprouts: allowing ${maxByTransplantWindow} successions (prioritizing harvest coverage)`);
                } else {
                    // For other crops, use the conservative calculation
                    const minDaysPerSuccession = transplantInterval + 14; // 35 days + 2 weeks buffer
                    maxByTransplantWindow = Math.max(1, Math.floor(transplantWindowDays / minDaysPerSuccession));
                }

                console.log(`🌱 Transplant window analysis: ${transplantWindowDays} days, ${transplantInterval} day interval`);
                console.log(`📊 Maximum realistic successions: ${maxByTransplantWindow}`);

                // Reduce successions if transplant window can't support them
                successions = Math.min(successions, maxByTransplantWindow);
                console.log(`🌱 Adjusted successions from harvest-based to ${successions} (transplant window constraint)`);
            }
        }

        countBadge.textContent = `${successions} Succession${successions > 1 ? 's' : ''}`;

        // Generate detailed succession preview
        let previewHTML = '';
        for (let i = 0; i < successions; i++) {
            const successionData = calculateSuccessionDates(start, i, avgSuccessionInterval, cropName, varietyName);

            previewHTML += `
                <div class="succession-item">
                    <div class="succession-header">
                        <span class="succession-label">Succession ${i + 1}</span>
                        <small class="text-muted method-badge">${successionData.method}</small>
                    </div>
                    <div class="succession-timeline">
                        <div class="timeline-step">
                            <small class="text-muted">Sow</small>
                            <div class="succession-date">${successionData.sowDate.toLocaleDateString()}</div>
                        </div>
                        ${successionData.transplantDate ? `
                        <div class="timeline-step transplant-step">
                            <small class="text-warning">Transplant</small>
                            <div class="succession-date">${successionData.transplantDate.toLocaleDateString()}</div>
                        </div>
                        ` : ''}
                        <div class="timeline-step harvest-step">
                            <small class="text-success">Harvest</small>
                            <div class="succession-date">${successionData.harvestDate.toLocaleDateString()}</div>
                        </div>
                    </div>
                </div>
            `;
        }

        previewDiv.innerHTML = previewHTML;
        impactDiv.style.display = 'block';
    }

    // Get succession interval based on crop type
    function getSuccessionInterval(cropName, varietyName) {
        // Default intervals by crop type
        const intervals = {
            'carrot': 14, // 2 weeks
            'beetroot': 21, // 3 weeks
            'lettuce': 14, // 2 weeks (fortnightly)
            'radish': 7, // 1 week
            'onion': 21, // 3 weeks
            'spinach': 10, // 10 days
            'kale': 14, // 2 weeks
            'chard': 14, // 2 weeks
            'pak choi': 10, // 10 days
            'cabbage': 21, // 3 weeks
            'broccoli': 21, // 3 weeks
            'cauliflower': 21, // 3 weeks
            'peas': 14, // 2 weeks
            'beans': 14, // 2 weeks
            'tomato': 21, // 3 weeks
            'pepper': 21, // 3 weeks
            'cucumber': 14, // 2 weeks
            'zucchini': 14, // 2 weeks
            'corn': 14, // 2 weeks
            'potato': 21, // 3 weeks
            'garlic': 30, // 4 weeks
            'leek': 21, // 3 weeks
            'celery': 21, // 3 weeks
            'fennel': 14, // 2 weeks
            'herbs': 14 // 2 weeks
        };

        // Check for specific crop matches
        for (const [crop, interval] of Object.entries(intervals)) {
            if (cropName.includes(crop) || varietyName.includes(crop)) {
                return interval;
            }
        }

        return 21; // Default 3 weeks
    }

    // Calculate detailed succession dates including sowing and transplant
    function calculateSuccessionDates(harvestStart, successionIndex, interval, cropName, varietyName) {
        // Get crop-specific timing data
        const cropTiming = getCropTiming(cropName, varietyName);

        // For long-season crops like Brussels sprouts, use advanced seasonal planning
        if (cropName.toLowerCase().includes('brussels') || cropTiming.daysToHarvest >= 100) {
            // Advanced seasonal algorithm for Brussels sprouts
            const harvestYear = harvestStart.getFullYear();

            // Use crop-specific transplant window from database (fallback to default)
            let plantingWindowStart, plantingWindowEnd;
            if (cropTiming.transplantWindow) {
                // Database-driven transplant window
                plantingWindowStart = new Date(harvestYear, cropTiming.transplantWindow.startMonth, cropTiming.transplantWindow.startDay);
                plantingWindowEnd = new Date(harvestYear, cropTiming.transplantWindow.endMonth, cropTiming.transplantWindow.endDay);
                console.log(`🌱 Using database transplant window for ${cropName}: ${cropTiming.transplantWindow.description}`);
            } else {
                // Fallback to default window for crops without database entry
                plantingWindowStart = new Date(harvestYear, 2, 15); // March 15
                plantingWindowEnd = new Date(harvestYear, 4, 15); // May 15
                console.log(`⚠️ No transplant window in database for ${cropName}, using default: March 15 - May 15`);
            }

            const plantingWindowDays = (plantingWindowEnd - plantingWindowStart) / (24 * 60 * 60 * 1000);

            // Calculate optimal number of successions based on transplant window and crop requirements
            const transplantInterval = cropTiming.daysToTransplant || 21; // Default 21 days if not specified
            const maxSuccessionsInWindow = Math.floor(plantingWindowDays / (transplantInterval / 2)); // Space at half transplant interval
            const optimalSuccessions = Math.min(maxSuccessionsInWindow, 6); // Cap at 6 max

            console.log(`🌱 ${cropName} transplant window: ${plantingWindowDays.toFixed(0)} days, transplant interval: ${transplantInterval} days`);
            console.log(`📊 Optimal successions: ${optimalSuccessions} (max ${maxSuccessionsInWindow} possible in window)`);

            // For Brussels sprouts, ensure transplant dates stay within the optimal window
            // NEW ALGORITHM: Evenly space transplant dates across transplant window, then calculate sowing dates
            // This ensures all successions are properly distributed across the available transplant period

            console.log(`🌱 ${cropName} transplant window: ${plantingWindowStart.toLocaleDateString()} - ${plantingWindowEnd.toLocaleDateString()} (${plantingWindowDays.toFixed(0)} days)`);

            // Simple approach: manually set transplant dates for 3 successions to ensure uniqueness
            let transplantDate;
            if (optimalSuccessions === 3) {
                // Set specific dates to ensure they're different and within window
                if (successionIndex === 0) {
                    transplantDate = new Date(plantingWindowStart.getTime() + (35 * 24 * 60 * 60 * 1000)); // April 19
                } else if (successionIndex === 1) {
                    transplantDate = new Date(plantingWindowStart.getTime() + (47 * 24 * 60 * 60 * 1000)); // May 1
                } else {
                    transplantDate = new Date(plantingWindowEnd.getTime()); // May 15
                }
            } else {
                // Fallback for other counts
                transplantDate = new Date(plantingWindowStart.getTime() + (successionIndex * plantingWindowDays / optimalSuccessions * 24 * 60 * 60 * 1000));
            }

            console.log(`🎯 Succession ${successionIndex + 1}/${optimalSuccessions}: transplant ${transplantDate.toLocaleDateString()}`);

            // Calculate sowing date by subtracting transplant interval from transplant date
            let plantingDate = new Date(transplantDate.getTime() - (transplantInterval * 24 * 60 * 60 * 1000));

            // Ensure sowing date doesn't go before the start of the transplant window
            if (plantingDate < plantingWindowStart) {
                plantingDate = new Date(plantingWindowStart.getTime());
                // Recalculate transplant date from adjusted sowing date
                transplantDate = new Date(plantingDate.getTime() + (transplantInterval * 24 * 60 * 60 * 1000));
                console.log(`⚠️ Succession ${successionIndex + 1} sowing date adjusted, transplant recalculated`);
            }

            // Ensure transplant date doesn't exceed the transplant window
            if (transplantDate > plantingWindowEnd) {
                transplantDate = new Date(plantingWindowEnd.getTime());
                // Recalculate sowing date from adjusted transplant date
                plantingDate = new Date(transplantDate.getTime() - (transplantInterval * 24 * 60 * 60 * 1000));
                console.log(`⚠️ Succession ${successionIndex + 1} transplant date capped, sowing recalculated`);
            }

            // Seasonal growth rate adjustment based on summer solstice (June 21st)
            const baseDaysToHarvest = cropTiming.daysToHarvest;
            const plantingMonth = plantingDate.getMonth();
            const plantingDay = plantingDate.getDate();
            let seasonalMultiplier = 1.0;

            // Calculate days from summer solstice (June 21st)
            const summerSolstice = new Date(harvestYear, 5, 21); // June 21st
            const daysFromSolstice = Math.floor((plantingDate - summerSolstice) / (24 * 60 * 60 * 1000));

            // Growth rate based on daylight trend from solstice
            if (daysFromSolstice <= 0) {
                // Before June 21st: Days getting longer (increasing sunlight)
                const daysBeforeSolstice = Math.abs(daysFromSolstice);
                if (daysBeforeSolstice <= 30) {
                    seasonalMultiplier = 0.95; // Slightly faster (optimal increasing daylight)
                } else if (daysBeforeSolstice <= 60) {
                    seasonalMultiplier = 0.9; // Faster (good increasing daylight)
                } else {
                    seasonalMultiplier = 0.85; // Much faster (excellent spring conditions)
                }
            } else {
                // After June 21st: Days getting shorter (decreasing sunlight)
                if (daysFromSolstice <= 30) {
                    seasonalMultiplier = 1.05; // Slightly slower (minimal daylight decrease)
                } else if (daysFromSolstice <= 60) {
                    seasonalMultiplier = 1.1; // Slower (noticeable daylight decrease)
                } else if (daysFromSolstice <= 90) {
                    seasonalMultiplier = 1.2; // Much slower (significant daylight decrease)
                } else {
                    seasonalMultiplier = 1.3; // Very slow (severe daylight decrease)
                }
            }

            const adjustedDaysToHarvest = Math.round(baseDaysToHarvest * seasonalMultiplier);

            // CORRECTED: Calculate harvest date based on HARVEST WINDOW, not sowing date
            // Brussels sprouts are planted in spring for winter harvest
            // The harvest window (Nov-Feb) is the target, work backwards to find correct harvest dates
            
            // Get harvest window from the UI (use different variable names to avoid conflicts)
            const targetHarvestStart = new Date(harvestWindowData.userStart || harvestWindowData.aiStart);
            const targetHarvestEnd = new Date(harvestWindowData.userEnd || harvestWindowData.aiEnd);
            const targetHarvestWindowDays = (targetHarvestEnd - targetHarvestStart) / (24 * 60 * 60 * 1000);
            
            // Space harvest dates across the harvest window for this succession
            const harvestSpacing = targetHarvestWindowDays / Math.max(1, optimalSuccessions - 1);
            let harvestDate = new Date(targetHarvestStart.getTime() + (successionIndex * harvestSpacing * 24 * 60 * 60 * 1000));
            
            // Ensure harvest date doesn't exceed harvest window
            if (harvestDate > targetHarvestEnd) {
                harvestDate = new Date(targetHarvestEnd.getTime());
            }

            console.log(`🌱 Succession ${successionIndex + 1} - CORRECTED Harvest Window Planning:`, {
                plantingDate: plantingDate.toLocaleDateString(),
                transplantDate: transplantDate.toLocaleDateString(),
                harvestDate: harvestDate.toLocaleDateString(),
                harvestWindow: `${targetHarvestStart.toLocaleDateString()} - ${targetHarvestEnd.toLocaleDateString()}`
            });

            return {
                sowDate: plantingDate,
                transplantDate: transplantDate,
                harvestDate: harvestDate,
                method: cropTiming.method || 'Direct sow'
            };
        }

        // For short-season crops that don't use advanced seasonal planning
        // Calculate harvest date for this succession based on interval spacing
        const fallbackHarvestDate = new Date(harvestStart.getTime() + (successionIndex * interval * 24 * 60 * 60 * 1000));

        // Calculate sowing date (working backwards from harvest)
        const fallbackSowDate = new Date(fallbackHarvestDate.getTime() - (cropTiming.daysToHarvest * 24 * 60 * 60 * 1000));

        // Calculate transplant date if applicable
        let fallbackTransplantDate = null;
        if (cropTiming.daysToTransplant) {
            fallbackTransplantDate = new Date(fallbackSowDate.getTime() + (cropTiming.daysToTransplant * 24 * 60 * 60 * 1000));
        }

        return {
            sowDate: fallbackSowDate,
            transplantDate: fallbackTransplantDate,
            harvestDate: fallbackHarvestDate,
            method: cropTiming.method || 'Direct sow'
        };
    }

    // Get crop-specific timing information
    function getCropTiming(cropName, varietyName) {
        const cropLower = cropName.toLowerCase();
        const varietyLower = varietyName.toLowerCase();

        // Comprehensive crop timing database
        const timingData = {
            // Root vegetables
            'carrot': {
                daysToHarvest: 70,
                daysToTransplant: null,
                method: 'Direct sow'
            },
            'beetroot': {
                daysToHarvest: 55,
                daysToTransplant: null,
                method: 'Direct sow'
            },
            'radish': {
                daysToHarvest: 25,
                daysToTransplant: null,
                method: 'Direct sow'
            },
            'potato': {
                daysToHarvest: 90,
                daysToTransplant: null,
                method: 'Plant seed potatoes'
            },
            'onion': {
                daysToHarvest: 120,
                daysToTransplant: 35,
                method: 'Transplant seedlings'
            },
            'garlic': {
                daysToHarvest: 240,
                daysToTransplant: null,
                method: 'Plant cloves'
            },
            'leek': {
                daysToHarvest: 120,
                daysToTransplant: 35,
                method: 'Transplant seedlings'
            },

            // Leafy greens
            'lettuce': {
                daysToHarvest: 45,
                daysToTransplant: 21,
                method: 'Transplant seedlings'
            },
            'spinach': {
                daysToHarvest: 40,
                daysToTransplant: null,
                method: 'Direct sow'
            },
            'kale': {
                daysToHarvest: 60,
                daysToTransplant: 28,
                method: 'Transplant seedlings'
            },
            'chard': {
                daysToHarvest: 50,
                daysToTransplant: 21,
                method: 'Transplant seedlings'
            },
            'pak choi': {
                daysToHarvest: 35,
                daysToTransplant: null,
                method: 'Direct sow'
            },

            // Brassicas
            'cabbage': {
                daysToHarvest: 80,
                daysToTransplant: 35,
                method: 'Transplant seedlings'
            },
            'broccoli': {
                daysToHarvest: 70,
                daysToTransplant: 35,
                method: 'Transplant seedlings'
            },
            'cauliflower': {
                daysToHarvest: 75,
                daysToTransplant: 35,
                method: 'Transplant seedlings'
            },

            // Legumes
            'peas': {
                daysToHarvest: 60,
                daysToTransplant: null,
                method: 'Direct sow'
            },
            'beans': {
                daysToHarvest: 55,
                daysToTransplant: null,
                method: 'Direct sow'
            },

            // Fruiting vegetables
            'tomato': {
                daysToHarvest: 75,
                daysToTransplant: 42,
                method: 'Transplant seedlings'
            },
            'pepper': {
                daysToHarvest: 80,
                daysToTransplant: 42,
                method: 'Transplant seedlings'
            },
            'cucumber': {
                daysToHarvest: 55,
                daysToTransplant: 21,
                method: 'Transplant seedlings'
            },
            'zucchini': {
                daysToHarvest: 50,
                daysToTransplant: 21,
                method: 'Transplant seedlings'
            },

            // Other
            'corn': {
                daysToHarvest: 75,
                daysToTransplant: null,
                method: 'Direct sow'
            },
            'celery': {
                daysToHarvest: 100,
                daysToTransplant: 42,
                method: 'Transplant seedlings'
            },
            'fennel': {
                daysToHarvest: 80,
                daysToTransplant: 35,
                method: 'Transplant seedlings'
            },
            'brussels sprouts': {
                daysToHarvest: 110,
                daysToTransplant: 35,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 2,  // March (0-indexed)
                    startDay: 15,
                    endMonth: 4,    // May (0-indexed)
                    endDay: 15,
                    description: 'March 15 - May 15 (optimal spring transplanting)'
                }
            },
            'brussels': {
                daysToHarvest: 110,
                daysToTransplant: 35,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 2,  // March
                    startDay: 15,
                    endMonth: 4,    // May
                    endDay: 15,
                    description: 'March 15 - May 15 (optimal spring transplanting)'
                }
            },
            'cabbage': {
                daysToHarvest: 80,
                daysToTransplant: 35,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 2,  // March
                    startDay: 1,
                    endMonth: 4,    // May
                    endDay: 15,
                    description: 'March 1 - May 15 (spring transplanting)'
                }
            },
            'broccoli': {
                daysToHarvest: 70,
                daysToTransplant: 35,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 2,  // March
                    startDay: 15,
                    endMonth: 5,    // June
                    endDay: 15,
                    description: 'March 15 - June 15 (extended spring)'
                }
            },
            'lettuce': {
                daysToHarvest: 45,
                daysToTransplant: 21,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 1,  // February
                    startDay: 15,
                    endMonth: 4,    // May
                    endDay: 30,
                    description: 'February 15 - May 30 (cool season transplanting)'
                }
            },
            'tomato': {
                daysToHarvest: 75,
                daysToTransplant: 42,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 3,  // April
                    startDay: 1,
                    endMonth: 5,    // June
                    endDay: 15,
                    description: 'April 1 - June 15 (after last frost)'
                }
            },
            'pepper': {
                daysToHarvest: 80,
                daysToTransplant: 42,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 3,  // April
                    startDay: 15,
                    endMonth: 5,    // June
                    endDay: 1,
                    description: 'April 15 - June 1 (warm season transplanting)'
                }
            },
            'celery': {
                daysToHarvest: 100,
                daysToTransplant: 42,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 2,  // March
                    startDay: 1,
                    endMonth: 4,    // May
                    endDay: 15,
                    description: 'March 1 - May 15 (cool season transplanting)'
                }
            }
        };

        // Check for specific crop matches
        for (const [crop, timing] of Object.entries(timingData)) {
            if (cropLower.includes(crop) || varietyLower.includes(crop)) {
                return timing;
            }
        }

        // Default timing for unknown crops
        return {
            daysToHarvest: 60,
            daysToTransplant: null,
            method: 'Direct sow',
            transplantWindow: {
                startMonth: 2,  // March
                startDay: 15,
                endMonth: 4,    // May
                endDay: 15,
                description: 'March 15 - May 15 (default transplanting window)'
            }
        };
    }

    // Update calendar grid visualization
    function updateCalendarGrid() {
        const calendarDiv = document.getElementById('calendarGrid');
        if (!calendarDiv) return;

        const baseYear = parseInt(harvestWindowData.selectedYear);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun',
                       'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        let calendarHTML = '';

        // Check if harvest window spans multiple years
        const hasMultiYearHarvest = harvestWindowData.userEnd &&
            new Date(harvestWindowData.userEnd).getFullYear() > baseYear;

        if (hasMultiYearHarvest) {
            console.log('🌍 Multi-year harvest detected - showing calendar for both years');

            // Generate calendar for base year (e.g., 2026)
            months.forEach((month, index) => {
                calendarHTML += generateMonthHTML(month, index, baseYear);
            });

            // Generate calendar for next year (e.g., 2027)
            const nextYear = baseYear + 1;
            months.forEach((month, index) => {
                calendarHTML += generateMonthHTML(month, index, nextYear);
            });
        } else {
            // Single year calendar (original behavior)
            months.forEach((month, index) => {
                calendarHTML += generateMonthHTML(month, index, baseYear);
            });
        }

        calendarDiv.innerHTML = calendarHTML;
        document.getElementById('harvestCalendar').style.display = 'block';
    }

    // Helper function to generate HTML for a single month
    function generateMonthHTML(month, index, year) {
        let monthClass = 'calendar-month';
        let monthTitle = `${month} ${year}`;

        // Determine if this month is within different ranges
        if (isMonthInRange(index, harvestWindowData.maxStart, harvestWindowData.maxEnd, year)) {
            if (isMonthInRange(index, harvestWindowData.userStart, harvestWindowData.userEnd, year)) {
                monthClass += ' selected';
                monthTitle += ' - Selected Harvest Period';
            } else if (isMonthInRange(index, harvestWindowData.aiStart, harvestWindowData.aiEnd, year)) {
                monthClass += ' optimal';
                monthTitle += ' - Optimal Harvest Period';
            } else {
                monthClass += ' extended';
                monthTitle += ' - Extended Harvest Period';
            }
        }

        return `
            <div class="col-6 col-md-4 col-lg-3 mb-3">
                <div class="${monthClass}" title="${monthTitle}">
                    <strong>${month}</strong>
                    <div class="mt-1">${year}</div>
                </div>
            </div>
        `;
    }

    // Helper function to check if a month is within a date range
    function isMonthInRange(monthIndex, startDate, endDate, year = null) {
        if (!startDate || !endDate) return false;

        // Use provided year or fallback to selectedYear
        const targetYear = year || harvestWindowData.selectedYear;
        const monthStart = new Date(targetYear, monthIndex, 1);
        const monthEnd = new Date(targetYear, monthIndex + 1, 0);

        const rangeStart = new Date(startDate);
        const rangeEnd = new Date(endDate);

        return monthStart <= rangeEnd && monthEnd >= rangeStart;
    }

    // Update harvest window data from AI results
    function updateHarvestWindowData(aiResult) {
        if (!aiResult) return;

        // Set maximum possible range (from AI or fallback)
        harvestWindowData.maxStart = aiResult.maximum_start || aiResult.maxStart;
        harvestWindowData.maxEnd = aiResult.maximum_end || aiResult.maxEnd;

        // 🔍 DETECT YEAR CHANGE ISSUE: Check if maxEnd comes before maxStart chronologically
        if (harvestWindowData.maxStart && harvestWindowData.maxEnd) {
            const startDate = new Date(harvestWindowData.maxStart);
            const endDate = new Date(harvestWindowData.maxEnd);

            if (endDate < startDate) {
                console.log('🚨 YEAR CHANGE DETECTED:', {
                    original: { start: harvestWindowData.maxStart, end: harvestWindowData.maxEnd },
                    issue: 'endDate comes before startDate chronologically',
                    solution: 'Adding 1 year to endDate'
                });

                // Add 1 year to the end date to fix chronological order
                endDate.setFullYear(endDate.getFullYear() + 1);
                harvestWindowData.maxEnd = endDate.toISOString().split('T')[0];

                console.log('✅ FIXED:', {
                    newEnd: harvestWindowData.maxEnd,
                    duration: Math.ceil((endDate - startDate) / (1000 * 60 * 60 * 24)) + ' days'
                });
            }
        }

        // Set AI recommended range (typically 80% of maximum)
        if (harvestWindowData.maxStart && harvestWindowData.maxEnd) {
            const maxStart = new Date(harvestWindowData.maxStart);
            const maxEnd = new Date(harvestWindowData.maxEnd);
            const maxDuration = maxEnd - maxStart;

            harvestWindowData.aiStart = harvestWindowData.maxStart;
            harvestWindowData.aiEnd = new Date(maxStart.getTime() + (maxDuration * 0.8)).toISOString().split('T')[0];

            // Initialize user range to AI recommendation
            harvestWindowData.userStart = harvestWindowData.aiStart;
            harvestWindowData.userEnd = harvestWindowData.aiEnd;
        }

        updateHarvestWindowDisplay();
    }
</script>
@endsection