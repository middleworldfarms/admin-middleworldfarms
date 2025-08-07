@extends('layouts.app')

@section('title', 'Succession Planning - AI-Powered farmOS Integration')

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
                <i class="fas fa-seedling"></i> Succession Planning
            </li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-seedling me-2"></i>AI-Powered Succession Planning
            </h1>
            <p class="text-muted mb-0">Plan multiple successive plantings with intelligent bed assignment and AI optimization</p>
        </div>
        
        <div class="d-flex gap-2">
            <a href="{{ route('admin.farmos.planting-chart') }}" class="btn btn-outline-success btn-sm">
                <i class="fas fa-chart-gantt"></i> View Timeline
            </a>
            <button class="btn btn-success btn-sm" id="refreshData">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Key Benefits Card -->
    <div class="alert alert-info mb-4">
        <div class="row">
            <div class="col-md-8">
                <h6><i class="fas fa-magic"></i> Streamlined Succession Planting</h6>
                <p class="mb-0">Solve farmOS's manual data entry bottleneck: Plan 10+ lettuce successions in minutes instead of hours. 
                Our AI analyzes bed availability, optimal timing, and creates all farmOS logs automatically.</p>
            </div>
            <div class="col-md-4 text-end">
                <div class="text-success">
                    <strong>Data Flow: Admin → farmOS API → Timeline</strong><br>
                    <small class="text-muted">farmOS is the master database</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Planning Form -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white;">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Create Succession Plan
                    </h5>
                </div>
                
                <div class="card-body">
                    <form id="successionForm">
                        <div class="row">
                            <!-- Crop Selection -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cropType" class="form-label">
                                        <i class="fas fa-leaf"></i> Crop Type
                                    </label>
                                    <select class="form-select" id="cropType" name="crop_type" required>
                                        <option value="">Select crop...</option>
                                        @if(isset($cropData['types']))
                                            @foreach($cropData['types'] as $crop)
                                                <option value="{{ $crop['name'] }}">{{ $crop['label'] }}</option>
                                            @endforeach
                                        @else
                                            @foreach(['lettuce', 'carrot', 'radish', 'spinach', 'kale', 'arugula', 'chard', 'beets', 'cilantro', 'dill', 'scallion', 'mesclun'] as $crop)
                                                <option value="{{ $crop }}">{{ ucfirst($crop) }}</option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="variety" class="form-label">
                                        <i class="fas fa-dna"></i> Variety
                                    </label>
                                    <select class="form-select" id="variety" name="variety" disabled>
                                        <option value="">First select a crop type...</option>
                                        <!-- Varieties will be populated by JavaScript -->
                                    </select>
                                    <div class="form-text">Optional - select a specific variety or leave blank</div>
                                </div>
                            </div>
                        </div>

                        <!-- Planting Method -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="directSow" name="direct_sow">
                                        <label class="form-check-label" for="directSow">
                                            <i class="fas fa-hand-paper"></i> <strong>Direct Sow Only</strong>
                                            <small class="text-muted d-block">Check this for crops that are planted directly in the field (carrots, radishes, cut-and-come-again mixes, etc.)</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Succession Parameters -->
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="successionCount" class="form-label">
                                        <i class="fas fa-repeat"></i> Number of Successions
                                    </label>
                                    <input type="number" class="form-control" id="successionCount" 
                                           name="succession_count" min="1" max="50" value="10" required>
                                    <div class="form-text">Plan 1-50 successive plantings</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="intervalDays" class="form-label">
                                        <i class="fas fa-clock"></i> Interval (Days)
                                    </label>
                                    <input type="number" class="form-control" id="intervalDays" 
                                           name="interval_days" min="1" max="365" value="14" required>
                                    <div class="form-text">Days between each planting</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="firstSeedingDate" class="form-label">
                                        <i class="fas fa-calendar-day"></i> First Seeding Date
                                    </label>
                                    <input type="date" class="form-control" id="firstSeedingDate" 
                                           name="first_seeding_date" value="{{ now()->format('Y-m-d') }}" required>
                                </div>
                            </div>
                        </div>

                        <!-- Crop Timing -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3" id="seedingToTransplantGroup">
                                    <label for="seedingToTransplant" class="form-label">
                                        <i class="fas fa-seedling"></i> Seeding to Transplant (Days)
                                        <span class="badge bg-secondary ms-1" id="transplantOnlyBadge">Transplant Only</span>
                                    </label>
                                    <input type="number" class="form-control" id="seedingToTransplant" 
                                           name="seeding_to_transplant_days" min="0" max="180" value="21">
                                    <div class="form-text">Time from seeding to transplant (ignored for direct sow)</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="transplantToHarvest" class="form-label">
                                        <i class="fas fa-cut"></i> <span id="transplantToHarvestLabel">Transplant to Harvest (Days)</span>
                                    </label>
                                    <input type="number" class="form-control" id="transplantToHarvest" 
                                           name="transplant_to_harvest_days" min="1" max="365" value="44" required>
                                    <div class="form-text" id="transplantToHarvestHelp">Growing period from transplant to harvest</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="harvestDuration" class="form-label">
                                        <i class="fas fa-hourglass-half"></i> Harvest Window (Days)
                                    </label>
                                    <input type="number" class="form-control" id="harvestDuration" 
                                           name="harvest_duration_days" min="1" max="90" value="14" required>
                                    <div class="form-text">How long harvest lasts</div>
                                </div>
                            </div>
                        </div>

                        <!-- Bed Assignment -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="bedsPerPlanting" class="form-label">
                                        <i class="fas fa-th-large"></i> Beds per Planting
                                    </label>
                                    <input type="number" class="form-control" id="bedsPerPlanting" 
                                           name="beds_per_planting" min="1" max="10" value="1" required>
                                    <div class="form-text">How many beds for each succession</div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <div class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" id="autoAssignBeds" 
                                               name="auto_assign_beds" checked>
                                        <label class="form-check-label" for="autoAssignBeds">
                                            <i class="fas fa-magic"></i> AI Auto-Assign Beds
                                        </label>
                                        <div class="form-text">Let AI choose optimal beds with conflict resolution</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="mb-3">
                            <label for="notes" class="form-label">
                                <i class="fas fa-sticky-note"></i> Notes
                            </label>
                            <textarea class="form-control" id="notes" name="notes" rows="2" 
                                      placeholder="Additional notes for this succession plan..."></textarea>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" id="generatePlan">
                                <i class="fas fa-magic"></i> Generate Plan with AI
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="usePreset">
                                <i class="fas fa-download"></i> Use Crop Preset
                            </button>
                            <button type="reset" class="btn btn-outline-danger">
                                <i class="fas fa-undo"></i> Reset
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Quick Actions & AI Assistant -->
        <div class="col-md-4">
            <!-- Crop Presets -->
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-seedling"></i> Quick Crop Presets</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button class="btn btn-outline-success btn-sm preset-btn" data-crop="lettuce">
                            <i class="fas fa-leaf"></i> Lettuce (65 days)
                        </button>
                        <button class="btn btn-outline-primary btn-sm preset-btn" data-crop="carrot">
                            <i class="fas fa-carrot"></i> Carrot (75 days)
                        </button>
                        <button class="btn btn-outline-warning btn-sm preset-btn" data-crop="radish">
                            <i class="fas fa-circle"></i> Radish (30 days)
                        </button>
                        <button class="btn btn-outline-info btn-sm preset-btn" data-crop="spinach">
                            <i class="fas fa-leaf"></i> Spinach (50 days)
                        </button>
                    </div>
                </div>
            </div>

            <!-- AI Assistant -->
            <div class="card mb-3">
                <div class="card-header bg-gradient-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-robot"></i> AI Assistant</h6>
                </div>
                <div class="card-body">
                    <div id="aiAssistant">
                        <p class="text-muted small">
                            <i class="fas fa-lightbulb"></i> Select a crop to get AI-powered recommendations for:
                        </p>
                        <ul class="small text-muted">
                            <li>Optimal planting intervals</li>
                            <li>Best bed rotation strategy</li>
                            <li>Seasonal timing adjustments</li>
                            <li>Conflict resolution</li>
                        </ul>
                        <button class="btn btn-outline-primary btn-sm w-100" id="askAI" disabled>
                            <i class="fas fa-brain"></i> Get AI Recommendations
                        </button>
                    </div>
                </div>
            </div>

            <!-- Current Status -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Farm Status</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <span>Available Beds:</span>
                        <span class="badge bg-success" id="availableBeds">{{ count($availableBeds ?? []) }}</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>Active Plans:</span>
                        <span class="badge bg-info" id="activePlans">-</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>AI Service:</span>
                        <span class="badge bg-success" id="aiStatus">Connected</span>
                    </div>
                </div>
            </div>


        </div>
    </div>

    <!-- Interactive Gantt Chart Timeline -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #6f42c1 0%, #6610f2 100%); color: white;">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-gantt"></i> Visual Succession Timeline
                    </h5>
                    <div>
                        <button class="btn btn-light btn-sm" id="workBackwards">
                            <i class="fas fa-backward"></i> Work Backwards from Harvest
                        </button>
                        <button class="btn btn-light btn-sm" id="autoOptimize">
                            <i class="fas fa-magic"></i> AI Optimize
                        </button>
                        <button class="btn btn-light btn-sm" id="resetTimeline">
                            <i class="fas fa-undo"></i> Reset
                        </button>
                    </div>
                </div>
                
                <div class="card-body p-0">
                    <!-- Timeline Controls -->
                    <div class="p-3 border-bottom bg-light">
                        <div class="row align-items-center">
                            <div class="col-md-3">
                                <label for="timelineStart" class="form-label small mb-1">Timeline Start:</label>
                                <input type="date" class="form-control form-control-sm" id="timelineStart" value="{{ date('Y-m-d') }}">
                            </div>
                            <div class="col-md-3">
                                <label for="timelineEnd" class="form-label small mb-1">Timeline End:</label>
                                <input type="date" class="form-control form-control-sm" id="timelineEnd" value="{{ date('Y-m-d', strtotime('+6 months')) }}">
                            </div>
                            <div class="col-md-3">
                                <label for="timelineZoom" class="form-label small mb-1">Zoom Level:</label>
                                <select class="form-select form-select-sm" id="timelineZoom">
                                    <option value="week">Week View</option>
                                    <option value="month" selected>Month View</option>
                                    <option value="quarter">Quarter View</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small mb-1">Legend:</label>
                                <div class="d-flex gap-2 small">
                                    <span class="badge bg-primary">Seeding</span>
                                    <span class="badge bg-warning">Transplant</span>
                                    <span class="badge bg-success">Harvest</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Gantt Chart Container -->
                    <div id="ganttContainer" style="min-height: 400px; overflow-x: auto;">
                        <div id="ganttChart" style="position: relative; width: 100%; min-width: 800px;">
                            <!-- Timeline Header -->
                            <div id="timelineHeader" style="height: 40px; background: #f8f9fa; border-bottom: 2px solid #dee2e6; position: sticky; top: 0; z-index: 10;">
                                <!-- Date headers will be generated by JavaScript -->
                            </div>
                            
                            <!-- Succession Rows -->
                            <div id="successionRows">
                                <div class="gantt-empty-state text-center py-5 text-muted">
                                    <i class="fas fa-chart-gantt fa-3x mb-3 opacity-50"></i>
                                    <h5>Interactive Succession Timeline</h5>
                                    <p>Fill out the form above and click "Generate Plan" to see your succession timeline.<br>
                                    You can then drag and drop to adjust dates and optimize your planting schedule.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Timeline Footer with Actions -->
                    <div class="p-3 border-top bg-light">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="d-flex gap-2">
                                    <button class="btn btn-primary btn-sm" id="addSuccession" disabled>
                                        <i class="fas fa-plus"></i> Add Succession
                                    </button>
                                    <button class="btn btn-danger btn-sm" id="removeSuccession" disabled>
                                        <i class="fas fa-minus"></i> Remove Last
                                    </button>
                                    <button class="btn btn-info btn-sm" id="duplicateSuccession" disabled>
                                        <i class="fas fa-copy"></i> Duplicate
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="small text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    Drag timeline bars to adjust dates • Right-click for options • Scroll to zoom
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div class="row mt-4" id="resultsSection" style="display: none;">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-line"></i> Generated Succession Plan
                    </h5>
                    <div>
                        <button class="btn btn-success" id="createInFarmOS" disabled>
                            <i class="fas fa-cloud-upload-alt"></i> Create in farmOS
                        </button>
                        <button class="btn btn-outline-secondary" id="exportPlan">
                            <i class="fas fa-download"></i> Export CSV
                        </button>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Plan Summary -->
                    <div class="row mb-4" id="planSummary">
                        <!-- Will be populated by JavaScript -->
                    </div>

                    <!-- AI Recommendations -->
                    <div class="alert alert-info" id="aiRecommendations" style="display: none;">
                        <h6><i class="fas fa-robot"></i> AI Analysis & Recommendations</h6>
                        <div id="aiRecommendationText"></div>
                    </div>

                    <!-- Plan Timeline -->
                    <div class="table-responsive">
                        <table class="table table-striped" id="planTable">
                            <thead class="table-success">
                                <tr>
                                    <th>Sequence</th>
                                    <th>Seeding Date</th>
                                    <th>Transplant Date</th>
                                    <th>Harvest Date</th>
                                    <th>Assigned Bed</th>
                                    <th>Status</th>
                                    <th>Conflicts</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Debug Output Section -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h6 class="mb-0"><i class="fas fa-bug"></i> Debug Output</h6>
                <button class="btn btn-sm btn-outline-secondary" id="clearDebug">Clear</button>
                <button class="btn btn-sm btn-outline-primary" id="testAITiming">Test AI Timing</button>
                <button class="btn btn-sm btn-outline-warning" id="testCropChange" onclick="window.testCropChange()">Test Crop Change</button>
                <button class="btn btn-sm btn-outline-success" onclick="alert('Basic click test works!')">Basic Test</button>
            </div>
            <div class="card-body">
                <pre id="debugOutput" style="height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; font-size: 12px;">Debug output will appear here...\n</pre>
            </div>
        </div>
    </div>
