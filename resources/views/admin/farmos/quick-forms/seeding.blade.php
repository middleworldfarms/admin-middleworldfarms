@extends('layouts.app')

@section('title', 'Quick Seeding Form')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form id="seedingForm" method="POST" action="{{ route('admin.farmos.succession-planning.submit-log') }}">
                        @csrf
                        <input type="hidden" name="log_type" value="seeding">

                        <!-- Succession Info -->
                        @if(request('succession_number'))
                        <div class="alert alert-info">
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
                                           value="Seeding {{ request('crop_name', '') }} {{ request('succession_number', '') ? 'Succession ' . request('succession_number') : '' }}"
                                           required>
                                </div>

                                <div class="mb-3">
                                    <label for="date" class="form-label">Seeding Date *</label>
                                    <input type="date" class="form-control" id="date" name="date"
                                           value="{{ request('seeding_date', date('Y-m-d')) }}" required>
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
                                    <label for="location" class="form-label">Bed/Location *</label>
                                    <input type="text" class="form-control" id="location" name="location"
                                           value="{{ request('bed_name', '') }}" required>
                                </div>

                                <div class="mb-3">
                                    <label for="quantity" class="form-label">Quantity/Amount *</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="quantity" name="quantity"
                                               value="{{ request('quantity', 100) }}" step="0.01" min="0" required>
                                        <select class="form-select" id="quantity_unit" name="quantity_unit">
                                            <option value="seeds" {{ request('quantity_unit', 'seeds') == 'seeds' ? 'selected' : '' }}>Seeds</option>
                                            <option value="grams" {{ request('quantity_unit', 'seeds') == 'grams' ? 'selected' : '' }}>Grams</option>
                                            <option value="ounces" {{ request('quantity_unit', 'seeds') == 'ounces' ? 'selected' : '' }}>Ounces</option>
                                            <option value="plants" {{ request('quantity_unit', 'seeds') == 'plants' ? 'selected' : '' }}>Plants</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="area" class="form-label">Area (optional)</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="area" name="area" step="0.01" min="0">
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

                        <!-- Additional Details -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="mb-3">Additional Details</h5>

                                <div class="mb-3">
                                    <label for="method" class="form-label">Seeding Method</label>
                                    <select class="form-select" id="method" name="method">
                                        <option value="direct" {{ request('method', 'direct') == 'direct' ? 'selected' : '' }}>Direct Seeding</option>
                                        <option value="tray" {{ request('method', 'direct') == 'tray' ? 'selected' : '' }}>Seed Tray/Plug</option>
                                        <option value="broadcast" {{ request('method', 'direct') == 'broadcast' ? 'selected' : '' }}>Broadcast</option>
                                        <option value="dibbled" {{ request('method', 'direct') == 'dibbled' ? 'selected' : '' }}>Dibbled</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="depth" class="form-label">Seeding Depth</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="depth" name="depth" step="0.1" min="0">
                                        <select class="form-select" id="depth_unit" name="depth_unit">
                                            <option value="inches">Inches</option>
                                            <option value="cm">Centimeters</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="spacing" class="form-label">Plant Spacing</label>
                                    <div class="input-group">
                                        <input type="number" class="form-control" id="spacing" name="spacing" step="0.1" min="0">
                                        <span class="input-group-text">inches between plants</span>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Notes</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3"
                                              placeholder="Any additional notes about this seeding...">{{ request('notes', '') }}</textarea>
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
                                        <button type="submit" class="btn btn-success">
                                            <i class="fas fa-save"></i> Save Seeding Log
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
    const fields = ['crop', 'variety', 'location', 'quantity', 'method', 'notes'];
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
});

// Form validation
document.getElementById('seedingForm').addEventListener('submit', function(e) {
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
    background: linear-gradient(135deg, #28a745, #20c997);
    color: white;
}

.card-header .fas {
    margin-right: 8px;
}

.alert-info {
    border-left: 4px solid #17a2b8;
}

.form-label {
    font-weight: 600;
    color: #495057;
}

.btn-success {
    background: linear-gradient(135deg, #28a745, #20c997);
    border: none;
}

.btn-success:hover {
    background: linear-gradient(135deg, #218838, #1aa085);
}

.is-invalid {
    border-color: #dc3545 !important;
}
</style>
@endsection
