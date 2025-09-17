@extends('layouts.app')

@section('title', 'Quick Harvest Form')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-tractor text-danger"></i>
                        Quick Harvest Form
                        @if(request('succession_number'))
                            <small class="text-muted">| Succession {{ request('succession_number') }}</small>
                        @endif
                    </h4>
                </div>
                <div class="card-body">
                    <form id="harvestForm" method="POST" action="{{ route('admin.farmos.logs.store') }}">
                        @csrf
                        <input type="hidden" name="log_type" value="harvest">

                        <!-- Succession Info -->
                        @if(request('succession_number'))
                        <div class="alert alert-danger">
                            <i class="fas fa-info-circle"></i>
                            <strong>Succession {{ request('succession_number') }}</strong>
                            @if(request('crop_name'))
                                - {{ request('crop_name') }}
                                @if(request('variety_name') && request('variety_name') !== 'Generic')
                                    ({{ request('variety_name') }})
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
                                           value="Harvest {{ request('crop_name', '') }} {{ request('succession_number', '') ? 'Succession ' . request('succession_number') : '' }}"
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label for="date" class="form-label">Harvest Date *</label>
                                    <input type="date" class="form-control" id="date" name="date"
                                           value="{{ request('harvest_date', date('Y-m-d')) }}" required>
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

                            <!-- Harvest Details -->
                            <div class="col-md-6">
                                <h5 class="mb-3">Harvest Details</h5>

                                <div class="mb-3">
                                    <label for="location" class="form-label">Location *</label>
                                    <input type="text" class="form-control" id="location" name="location"
                                           value="{{ request('bed_name', '') }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Harvest Quantity *</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="quantity" name="quantity"
                                               value="{{ request('quantity', 100) }}" step="0.01" min="0" required>
                                        <select class="form-select" id="quantity_unit" name="quantity_unit">
                                            <option value="lbs" {{ request('quantity_unit', 'lbs') == 'lbs' ? 'selected' : '' }}>Pounds</option>
                                            <option value="kg" {{ request('quantity_unit', 'lbs') == 'kg' ? 'selected' : '' }}>Kilograms</option>
                                            <option value="oz" {{ request('quantity_unit', 'lbs') == 'oz' ? 'selected' : '' }}>Ounces</option>
                                            <option value="grams" {{ request('quantity_unit', 'lbs') == 'grams' ? 'selected' : '' }}>Grams</option>
                                            <option value="bunches" {{ request('quantity_unit', 'lbs') == 'bunches' ? 'selected' : '' }}>Bunches</option>
                                            <option value="heads" {{ request('quantity_unit', 'lbs') == 'heads' ? 'selected' : '' }}>Heads</option>
                                            <option value="pieces" {{ request('quantity_unit', 'lbs') == 'pieces' ? 'selected' : '' }}>Pieces</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="area_harvested" class="form-label">Area Harvested</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="area_harvested" name="area_harvested" step="0.01" min="0">
                                        <select class="form-select" id="area_unit" name="area_unit">
                                            <option value="sqft">Square Feet</option>
                                            <option value="sqm">Square Meters</option>
                                            <option value="beds">Beds</option>
                                            <option value="rows">Rows</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Quality & Method -->
                        <div class="row">
                            <div class="col-md-6">
                                <h5 class="mb-3">Quality Assessment</h5>

                                <div class="mb-3">
                                    <label for="quality" class="form-label">Overall Quality</label>
                                    <select class="form-select" id="quality" name="quality">
                                        <option value="">Select quality...</option>
                                        <option value="excellent">Excellent</option>
                                        <option value="good">Good</option>
                                        <option value="fair">Fair</option>
                                        <option value="poor">Poor</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="size" class="form-label">Size Category</label>
                                    <select class="form-select" id="size" name="size">
                                        <option value="">Select size...</option>
                                        <option value="extra_large">Extra Large</option>
                                        <option value="large">Large</option>
                                        <option value="medium">Medium</option>
                                        <option value="small">Small</option>
                                        <option value="mixed">Mixed</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="maturity" class="form-label">Maturity Level</label>
                                    <select class="form-select" id="maturity" name="maturity">
                                        <option value="">Select maturity...</option>
                                        <option value="underripe">Underripe</option>
                                        <option value="perfect">Perfect</option>
                                        <option value="overripe">Overripe</option>
                                        <option value="mixed">Mixed</option>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h5 class="mb-3">Harvest Method & Conditions</h5>

                                <div class="mb-3">
                                    <label for="method" class="form-label">Harvest Method</label>
                                    <select class="form-select" id="method" name="method">
                                        <option value="hand">Hand harvest</option>
                                        <option value="mechanical">Mechanical harvest</option>
                                        <option value="cut_and_strip">Cut and strip</option>
                                        <option value="pull">Pull/bunch</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="weather" class="form-label">Weather Conditions</label>
                                    <input type="text" class="form-control" id="weather" name="weather"
                                           placeholder="e.g., Sunny, 68°F, Dew on plants" value="{{ request('weather', '') }}">
                                </div>

                                <div class="mb-3">
                                    <label for="labor_hours" class="form-label">Labor Hours</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="labor_hours" name="labor_hours" step="0.25" min="0">
                                        <span class="input-group-text">hours</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Information -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Additional Information</h5>

                                <div class="mb-3">
                                    <label for="destination" class="form-label">Harvest Destination</label>
                                    <input type="text" class="form-control" id="destination" name="destination"
                                           placeholder="e.g., Market, Storage, Processing, Home use" value="{{ request('destination', '') }}">
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"
                                              placeholder="Any additional notes about this harvest...">{{ request('notes', '') }}</textarea>
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
                                        <button type="submit" class="btn btn-danger">
                                            <i class="fas fa-save"></i> Save Harvest Log
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
    const fields = ['crop', 'variety', 'location', 'quantity', 'method', 'weather', 'destination', 'notes'];
    fields.forEach(field => {
        const value = urlParams.get(field);
        if (value && document.getElementById(field)) {
            document.getElementById(field).value = value;
        }
    });

    // Handle quantity_unit
    const quantityUnit = urlParams.get('quantity_unit');
    if (quantityUnit && document.getElementById('quantity_unit')) {
        document.getElementById('quantity_unit').value = quantityUnit;
    }

    // Handle area_unit
    const areaUnit = urlParams.get('area_unit');
    if (areaUnit && document.getElementById('area_unit')) {
        document.getElementById('area_unit').value = areaUnit;
    }
});

// Form validation
document.getElementById('harvestForm').addEventListener('submit', function(e) {
    const requiredFields = ['name', 'date', 'crop', 'location', 'quantity'];
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
    background: linear-gradient(135deg, #dc3545, #c82333);
    color: white;
}

.card-header .fas {
    margin-right: 8px;
}

.alert-danger {
    border-left: 4px solid #dc3545;
}

.form-label {
    font-weight: 600;
    color: #495057;
}

.btn-danger {
    background: linear-gradient(135deg, #dc3545, #c82333);
    border: none;
}

.btn-danger:hover {
    background: linear-gradient(135deg, #c82333, #a02622);
}

.is-invalid {
    border-color: #dc3545 !important;
}
</style>
@endsection