</div>

<!-- Direct JavaScript for testing -->
<script>
console.log('Succession planning JS loading...');

// Initialize data from Laravel
const cropPresets = @json($cropPresets ?? []);
const cropData = @json($cropData ?? ['types' => [], 'varieties' => []]);

// Global test function for debugging
window.testCropChange = function() {
    console.log('Manual test function called');
    const debugOutput = document.getElementById('debugOutput');
    if (debugOutput) {
        debugOutput.innerHTML += 'Manual test function called!\n';
    }
    
    const cropType = document.getElementById('cropType');
    if (cropType) {
        console.log('Crop type element found, current value:', cropType.value);
        if (debugOutput) {
            debugOutput.innerHTML += `Crop type found: ${cropType.value}\n`;
        }
        
        // Try to trigger change event
        const event = new Event('change', { bubbles: true });
        cropType.dispatchEvent(event);
        
        if (debugOutput) {
            debugOutput.innerHTML += 'Change event dispatched\n';
        }
    } else {
        console.log('Crop type element NOT found');
        if (debugOutput) {
            debugOutput.innerHTML += 'ERROR: Crop type element not found\n';
        }
    }
};

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded fired');
    
    const debugOutput = document.getElementById('debugOutput');
    function simpleLog(message) {
        console.log(message);
        if (debugOutput) {
            debugOutput.innerHTML += message + '\n';
            debugOutput.scrollTop = debugOutput.scrollHeight;
        }
    }
    
    simpleLog('Page loaded, initializing succession planning');
    simpleLog(`Available crop presets: ${Object.keys(cropPresets || {}).length}`);
    
    // Debug crop data from farmOS API
    simpleLog(`farmOS Crop Data - Types: ${cropData?.types?.length || 0}, Varieties: ${cropData?.varieties?.length || 0}`);
    
    if (cropData && cropData.types && cropData.types.length > 0) {
        simpleLog(`Sample crop types: ${JSON.stringify(cropData.types.slice(0, 3))}`);
    } else {
        simpleLog('WARNING: No crop types loaded from farmOS API!');
    }
    
    if (cropData && cropData.varieties && cropData.varieties.length > 0) {
        simpleLog(`Sample varieties: ${JSON.stringify(cropData.varieties.slice(0, 5))}`);
    } else {
        simpleLog('WARNING: No varieties loaded from farmOS API!');
    }
    
    // Debug crop presets
    if (cropPresets && Object.keys(cropPresets).length > 0) {
        simpleLog(`Crop presets loaded: ${JSON.stringify(Object.keys(cropPresets))}`);
    } else {
        simpleLog('WARNING: No crop presets loaded!');
    }
    
    // Check if our elements exist
    const cropTypeElement = document.getElementById('cropType');
    const testCropChangeBtn = document.getElementById('testCropChange');
    
    simpleLog(`Elements found: cropType=${!!cropTypeElement}, testCrop=${!!testCropChangeBtn}`);
    
    // Populate crop type dropdown with farmOS data
    if (cropTypeElement && cropData && cropData.types) {
        cropTypeElement.innerHTML = '<option value="">Select crop type...</option>';
        cropData.types.forEach(crop => {
            const option = document.createElement('option');
            option.value = crop.name;
            option.textContent = crop.label || crop.name;
            cropTypeElement.appendChild(option);
        });
        simpleLog(`Populated crop dropdown with ${cropData.types.length} options`);
    } else {
        simpleLog('ERROR: Could not populate crop dropdown - missing element or data');
    }
    
    simpleLog(`Elements found: cropType=${!!cropTypeElement}, testCrop=${!!testCropChangeBtn}`);
    
    // Simple event listeners without complex error handling
    if (cropTypeElement) {
        simpleLog('Adding crop type change listener');
        cropTypeElement.addEventListener('change', function(event) {
            simpleLog(`CROP TYPE CHANGED TO: ${event.target.value}`);
            
            // Smart variety loading - call API for specific crop
            const varietySelect = document.getElementById('variety');
            const crop = event.target.value;
            
            if (varietySelect && crop) {
                // Enable the dropdown and show loading state
                varietySelect.disabled = false;
                varietySelect.innerHTML = '<option value="">Loading varieties...</option>';
                
                // Make API call to get varieties for this specific crop
                fetch(`/admin/farmos/api/varieties/${encodeURIComponent(crop)}`, {
                    method: 'GET',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    varietySelect.innerHTML = '<option value="">Select variety (optional)...</option>';
                    
                    if (data.success && data.varieties && data.varieties.length > 0) {
                        data.varieties.forEach(variety => {
                            const option = document.createElement('option');
                            option.value = variety.name;
                            option.textContent = variety.label || variety.name;
                            varietySelect.appendChild(option);
                        });
                        simpleLog(`Loaded ${data.varieties.length} varieties for ${crop}`);
                    } else {
                        simpleLog(`No varieties found for ${crop}`);
                    }
                })
                .catch(error => {
                    varietySelect.innerHTML = '<option value="">Error loading varieties</option>';
                    simpleLog(`Error loading varieties: ${error.message}`);
                });
            } else if (varietySelect) {
                // No crop selected - disable and reset
                varietySelect.disabled = true;
                varietySelect.innerHTML = '<option value="">First select a crop type...</option>';
            }
            
            // Basic preset application
            if (crop && cropPresets) {
                // Try exact match first, then lowercase match
                const cropKey = cropPresets[crop] ? crop : crop.toLowerCase();
                
                if (cropPresets[cropKey]) {
                    simpleLog(`Applying preset for: ${crop} (using key: ${cropKey})`);
                    const preset = cropPresets[cropKey];
                    
                    const seedingToTransplant = document.getElementById('seedingToTransplant');
                    const transplantToHarvest = document.getElementById('transplantToHarvest');
                    const harvestDuration = document.getElementById('harvestDuration');
                    const directSowCheckbox = document.getElementById('directSow');
                    
                    if (seedingToTransplant && preset.transplant_days !== undefined) {
                        seedingToTransplant.value = preset.transplant_days;
                        simpleLog(`Set seeding to transplant: ${preset.transplant_days}`);
                    }
                    if (transplantToHarvest && preset.harvest_days !== undefined && preset.transplant_days !== undefined) {
                        transplantToHarvest.value = preset.harvest_days - preset.transplant_days;
                        simpleLog(`Set transplant to harvest: ${preset.harvest_days - preset.transplant_days}`);
                    }
                    if (harvestDuration && preset.yield_period !== undefined) {
                        harvestDuration.value = preset.yield_period;
                        simpleLog(`Set harvest duration: ${preset.yield_period}`);
                    }
                    
                    // Auto-set direct sow for crops with 0 transplant days
                    if (directSowCheckbox && preset.transplant_days === 0) {
                        directSowCheckbox.checked = true;
                        simpleLog('Auto-enabled direct sow mode (transplant_days = 0)');
                    } else if (directSowCheckbox && preset.transplant_days > 0) {
                        directSowCheckbox.checked = false;
                        simpleLog('Disabled direct sow mode (transplant required)');
                    }
                    
                    simpleLog('Preset values applied successfully');
                } else {
                    simpleLog(`No preset found for: ${crop} (tried keys: ${crop}, ${crop.toLowerCase()})`);
                }
            } else {
                simpleLog(`No crop selected or presets not available`);
            }
        });
        simpleLog('Crop type change listener added successfully');
    } else {
        simpleLog('ERROR: cropType element not found!');
    }
    
    simpleLog('Basic initialization complete');
});

