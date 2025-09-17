@extends('layouts.app')

@section('title', 'Quick Transplant Form')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-hand-paper text-warning"></i>
                        Quick Transplant Form
                        @if(request('succession_number'))
                            <small class="text-muted">| Succession {{ request('succession_number') }}</small>
                        @endif
                    </h4>
                </div>
                <div class="card-body">
                    <form id="transplantForm" method="POST" action="{{ route('admin.farmos.succession-planning.submit-log') }}">
                        @csrf
                        <input type="hidden" name="log_type" value="transplanting">

                        <!-- Succession Info -->
                        @if(request('succession_number'))
                        <div class="alert alert-warning">
                            <i class="fas fa-info-circle"></i>
                            <strong>Succession {{ request('succession_number') }}</strong>
                            @if(request('crop_name'))
                                - {{ request('crop_name') }}
                                @if(request('variety_name') && request('variety_name') !== 'Generic')
                                    @if(request('variety_name') && request('variety_name') !== 'Generic')
                                ({{ request('variety_name') }})
                            @endif
                                @endif
                            @endif
                        </div>
                        @endif

                        <div class="row">
                            <!-- Basic Information -->
                            <div class="col-md-6">
                                <h5 class="mb-3">Basic Information</h5>

                                <div class="mb-3">
                                    <label for="name" class="form-label">Log Name *</label>
                                    <input type="text" class="form-control" id="name" name="name"
                                           value="Transplant {{ request('crop_name', '') }} {{ request('succession_number', '') ? 'Succession ' . request('succession_number') : '' }}"
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label for="date" class="form-label">Transplant Date *</label>
                                    <input type="date" class="form-control" id="date" name="date"
                                           value="{{ request('transplant_date', date('Y-m-d')) }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="crop" class="form-label">Crop *</label>
                                    <input type="text" class="form-control" id="crop" name="crop"
                                           value="{{ request('crop_name', '') }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="variety" class="form-label">Variety</label>
                                    <input type="text" class="form-control" id="variety" name="variety"
                                           value="{{ request('variety_name', '') }}">
                                </div>
                            </div>

                            <!-- Location & Quantity -->
                            <div class="col-md-6">
                                <h5 class="mb-3">Location & Quantity</h5>

                                <div class="mb-3">
                                    <label for="source_location" class="form-label">From Location (Source)</label>
                                    <input type="text" class="form-control" id="source_location" name="source_location"
                                           placeholder="e.g., Seed Tray 1, Nursery Bed A" value="{{ request('source_location', '') }}">
                                </div>

                                <div class="mb-3">
                                    <label for="destination_location" class="form-label">To Location (Destination) *</label>
                                    <input type="text" class="form-control" id="destination_location" name="destination_location"
                                           value="{{ request('bed_name', '') }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Number of Plants *</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="quantity" name="quantity"
                                               value="{{ request('quantity', 100) }}" min="1" required>
                                        <span class="input-group-text">plants</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="spacing" class="form-label">Plant Spacing</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="spacing" name="spacing" step="0.1" min="0">
                                        <select class="form-select" id="spacing_unit" name="spacing_unit">
                                            <option value="inches">Inches</option>
                                            <option value="cm">Centimeters</option>
                                        </select>
                                        <span class="input-group-text">between plants</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Transplant Details -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Transplant Details</h5>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="plant_age" class="form-label">Plant Age</label>
                                            <div class="input-group">
                                                <input type="number" class="form-control" id="plant_age" name="plant_age" min="0">
                                                <select class="form-select" id="plant_age_unit" name="plant_age_unit">
                                                    <option value="days">Days</option>
                                                    <option value="weeks">Weeks</option>
                                                </select>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label for="soil_condition" class="form-label">Soil Condition</label>
                                            <select class="form-select" id="soil_condition" name="soil_condition">
                                                <option value="">Select condition...</option>
                                                <option value="moist">Moist</option>
                                                <option value="dry">Dry</option>
                                                <option value="wet">Wet</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="weather" class="form-label">Weather Conditions</label>
                                            <input type="text" class="form-control" id="weather" name="weather"
                                                   placeholder="e.g., Sunny, 72°F, Light breeze" value="{{ request('weather', '') }}">
                                        </div>

                                        <div class="mb-3">
                                            <label for="watering_after" class="form-label">Watering After Transplant</label>
                                            <select class="form-select" id="watering_after" name="watering_after">
                                                <option value="">Select...</option>
                                                <option value="light">Light watering</option>
                                                <option value="moderate">Moderate watering</option>
                                                <option value="heavy">Heavy watering</option>
                                                <option value="none">No watering</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="transplant_method" class="form-label">Transplant Method</label>
                                    <select class="form-select" id="transplant_method" name="transplant_method">
                                        <option value="hand">Hand transplant</option>
                                        <option value="mechanical">Mechanical transplanter</option>
                                        <option value="dibber">Dibber tool</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"
                                              placeholder="Any additional notes about this transplant...">{{ request('notes', '') }}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary" onclick="window.history.back()">
                                        <i class="fas fa-arrow-left"></i> Back
                                    </button>
                                    <div>
                                        <button type="submit" class="btn btn-warning">
                                            <i class="fas fa-save"></i> Save Transplant Log
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-populate form with URL parameters
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);

    // Auto-populate fields from URL parameters
    const fields = ['crop', 'variety', 'destination_location', 'source_location', 'quantity', 'spacing', 'weather', 'notes'];
    fields.forEach(field => {
        const value = urlParams.get(field);
        if (value && document.getElementById(field)) {
            document.getElementById(field).value = value;
        }
    });

    // Handle spacing_unit
    const spacingUnit = urlParams.get('spacing_unit');
    if (spacingUnit && document.getElementById('spacing_unit')) {
        document.getElementById('spacing_unit').value = spacingUnit;
    }
});

// Form validation
document.getElementById('transplantForm').addEventListener('submit', function(e) {
    const requiredFields = ['name', 'date', 'crop', 'destination_location', 'quantity'];
    let isValid = true;

    requiredFields.forEach(field => {
        const element = document.getElementById(field);
        if (!element.value.trim()) {
            element.classList.add('is-invalid');
            isValid = false;
        } else {
            element.classList.remove('is-invalid');
        }
    });

    if (!isValid) {
        e.preventDefault();
        alert('Please fill in all required fields.');
    }
});
</script>

<style>
.card-header {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
    color: white;
}

.card-header .fas {
    margin-right: 8px;
}

.alert-warning {
    border-left: 4px solid #ffc107;
}

.form-label {
    font-weight: 600;
    color: #495057;
}

.btn-warning {
    background: linear-gradient(135deg, #ffc107, #fd7e14);
    border: none;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #e0a800, #dc6a00);
}

.is-invalid {
    border-color: #dc3545 !important;
}
</style>
@endsection