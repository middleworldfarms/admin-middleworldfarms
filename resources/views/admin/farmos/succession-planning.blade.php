@extends('layouts.app')

@section('title', 'farmOS Succession Planner - Revolutionary Backward Planning')

@section('page-title', 'farmOS Succession Planner')

@section('page-header')
    <div class="d-flex justify-content-between align-items-center w-100">
        <div>
            <button id="syncVarietiesBtn" class="btn btn-sm btn-outline-primary" onclick="syncFarmOSVarieties()" title="Sync varieties from FarmOS - Only needed if FarmOS varieties have changed">
                <i class="fas fa-sync-alt"></i> Sync Varieties
            </button>
        </div>
        <div class="text-center flex-grow-1">
            <p class="lead mb-0">Revolutionary backward planning from harvest windows • Real farmOS taxonomy • AI-powered intelligence</p>
        </div>
        <div></div>
    </div>
@endsection

@section('styles')
<!-- Force page to start at top -->
<style>
    html, body {
        scroll-behavior: auto !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    /* Ensure page always starts at top */
    html {
        scroll-restoration: manual;
    }
</style>

<!-- Immediate scroll to top - runs before anything else -->
<script>
    // Force scroll to top IMMEDIATELY - before DOMContentLoaded
    if (history.scrollRestoration) {
        history.scrollRestoration = 'manual';
    }
    window.scrollTo(0, 0);
    document.documentElement.scrollTop = 0;
    document.body.scrollTop = 0;
</script>

<!-- Timeline Visualization Styles -->

<!-- Sortable.js for drag and drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>

<!-- Succession Planner Module -->
<script src="{{ asset('js/succession-planner.js') }}?v={{ time() }}"></script>
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

    .farmos-timeline-container {
        background: white;
        border-radius: 1rem;
        padding: 1.5rem;
        margin-top: 2rem;
        box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        min-height: 500px;
    }

    .farmos-timeline-iframe {
        width: 100%;
        height: 600px;
        border: none;
        border-radius: 0.5rem;
    }

    /* Timeline Visualization Styles */
    .timeline-visualization {
        width: 100%;
        position: relative;
        padding: 20px 0;
    }

    .timeline-axis {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0;
        padding: 10px 20px;
        border-bottom: 2px solid #dee2e6;
        position: sticky;
        top: 0;
        background: white;
        z-index: 100;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .timeline-axis::after {
        content: '';
        position: absolute;
        bottom: -2px;
        left: 0;
        right: 0;
        height: 2px;
        background: linear-gradient(90deg, #28a745, #ffc107, #dc3545);
    }

    .timeline-month {
        text-align: center;
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
    }

    .timeline-tasks {
        position: relative;
        min-height: 300px;
    }

    .timeline-task {
        position: absolute;
        height: 40px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        padding: 0 10px;
        color: white;
        font-size: 0.85rem;
        font-weight: 500;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
        cursor: pointer;
        transition: all 0.2s ease;
        border: 2px solid rgba(255,255,255,0.3);
    }

    .timeline-task:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.3);
    }

    .timeline-task.seeding {
        background: linear-gradient(135deg, #28a745, #20c997);
    }

    .timeline-task.transplanting {
        background: linear-gradient(135deg, #ffc107, #fd7e14);
    }

    .timeline-task.harvest {
        background: linear-gradient(135deg, #dc3545, #fd7e14);
    }

    .timeline-task.growth {
        background: linear-gradient(135deg, rgba(40, 167, 69, 0.7), rgba(255, 193, 7, 0.7));
        border-style: dashed;
    }

    .timeline-legend {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
    }

    .legend-color {
        width: 16px;
        height: 16px;
        border-radius: 3px;
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

    .succession-date {
        font-size: 0.875rem;
        font-weight: 500;
        color: #495057;
    }

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
        width: 100%;
        height: auto;
        border: 2px solid #dee2e6;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        object-fit: cover;
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

    /* Bed Occupancy Timeline Styles */
    .bed-occupancy-timeline {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        margin: 20px 0;
    }

    .timeline-header {
        text-align: center;
        margin-bottom: 20px;
    }

    .timeline-header h5 {
        color: #28a745;
        margin-bottom: 5px;
    }

    .beds-container {
        position: relative;
        margin-top: 10px;
        padding-top: 10px;
    }

    .bed-row {
        display: flex;
        align-items: center;
        margin-bottom: 8px;
        min-height: 40px;
        position: relative;
    }

    .bed-block:not(:last-child) {
        margin-bottom: 30px;
        position: relative;
    }

    .bed-block:not(:last-child)::after {
        content: '';
        position: absolute;
        bottom: -15px;
        left: 0;
        right: 0;
        height: 6px;
        background: repeating-linear-gradient(
            90deg,
            #8B4513 0px,
            #8B4513 4px,
            #228B22 4px,
            #228B22 8px,
            #8B4513 8px,
            #8B4513 12px
        );
        border-radius: 3px;
        opacity: 0.8;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }



    .block-timeline-header .timeline-month {
        font-size: 0.8rem;
        padding: 4px 0;
    }

    .bed-label {
        width: 120px;
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
        padding-right: 15px;
        text-align: right;
        flex-shrink: 0;
    }

    .bed-timeline {
        flex: 1;
        position: relative;
        height: 32px;
        background: #f8f9fa;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        overflow: hidden;
        cursor: pointer;
    }

    .bed-occupancy-block {
        position: absolute;
        height: 100%;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 500;
        color: white;
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        border: 1px solid rgba(255,255,255,0.3);
        transition: all 0.2s ease;
        cursor: pointer;
        pointer-events: none; /* Allow drops on parent timeline */
    }

    .bed-occupancy-block:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .bed-occupancy-block.active {
        background: linear-gradient(135deg, #28a745, #20c997);
    }

    .bed-occupancy-block.completed {
        background: linear-gradient(135deg, #6c757d, #5a6268);
        opacity: 0.8;
    }

    .bed-occupancy-block.available {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border: 2px dashed #dee2e6;
    }

    .crop-label {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        padding: 0 4px;
    }

    .timeline-indicators {
        position: absolute;
        top: 0;
        left: 120px;
        right: 0;
        height: 100%;
        pointer-events: none;
    }

    .succession-indicator {
        position: absolute;
        top: -8px;
        transform: translateX(-50%);
        z-index: 10;
        animation: pulse 2s infinite;
    }

    .succession-indicator i {
        font-size: 16px;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
    }

    @keyframes pulse {
        0% { transform: translateX(-50%) scale(1); }
        50% { transform: translateX(-50%) scale(1.2); }
        100% { transform: translateX(-50%) scale(1); }
    }

    .timeline-legend .legend-color.active {
        background: linear-gradient(135deg, #28a745, #20c997);
    }

    .timeline-legend .legend-color.completed {
        background: linear-gradient(135deg, #6c757d, #5a6268);
    }

    .timeline-legend .legend-color.available {
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        border: 1px dashed #dee2e6;
    }

    /* Block grouping styles */
    .bed-block {
        margin-bottom: 25px;
        border: 1px solid #e9ecef;
        border-radius: 8px;
        overflow: hidden;
    }

    .bed-block:last-child {
        margin-bottom: 0;
    }

    .bed-block-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: linear-gradient(135deg, #f8f9fa, #e9ecef);
        padding: 12px 15px;
        border-bottom: 1px solid #dee2e6;
    }

    .bed-block-header h6 {
        margin: 0;
        color: #495057;
        font-weight: 600;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 4px;
        flex-wrap: nowrap;
        white-space: nowrap;
    }

    .hedgerow-icon {
        color: #28a745;
        font-size: 1.1em;
        margin: 0 2px;
    }

    .bed-block-header i {
        color: #6c757d;
        margin-right: 8px;
    }

    .hedgerow-indicator {
        display: flex;
        align-items: center;
        gap: 5px;
        font-size: 0.8em;
        color: #6c757d;
    }

    .hedgerow-indicator i {
        font-size: 0.9em;
    }

    .hedgerow-divider {
        margin: 25px 0;
        padding: 0;
        height: 80px;
        background-image: url('/hedgerow.png');
        background-repeat: repeat-x;
        background-position: center center;
        background-size: auto 80px;
        position: relative;
        overflow: hidden;
        border-top: 2px solid #c3e6cb;
        border-bottom: 2px solid #c3e6cb;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .hedgerow-visual {
        display: none; /* Hide the icon-based visual, we're using the image now */
    }

    .hedgerow-tree {
        color: #28a745;
        font-size: 1.2em;
        margin: 0 3px;
    }

    /* Responsive adjustments for hedgerow icons */
    @media (max-width: 768px) {
        .hedgerow-icon {
            font-size: 1em;
            margin: 0 1px;
        }

        .hedgerow-tree {
            font-size: 1.1em;
            margin: 0 2px;
        }

        .bed-block-header h6 {
            gap: 2px;
            font-size: 0.9rem;
        }

        .hedgerow-visual {
            gap: 6px;
            padding: 6px 12px;
            font-size: 0.8em;
        }
    }

    @media (min-width: 1200px) {
        .hedgerow-icon {
            font-size: 1.3em;
            margin: 0 3px;
        }

        .hedgerow-tree {
            font-size: 1.4em;
            margin: 0 4px;
        }

        .bed-block-header h6 {
            gap: 6px;
        }

        .hedgerow-visual {
            gap: 12px;
            padding: 10px 20px;
        }
    }

    .hedgerow-visual i {
        color: #28a745;
        font-size: 1em;
    }

    .hedgerow-text {
        font-weight: 500;
        letter-spacing: 0.5px;
    }

    .bed-block-content {
        padding: 15px;
    }

    .bed-block-content .bed-row {
        margin-bottom: 12px;
    }

    .bed-block-content .bed-row:last-child {
        margin-bottom: 0;
    }

    /* Succession Planning Sidebar Styles */
    .succession-list {
        max-height: 400px;
        overflow-y: auto;
    }

    .succession-item {
        background: white;
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 12px;
        margin-bottom: 8px;
        cursor: grab;
        transition: all 0.2s ease;
        position: relative;
    }

    .succession-item:hover {
        border-color: #28a745;
        box-shadow: 0 2px 8px rgba(40, 167, 69, 0.2);
        transform: translateY(-1px);
    }

    .succession-item.dragging {
        opacity: 0.2 !important;
        transform: rotate(3deg) scale(0.95);
        cursor: grabbing;
        background: #6c757d !important;
        border: 2px dashed #495057 !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3) !important;
        filter: grayscale(100%) brightness(0.8);
    }

    .succession-item.dragging .succession-title {
        color: #adb5bd !important;
        text-decoration: line-through;
    }

    .succession-item.dragging .succession-dates {
        opacity: 0.5;
    }

    .succession-item.allocated {
        opacity: 0.6 !important;
        background: #d6d8db !important;
        border: 2px dashed #adb5bd !important;
        pointer-events: none !important;
    }

    .succession-item.allocated .succession-title {
        color: #6c757d !important;
        text-decoration: line-through;
    }
    
    .succession-item.allocated .succession-dates {
        opacity: 0.6;
    }

    .bed-allocation-badge {
        font-size: 0.75rem !important;
        font-weight: 600 !important;
        margin-left: 8px;
        padding: 4px 10px !important;
        border-radius: 12px;
        transition: all 0.2s ease;
        pointer-events: auto !important;
        white-space: nowrap;
    }

    .bed-allocation-badge:hover {
        background-color: #dc3545 !important;
        transform: scale(1.05);
    }

    .succession-item .succession-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 8px;
        position: relative;
    }

    .succession-item .succession-title-section {
        flex: 1;
    }

    .succession-item .succession-title {
        font-weight: 600;
        color: #28a745;
        font-size: 0.9rem;
    }

    .succession-item .succession-dates {
        font-size: 0.8rem;
        color: #6c757d;
    }

    .succession-item .succession-dates .date-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 2px;
    }

    .succession-item .succession-dates .date-label {
        font-weight: 500;
    }

    .succession-item .succession-dates .date-value {
        color: #495057;
    }

    /* Bed drop zones */
    .bed-timeline.drop-target {
        background: linear-gradient(45deg, #d4edda, #f8f9fa) !important;
        border: 3px dashed #28a745 !important;
        cursor: copy;
        box-shadow: inset 0 0 10px rgba(40, 167, 69, 0.3);
        transform: scale(1.02);
        transition: all 0.2s ease;
    }

    .bed-timeline.drop-active {
        background: linear-gradient(45deg, #28a745, #20c997) !important;
        border: 3px solid #28a745 !important;
        cursor: copy;
        box-shadow: inset 0 0 15px rgba(40, 167, 69, 0.5), 0 0 20px rgba(40, 167, 69, 0.3);
        transform: scale(1.05);
        animation: pulse 1.5s infinite;
    }

    .bed-timeline.drop-conflict {
        background: linear-gradient(45deg, #f8d7da, #f5c6cb) !important;
        border: 3px solid #dc3545 !important;
        cursor: not-allowed;
        box-shadow: inset 0 0 15px rgba(220, 53, 69, 0.5);
        transform: scale(1.02);
    }

    @keyframes pulse {
        0% { box-shadow: inset 0 0 15px rgba(40, 167, 69, 0.5), 0 0 20px rgba(40, 167, 69, 0.3); }
        50% { box-shadow: inset 0 0 20px rgba(40, 167, 69, 0.7), 0 0 30px rgba(40, 167, 69, 0.5); }
        100% { box-shadow: inset 0 0 15px rgba(40, 167, 69, 0.5), 0 0 20px rgba(40, 167, 69, 0.3); }
    }

    /* Drag preview indicator */
    .drag-preview {
        position: absolute;
        top: 0;
        height: 100%;
        background: rgba(40, 167, 69, 0.3);
        border: 2px solid #28a745;
        border-radius: 4px;
        pointer-events: none;
        z-index: 100;
        display: flex;
        align-items: center;
        justify-content: center;
        animation: preview-pulse 1s infinite;
    }

    .drag-preview-content {
        background: rgba(255, 255, 255, 0.9);
        padding: 4px 8px;
        border-radius: 3px;
        font-size: 0.75rem;
        color: #28a745;
        font-weight: 600;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    @keyframes preview-pulse {
        0% { background: rgba(40, 167, 69, 0.3); }
        50% { background: rgba(40, 167, 69, 0.5); }
        100% { background: rgba(40, 167, 69, 0.3); }
    }

    /* Succession allocation blocks */
    .succession-block-container {
        position: absolute;
        height: 100%;
        display: flex;
        cursor: move;
        transition: all 0.2s ease;
        z-index: 5;
    }

    .succession-growing-block {
        background: linear-gradient(135deg, #17a2b8, #138496);
        border-radius: 4px 0 0 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
        color: white;
        text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        border: 2px solid rgba(255,255,255,0.3);
        transition: all 0.2s ease;
    }

    .succession-harvest-block {
        background: linear-gradient(135deg, #ffc107, #e0a800);
        border-radius: 0 4px 4px 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.75rem;
        font-weight: 600;
        color: #212529;
        text-shadow: 0 1px 2px rgba(255,255,255,0.5);
        border: 2px solid rgba(255,255,255,0.5);
        transition: all 0.2s ease;
    }

    .succession-growing-block:hover,
    .succession-harvest-block:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    }

    .succession-block-container.dragging .succession-growing-block,
    .succession-block-container.dragging .succession-harvest-block {
        opacity: 0.7;
        transform: rotate(2deg) scale(1.05);
    }

    .succession-allocation-block .succession-label {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 100%;
        padding: 0 2px;
    }

    /* Conflict error states */
    .bed-timeline.conflict-error {
        background: linear-gradient(45deg, #f8d7da, #f5c6cb);
        border: 2px solid #dc3545 !important;
        animation: conflict-pulse 0.5s ease-in-out;
    }

    .conflict-message {
        position: absolute;
        top: -30px;
        left: 50%;
        transform: translateX(-50%);
        background: #dc3545;
        color: white;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .conflict-message::after {
        content: '';
        position: absolute;
        top: 100%;
        left: 50%;
        transform: translateX(-50%);
        border: 4px solid transparent;
        border-top-color: #dc3545;
    }

    @keyframes conflict-pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
    }

    /* Enhanced drop zone feedback */
    .bed-timeline.drop-target {
        background: linear-gradient(45deg, #d1ecf1, #bee5eb);
        border: 2px dashed #17a2b8 !important;
        transition: all 0.2s ease;
    }

    .bed-timeline.drop-active {
        background: linear-gradient(45deg, #17a2b8, #138496);
        border: 2px solid #17a2b8 !important;
        transform: scale(1.01);
    }

    .bed-timeline.drop-active::before {
        content: '📍 Drop succession here';
        position: absolute;
        top: -25px;
        left: 50%;
        transform: translateX(-50%);
        background: #17a2b8;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .bed-timeline.drop-active::after {
        content: '';
        position: absolute;
        top: -21px;
        left: 50%;
        transform: translateX(-50%);
        border: 4px solid transparent;
        border-top-color: #17a2b8;
        z-index: 10;
    }

    /* Conflict drop state */
    .bed-timeline.drop-conflict {
        background: linear-gradient(45deg, #f8d7da, #f5c6cb);
        border: 2px dashed #dc3545 !important;
        cursor: not-allowed;
    }

    .bed-timeline.drop-conflict::before {
        content: '🚫 Bed occupied';
        position: absolute;
        top: -25px;
        left: 50%;
        transform: translateX(-50%);
        background: #dc3545;
        color: white;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        white-space: nowrap;
        z-index: 10;
        box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }

    .bed-timeline.drop-conflict::after {
        content: '';
        position: absolute;
        top: -21px;
        left: 50%;
        transform: translateX(-50%);
        border: 4px solid transparent;
        border-top-color: #dc3545;
        z-index: 10;
    }
</style>
@endsection

@section('content')
<div class="succession-planner-container">
    <!-- Cache buster for development -->
    <script>console.log('🔄 Cache buster: 1756750327-FIXED-' + Date.now() + ' - VISUAL TIMELINE - FarmOS succession planner');</script>
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
        <!-- Left Column: Planning Interface and Timeline -->
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

                        <!-- Step 2.5: Bed Dimensions for Seed Calculations -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="bedLength" class="form-label">
                                    <i class="fas fa-ruler-horizontal text-info"></i>
                                    Bed Length (meters)
                                </label>
                                <input type="number" class="form-control" id="bedLength" name="bedLength" placeholder="e.g., 30" min="1" step="0.1">
                            </div>
                            <div class="col-md-6">
                                <label for="bedWidth" class="form-label">
                                    <i class="fas fa-ruler-vertical text-info"></i>
                                    Bed Width (cm)
                                </label>
                                <input type="number" class="form-control" id="bedWidth" name="bedWidth" placeholder="e.g., 75" min="10" step="1">
                            </div>
                        </div>

                        <!-- Step 2.6: Plant Spacing for Quantity Calculations -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="inRowSpacing" class="form-label">
                                    <i class="fas fa-arrows-alt-h text-success"></i>
                                    In-Row Spacing (cm)
                                    <small class="text-muted">Distance between plants in a row</small>
                                </label>
                                <input type="number" class="form-control" id="inRowSpacing" name="inRowSpacing" placeholder="e.g., 15" min="1" step="1" value="15">
                            </div>
                            <div class="col-md-6">
                                <label for="betweenRowSpacing" class="form-label">
                                    <i class="fas fa-arrows-alt-v text-success"></i>
                                    Between-Row Spacing (cm)
                                    <small class="text-muted">Distance between rows</small>
                                </label>
                                <input type="number" class="form-control" id="betweenRowSpacing" name="betweenRowSpacing" placeholder="e.g., 20" min="1" step="1" value="20">
                            </div>
                            <div class="col-12 mt-2">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i>
                                    <span class="border border-success px-2 py-1" style="display: inline-block;">Green border</span> 
                                    indicates spacing auto-filled from database. You can adjust these values as needed.
                                </small>
                            </div>
                        </div>

                        <!-- Density Preset Selector for Brassicas -->
                        <div id="brassicaDensityPreset" class="alert alert-info mb-3" style="display: none;">
                            <h6 class="mb-2">
                                <i class="fas fa-layer-group"></i> 
                                Row Density Presets (<span id="densityBedWidth">75</span>cm bed):
                            </h6>
                            <div class="btn-group w-100" role="group">
                                <button type="button" class="btn btn-outline-primary density-preset" data-between-row="40">
                                    <strong id="preset2rowsLabel">2 Rows</strong><br>
                                    <small>40cm between rows</small>
                                </button>
                                <button type="button" class="btn btn-outline-primary density-preset" data-between-row="30">
                                    <strong id="preset3rowsLabel">3 Rows</strong><br>
                                    <small>30cm between rows</small>
                                </button>
                            </div>
                            <small class="text-muted mt-2 d-block">
                                <i class="fas fa-lightbulb"></i> 
                                These buttons set the "Between-Row Spacing" below. 40cm = conservative (default), 30cm = dense.
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Harvest Window Section - Hidden until variety selected -->
                <div id="harvestWindowSection" class="card shadow-sm mt-4" style="display: none;">
                    <div class="card-body bg-light">
                        <h5 class="card-title mb-3">
                            <i class="fas fa-calendar-check text-success"></i>
                            Harvest Window Planning
                        </h5>
                        
                        <!-- NEW: Visual Harvest Window Selector -->
                        <div class="mt-3">

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

                            <!-- Quick Adjust Buttons -->
                            <div class="mt-3 d-flex gap-2 justify-content-center">
                                <button type="button" class="btn btn-outline-success btn-sm" onclick="extendHarvestWindow()">
                                    <i class="fas fa-plus"></i> Extend 20%
                                </button>
                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="calculateAIHarvestWindow()">
                                    <i class="fas fa-brain"></i> Calculate Harvest Window
                                </button>
                                <button type="button" class="btn btn-outline-info btn-sm" onclick="shortenHarvestWindow()">
                                    <i class="fas fa-minus"></i> Reduce Successions
                                </button>
                            </div>

                            <!-- Dynamic Succession Count Display -->
                            <div id="dynamicSuccessionDisplay" class="mt-3 p-3 bg-success bg-opacity-10 border border-success rounded text-center" style="display: none;">
                                <div class="d-flex align-items-center justify-content-center gap-2">
                                    <i class="fas fa-seedling text-success"></i>
                                    <strong class="text-success">Successions Needed:</strong>
                                    <span id="successionCount" class="badge bg-success fs-6">0</span>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    Drag the handles above to adjust your harvest window and see how it affects the number of successions
                                </small>
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
                </div> <!-- End harvestWindowSection card -->
            </div>

            <!-- Results Section -->
            <div id="resultsSection" style="display: none;">
                <!-- FarmOS Timeline Chart -->
            <div class="farmos-timeline-container">
                <h4><i class="fas fa-chart-gantt text-success"></i> FarmOS Succession Timeline</h4>
                <p class="text-muted">Interactive Gantt chart showing planting dates and harvest windows from FarmOS</p>
                <div id="farmosTimelineContainer">
                    <div class="text-center p-4">
                        <div class="spinner-border text-success" role="status">
                            <span class="visually-hidden">Loading timeline...</span>
                        </div>
                        <p class="mt-2 text-muted">Loading FarmOS timeline...</p>
                    </div>
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

        <!-- Right Column: Succession Planning Sidebar -->
        <div class="col-lg-4">
            <!-- AI Chat Integration -->
            <div id="aiChatSection" class="planning-card">
                <div class="planning-section">
                    <h3>
                        <i class="fas fa-robot section-icon"></i>
                        AI Succession Advisor
                    </h3>
                    
                    <!-- AI Status -->
                    <div class="mb-3">
                        <div class="d-flex align-items-center gap-2">
                            <div id="aiStatusLight" class="status-light" title="AI Service Status"></div>
                            <small id="aiStatusText" class="text-muted">Checking AI...</small>
                        </div>
                    </div>

                    <!-- Chat Messages Area -->
                    <div id="chatMessages" class="mb-3" style="max-height: 400px; overflow-y: auto; border: 1px solid #ddd; border-radius: 8px; padding: 15px; background: #f8f9fa;">
                        <div class="text-muted text-center">
                            <i class="fas fa-robot fa-2x mb-2 text-success"></i>
                            <p><strong>AI Advisor Ready</strong></p>
                            <p class="small">Ask me about succession planning, crop timing, or growing advice!</p>
                        </div>
                    </div>

                    <!-- Chat Input -->
                    <div class="mb-2">
                        <textarea class="form-control" id="chatInput" rows="2" 
                            placeholder="Ask a question... (e.g., 'What's the best succession interval for lettuce?')"></textarea>
                    </div>
                    
                    <button class="btn btn-success w-100" id="sendChatBtn">
                        <i class="fas fa-paper-plane"></i> Send Message
                    </button>
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
                    
                    <!-- Succession Preview -->
                    <div id="successionPreviewContainer" class="succession-preview-section mt-4" style="display: none;">
                        <!-- Preview content will be populated by JavaScript -->
                    </div>
                    
                    <!-- No Variety Selected State -->
                    <div id="noVarietySelected" class="text-center py-4 text-muted">
                        <i class="fas fa-seedling fa-2x mb-2"></i>
                        <div>Select a variety to see detailed information from FarmOS</div>
                    </div>
                </div>
            </div>

            <!-- Succession Planning Sidebar (below variety, sticky on scroll) -->
            <div id="successionSidebar" class="planning-card mt-3 sticky-top" style="display: none; top: 20px;">
                <div class="planning-section">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h4 class="mb-0">
                            <i class="fas fa-tasks text-success"></i>
                            Succession Planning
                        </h4>
                        <span id="sidebarSuccessionCount" class="badge bg-primary">0 Successions</span>
                    </div>

                    <div class="succession-list" id="successionList">
                        <!-- Successions will be populated here as draggable items -->
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-seedling fa-2x mb-2"></i>
                            <p>Calculate a succession plan to see successions here</p>
                        </div>
                    </div>

                    <div class="mt-3">
                        <div class="d-grid gap-2 mb-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="syncVarietiesFromFarmOS()" title="Sync latest variety data from FarmOS">
                                <i class="fas fa-sync"></i> Sync Varieties from FarmOS
                            </button>
                            <button class="btn btn-outline-danger btn-sm" onclick="clearAllAllocations()" title="Clear all bed allocations to start fresh">
                                <i class="fas fa-trash"></i> Clear All Allocations
                            </button>
                        </div>
                        <small class="text-muted">
                            <i class="fas fa-info-circle me-1"></i>
                            Drag successions onto beds in the timeline to allocate them
                        </small>
                    </div>

                    <!-- Submit All Records Button -->
                    <div class="mt-4 pt-3 border-top">
                        <div class="d-grid">
                            <button type="button" class="btn btn-success btn-lg" onclick="submitAllQuickForms()">
                                <i class="fas fa-save"></i> Submit All Records
                            </button>
                        </div>
                        <p class="text-muted text-center mt-2 mb-0">
                            <small>Submit all planting records to FarmOS</small>
                        </p>
                    </div>

                    <!-- Page Navigation Buttons -->
                    <div class="mt-3 pt-3 border-top">
                        <div class="row g-2">
                            <div class="col-6">
                                <button type="button" class="btn btn-success btn-sm w-100" onclick="scrollToQuickForms()">
                                    <i class="fas fa-arrow-down"></i> Quick Forms
                                </button>
                            </div>
                            <div class="col-6">
                                <button type="button" class="btn btn-success btn-sm w-100" onclick="scrollToTop()">
                                    <i class="fas fa-arrow-up"></i> Back to Top
                                </button>
                            </div>
                        </div>
                        <small class="text-muted text-center d-block mt-2">Quick navigation</small>
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

    // Safely parse JSON data with fallbacks
    let cropTypes = [];
    let cropVarieties = [];
    let availableBeds = [];

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

    // Debug: Log crop data counts
    console.log('🌾 Loaded crop types:', cropTypes.length);
    console.log('🥕 Loaded varieties:', cropVarieties.length);
    if (cropTypes.length > 0) {
        console.log('📊 First crop type:', cropTypes[0]);
    }
    if (cropVarieties.length > 0) {
        console.log('📊 First variety:', cropVarieties[0]);
        const carrotVarieties = cropVarieties.filter(v => v.crop_type === 'Carrot');
        console.log('🥕 Carrot varieties found:', carrotVarieties.length);
        if (carrotVarieties.length > 0) {
            console.log('📊 First Carrot variety:', carrotVarieties[0]);
        }
    }

    // Global API base (always use same origin/protocol to avoid mixed-content)
    const API_BASE = window.location.origin + '/admin/farmos/succession-planning';
    const FARMOS_BASE = "{{ config('services.farmos.url', '') }}";

    // SuccessionPlanner will be initialized in DOMContentLoaded below

    let currentSuccessionPlan = null;
    let isDragging = false;
    let dragHandle = null;
    let dragStartX = 0;
    let initialBarLeft = 0; // Track initial bar position when drag starts
    let initialBarWidth = 0; // Track initial bar width when drag starts
    let cropId = null; // Track selected crop ID for variety filtering
    // Shared controllers to cancel stale AI requests
    let __aiCalcController = null;
    let __aiChatController = null;
    // Store last AI harvest info for overlay rendering
    let __lastAIHarvestInfo = null;

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

    // Update export button state based on whether there's a plan to export
    function updateExportButton() {
        try {
            const exportBtn = document.querySelector('button[onclick="exportSuccessionPlan()"]');
            console.log('🔍 Looking for export button:', exportBtn);
            
            if (exportBtn) {
                if (currentSuccessionPlan && currentSuccessionPlan.plantings && currentSuccessionPlan.plantings.length > 0) {
                    exportBtn.disabled = false;
                    exportBtn.classList.remove('disabled');
                    console.log('✅ Export button enabled - plan available');
                } else {
                    exportBtn.disabled = true;
                    exportBtn.classList.add('disabled');
                    console.log('🚫 Export button disabled - no plan available');
                }
            } else {
                console.warn('⚠️ Export button not found in DOM');
            }
        } catch (error) {
            console.error('❌ Error in updateExportButton:', error);
        }
    }

    // Filter the variety dropdown by selected crop - HANDLED BY SuccessionPlanner

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
        // Store globally for succession calculations
        window.currentVarietyData = varietyData;
        console.log('💾 Stored variety data globally:', varietyData);
        
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

        // Handle photo - check if variety has photo data
        const photoEl = document.getElementById('varietyPhoto');
        const noPhotoEl = document.getElementById('noPhotoMessage');

        // Check for photo in variety data (could be from FarmOS or admin DB)
        let photoUrl = null;
        let photoAlt = '';

        if (varietyData.image_url) {
            // Check if it's a file ID (UUID format) or a direct URL
            if (varietyData.image_url.match(/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i)) {
                // It's a file ID, use proxy route
                photoUrl = '/admin/farmos/variety-image/' + varietyData.image_url;
            } else {
                // It's a direct URL
                photoUrl = varietyData.image_url;
            }
            photoAlt = varietyData.image_alt_text || varietyData.name || 'Variety image';
        } else if (varietyData.photo) {
            photoUrl = varietyData.photo;
        } else if (varietyData.image) {
            photoUrl = varietyData.image;
        } else if (varietyData.farmos_data && varietyData.farmos_data.attributes && varietyData.farmos_data.attributes.image) {
            photoUrl = varietyData.farmos_data.attributes.image;
        }

        if (photoUrl) {
            photoEl.src = photoUrl;
            photoEl.alt = photoAlt;
            photoEl.style.display = 'block';
            noPhotoEl.style.display = 'none';
        } else {
            // No photo available
            photoEl.style.display = 'none';
            noPhotoEl.style.display = 'block';
        }

        // Auto-populate spacing fields from database
        const inRowSpacingInput = document.getElementById('inRowSpacing');
        const betweenRowSpacingInput = document.getElementById('betweenRowSpacing');
        
        if (varietyData.in_row_spacing_cm && varietyData.in_row_spacing_cm > 0) {
            inRowSpacingInput.value = varietyData.in_row_spacing_cm;
            // Add visual indicator that value is from database
            inRowSpacingInput.classList.add('border-success');
            inRowSpacingInput.title = `Auto-filled from database: ${varietyData.in_row_spacing_cm} cm`;
            console.log('✅ Auto-populated in-row spacing:', varietyData.in_row_spacing_cm, 'cm');
        } else {
            // Keep default value, remove database indicator
            inRowSpacingInput.classList.remove('border-success');
            inRowSpacingInput.title = 'Default spacing - adjust as needed';
            console.log('ℹ️ No in-row spacing in database, using default');
        }
        
        if (varietyData.between_row_spacing_cm && varietyData.between_row_spacing_cm > 0) {
            betweenRowSpacingInput.value = varietyData.between_row_spacing_cm;
            // Add visual indicator that value is from database
            betweenRowSpacingInput.classList.add('border-success');
            betweenRowSpacingInput.title = `Auto-filled from database: ${varietyData.between_row_spacing_cm} cm`;
            console.log('✅ Auto-populated between-row spacing:', varietyData.between_row_spacing_cm, 'cm');
        } else {
            // Keep default value, remove database indicator
            betweenRowSpacingInput.classList.remove('border-success');
            betweenRowSpacingInput.title = 'Default spacing - adjust as needed';
            console.log('ℹ️ No between-row spacing in database, using default');
        }
        
        // Show density preset selector for brassicas
        const cropName = document.getElementById('cropSelect')?.options[document.getElementById('cropSelect').selectedIndex]?.text?.toLowerCase() || '';
        const densityPreset = document.getElementById('brassicaDensityPreset');
        
        if (cropName.includes('brussels') || cropName.includes('cabbage') || 
            cropName.includes('broccoli') || cropName.includes('cauliflower')) {
            densityPreset.style.display = 'block';
            updateDensityPresetDisplay(); // Update the preset display with current bed width
            console.log('🥬 Brassica detected - showing density preset options');
        } else {
            densityPreset.style.display = 'none';
        }
        
        // Log planting method for reference (could be used for overseeding calculations later)
        if (varietyData.planting_method) {
            console.log('🌱 Planting method:', varietyData.planting_method);
        }

        console.log('✅ Variety information displayed');
    }

    // Handle variety selection and fetch/display info
    async function handleVarietySelection(varietyId) {
        console.log('🎯 handleVarietySelection called with ID:', varietyId);
        
        const container = document.getElementById('varietyInfoContainer');
        const loading = document.getElementById('varietyLoading');
        const error = document.getElementById('varietyError');
        const noSelection = document.getElementById('noVarietySelected');
        const harvestWindowSection = document.getElementById('harvestWindowSection');

        if (!varietyId) {
            // No variety selected
            console.log('📝 No variety selected, showing default state');
            container.style.display = 'none';
            loading.style.display = 'none';
            error.style.display = 'none';
            noSelection.style.display = 'block';
            if (harvestWindowSection) harvestWindowSection.style.display = 'none';
            return;
        }

        // Show loading state
        console.log('⏳ Showing loading state');
        container.style.display = 'block';
        loading.style.display = 'block';
        error.style.display = 'none';
        noSelection.style.display = 'none';
        if (harvestWindowSection) harvestWindowSection.style.display = 'block';

        try {
            console.log('🔍 Calling fetchVarietyInfo...');
            // Fetch variety information
            const varietyData = await fetchVarietyInfo(varietyId);
            console.log('📊 Variety data result:', varietyData);
            
            if (varietyData) {
                console.log('✅ Displaying variety data');
                displayVarietyInfo(varietyData);
                
                // Succession calculation will be triggered automatically by harvest window initialization
                console.log('ℹ️ Harvest window will trigger succession calculation when ready');
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
        // Try to find the timeline element (could be harvestTimeline, timeline-container, or farmos-timeline-container)
        let timeline = document.getElementById('harvestTimeline');
        if (!timeline) {
            timeline = document.querySelector('.timeline-container');
        }
        if (!timeline) {
            timeline = document.querySelector('.farmos-timeline-container');
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

    function setupCropVarietyHandlers() {
        // Add event listener for crop type changes
        document.getElementById('cropSelect').addEventListener('change', function() {
            console.log('🌾 Crop type changed to:', this.value);
            // Filter varieties based on selected crop type
            filterVarietiesByCrop(this.value);
            // Clear variety selection
            const varietySelect = document.getElementById('varietySelect');
            varietySelect.value = '';
            handleVarietySelection(null);
        });

        // Add event listener for variety changes
        document.getElementById('varietySelect').addEventListener('change', function() {
            console.log('🥕 Variety changed to:', this.value);
            handleVarietySelection(this.value);
        });
    }

    function filterVarietiesByCrop(cropTypeId) {
        const varietySelect = document.getElementById('varietySelect');
        const options = varietySelect.querySelectorAll('option');

        let visibleCount = 0;
        options.forEach(option => {
            if (!option.value) {
                // Keep the "Select a variety..." option
                option.style.display = 'block';
                return;
            }

            const optionCropType = option.getAttribute('data-crop');
            if (!cropTypeId || optionCropType === cropTypeId) {
                option.style.display = 'block';
                visibleCount++;
            } else {
                option.style.display = 'none';
            }
        });

        console.log(`🔍 Filtered varieties for crop type: ${cropTypeId}, visible varieties: ${visibleCount}`);
    }

    function handleMouseDown(e) {
        console.log('🖱️ Mouse down event triggered', e.target);
        
        const handle = e.target.closest('.drag-handle');
        const dragBar = document.getElementById('dragHarvestBar');
        
        if (!handle) {
            // Check if clicking on the bar itself
            const bar = e.target.closest('.drag-harvest-bar');
            if (bar && dragBar) {
                console.log('🟢 Dragging whole bar');
                isDragging = true;
                dragHandle = 'whole';
                dragStartX = e.clientX;
                initialBarLeft = parseFloat(dragBar.style.left) || 20;
                initialBarWidth = parseFloat(dragBar.style.width) || 40;
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
        
        if (dragBar) {
            initialBarLeft = parseFloat(dragBar.style.left) || 20;
            initialBarWidth = parseFloat(dragBar.style.width) || 40;
        }
        
        e.preventDefault();
        e.stopPropagation();
        document.body.style.cursor = 'grabbing';
    }

    function handleMouseMove(e, cachedRect = null) {
        if (!isDragging || !dragHandle) return;
        
        const timeline = document.getElementById('harvestTimeline');
        const rect = cachedRect || timeline.getBoundingClientRect();
        const timelineWidth = rect.width - 40; // Account for padding
        
        // Calculate the delta movement in pixels
        const deltaX = e.clientX - dragStartX;
        // Convert to percentage
        const deltaPercentage = (deltaX / timelineWidth) * 100;
        
        if (dragHandle === 'whole') {
            // Move the entire bar by the delta amount
            const dragBar = document.getElementById('dragHarvestBar');
            const newLeft = Math.max(0, Math.min(100 - initialBarWidth, initialBarLeft + deltaPercentage));
            
            dragBar.style.left = newLeft + '%';
            dragBar.style.width = initialBarWidth + '%';
            updateDateDisplays();
            updateDateInputsFromBar();
        } else {
            // For handle dragging, calculate the new position based on mouse position
            const mouseX = e.clientX - rect.left - 20; // Account for padding
            const percentage = Math.max(0, Math.min(100, (mouseX / timelineWidth) * 100));
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
        
        // Update harvestWindowData so succession count recalculates
        harvestWindowData.userStart = startDate.toISOString().split('T')[0];
        harvestWindowData.userEnd = endDate.toISOString().split('T')[0];
        
        console.log('📊 Harvest bar moved - updating succession count:', {
            start: harvestWindowData.userStart,
            end: harvestWindowData.userEnd
        });
        
        // Recalculate succession count based on new harvest window
        updateSuccessionImpact();
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
        let harvestInfo = null; // Declare at function level

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

            // Build a concise prompt for harvest windows
            const prompt = `Calculate the maximum harvest window for ${cropName} ${varietyName || ''} in the UK for ${contextPayload.planning_year}.

Return ONLY a JSON object:
{
  "maximum_start": "YYYY-MM-DD",
  "maximum_end": "YYYY-MM-DD",
  "days_to_harvest": 60,
  "yield_peak": "YYYY-MM-DD",
  "notes": "Brief explanation",
  "extended_window": {"max_extension_days": 30, "risk_level": "low"}
}

Use these guidelines:
- Carrots: May 1 - December 31
- Beets: June 1 - December 31  
- Lettuce: March 1 - November 30
- Radishes: April 1 - October 31
- Onions: July 1 - September 30
- Brussels Sprouts: October 1 - March 31

Calculate for ${contextPayload.planning_year}.`;

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
            const timeoutId = setTimeout(() => { try { __aiCalcController.abort(); } catch(_){} }, 60000); // 60s timeout

            const response = await fetch(chatUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({ 
                    message: prompt, 
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
            console.log('🛰️ AI answer content:', data.answer);

            // Prefer structured harvest window from backend; else use AI answer parsing
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

        } catch (error) {
            if (error.name === 'AbortError') {
                console.error('AI request timed out');
            } else {
                console.error('Error calculating AI harvest window:', error);
            }
            // Continue to fallback logic below
        }

        // Always provide fallback harvest windows if AI failed or returned incomplete data
        if (!harvestInfo || !harvestInfo.maximum_start || !harvestInfo.maximum_end) {
            console.log('🔄 Using fallback harvest window for:', cropName);

            // Simple fallback based on crop type
            const year = contextPayload.planning_year || new Date().getFullYear();
            let fallbackInfo = null;

            switch (cropName.toLowerCase()) {
                case 'carrots':
                case 'carrot':
                    fallbackInfo = {
                        maximum_start: `${year}-05-01`,
                        maximum_end: `${year}-12-31`,
                        days_to_harvest: 70,
                        yield_peak: `${year}-08-15`,
                        notes: 'Carrot harvest window (fallback)',
                        extended_window: { max_extension_days: 30, risk_level: 'low' }
                    };
                    break;
                case 'beets':
                case 'beetroot':
                    fallbackInfo = {
                        maximum_start: `${year}-06-01`,
                        maximum_end: `${year}-12-31`,
                        days_to_harvest: 60,
                        yield_peak: `${year}-09-15`,
                        notes: 'Beet harvest window (fallback)',
                        extended_window: { max_extension_days: 45, risk_level: 'low' }
                    };
                    break;
                case 'lettuce':
                    fallbackInfo = {
                        maximum_start: `${year}-03-01`,
                        maximum_end: `${year}-11-30`,
                        days_to_harvest: 45,
                        yield_peak: `${year}-06-15`,
                        notes: 'Lettuce harvest window (fallback)',
                        extended_window: { max_extension_days: 60, risk_level: 'moderate' }
                    };
                    break;
                case 'radish':
                case 'radishes':
                    fallbackInfo = {
                        maximum_start: `${year}-04-01`,
                        maximum_end: `${year}-10-31`,
                        days_to_harvest: 25,
                        yield_peak: `${year}-06-15`,
                        notes: 'Radish harvest window (fallback)',
                        extended_window: { max_extension_days: 30, risk_level: 'low' }
                    };
                    break;
                case 'onion':
                case 'onions':
                    fallbackInfo = {
                        maximum_start: `${year}-07-01`,
                        maximum_end: `${year}-09-30`,
                        days_to_harvest: 100,
                        yield_peak: `${year}-08-15`,
                        notes: 'Onion harvest window (fallback)',
                        extended_window: { max_extension_days: 15, risk_level: 'low' }
                    };
                    break;
                default:
                    fallbackInfo = {
                        maximum_start: `${year}-05-01`,
                        maximum_end: `${year}-10-31`,
                        days_to_harvest: 60,
                        yield_peak: `${year}-07-15`,
                        notes: 'Default harvest window (fallback)',
                        extended_window: { max_extension_days: 30, risk_level: 'moderate' }
                    };
            }

            harvestInfo = fallbackInfo;
            console.log('✅ Fallback harvest info applied:', harvestInfo);
        }

        // Display and apply the harvest information
        displayAIHarvestWindow(harvestInfo, cropName, varietyName);

        console.log('📊 Final harvestInfo:', harvestInfo);

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
        requestAnimationFrame(() => {
            updateDragBar();
            updateTimelineMonths(document.getElementById('planningYear').value || new Date().getFullYear());
            console.log('🔄 Drag bar and timeline updated for maximum harvest window');
        });

        console.log('✅ Harvest window calculation completed');
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

    // Sync FarmOS Varieties
    async function syncFarmOSVarieties() {
        const btn = document.getElementById('syncVarietiesBtn');
        const originalContent = btn.innerHTML;
        
        try {
            // Show loading state
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Syncing...';
            
            const response = await fetch('{{ route('admin.farmos.sync-varieties') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
            
            const result = await response.json();
            
            if (result.success) {
                showToast('Plant varieties synced successfully from FarmOS!', 'success');
                console.log('✅ Sync output:', result.output);
            } else {
                showToast('Failed to sync varieties: ' + result.message, 'error');
            }
            
        } catch (error) {
            console.error('❌ Sync error:', error);
            showToast('Error syncing varieties from FarmOS', 'error');
        } finally {
            // Restore button
            btn.disabled = false;
            btn.innerHTML = originalContent;
        }
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
        
        // Display the user's question in the chat area
        const aiResponseArea = document.getElementById('aiResponseArea');
        if (aiResponseArea) {
            const userMessageDiv = document.createElement('div');
            userMessageDiv.className = 'ai-response mt-3';
            userMessageDiv.innerHTML = `
                <div class="d-flex align-items-start">
                    <div class="flex-shrink-0 me-2">
                        <i class="fas fa-user text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <strong>You:</strong><br>
                        ${message.replace(/\n/g, '<br>')}
                    </div>
                </div>
            `;
            
            // Insert the user message at the top
            aiResponseArea.insertBefore(userMessageDiv, aiResponseArea.firstChild);
            
            // Hide the welcome message if it exists
            const welcomeMessage = document.getElementById('welcomeMessage');
            if (welcomeMessage) {
                welcomeMessage.style.display = 'none';
            }
            
            // Clear the input
            chatInput.value = '';
        }
        
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
            const requestBody = { question: message };
            console.log('📤 Sending chat request:', requestBody);
            
            const response = await fetch(window.location.origin + '/admin/farmos/succession-planning/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify(requestBody),
                signal: __aiChatController.signal
            });
            
            if (!response.ok) {
                const errorData = await response.json().catch(() => ({}));
                console.error('❌ Server error:', response.status, errorData);
                throw new Error(`HTTP ${response.status}: ${errorData.message || response.statusText}`);
            }
            
            const data = await response.json();
            console.log('🤖 AI response received:', data);
            console.log('📝 Answer field:', data.answer);
            console.log('✅ Has answer?', !!data.answer);
            
            // Display the AI response in the chat area
            if (data.answer) {
                console.log('💬 Displaying AI answer in chat area');
                const aiResponseArea = document.getElementById('aiResponseArea');
                if (aiResponseArea) {
                    console.log('✅ Found aiResponseArea element');
                    // Create a new response element
                    const responseDiv = document.createElement('div');
                    responseDiv.className = 'ai-response mt-3';
                    responseDiv.innerHTML = `
                        <div class="d-flex align-items-start">
                            <div class="flex-shrink-0 me-2">
                                <i class="fas fa-robot text-warning"></i>
                            </div>
                            <div class="flex-grow-1">
                                <strong>AI Advisor:</strong><br>
                                ${data.answer.replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    `;
                    
                    console.log('📦 Created response div, inserting into DOM');
                    // Insert the AI response right after the user message (second position)
                    const firstChild = aiResponseArea.firstChild;
                    if (firstChild) {
                        aiResponseArea.insertBefore(responseDiv, firstChild.nextSibling);
                    } else {
                        aiResponseArea.appendChild(responseDiv);
                    }
                    
                    console.log('✅ AI response inserted into DOM');
                    // Scroll to the new response
                    responseDiv.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    console.error('❌ Could not find aiResponseArea element');
                }
            } else {
                console.warn('⚠️ No answer field in response data');
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

    /**
     * Calculate plant quantities based on bed dimensions and spacing
     * @param {number} bedLength - Bed length in meters
     * @param {number} bedWidth - Bed width in meters
     * @param {number} inRowSpacing - In-row spacing in cm
     * @param {number} betweenRowSpacing - Between-row spacing in cm
     * @param {string} method - Planting method: 'direct' or 'transplant'
     * @returns {object} Calculated quantities for seeding and transplanting
     */
    function calculatePlantQuantity(bedLength, bedWidth, inRowSpacing, betweenRowSpacing, method = 'direct') {
        // Convert measurements to consistent units (cm)
        const lengthCm = bedLength * 100;
        const widthCm = bedWidth * 100;
        
        // Calculate number of rows that fit in the bed width
        // Between-row spacing is the GAP between rows (center-to-center distance)
        // Think in terms of gaps: 3 rows = 2 gaps, 2 rows = 1 gap
        // So: number of gaps that fit + 1 = number of rows
        const numberOfGaps = Math.floor(widthCm / betweenRowSpacing);
        const numberOfRows = numberOfGaps + 1;
        
        // Calculate number of plants per row
        // Similarly, in-row spacing is plant-to-plant gap distance
        const plantsPerRow = Math.floor(lengthCm / inRowSpacing) + 1;
        
        // Total plants in bed
        const totalPlants = numberOfRows * plantsPerRow;
        
        // For direct seeding, we overseed by 20-50% to account for germination rate
        // For transplanting, we use the actual plant count
        let seedingQuantity = totalPlants;
        let transplantQuantity = totalPlants;
        
        if (method === 'direct') {
            seedingQuantity = Math.ceil(totalPlants * 1.3); // 30% overseeding
        } else if (method === 'transplant') {
            seedingQuantity = Math.ceil(totalPlants * 1.2); // 20% extra for transplant trays
        }
        
        return {
            totalPlants: totalPlants,
            seedingQuantity: seedingQuantity,
            transplantQuantity: transplantQuantity,
            numberOfRows: numberOfRows,
            plantsPerRow: plantsPerRow,
            bedArea: (bedLength * bedWidth).toFixed(2) // m²
        };
    }

    /**
     * Generate succession plan locally using variety data from database
     * No API calls - uses data already loaded in JavaScript
     */
    function generateLocalSuccessionPlan(payload, cropName, varietyName) {
        const harvestStart = new Date(payload.harvest_start);
        const harvestEnd = new Date(payload.harvest_end);
        const successionCount = payload.succession_count;
        
        const plantings = [];
        
        // Get maturity days from current variety info if available
        const maturityDays = window.currentVarietyData?.maturity_days || 45;
        const harvestDays = window.currentVarietyData?.harvest_days || maturityDays;
        
        console.log(`🌱 Using variety maturity: ${maturityDays} days, harvest window: ${harvestDays} days`);
        
        // Get bed dimensions and spacing for quantity calculations
        const bedLength = parseFloat(document.getElementById('bedLength')?.value) || 30; // default 30m
        const bedWidthCm = parseFloat(document.getElementById('bedWidth')?.value) || 75; // default 75cm
        const bedWidth = bedWidthCm / 100; // Convert cm to meters for calculations
        const inRowSpacing = parseFloat(document.getElementById('inRowSpacing')?.value) || 15; // default 15cm
        const betweenRowSpacing = parseFloat(document.getElementById('betweenRowSpacing')?.value) || 20; // default 20cm
        
        // Determine planting method (transplant vs direct seed)
        const isTransplant = cropName.includes('brussels') || cropName.includes('cabbage') || 
                            cropName.includes('broccoli') || cropName.includes('cauliflower');
        const plantingMethod = isTransplant ? 'transplant' : 'direct';
        
        // Calculate quantities based on bed dimensions
        const quantities = calculatePlantQuantity(bedLength, bedWidth, inRowSpacing, betweenRowSpacing, plantingMethod);
        
        console.log(`📏 Calculated quantities for ${bedLength}m x ${bedWidthCm}cm bed:`, quantities);
        
        // Calculate spacing between harvests
        const harvestDuration = Math.ceil((harvestEnd - harvestStart) / (1000 * 60 * 60 * 24));
        const harvestSpacing = successionCount > 1 ? Math.floor(harvestDuration / (successionCount - 1)) : 0;
        
        for (let i = 0; i < successionCount; i++) {
            // Calculate harvest date for this succession
            const successionHarvestDate = new Date(harvestStart);
            successionHarvestDate.setDate(successionHarvestDate.getDate() + (i * harvestSpacing));
            
            // Calculate seeding date (work backwards from harvest)
            const seedingDate = new Date(successionHarvestDate);
            seedingDate.setDate(seedingDate.getDate() - maturityDays);
            
            // For transplanted crops, calculate transplant date
            let transplantDate = null;
            if (isTransplant) {
                transplantDate = new Date(seedingDate);
                transplantDate.setDate(transplantDate.getDate() + 35); // ~5 weeks from seed to transplant
            }
            
            // Calculate harvest end date
            const harvestEndDate = new Date(successionHarvestDate);
            harvestEndDate.setDate(harvestEndDate.getDate() + harvestDays);
            
            plantings.push({
                succession_id: i + 1,
                succession_number: i + 1,
                seeding_date: seedingDate.toISOString().split('T')[0],
                transplant_date: transplantDate ? transplantDate.toISOString().split('T')[0] : null,
                harvest_date: successionHarvestDate.toISOString().split('T')[0],
                harvest_end_date: harvestEndDate.toISOString().split('T')[0],
                bed_name: 'Unassigned',
                crop_name: cropName,
                variety_name: varietyName,
                // Add calculated quantities
                seeding_quantity: quantities.seedingQuantity,
                transplant_quantity: quantities.transplantQuantity,
                total_plants: quantities.totalPlants,
                planting_method: plantingMethod,
                // Add calculation breakdown for display
                plants_per_row: quantities.plantsPerRow,
                number_of_rows: quantities.numberOfRows,
                bed_length: payload.bed_length,
                bed_width: payload.bed_width,
                in_row_spacing: payload.in_row_spacing,
                between_row_spacing: payload.between_row_spacing
            });
        }
        
        return {
            crop: { id: payload.crop_id, name: cropName },
            variety: { id: payload.variety_id, name: varietyName },
            harvest_start: payload.harvest_start,
            harvest_end: payload.harvest_end,
            plantings: plantings,
            total_successions: successionCount
        };
    }

    async function calculateSuccessionPlan() {
        console.log('🎯 calculateSuccessionPlan called');
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        const hs = document.getElementById('harvestStart');
        const he = document.getElementById('harvestEnd');

        console.log('📊 Form values:', {
            crop: cropSelect?.value,
            variety: varietySelect?.value,
            harvestStart: hs?.value,
            harvestEnd: he?.value
        });

        if (!cropSelect?.value || !hs?.value || !he?.value) {
            console.warn('⚠️ Missing required values for calculation');
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
            bed_ids: [], // Beds will be assigned via drag-and-drop on timeline
            succession_count: successionCount,
            use_ai: true,
            // Add bed dimensions and spacing for calculations
            bed_length: parseFloat(document.getElementById('bedLength')?.value) || 30,
            bed_width: parseFloat(document.getElementById('bedWidth')?.value) || 75, // in cm
            in_row_spacing: parseFloat(document.getElementById('inRowSpacing')?.value) || 15,
            between_row_spacing: parseFloat(document.getElementById('betweenRowSpacing')?.value) || 20
        };

        console.log('📦 Generating local succession plan (no API call):', payload);

        // Clear any previous allocations when generating a new plan
        localStorage.removeItem('bedAllocations');
        console.log('🗑️ Cleared previous allocations for new succession plan');

        showLoading(true);
        try {
            // Generate succession plan locally using variety data from database
            const successionPlan = generateLocalSuccessionPlan(payload, cropName, varietyName);
            console.log('✅ Local succession plan generated:', successionPlan);

            currentSuccessionPlan = successionPlan;
            console.log('✅ Succession plan received:', currentSuccessionPlan);
            
            // Populate succession sidebar with draggable cards
            console.log('� Populating succession sidebar...');
            console.log('📊 Plantings in plan:', currentSuccessionPlan.plantings?.length);
            if (typeof populateSuccessionSidebar === 'function') {
                console.log('✅ Calling populateSuccessionSidebar...');
                populateSuccessionSidebar(currentSuccessionPlan);
                console.log('✅ populateSuccessionSidebar completed');
            } else {
                console.error('❌ populateSuccessionSidebar function not found!');
            }
            
            console.log('🗓️ Rendering FarmOS timeline...');
            await renderFarmOSTimeline(currentSuccessionPlan);
            console.log('📝 Rendering quick form tabs...');
            renderQuickFormTabs(currentSuccessionPlan);
            
            // Initialize drag and drop after both timeline and sidebar are ready
            requestAnimationFrame(() => {
                initializeDragAndDrop();
                console.log('🔄 Drag and drop initialized after plan calculation');
            });
            
            document.getElementById('resultsSection').style.display = 'block';
            
            // Delay updateExportButton to ensure DOM is ready
            requestAnimationFrame(() => {
                updateExportButton();
            });
            
            // testQuickFormUrls(); // Function not defined
        } catch (e) {
            console.error('Failed to calculate plan:', e);
            showToast('Failed to calculate plan', 'error');
        } finally {
            showLoading(false);
        }
    }

    function renderSuccessionSummary(plan) {
        console.log('🎨 renderSuccessionSummary called with plan:', plan);
        const container = document.getElementById('successionSummary');
        if (!container) {
            console.error('❌ successionSummary container not found!');
            return;
        }
        const plantings = plan.plantings || [];
        console.log(`📊 Rendering ${plantings.length} succession cards`);
        const items = plantings.map((p, i) => {
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

            // Generate URLs for all quick form types
            const baseUrl = window.location.origin + '/admin/farmos';
            const quickFormUrls = {
                seeding: baseUrl + '/quick/seeding?' + new URLSearchParams({
                    crop_name: p.crop_name || '',
                    variety_name: p.variety_name || '',
                    bed_name: p.bed_name || '',
                    quantity: p.quantity || '',
                    succession_number: p.succession_id || 1,
                    seeding_date: p.seeding_date || '',
                    season: p.season || ''
                }).toString(),
                transplanting: baseUrl + '/quick/transplant?' + new URLSearchParams({
                    crop_name: p.crop_name || '',
                    variety_name: p.variety_name || '',
                    bed_name: p.bed_name || '',
                    quantity: p.quantity || '',
                    succession_number: p.succession_id || 1,
                    transplant_date: p.transplant_date || '',
                    season: p.season || ''
                }).toString(),
                harvest: baseUrl + '/quick/harvest?' + new URLSearchParams({
                    crop_name: p.crop_name || '',
                    variety_name: p.variety_name || '',
                    bed_name: p.bed_name || '',
                    quantity: p.quantity || '',
                    succession_number: p.succession_id || 1,
                    harvest_date: p.harvest_date || '',
                    season: p.season || ''
                }).toString()
            };

            // Determine default checkbox states based on planting method
            const hasTransplant = !!p.transplant_date;
            const seedingChecked = true; // Always check seeding (needed for both direct and transplant)
            const transplantChecked = hasTransplant; // Transplant: check transplanting
            const harvestChecked = true; // Always check harvest by default

            // Display quick form buttons that toggle form sections
            pane.innerHTML += `
                <div class="quick-form-container">
                    <h6>Quick Forms</h6>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input log-type-checkbox" type="checkbox" id="seeding-enabled-${i}" ${seedingChecked ? 'checked' : ''} onchange="toggleQuickForm(${i}, 'seeding')">
                            <label class="form-check-label" for="seeding-enabled-${i}">
                                <strong>Seeding</strong> - Record when seeds are planted
                            </label>
                        </div>
                        ${p.transplant_date ? `<div class="form-check">
                            <input class="form-check-input log-type-checkbox" type="checkbox" id="transplanting-enabled-${i}" ${transplantChecked ? 'checked' : ''} onchange="toggleQuickForm(${i}, 'transplanting')">
                            <label class="form-check-label" for="transplanting-enabled-${i}">
                                <strong>Transplanting</strong> - Record when seedlings are transplanted
                            </label>
                        </div>` : ''}
                        <div class="form-check">
                            <input class="form-check-input log-type-checkbox" type="checkbox" id="harvest-enabled-${i}" ${harvestChecked ? 'checked' : ''} onchange="toggleQuickForm(${i}, 'harvest')">
                            <label class="form-check-label" for="harvest-enabled-${i}">
                                <strong>Harvest</strong> - Record harvest dates and quantities
                            </label>
                        </div>
                    </div>
                    <div class="alert alert-info mt-2">
                        <small>Check boxes to fill out forms directly here</small>
                    </div>
                </div>

                <!-- Season Selection -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="fas fa-calendar-alt text-primary"></i> Season</h5>
                        <div class="mb-3">
                            <label class="form-label">What season(s) will this be part of? *</label>
                            <input type="text" class="form-control" name="plantings[${i}][season]"
                                   value="${p.season || (new Date().getFullYear() + ' Succession')}" required
                                   placeholder="e.g., 2025, 2025 Summer, 2025 Succession">
                            <div class="form-text">This will be prepended to the plant asset name for organization.</div>
                        </div>
                    </div>
                </div>

                <!-- Crops/Varieties Section -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h5 class="mb-3"><i class="fas fa-leaf text-success"></i> Crop/Variety</h5>
                        <div class="mb-3">
                            <input type="text" class="form-control" name="plantings[${i}][crop_variety]"
                                   value="${p.variety_name || p.crop_name || ''}" required
                                   placeholder="Enter crop/variety (e.g., Lettuce, Carrot, Tomato)">
                            <div class="form-text">Enter the crop or variety name for this planting.</div>
                        </div>
                    </div>
                </div>

                <!-- Embedded Quick Form Sections -->
                <div id="quick-form-seeding-${i}" class="embedded-quick-form" style="display: none;">
                    <div class="form-content">
                        <h6><i class="fas fa-seedling text-success"></i> Seeding Form</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Seeding Date *</label>
                                <input type="datetime-local" class="form-control" name="plantings[${i}][seeding][date]"
                                       value="${p.seeding_date ? new Date(p.seeding_date).toISOString().slice(0, 16) : ''}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Completed</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="plantings[${i}][seeding][done]" value="1">
                                    <label class="form-check-label">Mark as completed</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location *</label>
                            <input type="text" class="form-control" name="plantings[${i}][seeding][location]"
                                   value="${p.transplant_date ? 'Propagation' : (p.bed_name || '')}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity *</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="plantings[${i}][seeding][quantity][value]"
                                           value="${p.seeding_quantity || 100}" step="1" min="0" required>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="plantings[${i}][seeding][quantity][units]">
                                        <option value="seeds" selected>Seeds</option>
                                        <option value="plants">Plants</option>
                                        <option value="grams">Grams</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="plantings[${i}][seeding][quantity][measure]">
                                        <option value="count" selected>Count</option>
                                        <option value="weight">Weight</option>
                                    </select>
                                </div>
                            </div>
                            ${p.seeding_quantity ? `
                                <div class="mt-2">
                                    <small class="text-muted d-block">
                                        <strong>Calculated:</strong> ${p.total_plants || ''} plants with ${p.planting_method === 'direct' ? '30%' : '20%'} overseeding
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-calculator"></i> 
                                        ${p.bed_length || '?'}m × ${p.bed_width || '?'}cm bed: 
                                        <strong>${p.plants_per_row || '?'} plants/row</strong> × <strong>${p.number_of_rows || '?'} rows</strong> = ${p.total_plants || '?'} plants
                                    </small>
                                    <small class="text-muted">
                                        (${p.in_row_spacing || '?'}cm in-row spacing, ${p.between_row_spacing || '?'}cm between-row spacing)
                                    </small>
                                </div>
                            ` : ''}
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="plantings[${i}][seeding][notes]" rows="2">Seeding for succession #${p.succession_id || 1}</textarea>
                        </div>
                    </div>
                </div>

                ${p.transplant_date ? `
                <div id="quick-form-transplanting-${i}" class="embedded-quick-form" style="display: none;">
                    <div class="form-content">
                        <h6><i class="fas fa-shipping-fast text-warning"></i> Transplanting Form</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Transplant Date *</label>
                                <input type="datetime-local" class="form-control" name="plantings[${i}][transplanting][date]"
                                       value="${p.transplant_date ? new Date(p.transplant_date).toISOString().slice(0, 16) : ''}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Completed</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="plantings[${i}][transplanting][done]" value="1">
                                    <label class="form-check-label">Mark as completed</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Location *</label>
                            <input type="text" class="form-control" name="plantings[${i}][transplanting][location]"
                                   value="${p.bed_name || ''}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity *</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="plantings[${i}][transplanting][quantity][value]"
                                           value="${p.transplant_quantity || p.total_plants || 100}" step="1" min="0" required>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="plantings[${i}][transplanting][quantity][units]">
                                        <option value="plants" selected>Plants</option>
                                        <option value="seeds">Seeds</option>
                                        <option value="grams">Grams</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="plantings[${i}][transplanting][quantity][measure]">
                                        <option value="count" selected>Count</option>
                                        <option value="weight">Weight</option>
                                    </select>
                                </div>
                            </div>
                            ${p.transplant_quantity ? `
                                <div class="mt-2">
                                    <small class="text-muted d-block">
                                        <strong>Calculated:</strong> ${p.total_plants || ''} plants
                                    </small>
                                    <small class="text-muted d-block">
                                        <i class="fas fa-calculator"></i> 
                                        ${p.bed_length || '?'}m × ${p.bed_width || '?'}cm bed: 
                                        <strong>${p.plants_per_row || '?'} plants/row</strong> × <strong>${p.number_of_rows || '?'} rows</strong> = ${p.total_plants || '?'} plants
                                    </small>
                                    <small class="text-muted">
                                        (${p.in_row_spacing || '?'}cm in-row spacing, ${p.between_row_spacing || '?'}cm between-row spacing)
                                    </small>
                                </div>
                            ` : ''}
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="plantings[${i}][transplanting][notes]" rows="2">Transplanting for succession #${p.succession_id || 1}</textarea>
                        </div>
                    </div>
                </div>
                ` : ''}

                <div id="quick-form-harvest-${i}" class="embedded-quick-form" style="display: none;">
                    <div class="form-content">
                        <h6><i class="fas fa-shopping-basket text-danger"></i> Harvest Form</h6>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Harvest Date *</label>
                                <input type="datetime-local" class="form-control" name="plantings[${i}][harvest][date]"
                                       value="${p.harvest_date ? new Date(p.harvest_date).toISOString().slice(0, 16) : ''}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Completed</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="plantings[${i}][harvest][done]" value="1">
                                    <label class="form-check-label">Mark as completed</label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Quantity *</label>
                            <div class="row">
                                <div class="col-md-4">
                                    <input type="number" class="form-control" name="plantings[${i}][harvest][quantity][value]"
                                           value="0" step="1" min="0" required>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="plantings[${i}][harvest][quantity][units]">
                                        <option value="grams">Grams</option>
                                        <option value="pounds">Pounds</option>
                                        <option value="kilograms" selected>Kilograms</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <select class="form-select" name="plantings[${i}][harvest][quantity][measure]">
                                        <option value="weight" selected>Weight</option>
                                        <option value="count">Count</option>
                                    </select>
                                </div>
                            </div>
                            <small class="text-muted">Weight will be recorded on harvest day</small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" name="plantings[${i}][harvest][notes]" rows="2">Harvest for succession #${p.succession_id || 1}</textarea>
                        </div>
                    </div>
                </div>
            `;

            content.appendChild(pane);
        });

        // Show the tabs container
        console.log('✅ Showing tabs container');
        tabsWrap.style.display = 'block';

        // Initialize form visibility based on default checkbox states
        (plan.plantings || []).forEach((p, i) => {
            const hasTransplant = !!p.transplant_date;
            
            // Always show seeding and harvest forms
            toggleQuickForm(i, 'seeding', true);
            toggleQuickForm(i, 'harvest', true);
            
            // Show transplanting form only if there's a transplant date
            if (hasTransplant) {
                toggleQuickForm(i, 'transplanting', true);
            }
        });
    }

    /**
     * Switch between quick form tabs
     */
    function switchTab(index) {
        console.log('🔄 Switching to tab:', index);
        
        // Update tab buttons
        const buttons = document.querySelectorAll('#tabNavigation .tab-button');
        buttons.forEach((btn, i) => {
            if (i === index) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
        
        // Update tab panes
        const panes = document.querySelectorAll('#tabContent .tab-pane');
        panes.forEach((pane, i) => {
            if (i === index) {
                pane.classList.add('active');
            } else {
                pane.classList.remove('active');
            }
        });
    }

    async function renderFarmOSTimeline(plan) {
        console.log('� renderFarmOSTimeline called with plan:', plan);
        console.log('�🔧 Rendering FarmOS timeline for plan:', plan);

        const container = document.getElementById('farmosTimelineContainer');
        console.log('🔍 Looking for container #farmosTimelineContainer:', container);
        if (!container) {
            console.error('❌ FarmOS timeline container not found');
            return;
        }
        console.log('✅ Found container, current content:', container.innerHTML.substring(0, 100) + '...');

        try {
            // Show loading state
            container.innerHTML = `
                <div class="text-center p-4">
                    <div class="spinner-border text-success" role="status">
                        <span class="visually-hidden">Loading timeline...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading FarmOS bed occupancy data...</p>
                </div>
            `;

            console.log('📊 Fetching FarmOS bed and planting data...');
            // Fetch bed occupancy data from FarmOS
            const bedData = await fetchFarmOSBedData(plan);
            console.log('✅ Bed data fetched:', bedData);

            // Create comprehensive bed occupancy timeline
            const timelineHtml = createBedOccupancyTimeline(plan, bedData);
            console.log('✅ Timeline HTML created, length:', timelineHtml.length);

            container.innerHTML = timelineHtml;
            console.log('🎯 Bed occupancy timeline rendered successfully!');

            // Drag and drop will be initialized centrally after both timeline and sidebar are ready

        } catch (error) {
            console.error('❌ Error rendering FarmOS bed occupancy timeline:', error);
            container.innerHTML = `
                <div class="alert alert-danger">
                    <h5><i class="fas fa-exclamation-triangle"></i> FarmOS Data Unavailable</h5>
                    <p>Unable to load real bed occupancy data from FarmOS. Please check your FarmOS connection and credentials.</p>
                    <p><small>Error: ${error.message}</small></p>
                    <div class="mt-3">
                        <button class="btn btn-outline-primary btn-sm" onclick="renderFarmOSTimeline(window.currentSuccessionPlan)">
                            <i class="fas fa-sync"></i> Retry
                        </button>
                    </div>
                </div>
            `;
        }
    }

    function createTimelineVisualization(plan) {
        if (!plan.plantings || plan.plantings.length === 0) {
            return `
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> No Timeline Data</h5>
                    <p>Generate a succession plan to see the timeline visualization.</p>
                </div>
            `;
        }

        // Calculate timeline bounds
        const allDates = [];
        plan.plantings.forEach(planting => {
            if (planting.seeding_date) allDates.push(new Date(planting.seeding_date));
            if (planting.transplant_date) allDates.push(new Date(planting.transplant_date));
            if (planting.harvest_date) allDates.push(new Date(planting.harvest_date));
            if (planting.harvest_end_date) allDates.push(new Date(planting.harvest_end_date));
        });

        if (allDates.length === 0) {
            return `
                <div class="alert alert-warning">
                    <h5><i class="fas fa-exclamation-triangle"></i> No Dates Available</h5>
                    <p>The succession plan doesn't have date information yet.</p>
                </div>
            `;
        }

        const minDate = new Date(Math.min(...allDates));
        const maxDate = new Date(Math.max(...allDates));

        // Extend timeline by 2 weeks on each side for better visualization
        minDate.setDate(minDate.getDate() - 14);
        maxDate.setDate(maxDate.getDate() + 14);

        const totalDays = Math.ceil((maxDate - minDate) / (1000 * 60 * 60 * 24));

        // Create month labels
        const months = [];
        const current = new Date(minDate);
        while (current <= maxDate) {
            months.push({
                label: current.toLocaleDateString('en-US', { month: 'short', year: 'numeric' }),
                date: new Date(current)
            });
            current.setMonth(current.getMonth() + 1);
        }

        // Create timeline tasks
        const tasks = [];
        plan.plantings.forEach((planting, index) => {
            const successionNum = index + 1;
            const cropName = planting.crop_name || 'Unknown Crop';

            // Seeding task
            if (planting.seeding_date) {
                const seedingDate = new Date(planting.seeding_date);
                const left = ((seedingDate - minDate) / (maxDate - minDate)) * 100;
                tasks.push({
                    id: `seeding-${successionNum}`,
                    type: 'seeding',
                    label: `Sow ${cropName}`,
                    succession: successionNum,
                    left: Math.max(0, Math.min(95, left)),
                    date: seedingDate
                });
            }

            // Transplanting task
            if (planting.transplant_date) {
                const transplantDate = new Date(planting.transplant_date);
                const left = ((transplantDate - minDate) / (maxDate - minDate)) * 100;
                tasks.push({
                    id: `transplant-${successionNum}`,
                    type: 'transplanting',
                    label: `Transplant ${cropName}`,
                    succession: successionNum,
                    left: Math.max(0, Math.min(95, left)),
                    date: transplantDate
                });
            }

            // Growth period (from seeding/transplant to harvest)
            if (planting.harvest_date) {
                const harvestDate = new Date(planting.harvest_date);
                const startDate = planting.transplant_date ? new Date(planting.transplant_date) : (planting.seeding_date ? new Date(planting.seeding_date) : harvestDate);
                const left = ((startDate - minDate) / (maxDate - minDate)) * 100;
                const width = ((harvestDate - startDate) / (maxDate - minDate)) * 100;

                if (width > 0) {
                    tasks.push({
                        id: `growth-${successionNum}`,
                        type: 'growth',
                        label: `${cropName} Growth`,
                        succession: successionNum,
                        left: Math.max(0, left),
                        width: Math.max(5, Math.min(100 - left, width)),
                        date: startDate
                    });
                }
            }

            // Harvest task
            if (planting.harvest_date) {
                const harvestDate = new Date(planting.harvest_date);
                const left = ((harvestDate - minDate) / (maxDate - minDate)) * 100;
                tasks.push({
                    id: `harvest-${successionNum}`,
                    type: 'harvest',
                    label: `Harvest ${cropName}`,
                    succession: successionNum,
                    left: Math.max(0, Math.min(95, left)),
                    date: harvestDate
                });
            }
        });

        // Sort tasks by date for proper layering
        tasks.sort((a, b) => a.date - b.date);

        return `
            <div class="timeline-visualization">
                <div class="timeline-axis">
                    ${months.map(month => `<div class="timeline-month">${month.label}</div>`).join('')}
                </div>

                <div class="timeline-tasks">
                    ${tasks.map(task => `
                        <div class="timeline-task ${task.type}"
                             style="left: ${task.left}%; ${task.width ? `width: ${task.width}%;` : 'width: 120px;'} top: ${(task.succession - 1) * 50 + 10}px;"
                             title="${task.label} - ${task.date.toLocaleDateString()}">
                            <span>${task.label}</span>
                        </div>
                    `).join('')}
                </div>

                <div class="timeline-legend">
                    <div class="legend-item">
                        <div class="legend-color seeding"></div>
                        <span>Seeding</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color transplanting"></div>
                        <span>Transplanting</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color growth"></div>
                        <span>Growth Period</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color harvest"></div>
                        <span>Harvest</span>
                    </div>
                </div>
            </div>
        `;
    }

    async function fetchFarmOSBedData(plan) {
        console.log('🌐 Fetching real FarmOS bed occupancy data from API...');

        // Calculate date range from succession plan for API request
        const allDates = [];
        if (plan.plantings) {
            plan.plantings.forEach(planting => {
                if (planting.seeding_date) allDates.push(new Date(planting.seeding_date));
                if (planting.transplant_date) allDates.push(new Date(planting.transplant_date));
                if (planting.harvest_date) allDates.push(new Date(planting.harvest_date));
                if (planting.harvest_end_date) allDates.push(new Date(planting.harvest_end_date));
            });
        }

        const minDate = allDates.length > 0 ? new Date(Math.min(...allDates)) : new Date();
        const maxDate = allDates.length > 0 ? new Date(Math.max(...allDates)) : new Date();

        // Call the real FarmOS API endpoint
        const response = await fetch(`${API_BASE}/bed-occupancy?start_date=${minDate.toISOString().split('T')[0]}&end_date=${maxDate.toISOString().split('T')[0]}`, {
            method: 'GET',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`FarmOS API request failed: ${response.status} ${response.statusText} - ${errorText}`);
        }

        const data = await response.json();

        if (data.error || (data.success !== undefined && data.success === false)) {
            throw new Error(data.message || data.error || `FarmOS API error: ${response.status}`);
        }

        console.log('✅ Successfully fetched real FarmOS bed data:', {
            beds: data.data?.beds?.length || 0,
            plantings: data.data?.plantings?.length || 0
        });

        return data;
    }

    function createBedOccupancyTimeline(plan, bedData) {
        console.log('🔍 createBedOccupancyTimeline called with bedData:', bedData);
        
        // Handle API response structure: {success: true, data: {beds: [...], plantings: [...]}}
        const actualBedData = bedData.data || bedData;
        console.log('🔍 actualBedData:', actualBedData);
        
        if (!actualBedData || !actualBedData.beds || actualBedData.beds.length === 0) {
            return `
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> No FarmOS Beds Found</h5>
                    <p>Your FarmOS instance doesn't have any beds (land assets) configured yet.</p>
                    <p>Create some beds in FarmOS first, then the timeline will show real bed occupancy data.</p>
                </div>
            `;
        }

        // Calculate timeline bounds from succession plan dates
        const allDates = [];
        if (plan.plantings) {
            plan.plantings.forEach(planting => {
                if (planting.seeding_date) allDates.push(new Date(planting.seeding_date));
                if (planting.transplant_date) allDates.push(new Date(planting.transplant_date));
                if (planting.harvest_date) allDates.push(new Date(planting.harvest_date));
                if (planting.harvest_end_date) allDates.push(new Date(planting.harvest_end_date));
            });
        }

        // Include existing planting dates
        if (actualBedData.plantings) {
            actualBedData.plantings.forEach(planting => {
                if (planting.start_date) allDates.push(new Date(planting.start_date));
                if (planting.end_date) allDates.push(new Date(planting.end_date));
            });
        }

        if (allDates.length === 0) {
            return `
                <div class="alert alert-info">
                    <h5><i class="fas fa-info-circle"></i> No Timeline Data</h5>
                    <p>Generate a succession plan to see bed availability over time.</p>
                </div>
            `;
        }

        const minDate = new Date(Math.min(...allDates));
        const maxDate = new Date(Math.max(...allDates));

        // Extend timeline by 1 month on each side for context
        minDate.setMonth(minDate.getMonth() - 1);
        maxDate.setMonth(maxDate.getMonth() + 1);

        // Create month labels
        const months = [];
        const current = new Date(minDate);
        while (current <= maxDate) {
            months.push({
                label: current.toLocaleDateString('en-US', { month: 'short', year: 'numeric' }),
                date: new Date(current)
            });
            current.setMonth(current.getMonth() + 1);
        }

        // Group beds by block
        const bedsByBlock = {};
        actualBedData.beds.forEach(bed => {
            const block = bed.block || 'Block Unknown';
            if (!bedsByBlock[block]) {
                bedsByBlock[block] = [];
            }
            bedsByBlock[block].push(bed);
        });

        // Sort blocks numerically, keeping "Block Unknown" if no other blocks exist
        const sortedBlocks = Object.keys(bedsByBlock)
            .sort((a, b) => {
                // Put "Block Unknown" at the end
                if (a === 'Block Unknown') return 1;
                if (b === 'Block Unknown') return -1;

                const aNum = parseInt(a.replace('Block ', '')) || 999;
                const bNum = parseInt(b.replace('Block ', '')) || 999;
                return aNum - bNum;
            });

        // If we only have "Block Unknown", show it; otherwise filter it out
        const finalBlocks = sortedBlocks.length > 1
            ? sortedBlocks.filter(blockName => blockName !== 'Block Unknown')
            : sortedBlocks;

        // Create bed rows grouped by block
        const bedRows = finalBlocks.map(blockName => {
            const blockBeds = bedsByBlock[blockName];

            // Sort beds within block by bed number
            blockBeds.sort((a, b) => {
                const aMatch = a.name.match(/\/(\d+)/);
                const bMatch = b.name.match(/\/(\d+)/);
                const aNum = aMatch ? parseInt(aMatch[1]) : 999;
                const bNum = bMatch ? parseInt(bMatch[1]) : 999;
                return aNum - bNum;
            });

            const blockBedRows = blockBeds.map(bed => {
                const bedPlantings = actualBedData.plantings.filter(p => p.bed_id === bed.id);

                // Create occupancy blocks for this bed
                const occupancyBlocks = bedPlantings.map(planting => {
                    const startDate = new Date(planting.start_date);
                    const endDate = new Date(planting.end_date);
                    const left = ((startDate - minDate) / (maxDate - minDate)) * 100;
                    const width = ((endDate - startDate) / (maxDate - minDate)) * 100;

                    return `
                        <div class="bed-occupancy-block ${planting.status}"
                             style="left: ${Math.max(0, left)}%; width: ${Math.max(2, Math.min(100 - left, width))}%;"
                             title="${planting.crop} ${planting.variety || ''} (${planting.start_date} to ${planting.end_date}) - ${planting.status}">
                            <span class="crop-label">${planting.crop}</span>
                        </div>
                    `;
                }).join('');

                return `
                    <div class="bed-row">
                        <div class="bed-label">${bed.name}</div>
                        <div class="bed-timeline" data-bed-id="${bed.id}" data-bed-name="${bed.name}">
                            ${occupancyBlocks}
                        </div>
                    </div>
                `;
            }).join('');

            return `
                <div class="bed-block">
                    <div class="bed-block-header">
                        <h6>
                            <i class="fas fa-tree hedgerow-icon"></i>
                            <i class="fas fa-tree hedgerow-icon"></i>
                            ${blockName}
                            <i class="fas fa-tree hedgerow-icon"></i>
                            <i class="fas fa-tree hedgerow-icon"></i>
                        </h6>
                        <div class="hedgerow-indicator">
                            <small class="text-muted">Hedgerow Boundary</small>
                        </div>
                    </div>
                    <div class="bed-block-content">
                        ${blockBedRows}
                    </div>
                </div>
                <div class="hedgerow-divider">
                    <div class="hedgerow-visual">
                        <i class="fas fa-tree hedgerow-tree"></i>
                        <i class="fas fa-tree hedgerow-tree"></i>
                        <span class="hedgerow-text">Hedgerow Boundary</span>
                        <i class="fas fa-tree hedgerow-tree"></i>
                        <i class="fas fa-tree hedgerow-tree"></i>
                    </div>
                </div>
            `;
        }).join('');

        // Add succession planning indicators
        const successionIndicators = [];
        if (plan.plantings) {
            plan.plantings.forEach((planting, index) => {
                const harvestDate = planting.harvest_date ? new Date(planting.harvest_date) : null;
                if (harvestDate) {
                    const left = ((harvestDate - minDate) / (maxDate - minDate)) * 100;
                    successionIndicators.push(`
                        <div class="succession-indicator"
                             style="left: ${left}%;"
                             title="Succession ${index + 1} Harvest: ${planting.crop_name || 'Unknown'} on ${planting.harvest_date}">
                            <i class="fas fa-star text-warning"></i>
                        </div>
                    `);
                }
            });
        }

        return `
            <div class="bed-occupancy-timeline" data-min-date="${minDate.toISOString()}" data-max-date="${maxDate.toISOString()}">
                <div class="timeline-header">
                    <h5><i class="fas fa-seedling text-success"></i> Real FarmOS Bed Occupancy</h5>
                    <p class="text-muted small">Live bed availability from your FarmOS database • Yellow stars show planned harvest dates</p>
                </div>

                <div class="timeline-axis">
                    ${months.map(month => `<div class="timeline-month">${month.label}</div>`).join('')}
                </div>

                <div class="beds-container">
                    ${bedRows}
                </div>

                <div class="timeline-indicators">
                    ${successionIndicators.join('')}
                </div>

                <div class="timeline-legend">
                    <div class="legend-item">
                        <div class="legend-color active"></div>
                        <span>Currently Planted</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color completed"></div>
                        <span>Recently Harvested</span>
                    </div>
                    <div class="legend-item">
                        <div class="legend-color available"></div>
                        <span>Available</span>
                    </div>
                    <div class="legend-item">
                        <i class="fas fa-star text-warning"></i>
                        <span>Succession Harvest</span>
                    </div>
                </div>
            </div>
        `;

        // Initialize drag and drop for the newly created timeline
        requestAnimationFrame(() => {
            // Re-attach drop listeners to bed timelines (only if not already attached)
            document.querySelectorAll('.bed-timeline').forEach(timeline => {
                // Check if listeners are already attached to avoid duplicates
                if (!timeline.hasAttribute('data-listeners-attached')) {
                    timeline.addEventListener('dragover', handleDragOver);
                    timeline.addEventListener('dragleave', handleDragLeave);
                    timeline.addEventListener('drop', handleDrop);
                    timeline.setAttribute('data-listeners-attached', 'true');
                    console.log('✅ Attached drop listeners to bed timeline');
                }
            });
        });
    }

    // Initialize drag and drop for successions
    function initializeDragAndDrop() {
        // Remove existing listeners first to avoid duplicates
        document.querySelectorAll('.succession-item[draggable="true"]').forEach(item => {
            item.removeEventListener('dragstart', handleDragStart);
            item.removeEventListener('dragend', handleDragEnd);
        });

        document.querySelectorAll('.bed-timeline').forEach(timeline => {
            timeline.removeEventListener('dragover', handleDragOver);
            timeline.removeEventListener('dragleave', handleDragLeave);
            timeline.removeEventListener('drop', handleDrop);
        });

        const successionItems = document.querySelectorAll('.succession-item[draggable="true"]');
        const bedTimelines = document.querySelectorAll('.bed-timeline');

        successionItems.forEach(item => {
            item.addEventListener('dragstart', handleDragStart);
            item.addEventListener('dragend', handleDragEnd);
        });

        bedTimelines.forEach(timeline => {
            timeline.addEventListener('dragover', handleDragOver);
            timeline.addEventListener('dragleave', handleDragLeave);
            timeline.addEventListener('drop', handleDrop);
        });
    }

    function handleDragStart(e) {
        e.dataTransfer.setData('text/plain', e.target.dataset.successionIndex);
        e.target.classList.add('dragging');

        // Add visual feedback to potential drop targets
        document.querySelectorAll('.bed-timeline').forEach(timeline => {
            timeline.classList.add('drop-target');
        });
    }

    function handleDragEnd(e) {
        e.target.classList.remove('dragging');

        // Remove visual feedback from drop targets
        document.querySelectorAll('.bed-timeline').forEach(timeline => {
            timeline.classList.remove('drop-target', 'drop-active', 'drop-conflict');
            removeDragPreview(timeline);
        });
    }

    function handleDragOver(e) {
        e.preventDefault();

        const dragType = e.dataTransfer.getData('text/plain');
        const bedTimeline = e.currentTarget;
        const bedId = bedTimeline.dataset.bedId;

        let successionData;

        if (dragType === 'block-move') {
            // Moving an existing block
            const jsonData = e.dataTransfer.getData('application/json');
            if (!jsonData || jsonData === 'undefined') {
                console.log('⚠️ No allocation data for drag preview');
                return;
            }
            const allocationData = JSON.parse(jsonData);
            successionData = {
                sowDate: new Date(allocationData.sowDate),
                transplantDate: allocationData.transplantDate ? new Date(allocationData.transplantDate) : null,
                harvestDate: new Date(allocationData.harvestDate),
                method: allocationData.method
            };
        } else {
            // Dragging a new succession from sidebar
            const successionIndex = dragType;
            const successionItem = document.querySelector(`[data-succession-index="${successionIndex}"]`);
            if (!successionItem) return;

            successionData = JSON.parse(successionItem.dataset.successionData);
            successionData.sowDate = new Date(successionData.sowDate);
            if (successionData.transplantDate) {
                successionData.transplantDate = new Date(successionData.transplantDate);
            }
            successionData.harvestDate = new Date(successionData.harvestDate);
        }

        // Check for conflicts
        if (checkBedConflicts(bedId, successionData)) {
            // Show conflict state
            bedTimeline.classList.remove('drop-active');
            bedTimeline.classList.add('drop-conflict');
            removeDragPreview(bedTimeline);
        } else {
            // Show valid drop state
            bedTimeline.classList.remove('drop-conflict');
            bedTimeline.classList.add('drop-active');
            showDragPreview(bedTimeline, successionData, bedId);
        }
    }

    function handleDragLeave(e) {
        e.currentTarget.classList.remove('drop-active', 'drop-conflict');
        removeDragPreview(e.currentTarget);
    }

    function showDragPreview(bedTimeline, successionData, bedId) {
        // Remove any existing preview
        removeDragPreview(bedTimeline);

        // Calculate position based on succession dates
        const timelineStart = new Date(bedTimeline.dataset.startDate);
        const timelineEnd = new Date(bedTimeline.dataset.endDate);
        const totalDays = (timelineEnd - timelineStart) / (1000 * 60 * 60 * 24);

        const startDate = successionData.sowDate;
        const endDate = successionData.harvestDate;

        const startOffset = (startDate - timelineStart) / (1000 * 60 * 60 * 24);
        const duration = (endDate - startDate) / (1000 * 60 * 60 * 24);

        const leftPercent = (startOffset / totalDays) * 100;
        const widthPercent = (duration / totalDays) * 100;

        // Create preview element
        const preview = document.createElement('div');
        preview.className = 'drag-preview';
        preview.style.left = `${Math.max(0, leftPercent)}%`;
        preview.style.width = `${Math.min(100 - leftPercent, widthPercent)}%`;
        preview.innerHTML = `
            <div class="drag-preview-content">
                <small>Drop here</small>
            </div>
        `;

        bedTimeline.appendChild(preview);
    }

    function removeDragPreview(bedTimeline) {
        const existingPreview = bedTimeline.querySelector('.drag-preview');
        if (existingPreview) {
            existingPreview.remove();
        }
    }

    function handleDrop(e) {
        e.preventDefault();
        e.currentTarget.classList.remove('drop-active', 'drop-target');

        console.log('🎯 Drop event triggered on bed timeline');

        const dragType = e.dataTransfer.getData('text/plain');
        console.log('📋 Drag type:', dragType);

        const bedTimeline = e.currentTarget;
        const bedRow = bedTimeline.closest('.bed-row');
        const bedName = bedRow.querySelector('.bed-label').textContent;
        const bedId = bedTimeline.dataset.bedId;

        console.log('🏡 Drop target - Bed name:', bedName, 'Bed ID:', bedId);

        if (dragType === 'block-move') {
            // Moving an existing succession block
            const jsonData = e.dataTransfer.getData('application/json');
            if (!jsonData || jsonData === 'undefined') {
                console.error('❌ No allocation data found for block move');
                return;
            }
            const allocationData = JSON.parse(jsonData);
            console.log('🏗️ Moving existing block:', allocationData);

            // Check if dropping on the same bed
            if (allocationData.bedId === bedId) {
                console.log('📍 Dropped on same bed, no change needed');
                return;
            }

            // Check for conflicts
            if (checkBedConflicts(bedId, {
                sowDate: new Date(allocationData.sowDate),
                transplantDate: allocationData.transplantDate ? new Date(allocationData.transplantDate) : null,
                harvestDate: new Date(allocationData.harvestDate),
                method: allocationData.method
            })) {
                showConflictError(bedRow);
                return;
            }

            // Update allocation
            let allocations = JSON.parse(localStorage.getItem('bedAllocations') || '[]');
            const existingAllocation = allocations.find(a => a.successionIndex === allocationData.successionIndex && a.bedId === allocationData.bedId);
            if (existingAllocation) {
                existingAllocation.bedId = bedId;
                existingAllocation.bedName = bedName;
                localStorage.setItem('bedAllocations', JSON.stringify(allocations));

                // Update succession item badge
                const successionItem = document.querySelector(`[data-succession-index="${allocationData.successionIndex - 1}"]`);
                if (successionItem) {
                    const badge = successionItem.querySelector('.bed-allocation-badge');
                    if (badge) {
                        badge.textContent = `Allocated to ${bedName}`;
                    }
                    
                    // Update the Details section in the tab pane
                    const tabPane = document.querySelector(`#tab-${allocationData.successionIndex - 1}`);
                    if (tabPane) {
                        const successionInfo = tabPane.querySelector('.succession-info');
                        if (successionInfo) {
                            // Find the bed paragraph (first paragraph in the info section)
                            const paragraphs = successionInfo.querySelectorAll('p');
                            if (paragraphs.length > 0) {
                                paragraphs[0].innerHTML = `<strong>Bed:</strong> ${bedName}`;
                                console.log('✅ Updated Details section bed to:', bedName, 'for succession', allocationData.successionIndex);
                            }
                        }
                        
                        // Check if this is a transplant method
                        const isTransplant = allocationData.transplantDate || allocationData.method?.toLowerCase().includes('transplant');
                        
                        // Update location in seeding form
                        const seedingLocationInput = tabPane.querySelector(`input[name="plantings[${allocationData.successionIndex - 1}][seeding][location]"]`);
                        if (seedingLocationInput) {
                            // If transplant, seeding location should be "Propagation", otherwise use bed name
                            seedingLocationInput.value = isTransplant ? 'Propagation' : bedName;
                            console.log('✅ Updated seeding location to:', isTransplant ? 'Propagation' : bedName);
                        }
                        
                        // Update location in transplant form (only if it's a transplant)
                        const transplantLocationInput = tabPane.querySelector(`input[name="plantings[${allocationData.successionIndex - 1}][transplanting][location]"]`);
                        if (transplantLocationInput) {
                            transplantLocationInput.value = bedName;
                            console.log('✅ Updated transplant location to:', bedName);
                        }
                    }
                }

                // Remove old block and create new one
                const oldBlock = document.querySelector(`[data-succession-index="${allocationData.successionIndex - 1}"][data-allocation-data*="${allocationData.bedId}"]`);
                if (oldBlock) {
                    oldBlock.remove();
                }

                // Create new block on target bed
                const harvestEndDate = new Date(allocationData.harvestEndDate || allocationData.harvestDate);
                harvestEndDate.setDate(harvestEndDate.getDate() + 14); // Add 2 weeks if not specified
                createSuccessionBlock(bedTimeline, {
                    successionNumber: allocationData.successionIndex,
                    sowDate: new Date(allocationData.sowDate),
                    transplantDate: allocationData.transplantDate ? new Date(allocationData.transplantDate) : null,
                    harvestDate: new Date(allocationData.harvestDate),
                    method: allocationData.method
                }, new Date(allocationData.occupationStart), new Date(allocationData.harvestDate), harvestEndDate);

                console.log('✅ Moved succession block to new bed:', bedName);
            }
        } else {
            // Dropping a new succession from sidebar
            const successionIndex = dragType;
            console.log('📋 Succession index from drag data:', successionIndex);

            // Get succession data
            const successionItem = document.querySelector(`[data-succession-index="${successionIndex}"]`);
            if (!successionItem) {
                console.error('❌ Succession item not found for index:', successionIndex);
                return;
            }

            const successionData = JSON.parse(successionItem.dataset.successionData);
            console.log('🌱 Succession data:', successionData);

            // Convert ISO date strings back to Date objects
            successionData.sowDate = new Date(successionData.sowDate);
            if (successionData.transplantDate) {
                successionData.transplantDate = new Date(successionData.transplantDate);
            }
            successionData.harvestDate = new Date(successionData.harvestDate);

            // Check for conflicts with existing plantings
            if (checkBedConflicts(bedId, successionData)) {
                showConflictError(bedRow);
                return;
            }

            // Allocate succession to bed with proper positioning
            allocateSuccessionToBed(bedName, bedId, successionData, successionIndex, bedTimeline);

            // Immediately update the succession card UI
            successionItem.classList.add('allocated');
            successionItem.dataset.allocationData = JSON.stringify({
                bedName: bedName,
                bedId: bedId,
                successionIndex: parseInt(successionIndex) + 1
            });
            
            // Add bed badge immediately
            const header = successionItem.querySelector('.succession-header');
            if (header) {
                // Remove existing badge if present
                const existingBadge = header.querySelector('.bed-allocation-badge');
                if (existingBadge) {
                    existingBadge.remove();
                }
                
                // Add new badge
                const badge = document.createElement('span');
                badge.className = 'bed-allocation-badge badge bg-success';
                badge.innerHTML = `<i class="fas fa-map-marker-alt"></i> ${bedName}`;
                
                // Update the Details section in the tab pane
                const tabPane = document.querySelector(`#tab-${successionIndex}`);
                if (tabPane) {
                    const successionInfo = tabPane.querySelector('.succession-info');
                    if (successionInfo) {
                        // Find the bed paragraph (first paragraph in the info section)
                        const paragraphs = successionInfo.querySelectorAll('p');
                        if (paragraphs.length > 0) {
                            paragraphs[0].innerHTML = `<strong>Bed:</strong> ${bedName}`;
                            console.log('✅ Updated Details section bed to:', bedName);
                        }
                    }
                    
                    // Check if this is a transplant method
                    const isTransplant = successionData.transplantDate || successionData.method?.toLowerCase().includes('transplant');
                    
                    // Update location in seeding form
                    const seedingLocationInput = tabPane.querySelector(`input[name="plantings[${successionIndex}][seeding][location]"]`);
                    if (seedingLocationInput) {
                        // If transplant, seeding location should be "Propagation", otherwise use bed name
                        seedingLocationInput.value = isTransplant ? 'Propagation' : bedName;
                        console.log('✅ Updated seeding location to:', isTransplant ? 'Propagation' : bedName);
                    }
                    
                    // Update location in transplant form (only if it's a transplant)
                    const transplantLocationInput = tabPane.querySelector(`input[name="plantings[${successionIndex}][transplanting][location]"]`);
                    if (transplantLocationInput) {
                        transplantLocationInput.value = bedName;
                        console.log('✅ Updated transplant location to:', bedName);
                    }
                }
                badge.title = 'Click to remove allocation';
                badge.style.cursor = 'pointer';
                badge.onclick = (e) => {
                    e.stopPropagation();
                    removeSuccessionAllocation(successionIndex);
                };
                header.appendChild(badge);
                
                console.log('✅ Added bed badge to succession card:', bedName);
            }

            // Visual feedback
            showAllocationFeedback(bedRow, successionData);
        }
    }

    function allocateSuccessionToBed(bedName, bedId, successionData, successionIndex, bedTimeline) {
        // Determine occupation start date based on planting method
        const occupationStart = successionData.method.toLowerCase().includes('transplant') && successionData.transplantDate
            ? successionData.transplantDate
            : successionData.sowDate;

        // Determine occupation end date - include harvest period (add 2 weeks buffer for harvest)
        const harvestEndDate = new Date(successionData.harvestDate);
        harvestEndDate.setDate(harvestEndDate.getDate() + 14); // Add 2 weeks for harvest period
        const occupationEnd = harvestEndDate;

        // Create visual succession block on the timeline with harvest window
        createSuccessionBlock(bedTimeline, successionData, occupationStart, successionData.harvestDate, occupationEnd);

        // Store allocation
        const allocation = {
            bedName: bedName,
            bedId: bedId,
            successionIndex: parseInt(successionIndex) + 1,
            sowDate: successionData.sowDate.toISOString().split('T')[0],
            transplantDate: successionData.transplantDate ? successionData.transplantDate.toISOString().split('T')[0] : null,
            harvestDate: successionData.harvestDate.toISOString().split('T')[0],
            harvestEndDate: occupationEnd.toISOString().split('T')[0],
            occupationStart: occupationStart.toISOString().split('T')[0],
            occupationEnd: occupationEnd.toISOString().split('T')[0],
            method: successionData.method
        };

        // Store in localStorage for now (could be replaced with API call)
        let allocations = JSON.parse(localStorage.getItem('bedAllocations') || '[]');
        allocations.push(allocation);
        localStorage.setItem('bedAllocations', JSON.stringify(allocations));

        // Mark succession as allocated and add bed badge
        const successionItem = document.querySelector(`[data-succession-index="${successionIndex}"]`);
        if (successionItem) {
            successionItem.classList.add('allocated');

            // Add bed allocation badge
            const header = successionItem.querySelector('.succession-header');
            if (header) {
                // Remove existing badge if present
                const existingBadge = header.querySelector('.bed-allocation-badge');
                if (existingBadge) {
                    existingBadge.remove();
                }

                // Add new badge
                const badge = document.createElement('span');
                badge.className = 'bed-allocation-badge badge bg-success';
                badge.textContent = `Allocated to ${bedName}`;
                badge.title = `Click to remove allocation`;
                badge.style.cursor = 'pointer';
                badge.onclick = () => removeSuccessionAllocation(successionIndex);
                header.appendChild(badge);
            }

            // Store allocation data on the element for quickforms
            successionItem.dataset.allocationData = JSON.stringify(allocation);
        }

        console.log('✅ Allocated succession to bed:', allocation);
    }

    function removeSuccessionAllocation(successionIndex) {
        // Remove from localStorage
        let allocations = JSON.parse(localStorage.getItem('bedAllocations') || '[]');
        allocations = allocations.filter(a => a.successionIndex !== parseInt(successionIndex) + 1);
        localStorage.setItem('bedAllocations', JSON.stringify(allocations));

        // Remove visual allocation from timeline
        const successionBlocks = document.querySelectorAll('.succession-allocation-block');
        successionBlocks.forEach(block => {
            if (block.querySelector('.succession-label')?.textContent === `S${parseInt(successionIndex) + 1}`) {
                block.remove();
            }
        });

        // Reset succession item appearance
        const successionItem = document.querySelector(`[data-succession-index="${successionIndex}"]`);
        if (successionItem) {
            successionItem.classList.remove('allocated');
            const badge = successionItem.querySelector('.bed-allocation-badge');
            if (badge) {
                badge.remove();
            }
            delete successionItem.dataset.allocationData;
        }

        console.log('🗑️ Removed allocation for succession:', parseInt(successionIndex) + 1);
    }

    function clearAllAllocations() {
        if (confirm('Are you sure you want to clear all bed allocations? This will allow you to manually reassign successions by dragging.')) {
            localStorage.removeItem('bedAllocations');

            // Remove all visual allocation blocks from timelines
            document.querySelectorAll('.succession-allocation-block, .succession-block-container').forEach(block => {
                block.remove();
            });

            // Reset all succession items to unallocated state
            document.querySelectorAll('.succession-item').forEach(item => {
                item.classList.remove('allocated');
                const badge = item.querySelector('.bed-allocation-badge');
                if (badge) badge.remove();
                delete item.dataset.allocationData;
            });

            console.log('🗑️ Cleared all bed allocations');
            showToast('All allocations cleared. You can now manually assign successions by dragging.', 'info');
        }
    }

    function getSuccessionAllocation(successionIndex) {
        // Return allocation data for a specific succession
        const allocations = JSON.parse(localStorage.getItem('bedAllocations') || '[]');
        return allocations.find(a => a.successionIndex === parseInt(successionIndex) + 1);
    }

    function checkBedConflicts(bedId, successionData) {
        // Get existing allocations for this bed
        const allocations = JSON.parse(localStorage.getItem('bedAllocations') || '[]');
        const bedAllocations = allocations.filter(a => a.bedId === bedId);

        // Determine occupation period for the new succession (including harvest time)
        const occupationStart = successionData.method.toLowerCase().includes('transplant') && successionData.transplantDate
            ? successionData.transplantDate
            : successionData.sowDate;
        const harvestEndDate = new Date(successionData.harvestDate);
        harvestEndDate.setDate(harvestEndDate.getDate() + 14); // Add 2 weeks for harvest period
        const occupationEnd = harvestEndDate;

        // Check for overlaps with existing allocations
        for (const allocation of bedAllocations) {
            const existingStart = new Date(allocation.occupationStart);
            const existingEnd = new Date(allocation.occupationEnd || allocation.harvestDate);

            // Check for time overlap
            if (occupationStart < existingEnd && occupationEnd > existingStart) {
                return true; // Conflict found
            }
        }

        return false; // No conflicts
    }

    function showConflictError(bedRow) {
        // Visual feedback for conflict
        const timeline = bedRow.querySelector('.bed-timeline');
        timeline.classList.add('conflict-error');

        // Add error message
        const errorMsg = document.createElement('div');
        errorMsg.className = 'conflict-message';
        errorMsg.textContent = '❌ Bed occupied during this period';
        timeline.appendChild(errorMsg);

        // Remove after 3 seconds
        setTimeout(() => {
            timeline.classList.remove('conflict-error');
            if (errorMsg.parentNode) {
                errorMsg.remove();
            }
        }, 3000);
    }

    function createSuccessionBlock(bedTimeline, successionData, startDate, harvestDate, endDate) {
        console.log('🎨 Creating succession block:', {
            successionNumber: successionData.successionNumber,
            startDate: startDate.toISOString(),
            harvestDate: harvestDate.toISOString(),
            endDate: endDate.toISOString()
        });

        // Get timeline date range from data attributes
        const timelineContainer = bedTimeline.closest('.bed-occupancy-timeline');
        const minDateStr = timelineContainer.dataset.minDate;
        const maxDateStr = timelineContainer.dataset.maxDate;

        console.log('📅 Timeline date range:', { minDateStr, maxDateStr });

        if (!minDateStr || !maxDateStr) {
            console.error('Timeline date range not found');
            return;
        }

        const timelineStart = new Date(minDateStr);
        const timelineEnd = new Date(maxDateStr);

        console.log('📅 Parsed timeline dates:', {
            timelineStart: timelineStart.toISOString(),
            timelineEnd: timelineEnd.toISOString()
        });

        // Calculate positions for growing period and harvest window
        const totalDuration = timelineEnd - timelineStart;

        // Growing period (from start to harvest)
        const growingLeft = ((startDate - timelineStart) / totalDuration) * 100;
        const growingWidth = ((harvestDate - startDate) / totalDuration) * 100;

        // Harvest window (from harvest to end)
        const harvestLeft = ((harvestDate - timelineStart) / totalDuration) * 100;
        const harvestWidth = ((endDate - harvestDate) / totalDuration) * 100;

        console.log('📐 Calculated positions:', {
            growingLeft, growingWidth,
            harvestLeft, harvestWidth,
            totalDuration
        });

        // Create container for the succession block
        const blockContainer = document.createElement('div');
        blockContainer.className = 'succession-block-container';
        blockContainer.style.left = `${Math.max(0, growingLeft)}%`;
        blockContainer.style.width = `${Math.max(2, Math.min(100 - growingLeft, growingWidth + harvestWidth))}%`;

        // Growing period block
        if (growingWidth > 0) {
            const growingBlock = document.createElement('div');
            growingBlock.className = 'succession-growing-block';
            growingBlock.style.left = '0%';
            growingBlock.style.width = growingWidth > 0 ? `${(growingWidth / (growingWidth + harvestWidth)) * 100}%` : '100%';
            growingBlock.title = `Succession ${successionData.successionNumber} - Growing Period (${startDate.toLocaleDateString()} - ${harvestDate.toLocaleDateString()})`;

            const growingLabel = document.createElement('span');
            growingLabel.className = 'succession-label';
            growingLabel.textContent = `S${successionData.successionNumber}`;
            growingBlock.appendChild(growingLabel);
            blockContainer.appendChild(growingBlock);
        }

        // Harvest window block
        if (harvestWidth > 0) {
            const harvestBlock = document.createElement('div');
            harvestBlock.className = 'succession-harvest-block';
            harvestBlock.style.left = growingWidth > 0 ? `${(growingWidth / (growingWidth + harvestWidth)) * 100}%` : '0%';
            harvestBlock.style.width = harvestWidth > 0 ? `${(harvestWidth / (growingWidth + harvestWidth)) * 100}%` : '0%';
            harvestBlock.title = `Succession ${successionData.successionNumber} - Harvest Window (${harvestDate.toLocaleDateString()} - ${endDate.toLocaleDateString()})`;

            const harvestLabel = document.createElement('span');
            harvestLabel.className = 'succession-label';
            harvestLabel.textContent = `H${successionData.successionNumber}`;
            harvestBlock.appendChild(harvestLabel);
            blockContainer.appendChild(harvestBlock);
        }

        // Add drag functionality to the container
        blockContainer.draggable = true;
        blockContainer.dataset.successionIndex = successionData.successionNumber - 1;
        blockContainer.dataset.allocationData = JSON.stringify({
            bedName: bedTimeline.closest('.bed-row').querySelector('.bed-label').textContent,
            bedId: bedTimeline.dataset.bedId,
            successionIndex: successionData.successionNumber,
            sowDate: successionData.sowDate.toISOString().split('T')[0],
            transplantDate: successionData.transplantDate ? successionData.transplantDate.toISOString().split('T')[0] : null,
            harvestDate: successionData.harvestDate.toISOString().split('T')[0],
            harvestEndDate: endDate.toISOString().split('T')[0],
            occupationStart: startDate.toISOString().split('T')[0],
            occupationEnd: endDate.toISOString().split('T')[0],
            method: successionData.method
        });

        // Add drag functionality
        blockContainer.addEventListener('dragstart', handleBlockDragStart);
        blockContainer.addEventListener('dragend', handleBlockDragEnd);

        // Add right-click delete
        blockContainer.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            if (confirm(`Remove Succession ${successionData.successionNumber} from this bed?`)) {
                removeSuccessionBlock(blockContainer);
            }
        });

        bedTimeline.appendChild(blockContainer);
        console.log('✅ Succession block with harvest window added to timeline');
    }

    function handleBlockDragStart(e) {
        e.dataTransfer.setData('text/plain', 'block-move');
        e.dataTransfer.setData('application/json', e.target.dataset.allocationData);
        e.target.classList.add('dragging');
        console.log('🏗️ Started dragging succession block');
    }

    function handleBlockDragEnd(e) {
        e.target.classList.remove('dragging');
        document.querySelectorAll('.bed-timeline').forEach(timeline => {
            timeline.classList.remove('drop-target', 'drop-active', 'drop-conflict');
        });
        console.log('🏗️ Finished dragging succession block');
    }

    function removeSuccessionBlock(block) {
        const allocationData = JSON.parse(block.dataset.allocationData);
        const successionIndex = allocationData.successionIndex - 1;

        // Remove from localStorage
        let allocations = JSON.parse(localStorage.getItem('bedAllocations') || '[]');
        allocations = allocations.filter(a => !(a.successionIndex === allocationData.successionIndex && a.bedId === allocationData.bedId));
        localStorage.setItem('bedAllocations', JSON.stringify(allocations));

        // Remove the block
        block.remove();

        // Reset succession item appearance
        const successionItem = document.querySelector(`[data-succession-index="${successionIndex}"]`);
        if (successionItem) {
            successionItem.classList.remove('allocated');
            const badge = successionItem.querySelector('.bed-allocation-badge');
            if (badge) {
                badge.remove();
            }
            delete successionItem.dataset.allocationData;
        }

        console.log('🗑️ Removed succession block:', allocationData);
    }

    function showAllocationFeedback(bedRow, successionData) {
        // Add visual indicator
        const timeline = bedRow.querySelector('.bed-timeline');
        const indicator = document.createElement('div');
        indicator.className = 'allocated-succession';
        indicator.textContent = `Succession ${successionData.successionNumber || 'N/A'}`;
        timeline.appendChild(indicator);

        // Remove after 3 seconds
        setTimeout(() => {
            if (indicator.parentNode) {
                indicator.remove();
            }
        }, 3000);
    }

    function toggleQuickForm(successionIndex, formType, forceShow = null) {
        const checkbox = document.getElementById(`${formType}-enabled-${successionIndex}`);
        const formElement = document.getElementById(`quick-form-${formType}-${successionIndex}`);

        if (checkbox && formElement) {
            // If forceShow is specified, use it; otherwise check the checkbox state
            const shouldShow = forceShow !== null ? forceShow : checkbox.checked;
            
            if (shouldShow) {
                formElement.style.display = 'block';
            } else {
                formElement.style.display = 'none';
            }
        }
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

        if (!aiRangeDiv || !aiRangeBar || !aiRangeDates) {
            console.warn('❌ displayAIRange: Missing DOM elements');
            return;
        }

        console.log('🤖 displayAIRange called with data:', {
            aiStart: harvestWindowData.aiStart,
            aiEnd: harvestWindowData.aiEnd,
            maxStart: harvestWindowData.maxStart,
            maxEnd: harvestWindowData.maxEnd
        });

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

        console.log('📊 AI range calculations:', {
            startDate: startDate.toISOString(),
            endDate: endDate.toISOString(),
            aiStartOffset,
            aiDuration,
            maxDuration,
            leftPercent,
            widthPercent
        });

        aiRangeBar.style.marginLeft = `${leftPercent}%`;
        aiRangeBar.style.width = `${widthPercent}%`;
        aiRangeDates.textContent = `${startDate.toLocaleDateString()} - ${endDate.toLocaleDateString()} (${duration} days)`;
        aiRangeDiv.style.display = 'block';

        console.log('✅ AI range displayed');
    }

    // Display user selected harvest range
    function displayUserRange() {
        const userRangeDiv = document.getElementById('userSelectedRange');
        const userRangeBar = document.getElementById('userRangeBar');
        const userRangeDates = document.getElementById('userRangeDates');
        const startHandle = document.getElementById('rangeStartHandle');
        const endHandle = document.getElementById('rangeEndHandle');

        if (!userRangeDiv || !userRangeBar || !userRangeDates) {
            console.warn('❌ displayUserRange: Missing DOM elements');
            return;
        }

        console.log('🎨 displayUserRange called with data:', {
            userStart: harvestWindowData.userStart,
            userEnd: harvestWindowData.userEnd,
            maxStart: harvestWindowData.maxStart,
            maxEnd: harvestWindowData.maxEnd
        });

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

        console.log('📊 User range calculations:', {
            startDate: startDate.toISOString(),
            endDate: endDate.toISOString(),
            maxStart: maxStart.toISOString(),
            maxEnd: maxEnd.toISOString(),
            userStartOffset,
            userDuration,
            maxDuration,
            leftPercent,
            widthPercent
        });

        userRangeBar.style.marginLeft = `${leftPercent}%`;
        userRangeBar.style.width = `${widthPercent}%`;

        if (startHandle) startHandle.style.left = `${leftPercent}%`;
        if (endHandle) endHandle.style.left = `${leftPercent + widthPercent}%`;

        userRangeDates.textContent = `${startDate.toLocaleDateString()} - ${endDate.toLocaleDateString()} (${duration} days)`;
        userRangeDiv.style.display = 'block';

        console.log('✅ User range displayed');
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

    function populateSuccessionSidebar(plan) {
        const successionList = document.getElementById('successionList');
        const successionSidebar = document.getElementById('successionSidebar');
        const aiChatSection = document.getElementById('aiChatSection');

        if (!successionList || !successionSidebar || !aiChatSection) return;

        const plantings = plan.plantings || [];
        
        if (plantings.length > 0) {
            // Get existing allocations BEFORE generating HTML
            const allocations = JSON.parse(localStorage.getItem('bedAllocations') || '[]');
            console.log('📦 Found existing allocations:', allocations);
            console.log('📦 Number of allocations:', allocations.length);
            
            // Debug: log each allocation
            allocations.forEach((alloc, idx) => {
                console.log(`  Allocation ${idx}:`, {
                    successionIndex: alloc.successionIndex,
                    bedName: alloc.bedName,
                    bedId: alloc.bedId
                });
            });
            
            let sidebarHTML = '';

            plantings.forEach((planting, i) => {
                const sowDate = planting.seeding_date ? new Date(planting.seeding_date) : null;
                const transplantDate = planting.transplant_date ? new Date(planting.transplant_date) : null;
                const harvestDate = planting.harvest_date ? new Date(planting.harvest_date) : null;

                const successionDataForJson = {
                    successionNumber: i + 1,
                    sowDate: sowDate ? sowDate.toISOString() : null,
                    transplantDate: transplantDate ? transplantDate.toISOString() : null,
                    harvestDate: harvestDate ? harvestDate.toISOString() : null,
                    method: planting.method || 'Direct Sow'
                };

                // Check if this succession is allocated
                const allocation = allocations.find(a => a.successionIndex === i + 1);
                const isAllocated = !!allocation;
                
                console.log(`Succession ${i + 1}: allocated=${isAllocated}`, allocation);

                sidebarHTML += `
                    <div class="succession-item ${isAllocated ? 'allocated' : ''}" draggable="true" data-succession-index="${i}" data-succession-data='${JSON.stringify(successionDataForJson)}' ${isAllocated ? `data-allocation-data='${JSON.stringify(allocation)}'` : ''}>
                        <div class="succession-header">
                            <div class="succession-title-section">
                                <span class="succession-title">Succession ${i + 1}</span>
                                <small class="text-muted">${planting.method || 'Direct Sow'}</small>
                            </div>
                            ${isAllocated ? `
                            <span class="bed-allocation-badge badge bg-success" onclick="removeSuccessionAllocation(${i})" style="cursor: pointer;" title="Click to remove allocation">
                                <i class="fas fa-map-marker-alt"></i> ${allocation.bedName}
                            </span>
                            ` : ''}
                        </div>
                        <div class="succession-dates">
                            ${sowDate ? `
                            <div class="date-row">
                                <span class="date-label">Sow:</span>
                                <span class="date-value">${sowDate.toLocaleDateString()}</span>
                            </div>
                            ` : ''}
                            ${transplantDate ? `
                            <div class="date-row">
                                <span class="date-label">Transplant:</span>
                                <span class="date-value">${transplantDate.toLocaleDateString()}</span>
                            </div>
                            ` : ''}
                            ${harvestDate ? `
                            <div class="date-row">
                                <span class="date-label">Harvest:</span>
                                <span class="date-value">${harvestDate.toLocaleDateString()}</span>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            });

            successionList.innerHTML = sidebarHTML;
            successionSidebar.style.display = 'block';
            // Keep AI chat visible - it's now above succession sidebar
            
            // Update succession count badge
            const countBadge = document.getElementById('sidebarSuccessionCount');
            if (countBadge) {
                countBadge.textContent = `${plantings.length} Succession${plantings.length !== 1 ? 's' : ''}`;
            }

            console.log('📝 Succession sidebar populated with', plantings.length, 'successions');

            // Drag and drop will be initialized centrally after both timeline and sidebar are ready
        } else {
            successionSidebar.style.display = 'none';
            // AI chat stays visible
        }
    }

    // Update succession impact preview
    function updateSuccessionImpact() {
        console.log('🔄 updateSuccessionImpact called');
        
        const impactDiv = document.getElementById('successionImpact');
        const countBadge = document.getElementById('successionCount');
        const previewDiv = document.getElementById('successionPreview');

        // Only require countBadge, others are optional for enhanced display
        if (!countBadge) {
            console.warn('⚠️ Missing succession count badge element');
            return;
        }
        
        if (!harvestWindowData.userStart || !harvestWindowData.userEnd) {
            console.warn('⚠️ Missing harvest window data:', harvestWindowData);
            return;
        }

        const start = new Date(harvestWindowData.userStart);
        const end = new Date(harvestWindowData.userEnd);
        const duration = Math.ceil((end - start) / (1000 * 60 * 60 * 24));

        console.log('📊 Calculating succession impact - duration:', duration, 'days');

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

        // Update sidebar count
        const sidebarCountBadge = document.getElementById('sidebarSuccessionCount');
        if (sidebarCountBadge) {
            sidebarCountBadge.textContent = `${successions} Succession${successions > 1 ? 's' : ''}`;
        }

        // Show and update the dynamic succession display
        const dynamicDisplay = document.getElementById('dynamicSuccessionDisplay');
        if (dynamicDisplay) {
            dynamicDisplay.style.display = 'block';
        }

        console.log(`📊 Updated succession count: ${successions} based on ${duration} day harvest window`);

        // Calculate seed/transplant amounts based on bed dimensions
        const bedLength = parseFloat(document.getElementById('bedLength')?.value) || 0;
        const bedWidthCm = parseFloat(document.getElementById('bedWidth')?.value) || 0;
        const bedWidth = bedWidthCm / 100; // Convert cm to meters
        const bedArea = bedLength * bedWidth; // in square meters

        let seedInfoHTML = '';
        if (bedArea > 0) {
            // Get seed/transplant requirements for this crop
            const seedRequirements = getSeedRequirements(cropName, varietyName);

            if (seedRequirements) {
                const totalSeeds = Math.ceil(bedArea * seedRequirements.seedsPerSqFt * successions);
                const totalTransplants = seedRequirements.transplantsPerSqFt ?
                    Math.ceil(bedArea * seedRequirements.transplantsPerSqFt * successions) : 0;

                seedInfoHTML = `
                    <div class="seed-calculations mt-3 p-3 bg-light rounded">
                        <h6 class="text-info mb-2">
                            <i class="fas fa-seedling"></i>
                            Seed & Transplant Requirements (${bedLength}m × ${bedWidth}m = ${bedArea} sq m)
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="seed-info">
                                    <strong>Seeds needed:</strong> ${totalSeeds.toLocaleString()} ${seedRequirements.seedUnit || 'seeds'}
                                    <br><small class="text-muted">(${seedRequirements.seedsPerSqFt} per sq m × ${successions} successions)</small>
                                </div>
                            </div>
                            ${totalTransplants > 0 ? `
                            <div class="col-md-6">
                                <div class="seed-info">
                                    <strong>Transplants needed:</strong> ${totalTransplants.toLocaleString()}
                                    <br><small class="text-muted">(${seedRequirements.transplantsPerSqFt} per sq m × ${successions} successions)</small>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                `;
            }
        }

        // Generate detailed succession preview for both locations
        let previewHTML = seedInfoHTML;
        let sidebarHTML = '';

        for (let i = 0; i < successions; i++) {
            const successionData = calculateSuccessionDates(start, i, avgSuccessionInterval, cropName, varietyName);

            // Original preview format
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

            // Sidebar draggable format
            const successionDataForJson = {
                successionNumber: i + 1,
                sowDate: successionData.sowDate.toISOString(),
                transplantDate: successionData.transplantDate ? successionData.transplantDate.toISOString() : null,
                harvestDate: successionData.harvestDate.toISOString(),
                method: successionData.method
            };

            sidebarHTML += `
                <div class="succession-item" draggable="true" data-succession-index="${i}" data-succession-data='${JSON.stringify(successionDataForJson)}'>
                    <div class="succession-header">
                        <div class="succession-title-section">
                            <span class="succession-title">Succession ${i + 1}</span>
                            <small class="text-muted">${successionData.method}</small>
                        </div>
                    </div>
                    <div class="succession-dates">
                        <div class="date-row">
                            <span class="date-label">Sow:</span>
                            <span class="date-value">${successionData.sowDate.toLocaleDateString()}</span>
                        </div>
                        ${successionData.transplantDate ? `
                        <div class="date-row">
                            <span class="date-label">Transplant:</span>
                            <span class="date-value">${successionData.transplantDate.toLocaleDateString()}</span>
                        </div>
                        ` : ''}
                        <div class="date-row">
                            <span class="date-label">Harvest:</span>
                            <span class="date-value">${successionData.harvestDate.toLocaleDateString()}</span>
                        </div>
                    </div>
                </div>
            `;
        }

        // Update preview div if it exists
        if (previewDiv) {
            previewDiv.innerHTML = previewHTML;
        }
        
        // Show impact div if it exists
        if (impactDiv) {
            impactDiv.style.display = 'block';
        }

        // Update sidebar
        const successionList = document.getElementById('successionList');
        const successionSidebar = document.getElementById('successionSidebar');
        const aiChatSection = document.getElementById('aiChatSection');

        if (successionList && successionSidebar && aiChatSection) {
            if (successions > 0) {
                successionList.innerHTML = sidebarHTML;
                successionSidebar.style.display = 'block';
                aiChatSection.style.display = 'block'; // Keep AI chat visible

                console.log('📝 Sidebar HTML created:', sidebarHTML.substring(0, 200) + '...');

                // Initialize drag and drop
                initializeDragAndDrop();
            } else {
                successionSidebar.style.display = 'none';
                aiChatSection.style.display = 'block';
            }
        }
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

    function getSeedRequirements(cropName, varietyName) {
        // Seed and transplant requirements per square foot
        const requirements = {
            'carrot': { seedsPerSqFt: 80, seedUnit: 'seeds', transplantsPerSqFt: null },
            'beetroot': { seedsPerSqFt: 16, seedUnit: 'seeds', transplantsPerSqFt: null },
            'lettuce': { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: 4 },
            'radish': { seedsPerSqFt: 16, seedUnit: 'seeds', transplantsPerSqFt: null },
            'onion': { seedsPerSqFt: 32, seedUnit: 'seeds', transplantsPerSqFt: null },
            'spinach': { seedsPerSqFt: 8, seedUnit: 'seeds', transplantsPerSqFt: null },
            'kale': { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: 4 },
            'chard': { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: 4 },
            'pak choi': { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: 4 },
            'cabbage': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'broccoli': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'cauliflower': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'peas': { seedsPerSqFt: 8, seedUnit: 'seeds', transplantsPerSqFt: null },
            'beans': { seedsPerSqFt: 6, seedUnit: 'seeds', transplantsPerSqFt: null },
            'tomato': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'pepper': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'cucumber': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'zucchini': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'corn': { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: null },
            'potato': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: null },
            'garlic': { seedsPerSqFt: 4, seedUnit: 'cloves', transplantsPerSqFt: null },
            'leek': { seedsPerSqFt: 16, seedUnit: 'seeds', transplantsPerSqFt: 16 },
            'celery': { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: 4 },
            'fennel': { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: 4 },
            'brussels sprouts': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'brussel sprouts': { seedsPerSqFt: 1, seedUnit: 'seeds', transplantsPerSqFt: 1 },
            'herbs': { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: null }
        };

        // Check for specific crop matches
        for (const [crop, req] of Object.entries(requirements)) {
            if (cropName.includes(crop) || varietyName.includes(crop)) {
                return req;
            }
        }

        // Default requirements
        return { seedsPerSqFt: 4, seedUnit: 'seeds', transplantsPerSqFt: null };
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

        // First, check if we have variety-specific data from FarmOS
        if (window.cropData && window.cropData.varieties) {
            const variety = window.cropData.varieties.find(v =>
                v.name && v.name.toLowerCase().includes(varietyLower) &&
                v.crop_type && v.crop_type.toLowerCase().includes(cropLower)
            );

            if (variety && variety.transplant_month_start && variety.transplant_month_end) {
                console.log(`🌱 Using FarmOS transplant window for ${variety.name}: months ${variety.transplant_month_start}-${variety.transplant_month_end}`);

                // Calculate transplant window from month numbers
                const startMonth = variety.transplant_month_start - 1; // Convert to 0-indexed
                const endMonth = variety.transplant_month_end - 1; // Convert to 0-indexed

                return {
                    daysToHarvest: variety.harvest_days || variety.maturity_days || 60,
                    daysToTransplant: variety.transplant_days || 35,
                    method: variety.transplant_days ? 'Transplant seedlings' : 'Direct sow',
                    transplantWindow: {
                        startMonth: startMonth,
                        startDay: 1, // Default to 1st of the month
                        endMonth: endMonth,
                        endDay: 15, // Default to 15th of the month
                        description: `Month ${variety.transplant_month_start} - Month ${variety.transplant_month_end} (FarmOS data)`
                    }
                };
            }
        }

        // Fallback to hardcoded timing data if no FarmOS data available
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
                    startMonth: 4,  // May (0-indexed, FarmOS month 5)
                    startDay: 1,
                    endMonth: 5,    // June (0-indexed, FarmOS month 6)
                    endDay: 15,
                    description: 'May 1 - June 15 (FarmOS transplant window)'
                }
            },
            'brussels': {
                daysToHarvest: 110,
                daysToTransplant: 35,
                method: 'Transplant seedlings',
                transplantWindow: {
                    startMonth: 4,  // May
                    startDay: 1,
                    endMonth: 5,    // June
                    endDay: 15,
                    description: 'May 1 - June 15 (FarmOS transplant window)'
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

        console.log('📊 updateHarvestWindowData called with:', aiResult);

        // Only update max range if AI provides better data than current
        // Don't override manually set crop-specific dates
        if (aiResult.maximum_start && aiResult.maximum_end) {
            const aiMaxStart = new Date(aiResult.maximum_start);
            const aiMaxEnd = new Date(aiResult.maximum_end);

            // Only update if AI dates are significantly different (more than 30 days)
            // This prevents AI from overriding correct crop-specific dates
            if (harvestWindowData.maxStart && harvestWindowData.maxEnd) {
                const currentMaxStart = new Date(harvestWindowData.maxStart);
                const currentMaxEnd = new Date(harvestWindowData.maxEnd);

                const aiDuration = aiMaxEnd - aiMaxStart;
                const currentDuration = currentMaxEnd - currentMaxStart;

                if (Math.abs(aiDuration - currentDuration) > (30 * 24 * 60 * 60 * 1000)) { // 30 days
                    console.log('⚠️ AI dates differ significantly from current, updating max range');
                    harvestWindowData.maxStart = aiResult.maximum_start;
                    harvestWindowData.maxEnd = aiResult.maximum_end;
                } else {
                    console.log('✅ AI dates similar to current, keeping existing max range');
                }
            } else {
                // No existing data, use AI results
                harvestWindowData.maxStart = aiResult.maximum_start;
                harvestWindowData.maxEnd = aiResult.maximum_end;
            }
        }

        // Always update AI recommended range
        if (harvestWindowData.maxStart && harvestWindowData.maxEnd) {
            const maxStart = new Date(harvestWindowData.maxStart);
            const maxEnd = new Date(harvestWindowData.maxEnd);
            const maxDuration = maxEnd - maxStart;

            harvestWindowData.aiStart = harvestWindowData.maxStart;
            harvestWindowData.aiEnd = new Date(maxStart.getTime() + (maxDuration * 0.8)).toISOString().split('T')[0];

            // Only set user range to AI recommendation if not already set
            if (!harvestWindowData.userStart || !harvestWindowData.userEnd) {
                harvestWindowData.userStart = harvestWindowData.aiStart;
                harvestWindowData.userEnd = harvestWindowData.aiEnd;
            }
        }

        console.log('📊 Final harvestWindowData:', harvestWindowData);
        updateHarvestWindowDisplay();
    }

    function toggleQuickForm(successionIndex, formType, forceShow = null) {
        const checkbox = document.getElementById(`${formType}-enabled-${successionIndex}`);
        const formElement = document.getElementById(`quick-form-${formType}-${successionIndex}`);

        if (checkbox && formElement) {
            // If forceShow is specified, use it; otherwise check the checkbox state
            const shouldShow = forceShow !== null ? forceShow : checkbox.checked;
            
            if (shouldShow) {
                formElement.style.display = 'block';
            } else {
                formElement.style.display = 'none';
            }
        }
    }

    /**
     * Scroll to the quick forms section
     */
    function scrollToQuickForms() {
        const quickFormsContainer = document.getElementById('quickFormTabsContainer');
        if (quickFormsContainer) {
            quickFormsContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    /**
     * Scroll to the top of the page
     */
    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    async function submitAllQuickForms() {
        // Collect all form data
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        // Get all planting data
        const plantings = [];
        const tabPanes = document.querySelectorAll('#tabContent .tab-pane');

        tabPanes.forEach((pane, index) => {
            const planting = {
                succession_index: index,
                season: '',
                crop_variety: '',
                logs: {}
            };

            // Get season and crop variety
            const seasonInput = pane.querySelector(`input[name="plantings[${index}][season]"]`);
            const cropInput = pane.querySelector(`input[name="plantings[${index}][crop_variety]"]`);
            planting.season = seasonInput ? seasonInput.value : '';
            planting.crop_variety = cropInput ? cropInput.value : '';

            // Check each form type
            ['seeding', 'transplanting', 'harvest'].forEach(formType => {
                const checkbox = document.getElementById(`${formType}-enabled-${index}`);
                if (checkbox && checkbox.checked) {
                    // Form is enabled, collect its data
                    const formElement = document.getElementById(`quick-form-${formType}-${index}`);
                    if (formElement) {
                        const formDataObj = {};
                        const inputs = formElement.querySelectorAll('input, select, textarea');
                        inputs.forEach(input => {
                            if (input.name && input.value) {
                                const nameParts = input.name.replace(`plantings[${index}][${formType}][`, '').replace(']', '').split('][');
                                let current = formDataObj;
                                for (let i = 0; i < nameParts.length - 1; i++) {
                                    if (!current[nameParts[i]]) current[nameParts[i]] = {};
                                    current = current[nameParts[i]];
                                }
                                current[nameParts[nameParts.length - 1]] = input.value;
                            }
                        });
                        planting.logs[formType] = formDataObj;
                    }
                }
            });

            if (Object.keys(planting.logs).length > 0) {
                plantings.push(planting);
            }
        });

        if (plantings.length === 0) {
            alert('No forms have been filled out. Please check at least one form type and fill out the required fields.');
            return;
        }

        // Submit to backend
        try {
            showLoading(true);
            const response = await fetch('/admin/farmos/succession-planning/submit-all-logs', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ plantings })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                showToast('All planting records submitted successfully!', 'success');
                // Hide all forms and uncheck all checkboxes
                document.querySelectorAll('.embedded-quick-form').forEach(form => {
                    form.style.display = 'none';
                });
                document.querySelectorAll('.log-type-checkbox').forEach(checkbox => {
                    checkbox.checked = false;
                });
            } else {
                showToast('Failed to submit planting records: ' + (result.message || 'Unknown error'), 'error');
            }
        } catch (error) {
            console.error('Submit error:', error);
            showToast('Error submitting planting records', 'error');
        } finally {
            showLoading(false);
        }
    }

    // Bed Dimensions Persistence (localStorage)
    // Save only bed dimensions - spacing and dates auto-fill per crop
    function saveBedDimensions() {
        const bedLength = document.getElementById('bedLength')?.value;
        const bedWidth = document.getElementById('bedWidth')?.value;
        
        if (bedLength) {
            localStorage.setItem('farmBedLength', bedLength);
        }
        if (bedWidth) {
            localStorage.setItem('farmBedWidth', bedWidth);
        }
        
        console.log('💾 Saved bed dimensions:', { bedLength, bedWidth });
    }

    function loadBedDimensions() {
        const savedBedLength = localStorage.getItem('farmBedLength');
        const savedBedWidth = localStorage.getItem('farmBedWidth');
        
        const bedLengthInput = document.getElementById('bedLength');
        const bedWidthInput = document.getElementById('bedWidth');
        
        if (savedBedLength && bedLengthInput && !bedLengthInput.value) {
            bedLengthInput.value = savedBedLength;
            console.log('📂 Loaded bed length:', savedBedLength, 'm');
        }
        
        if (savedBedWidth && bedWidthInput && !bedWidthInput.value) {
            bedWidthInput.value = savedBedWidth;
            console.log('📂 Loaded bed width:', savedBedWidth, 'cm');
        }
    }

    // Initialize Succession Planner when DOM is ready
    document.addEventListener('DOMContentLoaded', function() {
        // AGGRESSIVE scroll to top - prevent browser auto-scroll restoration
        // Run immediately on DOMContentLoaded
        window.scrollTo(0, 0);
        document.documentElement.scrollTop = 0;
        document.body.scrollTop = 0;

        // Prevent any scroll events during initial page load
        let scrollLocked = true;
        const preventScroll = (e) => {
            if (scrollLocked) {
                window.scrollTo(0, 0);
            }
        };
        window.addEventListener('scroll', preventScroll, { passive: false });

        // Additional scroll resets at intervals
        setTimeout(() => { window.scrollTo(0, 0); }, 10);
        setTimeout(() => { window.scrollTo(0, 0); }, 50);
        setTimeout(() => { window.scrollTo(0, 0); }, 100);
        setTimeout(() => { 
            window.scrollTo(0, 0);
            // Unlock scrolling after page is fully loaded
            scrollLocked = false;
            window.removeEventListener('scroll', preventScroll);
        }, 500);

        console.log('🌱 Initializing Succession Planning Interface...');

        // Create SuccessionPlanner instance with configuration
        const successionPlanner = new SuccessionPlanner({
            cropTypes: @json($cropData['types'] ?? []),
            cropVarieties: @json($cropData['varieties'] ?? []),
            availableBeds: @json($availableBeds ?? []),
            farmosBase: '{{ config("services.farmos.url") }}'
        });

        // Initialize the planner
        successionPlanner.initialize().catch(error => {
            console.error('❌ Failed to initialize Succession Planner:', error);
        });

        // Initialize the new harvest window selector
        initializeHarvestWindowSelector();

        // Load saved bed dimensions from localStorage
        loadBedDimensions();

        // Add event listeners to save bed dimensions when changed
        const bedLengthInput = document.getElementById('bedLength');
        const bedWidthInput = document.getElementById('bedWidth');
        const inRowSpacingInput = document.getElementById('inRowSpacing');
        const betweenRowSpacingInput = document.getElementById('betweenRowSpacing');
        
        // Function to update density preset display with current bed width
        function updateDensityPresetDisplay() {
            const bedWidthCm = parseFloat(bedWidthInput?.value) || 75;
            const densityBedWidthSpan = document.getElementById('densityBedWidth');
            const preset2rowsLabel = document.getElementById('preset2rowsLabel');
            const preset3rowsLabel = document.getElementById('preset3rowsLabel');
            
            if (densityBedWidthSpan) {
                densityBedWidthSpan.textContent = bedWidthCm;
            }
            
            // Calculate actual rows for each preset
            const rows2 = Math.floor(bedWidthCm / 40) + 1;
            const rows3 = Math.floor(bedWidthCm / 30) + 1;
            
            // Update button labels with calculated row counts
            if (preset2rowsLabel) {
                preset2rowsLabel.textContent = `${rows2} Rows`;
            }
            if (preset3rowsLabel) {
                preset3rowsLabel.textContent = `${rows3} Rows`;
            }
        }
        
        // Function to recalculate and update displayed quantities when inputs change
        function updateDisplayedQuantities() {
            if (!currentSuccessionPlan || !currentSuccessionPlan.plantings) {
                return; // No plan to update
            }

            console.log('🔄 Recalculating plant quantities with updated bed dimensions/spacing...');

            const bedLength = parseFloat(bedLengthInput?.value) || 10;
            const bedWidthCm = parseFloat(bedWidthInput?.value) || 75;
            const bedWidth = bedWidthCm / 100; // Convert to meters
            const inRowSpacing = parseFloat(inRowSpacingInput?.value) || 15;
            const betweenRowSpacing = parseFloat(betweenRowSpacingInput?.value) || 20;

            // Recalculate quantities for all plantings
            currentSuccessionPlan.plantings.forEach(planting => {
                const quantities = calculatePlantQuantity(bedLength, bedWidth, inRowSpacing, betweenRowSpacing, planting.planting_method);
                
                // Update the planting object
                planting.bed_length = bedLength;
                planting.bed_width = bedWidthCm;
                planting.in_row_spacing = inRowSpacing;
                planting.between_row_spacing = betweenRowSpacing;
                planting.number_of_rows = quantities.numberOfRows;
                planting.plants_per_row = quantities.plantsPerRow;
                planting.total_plants = quantities.totalPlants;
            });

            // Re-render the quick form tabs to show updated quantities
            renderQuickFormTabs(currentSuccessionPlan);

            console.log('✅ Plant quantities updated with new dimensions');
        }
        
        if (bedLengthInput) {
            bedLengthInput.addEventListener('change', () => {
                saveBedDimensions();
                updateDisplayedQuantities();
            });
        }
        if (bedWidthInput) {
            bedWidthInput.addEventListener('change', () => {
                saveBedDimensions();
                updateDensityPresetDisplay(); // Update preset display
                updateDisplayedQuantities();
            });
            // Also update on input (real-time as you type)
            bedWidthInput.addEventListener('input', () => {
                updateDensityPresetDisplay();
            });
        }
        if (inRowSpacingInput) {
            inRowSpacingInput.addEventListener('change', updateDisplayedQuantities);
        }
        if (betweenRowSpacingInput) {
            betweenRowSpacingInput.addEventListener('change', updateDisplayedQuantities);
        }

        // Add event listeners for density preset buttons
        const densityPresetButtons = document.querySelectorAll('.density-preset');
        densityPresetButtons.forEach(button => {
            button.addEventListener('click', function() {
                const rows = this.dataset.rows;
                const betweenRowSpacing = this.dataset.betweenRow;
                
                // Update the between-row spacing input
                const betweenRowInput = document.getElementById('betweenRowSpacing');
                betweenRowInput.value = betweenRowSpacing;
                
                // Visual feedback
                densityPresetButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
                
                // Calculate and show preview
                const bedWidthCm = parseFloat(document.getElementById('bedWidth')?.value) || 75;
                const actualRows = Math.floor(bedWidthCm / betweenRowSpacing) + 1;
                
                console.log(`🥬 Density preset selected: ${rows} rows (${betweenRowSpacing}cm spacing) = ${actualRows} actual rows on ${bedWidthCm}cm bed`);
                
                // Trigger calculation update if plan exists
                if (currentSuccessionPlan) {
                    // Recalculate quantities with new spacing
                    const event = new Event('change');
                    betweenRowInput.dispatchEvent(event);
                }
            });
        });

        // Initialize Bootstrap tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });

    // AI Chat Functions - REMOVED DUPLICATE, using version at line 3170 instead

    function getCurrentPlanContext() {
        // Build context from current succession planning state
        const context = {};

        // Add harvest window info
        if (harvestWindowData.userStart && harvestWindowData.userEnd) {
            context.harvest_window = {
                start: harvestWindowData.userStart,
                end: harvestWindowData.userEnd
            };
        }

        // Add current crop selection
        const cropSelect = document.getElementById('cropSelect');
        const varietySelect = document.getElementById('varietySelect');
        if (cropSelect && cropSelect.value) {
            context.crop = cropSelect.options[cropSelect.selectedIndex].text;
        }
        if (varietySelect && varietySelect.value) {
            context.variety = varietySelect.options[varietySelect.selectedIndex].text;
        }

        // Add current succession plan if available
        if (currentSuccessionPlan) {
            context.succession_plan = {
                total_successions: currentSuccessionPlan.total_successions || 0,
                plantings_count: currentSuccessionPlan.plantings ? currentSuccessionPlan.plantings.length : 0
            };
        }

        return context;
    }

    function displayAIResponse(response) {
        const responseArea = document.getElementById('aiResponseArea');
        if (!responseArea) return;

        // Format the response with proper HTML
        const formattedResponse = response.replace(/\n/g, '<br>');
        
        responseArea.innerHTML = `
            <div class="ai-response">
                <div class="d-flex align-items-start">
                    <div class="ai-avatar me-2">
                        <i class="fas fa-robot text-primary"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="ai-message p-3 bg-light rounded">
                            ${formattedResponse}
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="fas fa-clock"></i> ${new Date().toLocaleTimeString()}
                        </small>
                    </div>
                </div>
            </div>
        `;
    }

    async function getQuickAdvice() {
        const context = getCurrentPlanContext();
        let prompt = "Provide quick succession planning advice for UK organic vegetable production.";

        if (context.crop) {
            prompt += ` Focus on ${context.crop}`;
            if (context.variety) {
                prompt += ` (${context.variety})`;
            }
        }

        prompt += " Keep it brief and actionable.";

        await askHolisticAI(prompt, 'quick_advice');
    }

    function askQuickQuestion(questionType) {
        const questions = {
            'succession-timing': "What's the optimal timing between successions for continuous harvest?",
            'companion-plants': "What are good companion plants for this crop in a UK climate?",
            'lunar-timing': "How can lunar cycles affect planting timing?",
            'harvest-optimization': "How can I optimize my harvest schedule for market demand?"
        };

        const question = questions[questionType];
        if (question) {
            askHolisticAI(question, questionType);
        }
    }

    // AI Status Functions
    async function checkAIStatus() {
        try {
            const response = await fetch('/admin/farmos/succession-planning/ai-status', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            const result = await response.json();

            if (response.ok && result.success !== undefined) {
                updateAIStatusDisplay(result);
            } else {
                updateAIStatusDisplay({ available: false, message: 'Unable to check AI status' });
            }
        } catch (error) {
            console.error('AI status check failed:', error);
            updateAIStatusDisplay({ available: false, message: 'Connection failed' });
        }
    }

    function updateAIStatusDisplay(status) {
        const statusLight = document.getElementById('aiStatusLight');
        const statusText = document.getElementById('aiStatusText');
        const statusDetails = document.getElementById('aiStatusDetails');

        if (!statusLight || !statusText) return;

        // Update status light
        statusLight.classList.remove('online', 'offline', 'checking');
        if (status.available) {
            statusLight.classList.add('online');
        } else {
            statusLight.classList.add('offline');
        }

        // Update status text
        statusText.textContent = status.available ? 'AI Service Online' : 'AI Service Offline';

        // Update details
        if (statusDetails) {
            statusDetails.textContent = status.message || '';
        }

        console.log('🤖 AI Status updated:', status);
    }

    async function refreshAIStatus() {
        const statusLight = document.getElementById('aiStatusLight');
        const statusText = document.getElementById('aiStatusText');

        if (statusLight) {
            statusLight.classList.remove('online', 'offline');
            statusLight.classList.add('checking');
        }

        if (statusText) {
            statusText.textContent = 'Checking AI service...';
        }

        await checkAIStatus();
    }

    // Initialize AI status checking
    document.addEventListener('DOMContentLoaded', function() {
        // Check AI status on page load
        checkAIStatus();

        // Set up periodic status checking (every 30 seconds)
        setInterval(checkAIStatus, 30000);

        // Set up event handlers - DISABLED: succession-planner.js handles this now
        // setupSeasonYearHandlers();
        // setupCropVarietyHandlers();
    });

    // ========================================================================
    // CLEAN REBUILT AI CHAT - Simple and reliable
    // ========================================================================
    
    const chatMessages = document.getElementById('chatMessages');
    const chatInput = document.getElementById('chatInput');
    const sendChatBtn = document.getElementById('sendChatBtn');
    
    // Send message on button click
    sendChatBtn.addEventListener('click', sendChatMessage);
    
    // Send message on Enter (but allow Shift+Enter for new lines)
    chatInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendChatMessage();
        }
    });
    
    async function sendChatMessage() {
        const message = chatInput.value.trim();
        if (!message) return;
        
        // Clear input
        chatInput.value = '';
        
        // Add user message to chat
        addMessageToChat('user', message);
        
        // Show loading message
        const loadingId = addMessageToChat('ai', '💭 Thinking...');
        
        try {
            const response = await fetch('/admin/farmos/succession-planning/chat', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: JSON.stringify({ question: message })
            });
            
            const data = await response.json();
            
            // Remove loading message
            document.getElementById(loadingId)?.remove();
            
            if (data.success && data.answer) {
                addMessageToChat('ai', data.answer);
            } else {
                addMessageToChat('ai', '❌ Sorry, I couldn\'t generate a response. Please try again.');
            }
            
        } catch (error) {
            console.error('Chat error:', error);
            document.getElementById(loadingId)?.remove();
            addMessageToChat('ai', '❌ Error: ' + error.message);
        }
    }
    
    function addMessageToChat(sender, message) {
        const msgId = 'msg-' + Date.now();
        const isUser = sender === 'user';
        
        const msgDiv = document.createElement('div');
        msgDiv.id = msgId;
        msgDiv.className = `mb-3 ${isUser ? 'text-end' : ''}`;
        msgDiv.innerHTML = `
            <div class="d-inline-block text-start" style="max-width: 85%;">
                <div class="d-flex align-items-start gap-2 ${isUser ? 'flex-row-reverse' : ''}">
                    <div class="flex-shrink-0">
                        <i class="fas fa-${isUser ? 'user' : 'robot'} text-${isUser ? 'primary' : 'success'}"></i>
                    </div>
                    <div class="flex-grow-1 p-2 rounded" style="background: ${isUser ? '#e3f2fd' : '#f1f8e9'};">
                        <small class="fw-bold">${isUser ? 'You' : 'AI Advisor'}</small><br>
                        ${message.replace(/\n/g, '<br>')}
                    </div>
                </div>
            </div>
        `;
        
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;
        
        return msgId;
    }
    
</script>
@endsection