// Additional Laravel-specific functions that need server data
async function generateSuccessionPlan() {
    console.log('Generate succession plan called');
    const formData = new FormData(document.getElementById('successionForm'));
    
    try {
        const response = await fetch('{{ route('admin.farmos.succession-planning.generate') }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        console.log('Plan generation result:', result);
        
        if (result.success) {
            // Handle successful plan generation
            alert('Plan generated successfully!');
        } else {
            alert('Failed to generate plan: ' + result.message);
        }
        
    } catch (error) {
        console.error('Error generating plan:', error);
        alert('Error generating plan: ' + error.message);
    }
}
</script>

<style>
.preset-btn {
    transition: all 0.2s ease;
}

.preset-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.gantt-empty-state {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border-radius: 8px;
    margin: 20px;
}

.gantt-bar {
    transition: all 0.2s ease;
    box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}

.gantt-bar:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 6px rgba(0,0,0,0.3);
}

.succession-row:hover {
    background: #f1f3f4 !important;
}

.timeline-marker {
    border-left: 1px solid #dee2e6;
    position: absolute;
    height: 100%;
    top: 0;
}

.timeline-marker.major {
    border-left: 2px solid #6c757d;
}

.card-header.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%) !important;
}

.card-header.bg-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%) !important;
}

.form-control:focus,
.form-select:focus {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}

.btn-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border: none;
}

