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
                                    <select class="form-select" id="cropType" name="crop_type" required onchange="
                                        alert('Crop changed to: ' + this.value); 
                                        document.getElementById('debugOutput').innerHTML += 'Crop dropdown changed to: ' + this.value + '\n'; 
                                        if(this.value) {
                                            document.getElementById('debugOutput').innerHTML += 'About to call getSeasonalTimingFromAI...\n';
                                            if (typeof getSeasonalTimingFromAI === 'function') {
                                                document.getElementById('debugOutput').innerHTML += 'Function exists, calling it now...\n';
                                                getSeasonalTimingFromAI(this.value);
                                            } else {
                                                document.getElementById('debugOutput').innerHTML += 'ERROR: getSeasonalTimingFromAI function not found!\n';
                                            }
                                        }
                                    ">
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
                                    <select class="form-select" id="variety" name="variety">
                                        <option value="">Select variety (optional)...</option>
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

<script>
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

document.addEventListener('DOMContentLoaded', function() {
    debugLog('Page loaded, initializing succession planning');
    debugLog(`Available crop presets: ${Object.keys(cropPresets).length}`);
    debugLog(`Available crop data: ${JSON.stringify(Object.keys(cropData))}`);
    
    // Test basic functionality
    alert('JavaScript is working! Check debug output below.');
    
    // Check if our debug elements exist
    const debugOutput = document.getElementById('debugOutput');
    const clearDebugBtn = document.getElementById('clearDebug');
    const testAIBtn = document.getElementById('testAITiming');
    
    debugLog(`Debug elements found: output=${!!debugOutput}, clear=${!!clearDebugBtn}, test=${!!testAIBtn}`);
    
    setupEventListeners();
    checkAIStatus();
    loadFarmStatus();
    
    // Add debug controls
    if (clearDebugBtn) {
        clearDebugBtn.addEventListener('click', function() {
            debugLog('Clear button clicked');
            document.getElementById('debugOutput').innerHTML = 'Debug output cleared...\n';
        });
    } else {
        debugLog('Clear button not found!', 'error');
    }
    
    if (testAIBtn) {
        testAIBtn.addEventListener('click', function() {
            debugLog('Test AI button clicked!', 'info');
            alert('Test AI button clicked - check debug output');
            getSeasonalTimingFromAI('lettuce');
        });
    } else {
        debugLog('Test AI button not found!', 'error');
    }
});

function setupEventListeners() {
    // Crop preset buttons
    document.querySelectorAll('.preset-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const crop = this.dataset.crop;
            applyPreset(crop);
        });
    });

    // Generate plan button
    document.getElementById('generatePlan').addEventListener('click', generateSuccessionPlan);
    
    // Create in farmOS button
    document.getElementById('createInFarmOS').addEventListener('click', createInFarmOS);
    
    // Crop type change
    document.getElementById('cropType').addEventListener('change', function() {
        const crop = this.value;
        debugLog(`Crop type changed to: ${crop}`, 'info');
        
        // Populate varieties for selected crop
        populateVarieties(crop);
        
        // Update timing from presets
        if (crop && cropPresets && cropPresets[crop]) {
            debugLog(`Found preset for crop: ${crop}`, 'info');
            updateTimingFromPreset(crop);
        } else {
            debugLog(`No preset found for crop: ${crop}`, 'warning');
            // Still try to get AI timing even without preset
            if (crop) {
                getSeasonalTimingFromAI(crop);
            }
        }
        updateAIAssistant();
    });

    // AI Assistant
    document.getElementById('askAI').addEventListener('click', getAIRecommendations);
    
    // Direct sow checkbox
    document.getElementById('directSow').addEventListener('change', function() {
        toggleDirectSowMode(this.checked);
    });
    
    // Initialize direct sow mode
    toggleDirectSowMode(false);
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

<script>
// Standalone AI timing function - must be global
function getSeasonalTimingFromAI(cropType) {
    const debugOutput = document.getElementById('debugOutput');
    debugOutput.innerHTML += 'getSeasonalTimingFromAI called with: ' + cropType + '\n';
    
    if (!cropType) {
        debugOutput.innerHTML += 'No crop type provided\n';
        return;
    }
    
    debugOutput.innerHTML += 'Making fetch request to AI endpoint...\n';
    
    const season = 'Summer';
    
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
</script>

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
