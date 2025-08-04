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
                                        @foreach($cropTypes ?? ['lettuce', 'carrot', 'radish', 'spinach', 'kale', 'arugula', 'chard', 'beets'] as $crop)
                                            <option value="{{ $crop }}">{{ ucfirst($crop) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="variety" class="form-label">
                                        <i class="fas fa-dna"></i> Variety
                                    </label>
                                    <input type="text" class="form-control" id="variety" name="variety" 
                                           placeholder="e.g., Butter Lettuce, Nantes Carrot">
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
                                <div class="mb-3">
                                    <label for="seedingToTransplant" class="form-label">
                                        <i class="fas fa-seedling"></i> Seeding to Transplant (Days)
                                    </label>
                                    <input type="number" class="form-control" id="seedingToTransplant" 
                                           name="seeding_to_transplant_days" min="0" max="180" value="21">
                                    <div class="form-text">0 for direct seeding</div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="transplantToHarvest" class="form-label">
                                        <i class="fas fa-cut"></i> Transplant to Harvest (Days)
                                    </label>
                                    <input type="number" class="form-control" id="transplantToHarvest" 
                                           name="transplant_to_harvest_days" min="1" max="365" value="44" required>
                                    <div class="form-text">Growing period length</div>
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
// Crop timing presets
const cropPresets = @json($cropPresets ?? []);
let currentPlan = null;

document.addEventListener('DOMContentLoaded', function() {
    setupEventListeners();
    checkAIStatus();
    loadFarmStatus();
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
        if (crop && cropPresets[crop]) {
            updateTimingFromPreset(crop);
        }
        updateAIAssistant();
    });

    // AI Assistant
    document.getElementById('askAI').addEventListener('click', getAIRecommendations);
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
    if (!cropPresets[cropType]) return;
    
    const preset = cropPresets[cropType];
    const seedingToTransplant = document.getElementById('seedingToTransplant');
    const transplantToHarvest = document.getElementById('transplantToHarvest');
    const harvestDuration = document.getElementById('harvestDuration');
    
    // Only update if field is empty or has default value
    if (!seedingToTransplant.value || seedingToTransplant.value === '21') {
        seedingToTransplant.value = preset.transplant_days;
    }
    if (!transplantToHarvest.value || transplantToHarvest.value === '44') {
        transplantToHarvest.value = preset.harvest_days - preset.transplant_days;
    }
    if (!harvestDuration.value || harvestDuration.value === '14') {
        harvestDuration.value = preset.yield_period;
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
        row.innerHTML = `
            <td><span class="badge bg-primary">#${planting.sequence}</span></td>
            <td>${planting.seeding_date}</td>
            <td>${planting.transplant_date || 'Direct seed'}</td>
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
    // Check if AI service is running
    fetch('{{ env('AI_SERVICE_URL', 'http://localhost:8001') }}/health')
        .then(response => response.ok)
        .then(isHealthy => {
            const status = document.getElementById('aiStatus');
            if (isHealthy) {
                status.className = 'badge bg-success';
                status.textContent = 'Connected';
            } else {
                status.className = 'badge bg-warning';
                status.textContent = 'Limited';
            }
        })
        .catch(() => {
            const status = document.getElementById('aiStatus');
            status.className = 'badge bg-danger';
            status.textContent = 'Offline';
        });
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