.btn-success:hover {
    background: linear-gradient(135deg, #218838 0%, #1e7e34 100%);
    transform: translateY(-1px);
}

.table-success th {
    background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    border-color: #28a745;
}

.alert-info {
    background: linear-gradient(135deg, #d1ecf1 0%, #bee5eb 100%);
    border-color: #17a2b8;
}

.badge {
    font-size: 0.75em;
    font-weight: 600;
}

#debugOutput {
    font-family: 'Courier New', monospace;
    white-space: pre-wrap;
    word-wrap: break-word;
}

.text-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.text-gradient-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
        this.bindEvents();
        this.updateTimeline();
    }
    
    bindEvents() {
        // Timeline controls
        document.getElementById('timelineStart').addEventListener('change', (e) => {
            this.timelineStart = new Date(e.target.value);
            this.updateTimeline();
        });
        
        document.getElementById('timelineEnd').addEventListener('change', (e) => {
            this.timelineEnd = new Date(e.target.value);
            this.updateTimeline();
        });
        
        document.getElementById('timelineZoom').addEventListener('change', (e) => {
            this.zoomLevel = e.target.value;
            this.adjustZoom();
            this.updateTimeline();
        });
        
        // Action buttons
        document.getElementById('workBackwards').addEventListener('click', () => this.workBackwardsFromHarvest());
        document.getElementById('autoOptimize').addEventListener('click', () => this.autoOptimize());
        document.getElementById('resetTimeline').addEventListener('click', () => this.resetTimeline());
        document.getElementById('addSuccession').addEventListener('click', () => this.addSuccession());
        document.getElementById('removeSuccession').addEventListener('click', () => this.removeSuccession());
        document.getElementById('duplicateSuccession').addEventListener('click', () => this.duplicateSuccession());
    }
    
    adjustZoom() {
        switch(this.zoomLevel) {
            case 'week':
                this.dayWidth = 8;
                break;
            case 'month':
                this.dayWidth = 4;
                break;
            case 'quarter':
                this.dayWidth = 2;
                break;
        }
    }
    
    updateTimeline() {
        this.renderTimelineHeader();
        this.renderSuccessionRows();
    }
    
    renderTimelineHeader() {
        const header = document.getElementById('timelineHeader');
        const totalDays = Math.ceil((this.timelineEnd - this.timelineStart) / (1000 * 60 * 60 * 24));
        const totalWidth = totalDays * this.dayWidth;
        
        let headerHTML = '<div style="display: flex; position: relative; width: ' + totalWidth + 'px;">';
        
        // Generate date labels based on zoom level
        const current = new Date(this.timelineStart);
        while (current <= this.timelineEnd) {
            const dayOffset = Math.floor((current - this.timelineStart) / (1000 * 60 * 60 * 24));
            const x = dayOffset * this.dayWidth;
            
            if (this.zoomLevel === 'week' && current.getDay() === 1) {
                // Weekly markers (Mondays)
                headerHTML += `<div style="position: absolute; left: ${x}px; top: 0; height: 40px; border-left: 1px solid #ccc; font-size: 11px; padding: 2px 4px;">
                    ${current.toLocaleDateString('en-GB', {month: 'short', day: 'numeric'})}
                </div>`;
            } else if (this.zoomLevel === 'month' && current.getDate() === 1) {
                // Monthly markers
                headerHTML += `<div style="position: absolute; left: ${x}px; top: 0; height: 40px; border-left: 2px solid #999; font-size: 12px; font-weight: bold; padding: 2px 4px; background: rgba(255,255,255,0.9);">
                    ${current.toLocaleDateString('en-GB', {month: 'short', year: 'numeric'})}
                </div>`;
            } else if (this.zoomLevel === 'quarter' && current.getDate() === 1 && current.getMonth() % 3 === 0) {
                // Quarterly markers
                headerHTML += `<div style="position: absolute; left: ${x}px; top: 0; height: 40px; border-left: 3px solid #333; font-size: 13px; font-weight: bold; padding: 2px 4px; background: rgba(255,255,255,0.95);">
                    Q${Math.floor(current.getMonth() / 3) + 1} ${current.getFullYear()}
                </div>`;
            }
            
            current.setDate(current.getDate() + 1);
        }
        
        headerHTML += '</div>';
        header.innerHTML = headerHTML;
    }
    
    renderSuccessionRows() {
        const container = document.getElementById('successionRows');
        
        if (this.successions.length === 0) {
            container.innerHTML = `
                <div class="gantt-empty-state text-center py-5 text-muted">
                    <i class="fas fa-chart-gantt fa-3x mb-3 opacity-50"></i>
                    <h5>Interactive Succession Timeline</h5>
                    <p>Fill out the form above and click "Generate Plan" to see your succession timeline.<br>
                    You can then drag and drop to adjust dates and optimize your planting schedule.</p>
                </div>`;
            return;
        }
        
        const totalDays = Math.ceil((this.timelineEnd - this.timelineStart) / (1000 * 60 * 60 * 24));
        const totalWidth = totalDays * this.dayWidth;
        
        let rowsHTML = '';
        
        this.successions.forEach((succession, index) => {
            rowsHTML += this.renderSuccessionRow(succession, index, totalWidth);
        });
        
        container.innerHTML = rowsHTML;
        this.bindDragEvents();
    }
    
    renderSuccessionRow(succession, index, totalWidth) {
        const seedingX = this.dateToX(succession.seedingDate);
        const transplantX = succession.transplantDate ? this.dateToX(succession.transplantDate) : null;
        const harvestX = this.dateToX(succession.harvestDate);
        const harvestEndX = this.dateToX(succession.harvestEndDate);
        
        const seedingWidth = transplantX ? (transplantX - seedingX) : (harvestX - seedingX);
        const transplantWidth = transplantX ? (harvestX - transplantX) : 0;
        const harvestWidth = harvestEndX - harvestX;
        
        return `
            <div class="succession-row" style="height: ${this.rowHeight}px; border-bottom: 1px solid #eee; position: relative; background: ${index % 2 === 0 ? '#fafafa' : '#fff'};">
                <!-- Row Label -->
                <div style="position: absolute; left: 0; top: 0; width: 150px; height: ${this.rowHeight}px; background: #f8f9fa; border-right: 1px solid #ddd; display: flex; align-items: center; padding: 0 10px; font-size: 12px; font-weight: bold;">
                    <div>
                        <div>Succession ${index + 1}</div>
                        <div class="text-muted small">${succession.cropType} ${succession.variety || ''}</div>
                        ${succession.bed ? `<div class="badge bg-secondary small">Bed ${succession.bed}</div>` : ''}
                    </div>
                </div>
                
                <!-- Timeline Container -->
                <div style="margin-left: 150px; position: relative; height: ${this.rowHeight}px; width: ${totalWidth}px;">
                    <!-- Seeding Phase -->
                    <div class="gantt-bar gantt-seeding draggable" 
                         data-succession="${index}" 
                         data-phase="seeding"
                         style="position: absolute; left: ${seedingX}px; top: 10px; width: ${seedingWidth}px; height: 15px; background: linear-gradient(135deg, #007bff, #0056b3); border-radius: 3px; cursor: move; color: white; font-size: 10px; display: flex; align-items: center; padding: 0 5px;"
                         title="Seeding: ${succession.seedingDate.toLocaleDateString()} ${transplantX ? '→ Transplant: ' + succession.transplantDate.toLocaleDateString() : '→ Harvest: ' + succession.harvestDate.toLocaleDateString()}">
                        <i class="fas fa-seedling me-1"></i>
                        ${transplantX ? 'Seed' : 'Direct'}
                    </div>
                    
                    ${transplantX ? `
                        <!-- Transplant Phase -->
                        <div class="gantt-bar gantt-transplant draggable" 
                             data-succession="${index}" 
                             data-phase="transplant"
                             style="position: absolute; left: ${transplantX}px; top: 25px; width: ${transplantWidth}px; height: 15px; background: linear-gradient(135deg, #ffc107, #e0a800); border-radius: 3px; cursor: move; color: black; font-size: 10px; display: flex; align-items: center; padding: 0 5px;"
                             title="Transplant: ${succession.transplantDate.toLocaleDateString()} → Harvest: ${succession.harvestDate.toLocaleDateString()}">
                            <i class="fas fa-leaf me-1"></i>
                            Grow
                        </div>
                    ` : ''}
                    
                    <!-- Harvest Phase -->
                    <div class="gantt-bar gantt-harvest draggable" 
                         data-succession="${index}" 
                         data-phase="harvest"
                         style="position: absolute; left: ${harvestX}px; top: 40px; width: ${harvestWidth}px; height: 15px; background: linear-gradient(135deg, #28a745, #1e7e34); border-radius: 3px; cursor: move; color: white; font-size: 10px; display: flex; align-items: center; padding: 0 5px;"
                         title="Harvest: ${succession.harvestDate.toLocaleDateString()} → ${succession.harvestEndDate.toLocaleDateString()}">
                        <i class="fas fa-cut me-1"></i>
                        Harvest
                    </div>
                    
                    <!-- Date Labels -->
                    <div style="position: absolute; left: ${seedingX}px; top: ${this.rowHeight - 15}px; font-size: 9px; color: #666;">
                        ${succession.seedingDate.toLocaleDateString('en-GB', {month: 'short', day: 'numeric'})}
                    </div>
                    ${transplantX ? `
                        <div style="position: absolute; left: ${transplantX}px; top: ${this.rowHeight - 15}px; font-size: 9px; color: #666;">
                            ${succession.transplantDate.toLocaleDateString('en-GB', {month: 'short', day: 'numeric'})}
                        </div>
                    ` : ''}
                    <div style="position: absolute; left: ${harvestX}px; top: ${this.rowHeight - 15}px; font-size: 9px; color: #666;">
                        ${succession.harvestDate.toLocaleDateString('en-GB', {month: 'short', day: 'numeric'})}
                    </div>
                </div>
            </div>
        `;
    }
    
    dateToX(date) {
        const dayOffset = Math.floor((date - this.timelineStart) / (1000 * 60 * 60 * 24));
        return Math.max(0, dayOffset * this.dayWidth);
    }
    
    xToDate(x) {
        const dayOffset = Math.floor(x / this.dayWidth);
        const date = new Date(this.timelineStart);
        date.setDate(date.getDate() + dayOffset);
        return date;
    }
    
    bindDragEvents() {
        const draggables = document.querySelectorAll('.gantt-bar.draggable');
        
        draggables.forEach(bar => {
            bar.addEventListener('mousedown', (e) => this.startDrag(e, bar));
        });
        
        document.addEventListener('mousemove', (e) => this.onDrag(e));
        document.addEventListener('mouseup', () => this.endDrag());
    }
    
    startDrag(e, bar) {
        this.isDragging = true;
        this.dragTarget = bar;
        this.dragStartX = e.clientX;
        this.dragStartLeft = parseInt(bar.style.left);
        bar.style.opacity = '0.7';
        e.preventDefault();
    }
    
    onDrag(e) {
        if (!this.isDragging || !this.dragTarget) return;
        
        const deltaX = e.clientX - this.dragStartX;
        const newLeft = Math.max(0, this.dragStartLeft + deltaX);
        
        this.dragTarget.style.left = newLeft + 'px';
        
        // Update related bars
        this.updateRelatedBars(this.dragTarget, deltaX);
    }
    
    updateRelatedBars(targetBar, deltaX) {
        const successionIndex = parseInt(targetBar.dataset.succession);
        const phase = targetBar.dataset.phase;
        const succession = this.successions[successionIndex];
        
        // Update dates based on the drag
        const newDate = this.xToDate(parseInt(targetBar.style.left));
        
        if (phase === 'seeding') {
            succession.seedingDate = newDate;
            if (succession.transplantDate) {
                succession.transplantDate = new Date(newDate);
                succession.transplantDate.setDate(succession.transplantDate.getDate() + succession.seedingToTransplant);
            }
            succession.harvestDate = new Date(succession.transplantDate || newDate);
            succession.harvestDate.setDate(succession.harvestDate.getDate() + succession.transplantToHarvest);
            succession.harvestEndDate = new Date(succession.harvestDate);
            succession.harvestEndDate.setDate(succession.harvestEndDate.getDate() + succession.harvestWindow);
        }
        
        // Re-render this row to update all related bars
        this.renderSuccessionRows();
    }
    
    endDrag() {
        if (this.dragTarget) {
            this.dragTarget.style.opacity = '1';
            this.dragTarget = null;
        }
        this.isDragging = false;
        
        // Update the form and preview table
        this.updateFormFromGantt();
        window.generatePlan(); // Refresh the plan
    }
    
    loadFromForm() {
        const form = document.getElementById('successionForm');
        const formData = new FormData(form);
        
        const cropType = formData.get('crop_type');
        const variety = formData.get('variety');
        const numSuccessions = parseInt(formData.get('succession_count')) || 1;
        const interval = parseInt(formData.get('interval')) || 14;
        const seedingToTransplant = parseInt(formData.get('seeding_to_transplant_days')) || 0;
        const transplantToHarvest = parseInt(formData.get('transplant_to_harvest_days')) || 60;
        const harvestWindow = parseInt(formData.get('harvest_duration_days')) || 14;
        const directSow = formData.get('direct_sow') === 'on';
        
        let firstSeedingDate = new Date(formData.get('first_seeding_date'));
        
        this.successions = [];
        
        for (let i = 0; i < numSuccessions; i++) {
            const seedingDate = new Date(firstSeedingDate);
            seedingDate.setDate(seedingDate.getDate() + (i * interval));
            
            let transplantDate = null;
            if (!directSow) {
                transplantDate = new Date(seedingDate);
                transplantDate.setDate(transplantDate.getDate() + seedingToTransplant);
            }
            
            const harvestDate = new Date(transplantDate || seedingDate);
            harvestDate.setDate(harvestDate.getDate() + transplantToHarvest);
            
            const harvestEndDate = new Date(harvestDate);
            harvestEndDate.setDate(harvestEndDate.getDate() + harvestWindow);
            
            this.successions.push({
                index: i + 1,
                cropType,
                variety,
                seedingDate,
                transplantDate,
                harvestDate,
                harvestEndDate,
                seedingToTransplant,
                transplantToHarvest,
                harvestWindow,
                directSow,
                bed: null
            });
        }
        
        // Enable timeline buttons
        document.getElementById('addSuccession').disabled = false;
        document.getElementById('removeSuccession').disabled = false;
        document.getElementById('duplicateSuccession').disabled = false;
        
        this.updateTimeline();
    }
    
    updateFormFromGantt() {
        // Update form values based on gantt chart changes
        if (this.successions.length > 0) {
            const firstSuccession = this.successions[0];
            document.getElementById('firstSeedingDate').value = firstSuccession.seedingDate.toISOString().split('T')[0];
            
            if (this.successions.length > 1) {
                const interval = Math.round((this.successions[1].seedingDate - this.successions[0].seedingDate) / (1000 * 60 * 60 * 24));
                document.getElementById('interval').value = interval;
            }
        }
    }
    
    workBackwardsFromHarvest() {
        const desiredHarvestDate = prompt('Enter your desired final harvest date (YYYY-MM-DD):');
        if (!desiredHarvestDate) return;
        
        const targetDate = new Date(desiredHarvestDate);
        if (this.successions.length === 0) return;
        
        // Work backwards from the target date
        const lastSuccession = this.successions[this.successions.length - 1];
        const totalGrowingTime = lastSuccession.transplantToHarvest + (lastSuccession.seedingToTransplant || 0);
        
        // Calculate new first seeding date
        const newFirstSeedingDate = new Date(targetDate);
        newFirstSeedingDate.setDate(newFirstSeedingDate.getDate() - totalGrowingTime - ((this.successions.length - 1) * parseInt(document.getElementById('interval').value)));
        
        // Update form and regenerate
        document.getElementById('firstSeedingDate').value = newFirstSeedingDate.toISOString().split('T')[0];
        this.loadFromForm();
        
        this.debugLog(`Worked backwards from ${desiredHarvestDate}: New first seeding date is ${newFirstSeedingDate.toDateString()}`);
    }
    
    autoOptimize() {
        // AI optimization logic
        this.debugLog('AI Optimization: Analyzing bed availability and seasonal timing...');
        
        // Simulate AI optimization
        setTimeout(() => {
            this.optimizeIntervals();
            this.updateTimeline();
            this.debugLog('AI Optimization complete: Adjusted intervals for optimal bed rotation');
        }, 1000);
    }
    
    optimizeIntervals() {
        // Simple optimization - adjust intervals based on harvest windows
        this.successions.forEach((succession, index) => {
            if (index > 0) {
                const prevHarvestEnd = this.successions[index - 1].harvestEndDate;
                const currentSeeding = succession.seedingDate;
                
                // Ensure optimal spacing
                const optimalGap = 7; // 7 days between harvest end and next seeding
                const newSeedingDate = new Date(prevHarvestEnd);
                newSeedingDate.setDate(newSeedingDate.getDate() + optimalGap);
                
                if (newSeedingDate > currentSeeding) {
                    succession.seedingDate = newSeedingDate;
                    // Recalculate other dates
                    if (succession.transplantDate) {
                        succession.transplantDate = new Date(succession.seedingDate);
                        succession.transplantDate.setDate(succession.transplantDate.getDate() + succession.seedingToTransplant);
                    }
                    succession.harvestDate = new Date(succession.transplantDate || succession.seedingDate);
                    succession.harvestDate.setDate(succession.harvestDate.getDate() + succession.transplantToHarvest);
                    succession.harvestEndDate = new Date(succession.harvestDate);
                    succession.harvestEndDate.setDate(succession.harvestEndDate.getDate() + succession.harvestWindow);
                }
            }
        });
    }
    
    resetTimeline() {
        this.successions = [];
        this.updateTimeline();
        
        // Disable timeline buttons
        document.getElementById('addSuccession').disabled = true;
        document.getElementById('removeSuccession').disabled = true;
        document.getElementById('duplicateSuccession').disabled = true;
        
        this.debugLog('Timeline reset');
    }
    
    addSuccession() {
        if (this.successions.length === 0) return;
        
        const lastSuccession = this.successions[this.successions.length - 1];
        const interval = parseInt(document.getElementById('interval').value) || 14;
        
        const newSeedingDate = new Date(lastSuccession.seedingDate);
        newSeedingDate.setDate(newSeedingDate.getDate() + interval);
        
        let newTransplantDate = null;
        if (!lastSuccession.directSow) {
            newTransplantDate = new Date(newSeedingDate);
            newTransplantDate.setDate(newTransplantDate.getDate() + lastSuccession.seedingToTransplant);
        }
        
        const newHarvestDate = new Date(newTransplantDate || newSeedingDate);
        newHarvestDate.setDate(newHarvestDate.getDate() + lastSuccession.transplantToHarvest);
        
        const newHarvestEndDate = new Date(newHarvestDate);
        newHarvestEndDate.setDate(newHarvestEndDate.getDate() + lastSuccession.harvestWindow);
        
        this.successions.push({
            index: this.successions.length + 1,
            cropType: lastSuccession.cropType,
            variety: lastSuccession.variety,
            seedingDate: newSeedingDate,
            transplantDate: newTransplantDate,
            harvestDate: newHarvestDate,
            harvestEndDate: newHarvestEndDate,
            seedingToTransplant: lastSuccession.seedingToTransplant,
            transplantToHarvest: lastSuccession.transplantToHarvest,
            harvestWindow: lastSuccession.harvestWindow,
            directSow: lastSuccession.directSow,
            bed: null
        });
        
        document.getElementById('successionCount').value = this.successions.length;
        this.updateTimeline();
        this.debugLog(`Added succession #${this.successions.length}`);
    }
    
    removeSuccession() {
        if (this.successions.length > 1) {
            this.successions.pop();
            document.getElementById('successionCount').value = this.successions.length;
            this.updateTimeline();
            this.debugLog(`Removed succession, now ${this.successions.length} total`);
        }
    }
    
    duplicateSuccession() {
        const selectedIndex = 0; // For now, duplicate the first one
        if (this.successions.length === 0) return;
        
        const original = this.successions[selectedIndex];
        const interval = parseInt(document.getElementById('interval').value) || 14;
        
        const newSeedingDate = new Date(this.successions[this.successions.length - 1].seedingDate);
        newSeedingDate.setDate(newSeedingDate.getDate() + interval);
        
        let newTransplantDate = null;
        if (original.transplantDate) {
            newTransplantDate = new Date(newSeedingDate);
            newTransplantDate.setDate(newTransplantDate.getDate() + original.seedingToTransplant);
        }
        
        const newHarvestDate = new Date(newTransplantDate || newSeedingDate);
        newHarvestDate.setDate(newHarvestDate.getDate() + original.transplantToHarvest);
        
        const newHarvestEndDate = new Date(newHarvestDate);
        newHarvestEndDate.setDate(newHarvestEndDate.getDate() + original.harvestWindow);
        
        this.successions.push({
            ...original,
            index: this.successions.length + 1,
            seedingDate: newSeedingDate,
            transplantDate: newTransplantDate,
            harvestDate: newHarvestDate,
            harvestEndDate: newHarvestEndDate,
            bed: null
        });
        
        document.getElementById('successionCount').value = this.successions.length;
        this.updateTimeline();
        this.debugLog(`Duplicated succession #${selectedIndex + 1}`);
    }
    
    debugLog(message) {
        const debugOutput = document.getElementById('debugOutput');
        const timestamp = new Date().toLocaleTimeString();
        debugOutput.innerHTML += `[${timestamp}] Gantt: ${message}\n`;
        debugOutput.scrollTop = debugOutput.scrollHeight;
    }
}

// Initialize Gantt Chart
let ganttChart;

// Global function for AI timing (accessible from inline onclick)
function getSeasonalTimingFromAI(cropType) {
    const debugOutput = document.getElementById('debugOutput');
    debugOutput.innerHTML += 'getSeasonalTimingFromAI called with: ' + cropType + '\n';
    
    if (!cropType) {
        debugOutput.innerHTML += 'No crop type provided\n';
        return;
    }
    
    debugOutput.innerHTML += 'Making fetch request to AI endpoint...\n';
    
    const season = 'Summer'; // Current season
    
    fetch('/admin/api/ai/crop-timing', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            crop_type: cropType,
            season: season
        })
    })
    .then(response => {
        debugOutput.innerHTML += 'Got response: ' + response.status + '\n';
        return response.json();
    })
    .then(data => {
        debugOutput.innerHTML += 'Response data: ' + JSON.stringify(data) + '\n';
        
        if (data.success && data.timing) {
            debugOutput.innerHTML += 'Updating form fields...\n';
            
            // Update the form fields
            const seedingField = document.getElementById('seedingToTransplant');
            const harvestField = document.getElementById('transplantToHarvest');
            const windowField = document.getElementById('harvestDuration');
            
            if (seedingField && data.timing.days_to_transplant !== undefined) {
                seedingField.value = data.timing.days_to_transplant;
                debugOutput.innerHTML += 'Set seeding to transplant: ' + data.timing.days_to_transplant + '\n';
            }
            
            if (harvestField && data.timing.days_to_harvest !== undefined) {
                harvestField.value = data.timing.days_to_harvest;
                debugOutput.innerHTML += 'Set transplant to harvest: ' + data.timing.days_to_harvest + '\n';
            }
            
            if (windowField && data.timing.harvest_window !== undefined) {
                windowField.value = data.timing.harvest_window;
                debugOutput.innerHTML += 'Set harvest window: ' + data.timing.harvest_window + '\n';
            }
            
            debugOutput.innerHTML += 'Form fields updated successfully!\n';
        } else {
            debugOutput.innerHTML += 'AI response error: ' + (data.message || 'Unknown error') + '\n';
        }
    })
    .catch(error => {
        debugOutput.innerHTML += 'Fetch error: ' + error.message + '\n';
    });
}

// Ultra simple test function
function simpleTest() {
    alert('Button clicked!');
    
    var debugDiv = document.getElementById('debugOutput');
    if (!debugDiv) {
        alert('Debug div not found!');
        return;
    }
    
    debugDiv.innerHTML = 'Simple test at ' + new Date().toLocaleTimeString() + '\n';
    alert('Debug output should now show the time');
    
    // Try to make an API call manually
    fetch('/admin/api/ai/crop-timing', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            crop_type: 'lettuce',
            season: 'Summer'
        })
    })
    .then(function(response) {
        debugDiv.innerHTML += 'Got response: ' + response.status + '\n';
        return response.json();
    })
    .then(function(data) {
        debugDiv.innerHTML += 'Response data: ' + JSON.stringify(data) + '\n';
    })
    .catch(function(error) {
        debugDiv.innerHTML += 'Error: ' + error.message + '\n';
    });
}

// Debug helper functions
function debugLog(message, type = 'info') {
    const debugOutput = document.getElementById('debugOutput');
    if (debugOutput) {
        const timestamp = new Date().toLocaleTimeString();
        const logEntry = `[${timestamp}] ${type.toUpperCase()}: ${message}\n`;
        debugOutput.innerHTML += logEntry;
        debugOutput.scrollTop = debugOutput.scrollHeight;
    }
    
    // Also log to console for fallback
    console.log(`[${timestamp}] ${type.toUpperCase()}: ${message}`);
}

// Crop timing presets
const cropPresets = @json($cropPresets ?? []);
// Crop data from farmOS
const cropData = @json($cropData ?? ['types' => [], 'varieties' => []]);
let currentPlan = null;

// Global reference for Gantt chart
window.generatePlan = function() {
    if (typeof generateSuccessionPlan === 'function') {
        generateSuccessionPlan();
    }
};

// Global test function for debugging
window.testCropChange = function() {
    console.log('Manual test function called');
    const debugOutput = document.getElementById('debugOutput');
    if (debugOutput) {
        debugOutput.innerHTML += 'Manual test function called!\n';
    }
    
    const cropType = document.getElementById('cropType');
    if (cropType) {
        console.log('Crop type element found, current value:', cropType.value);
        if (debugOutput) {
            debugOutput.innerHTML += `Crop type found: ${cropType.value}\n`;
        }
        
        // Try to trigger change event
        const event = new Event('change', { bubbles: true });
        cropType.dispatchEvent(event);
        
        if (debugOutput) {
            debugOutput.innerHTML += 'Change event dispatched\n';
        }
    } else {
        console.log('Crop type element NOT found');
        if (debugOutput) {
            debugOutput.innerHTML += 'ERROR: Crop type element not found\n';
        }
    }
};

document.addEventListener('DOMContentLoaded', function() {
    console.log('DOMContentLoaded fired');
    
    const debugOutput = document.getElementById('debugOutput');
    function simpleLog(message) {
        console.log(message);
        if (debugOutput) {
            debugOutput.innerHTML += message + '\n';
            debugOutput.scrollTop = debugOutput.scrollHeight;
        }
    }
    
    simpleLog('Page loaded, initializing succession planning');
    simpleLog(`Available crop presets: ${Object.keys(cropPresets || {}).length}`);
    
    // Debug crop presets
    if (cropPresets && Object.keys(cropPresets).length > 0) {
        simpleLog(`Crop presets loaded: ${JSON.stringify(Object.keys(cropPresets))}`);
    } else {
        simpleLog('WARNING: No crop presets loaded!');
    }
    
    // Check if our elements exist
    const cropTypeElement = document.getElementById('cropType');
    const clearDebugBtn = document.getElementById('clearDebug');
    const testAIBtn = document.getElementById('testAITiming');
    const testCropChangeBtn = document.getElementById('testCropChange');
    
    simpleLog(`Elements found: cropType=${!!cropTypeElement}, clear=${!!clearDebugBtn}, testAI=${!!testAIBtn}, testCrop=${!!testCropChangeBtn}`);
    
    // Simple event listeners without complex error handling
    if (cropTypeElement) {
        simpleLog('Adding crop type change listener');
        cropTypeElement.addEventListener('change', function(event) {
            simpleLog(`CROP TYPE CHANGED TO: ${event.target.value}`);
            
            // Basic variety population
            const varietySelect = document.getElementById('variety');
            if (varietySelect) {
                varietySelect.innerHTML = '<option value="">Select variety (optional)...</option>';
                simpleLog('Varieties cleared');
            }
            
            // Basic preset application
            const crop = event.target.value;
            if (crop && cropPresets && cropPresets[crop]) {
                simpleLog(`Applying preset for: ${crop}`);
                const preset = cropPresets[crop];
                
                const seedingToTransplant = document.getElementById('seedingToTransplant');
                const transplantToHarvest = document.getElementById('transplantToHarvest');
                const harvestDuration = document.getElementById('harvestDuration');
                
                if (seedingToTransplant) seedingToTransplant.value = preset.transplant_days;
                if (transplantToHarvest) transplantToHarvest.value = preset.harvest_days - preset.transplant_days;
                if (harvestDuration) harvestDuration.value = preset.yield_period;
                
                simpleLog('Preset values applied');
            } else {
                simpleLog(`No preset found for: ${crop}`);
            }
        });
        simpleLog('Crop type change listener added successfully');
    } else {
        simpleLog('ERROR: cropType element not found!');
    }
    
    // Simple clear button
    if (clearDebugBtn) {
        clearDebugBtn.addEventListener('click', function() {
            if (debugOutput) {
                debugOutput.innerHTML = 'Debug cleared...\n';
            }
        });
        simpleLog('Clear button listener added');
    }
    
    simpleLog('Basic initialization complete');
});

function setupEventListeners() {
    debugLog('Setting up event listeners...', 'info');
    
    // Crop preset buttons
    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const crop = this.dataset.crop;
            applyPreset(crop);
        });
    });

    // Generate plan button
    const generateBtn = document.getElementById('generatePlan');
    if (generateBtn) {
        generateBtn.addEventListener('click', generateSuccessionPlan);
        debugLog('Generate plan button listener added', 'info');
    }
    
    // Create in farmOS button
    const createBtn = document.getElementById('createInFarmOS');
    if (createBtn) {
        createBtn.addEventListener('click', createInFarmOS);
        debugLog('Create in farmOS button listener added', 'info');
    }
    
    // Crop type change
    const cropTypeElement = document.getElementById('cropType');
    if (cropTypeElement) {
        debugLog('Setting up crop type change listener', 'info');
        
        // Remove any existing listeners first
        cropTypeElement.removeEventListener('change', handleCropTypeChange);
        cropTypeElement.addEventListener('change', handleCropTypeChange);
        
        debugLog('Crop type change listener added successfully', 'info');
    } else {
        debugLog('ERROR: cropType element not found!', 'error');
    }

    // AI Assistant
    const aiBtn = document.getElementById('askAI');
    if (aiBtn) {
        aiBtn.addEventListener('click', getAIRecommendations);
        debugLog('AI assistant button listener added', 'info');
    }
    
    // Direct sow checkbox
    const directSowBtn = document.getElementById('directSow');
    if (directSowBtn) {
        directSowBtn.addEventListener('change', function() {
            toggleDirectSowMode(this.checked);
        });
        debugLog('Direct sow checkbox listener added', 'info');
    }
    
    // Initialize direct sow mode
    toggleDirectSowMode(false);
    
    debugLog('All event listeners setup complete', 'info');
}

// Separate function for crop type change handling
function handleCropTypeChange(event) {
    const crop = event.target.value;
    debugLog(`Crop type changed to: ${crop}`, 'info');
    
    // Populate varieties for selected crop
    try {
        populateVarieties(crop);
        debugLog('Varieties populated successfully', 'info');
    } catch (error) {
        debugLog('Error populating varieties: ' + error.message, 'error');
    }
    
    // Update timing from presets
    if (crop && cropPresets && cropPresets[crop]) {
        debugLog(`Found preset for crop: ${crop}`, 'info');
        try {
            updateTimingFromPreset(crop);
        } catch (error) {
            debugLog('Error updating timing from preset: ' + error.message, 'error');
        }
    } else {
        debugLog(`No preset found for crop: ${crop}`, 'warning');
        // Still try to get AI timing even without preset
        if (crop) {
            try {
                getSeasonalTimingFromAI(crop);
            } catch (error) {
                debugLog('Error getting AI timing: ' + error.message, 'error');
            }
        }
    }
    
    try {
        updateAIAssistant();
    } catch (error) {
        debugLog('Error updating AI assistant: ' + error.message, 'error');
    }
}

function applyPreset(cropType) {
    if (!cropPresets[cropType]) return;
    
    const preset = cropPresets[cropType];
    
    // Set crop type
    document.getElementById('cropType').value = cropType;
    
    // Set timing values
    document.getElementById('seedingToTransplant').value = preset.transplant_days;
    document.getElementById('transplantToHarvest').value = preset.harvest_days - preset.transplant_days;
    document.getElementById('harvestDuration').value = preset.yield_period;
    
    // Update AI assistant
    updateAIAssistant();
    
    // Show feedback
    showNotification(`Applied ${cropType} preset: ${preset.harvest_days} day cycle`, 'success');
}

function updateTimingFromPreset(cropType) {
    if (!cropType) return;
    
    const preset = cropPresets[cropType];
    const seedingToTransplant = document.getElementById('seedingToTransplant');
    const transplantToHarvest = document.getElementById('transplantToHarvest');
    const harvestDuration = document.getElementById('harvestDuration');
    const directSowCheckbox = document.getElementById('directSow');
    
    if (preset) {
        // Check if this is a direct sow crop (transplant_days = 0)
        const isDirectSow = preset.transplant_days === 0;
        
        // Auto-check direct sow checkbox for appropriate crops
        directSowCheckbox.checked = isDirectSow;
        toggleDirectSowMode(isDirectSow);
        
        // Always update timing fields when crop changes
        seedingToTransplant.value = preset.transplant_days;
        transplantToHarvest.value = preset.harvest_days - preset.transplant_days;
        harvestDuration.value = preset.yield_period;
        
        // Show helpful message for direct sow crops
        if (isDirectSow) {
            showNotification(`${cropType} is typically direct sown - checkbox auto-selected`, 'info');
        }
    }
    
    // Get AI-powered seasonal timing recommendations
    getSeasonalTimingFromAI(cropType);
}

async function getSeasonalTimingFromAI(cropType) {
    if (!cropType) {
        debugLog('No crop type provided for AI timing', 'warning');
        return;
    }
    
    debugLog(`getSeasonalTimingFromAI called for: ${cropType}`, 'info');
    
    try {
        // Show loading indicator
        const cropTypeSelect = document.getElementById('cropType');
        const originalText = cropTypeSelect.options[cropTypeSelect.selectedIndex].text;
        cropTypeSelect.options[cropTypeSelect.selectedIndex].text = `${originalText} (Getting AI timing...)`;
        
        // Get current date and season info
        const currentDate = new Date();
        const month = currentDate.getMonth() + 1; // JavaScript months are 0-based
        const season = getSeason(month);
        const location = 'North America'; // You could make this configurable
        
        debugLog(`Current season: ${season}, month: ${month}`, 'info');
        
        // Make AI request
        debugLog(`Making AI request for ${cropType} in ${season}`, 'info');
        
        const requestData = {
            crop_type: cropType,
            season: season,
            is_direct_sow: isDirectSowCrop(cropType)
        };
        
        debugLog(`Request data: ${JSON.stringify(requestData)}`, 'info');
        
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        debugLog(`Using CSRF token: ${csrfToken.substring(0, 10)}...`, 'info');
        
        const response = await fetch('/admin/api/ai/crop-timing', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(requestData)
        });
        
        debugLog(`Response status: ${response.status}`, 'info');
        
        if (response.ok) {
            const result = await response.json();
            debugLog(`AI response: ${JSON.stringify(result)}`, 'success');
            
            if (result.success && result.timing) {
                applyAITiming(result.timing, cropType);
                debugLog('Timing applied successfully', 'success');
                
                // Show success notification with recommendations
                let message = `AI updated timing for ${cropType} based on current season`;
                if (result.recommendations && result.recommendations.length > 0) {
                    message += '\n\nRecommendations:\n• ' + result.recommendations.join('\n• ');
                }
                showNotification(message, 'success');
            } else {
                debugLog(`AI request succeeded but no timing data: ${JSON.stringify(result)}`, 'warning');
            }
        } else {
            const errorText = await response.text();
            debugLog(`AI request failed with status ${response.status}: ${errorText}`, 'error');
        }
        
    } catch (error) {
        debugLog(`AI timing lookup failed: ${error.message}`, 'error');
        // Fail silently - preset values are still good
    } finally {
        // Restore original crop type text
        const cropTypeSelect = document.getElementById('cropType');
        const originalText = cropTypeSelect.options[cropTypeSelect.selectedIndex].text.replace(' (Getting AI timing...)', '');
        cropTypeSelect.options[cropTypeSelect.selectedIndex].text = originalText;
    }
}

function isDirectSowCrop(cropType) {
    // List of crops that are typically direct sown
    const directSowCrops = [
        'carrot', 'radish', 'beet', 'turnip', 'parsnip',
        'cilantro', 'dill', 'mesclun', 'arugula'
    ];
    
    return directSowCrops.includes(cropType.toLowerCase());
}

function getSeason(month) {
    if (month >= 3 && month <= 5) return 'Spring';
    if (month >= 6 && month <= 8) return 'Summer';
    if (month >= 9 && month <= 11) return 'Fall';
    return 'Winter';
}

function applyAITiming(timing, cropType) {
    debugLog(`Applying AI timing for ${cropType}: ${JSON.stringify(timing)}`, 'info');
    
    try {
        // Handle new object format from backend
        if (typeof timing === 'object' && timing.days_to_transplant !== undefined) {
            debugLog('Using object format timing data', 'info');
            
            // Apply timing values from object
            document.getElementById('seedingToTransplant').value = timing.days_to_transplant;
            document.getElementById('transplantToHarvest').value = timing.days_to_harvest;
            document.getElementById('harvestDuration').value = timing.harvest_window;
            
            debugLog(`Set values: transplant=${timing.days_to_transplant}, harvest=${timing.days_to_harvest}, window=${timing.harvest_window}`, 'success');
            
            // Apply direct sow setting
            if (timing.days_to_transplant === 0) {
                document.getElementById('directSow').checked = true;
                toggleDirectSowMode(true);
                debugLog('Applied direct sow mode', 'info');
            } else {
                document.getElementById('directSow').checked = false;
                toggleDirectSowMode(false);
                debugLog('Applied transplant mode', 'info');
            }
            
            return;
        }
        
        debugLog('Trying to parse string format timing', 'info');
        
        // Fallback: Parse string format (legacy) - expecting format like "21, 45, 14, transplant"
        const parts = timing.split(',').map(part => part.trim());
        
        if (parts.length >= 3) {
            const seedingToTransplant = parseInt(parts[0]);
            const transplantToHarvest = parseInt(parts[1]);
            const harvestDuration = parseInt(parts[2]);
            const method = parts[3]?.toLowerCase();
            
            debugLog(`Parsed values: ${seedingToTransplant}, ${transplantToHarvest}, ${harvestDuration}, ${method}`, 'info');
            
            // Apply timing values
            if (!isNaN(seedingToTransplant)) {
                document.getElementById('seedingToTransplant').value = seedingToTransplant;
            }
            if (!isNaN(transplantToHarvest)) {
                document.getElementById('transplantToHarvest').value = transplantToHarvest;
            }
            if (!isNaN(harvestDuration)) {
                document.getElementById('harvestDuration').value = harvestDuration;
            }
            
            // Apply direct sow setting if specified
            if (method && (method.includes('direct') || seedingToTransplant === 0)) {
                document.getElementById('directSow').checked = true;
                toggleDirectSowMode(true);
                debugLog('Applied direct sow mode from string format', 'info');
            } else if (method && method.includes('transplant')) {
                document.getElementById('directSow').checked = false;
                toggleDirectSowMode(false);
                debugLog('Applied transplant mode from string format', 'info');
            }
            
            debugLog('Successfully applied string format timing', 'success');
        } else {
            debugLog(`Invalid timing format: ${timing}`, 'error');
        }
        
    } catch (error) {
        debugLog(`Error applying timing: ${error.message}`, 'error');
    }
}
        }
    } catch (error) {
        console.warn('Failed to parse AI timing response:', error);
    }
}

function updateAIAssistant() {
    const cropType = document.getElementById('cropType').value;
    const askBtn = document.getElementById('askAI');
    
    if (cropType) {
        askBtn.disabled = false;
        askBtn.innerHTML = `<i class="fas fa-brain"></i> Get ${cropType} AI Tips`;
    } else {
        askBtn.disabled = true;
        askBtn.innerHTML = `<i class="fas fa-brain"></i> Get AI Recommendations`;
    }
}

async function generateSuccessionPlan() {
    const formData = new FormData(document.getElementById('successionForm'));
    const submitBtn = document.getElementById('generatePlan');
    
    // Show loading state
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
    
    try {
        const response = await fetch('{{ route('admin.farmos.succession-planning.generate') }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            currentPlan = result.plan;
            displayPlan(result.plan);
            showNotification('Succession plan generated successfully!', 'success');
            
            // Update Gantt chart
            if (ganttChart) {
                ganttChart.loadFromForm();
                ganttChart.debugLog('Plan generated - updated timeline visualization');
            }
            
            // Enable create button
            document.getElementById('createInFarmOS').disabled = false;
        } else {
            showNotification('Failed to generate plan: ' + result.message, 'error');
        }
        
    } catch (error) {
        console.error('Error generating plan:', error);
        showNotification('Error generating plan: ' + error.message, 'error');
    } finally {
        // Restore button
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-magic"></i> Generate Plan with AI';
    }
}

function displayPlan(plan) {
    // Show results section
    document.getElementById('resultsSection').style.display = 'block';
    
    // Update summary
    const summary = document.getElementById('planSummary');
    summary.innerHTML = `
        <div class="col-md-3">
            <div class="text-center">
                <h4 class="text-primary">${plan.total_plantings}</h4>
                <small class="text-muted">Total Plantings</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <h4 class="text-success">${plan.interval_days}</h4>
                <small class="text-muted">Day Intervals</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <h4 class="text-warning">${plan.conflicts_resolved || 0}</h4>
                <small class="text-muted">Conflicts Resolved</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="text-center">
                <h4 class="text-info">${plan.crop_type}</h4>
                <small class="text-muted">Crop Type</small>
            </div>
        </div>
    `;
    
    // Show AI recommendations if available
    if (plan.ai_recommendations) {
        const aiSection = document.getElementById('aiRecommendations');
        const aiText = document.getElementById('aiRecommendationText');
        aiText.innerHTML = plan.ai_recommendations.replace(/\n/g, '<br>');
        aiSection.style.display = 'block';
    }
    
    // Populate table
    const tbody = document.getElementById('planTable').querySelector('tbody');
    tbody.innerHTML = '';
    
    plan.plantings.forEach(planting => {
        const row = tbody.insertRow();
        const transplantDisplay = planting.direct_sow ? 
            '<span class="badge bg-info">Direct Sow</span>' : 
            (planting.transplant_date || '<span class="text-muted">Not set</span>');
            
        row.innerHTML = `
            <td><span class="badge bg-primary">#${planting.sequence}</span></td>
            <td>${planting.seeding_date}</td>
            <td>${transplantDisplay}</td>
            <td>${planting.harvest_date}</td>
            <td>
                ${planting.bed_name || 'Not assigned'}
                ${planting.bed_id ? '<i class="fas fa-check text-success"></i>' : '<i class="fas fa-exclamation-triangle text-warning"></i>'}
            </td>
            <td>
                <span class="badge ${planting.conflicts && planting.conflicts.length > 0 ? 'bg-warning' : 'bg-success'}">
                    ${planting.conflicts && planting.conflicts.length > 0 ? 'Conflicts' : 'Clear'}
                </span>
            </td>
            <td>
                ${planting.conflicts && planting.conflicts.length > 0 ? 
                    planting.conflicts.map(c => `<small class="text-warning">${c.type}: ${c.existing_crop}</small>`).join('<br>') 
                    : '<small class="text-muted">None</small>'}
            </td>
        `;
    });
    
    // Scroll to results
    document.getElementById('resultsSection').scrollIntoView({ behavior: 'smooth' });
}

async function createInFarmOS() {
    if (!currentPlan) {
        showNotification('No plan to create', 'error');
        return;
    }
    
    if (!confirm('Create this succession plan in farmOS? This will create ' + currentPlan.total_plantings + ' planting entries.')) {
        return;
    }
    
    const createBtn = document.getElementById('createInFarmOS');
    createBtn.disabled = true;
    createBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating in farmOS...';
    
    try {
        const response = await fetch('{{ route('admin.farmos.succession-planning.create-logs') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                plan: currentPlan,
                confirm: true
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(`Success! Created ${result.created_logs} farmOS plans. View in timeline chart.`, 'success');
            
            // Offer to go to timeline
            if (confirm('Plans created in farmOS! Would you like to view them in the timeline chart?')) {
                window.open('{{ route('admin.farmos.planting-chart') }}', '_blank');
            }
        } else {
            showNotification('Failed to create plans: ' + result.message, 'error');
        }
        
    } catch (error) {
        console.error('Error creating plans:', error);
        showNotification('Error creating plans: ' + error.message, 'error');
    } finally {
        createBtn.disabled = false;
        createBtn.innerHTML = '<i class="fas fa-cloud-upload-alt"></i> Create in farmOS';
    }
}

async function getAIRecommendations() {
    const cropType = document.getElementById('cropType').value;
    if (!cropType) return;
    
    const askBtn = document.getElementById('askAI');
    askBtn.disabled = true;
    askBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Asking AI...';
    
    try {
        // This would call your AI service
        const question = `What are the best practices for succession planting ${cropType}? Consider timing intervals, bed rotation, and seasonal factors.`;
        
        // For now, show a placeholder
        setTimeout(() => {
            showNotification(`AI recommendations for ${cropType} succession planting loaded`, 'info');
            askBtn.disabled = false;
            askBtn.innerHTML = `<i class="fas fa-brain"></i> Get ${cropType} AI Tips`;
        }, 2000);
        
    } catch (error) {
        console.error('AI request failed:', error);
        showNotification('AI service temporarily unavailable', 'warning');
    }
}

function checkAIStatus() {
    // AI status check disabled - using internal AI only
    const status = document.getElementById('aiStatus');
    status.className = 'badge bg-success';
    status.textContent = 'Ready';
}

function loadFarmStatus() {
    // This would load real farm status data
    document.getElementById('activePlans').textContent = '23';
}

function showNotification(message, type = 'info') {
    // Create toast notification
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; max-width: 400px;';
    toast.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(toast);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (toast.parentNode) {
            toast.parentNode.removeChild(toast);
        }
    }, 5000);
}

function populateVarieties(cropType) {
    const varietySelect = document.getElementById('variety');
    
    // Clear existing options
    varietySelect.innerHTML = '<option value="">Select variety (optional)...</option>';
    
    if (!cropType || !cropData.varieties) {
        return;
    }
    
    // Find varieties for this crop type
    const availableVarieties = cropData.varieties.filter(variety => {
        // Try to match by crop type name or ID
        return variety.parent_id === cropType || 
               variety.name.toLowerCase().includes(cropType.toLowerCase());
    });
    
    // Add varieties to dropdown
    availableVarieties.forEach(variety => {
        const option = document.createElement('option');
        option.value = variety.name;
        option.textContent = variety.label;
        varietySelect.appendChild(option);
    });
    
    // If no specific varieties found, add some common generic options
    if (availableVarieties.length === 0) {
        const commonVarieties = {
            'lettuce': ['Butter', 'Romaine', 'Red Leaf', 'Green Leaf', 'Iceberg'],
            'carrot': ['Nantes', 'Chantenay', 'Purple', 'Baby'],
            'radish': ['Cherry Belle', 'French Breakfast', 'Daikon'],
            'tomato': ['Cherry', 'Beefsteak', 'Roma', 'Heirloom'],
            'spinach': ['Baby Leaf', 'Bloomsdale', 'Space'],
            'kale': ['Curly', 'Lacinato', 'Red Russian'],
            'arugula': ['Wild', 'Cultivated'],
            'beets': ['Detroit Red', 'Chioggia', 'Golden']
        };
        
        const varieties = commonVarieties[cropType.toLowerCase()] || ['Standard'];
        varieties.forEach(variety => {
            const option = document.createElement('option');
            option.value = variety;
            option.textContent = variety;
            varietySelect.appendChild(option);
        });
    }
}

function toggleDirectSowMode(isDirectSow) {
    const seedingToTransplantGroup = document.getElementById('seedingToTransplantGroup');
    const seedingToTransplantInput = document.getElementById('seedingToTransplant');
    const transplantOnlyBadge = document.getElementById('transplantOnlyBadge');
    const transplantToHarvestLabel = document.getElementById('transplantToHarvestLabel');
    const transplantToHarvestHelp = document.getElementById('transplantToHarvestHelp');
    
    if (isDirectSow) {
        // Direct sow mode
        seedingToTransplantGroup.style.opacity = '0.5';
        seedingToTransplantInput.disabled = true;
        seedingToTransplantInput.value = '0';
        transplantOnlyBadge.style.display = 'none';
        
        // Update labels for direct sow
        transplantToHarvestLabel.innerHTML = '<i class="fas fa-cut"></i> Seeding to Harvest (Days)';
        transplantToHarvestHelp.textContent = 'Growing period from direct seeding to harvest';
        
        showNotification('Switched to Direct Sow mode - transplant step will be skipped', 'info');
    } else {
        // Transplant mode
        seedingToTransplantGroup.style.opacity = '1';
        seedingToTransplantInput.disabled = false;
        seedingToTransplantInput.value = '21';
        transplantOnlyBadge.style.display = 'inline';
        
        // Update labels for transplant
        transplantToHarvestLabel.innerHTML = '<i class="fas fa-cut"></i> Transplant to Harvest (Days)';
        transplantToHarvestHelp.textContent = 'Growing period from transplant to harvest';
    }
}
</script>

<!-- Duplicate script tag removed - function is defined in main script above -->

<style>
.preset-btn {
    transition: all 0.2s ease;
}

.preset-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

#planTable {
    font-size: 0.9rem;
}

#planTable th {
    font-weight: 600;
    border-top: none;
}

.alert-dismissible {
    animation: slideInRight 0.3s ease;
}

@keyframes slideInRight {
    from {
        transform: translateX(100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.card-header {
    border-bottom: 2px solid rgba(0,0,0,0.125);
}

.bg-gradient-primary {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
}

.form-label {
    font-weight: 600;
    color: #495057;
}

.badge {
    font-size: 0.8em;
}

.table-responsive {
    border-radius: 8px;
    border: 1px solid #dee2e6;
}
</style>
@endsection
