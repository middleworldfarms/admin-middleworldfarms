@extends('layouts.app')

@section('title', 'FarmOS - Crop Planning')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2><i class="fas fa-calendar-alt text-success"></i> Crop Planning</h2>
                    <p class="text-muted">Plan and track crop plantings, expected harvests, and production schedules</p>
                </div>
                <div class="btn-group">
                    <a href="{{ route('admin.farmos.dashboard') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addCropPlanModal">
                        <i class="fas fa-plus"></i> New Crop Plan
                    </button>
                    <button type="button" class="btn btn-info" id="generateReportBtn">
                        <i class="fas fa-chart-bar"></i> Generate Report
                    </button>
                </div>
            </div>

            {{-- Planning Overview Cards --}}
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-seedling fa-2x mb-2"></i>
                            <h4 class="mb-0">{{ $planningStats['total_plans'] }}</h4>
                            <small>Total Plans</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-clock fa-2x mb-2"></i>
                            <h4 class="mb-0">{{ $planningStats['planned'] }}</h4>
                            <small>Planned</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-warning text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-play fa-2x mb-2"></i>
                            <h4 class="mb-0">{{ $planningStats['in_progress'] }}</h4>
                            <small>In Progress</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-check fa-2x mb-2"></i>
                            <h4 class="mb-0">{{ $planningStats['completed'] }}</h4>
                            <small>Completed</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-danger text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-times fa-2x mb-2"></i>
                            <h4 class="mb-0">{{ $planningStats['cancelled'] }}</h4>
                            <small>Cancelled</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card bg-secondary text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                            <h4 class="mb-0">{{ $planningStats['overdue'] }}</h4>
                            <small>Overdue</small>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Calendar View Toggle --}}
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-2">
                            <label for="view_mode" class="form-label">View Mode</label>
                            <select id="view_mode" class="form-select">
                                <option value="list" {{ request('view') != 'calendar' ? 'selected' : '' }}>List View</option>
                                <option value="calendar" {{ request('view') == 'calendar' ? 'selected' : '' }}>Calendar View</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="season" class="form-label">Season</label>
                            <select name="season" id="season" class="form-select">
                                <option value="">All Seasons</option>
                                <option value="spring" {{ request('season') == 'spring' ? 'selected' : '' }}>Spring</option>
                                <option value="summer" {{ request('season') == 'summer' ? 'selected' : '' }}>Summer</option>
                                <option value="fall" {{ request('season') == 'fall' ? 'selected' : '' }}>Fall</option>
                                <option value="winter" {{ request('season') == 'winter' ? 'selected' : '' }}>Winter</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="crop_type" class="form-label">Crop Type</label>
                            <select name="crop_type" id="crop_type" class="form-select">
                                <option value="">All Crops</option>
                                @foreach($cropTypes as $type)
                                    <option value="{{ $type }}" {{ request('crop_type') == $type ? 'selected' : '' }}>
                                        {{ ucfirst($type) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="status" class="form-label">Status</label>
                            <select name="status" id="status" class="form-select">
                                <option value="">All Status</option>
                                <option value="planned" {{ request('status') == 'planned' ? 'selected' : '' }}>Planned</option>
                                <option value="seeded" {{ request('status') == 'seeded' ? 'selected' : '' }}>Seeded</option>
                                <option value="transplanted" {{ request('status') == 'transplanted' ? 'selected' : '' }}>Transplanted</option>
                                <option value="growing" {{ request('status') == 'growing' ? 'selected' : '' }}>Growing</option>
                                <option value="ready_to_harvest" {{ request('status') == 'ready_to_harvest' ? 'selected' : '' }}>Ready to Harvest</option>
                                <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                                <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="location" class="form-label">Location</label>
                            <select name="location" id="location" class="form-select">
                                <option value="">All Locations</option>
                                @foreach($locations as $location)
                                    <option value="{{ $location }}" {{ request('location') == $location ? 'selected' : '' }}>
                                        {{ $location }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="button" class="btn btn-outline-primary me-2" onclick="applyFilters()">
                                <i class="fas fa-filter"></i> Filter
                            </button>
                            <a href="{{ route('admin.farmos.crop-plans') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Clear
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            {{-- List View --}}
            <div id="listView" class="card" style="{{ request('view') == 'calendar' ? 'display: none;' : '' }}">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Crop Plans</h5>
                        <div class="btn-group btn-group-sm">
                            <button class="btn btn-outline-primary" onclick="sortBy('planned_seed_date')">
                                <i class="fas fa-sort"></i> Sort by Seed Date
                            </button>
                            <button class="btn btn-outline-primary" onclick="sortBy('expected_harvest_date')">
                                <i class="fas fa-sort"></i> Sort by Harvest Date
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="table-dark">
                                <tr>
                                    <th>Crop</th>
                                    <th>Season</th>
                                    <th>Planned Dates</th>
                                    <th>Actual Dates</th>
                                    <th>Location</th>
                                    <th>Expected Yield</th>
                                    <th>Status</th>
                                    <th>Progress</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($cropPlans as $plan)
                                <tr data-plan-id="{{ $plan->id }}">
                                    <td>
                                        <div class="d-flex align-items-center">
                                            @php
                                                $iconClass = match(strtolower($plan->crop_type)) {
                                                    'tomato', 'tomatoes' => 'fas fa-apple-alt text-danger',
                                                    'lettuce', 'greens' => 'fas fa-leaf text-success',
                                                    'herbs' => 'fas fa-seedling text-success',
                                                    'carrot', 'carrots' => 'fas fa-carrot text-warning',
                                                    default => 'fas fa-leaf text-success'
                                                };
                                            @endphp
                                            <i class="{{ $iconClass }} me-2"></i>
                                            <div>
                                                <strong>{{ $plan->crop_type }}</strong>
                                                @if($plan->variety)
                                                    <br><small class="text-muted">{{ $plan->variety }}</small>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-info">{{ ucfirst($plan->season) }}</span>
                                        @if($plan->year)
                                            <br><small class="text-muted">{{ $plan->year }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        <small>
                                            <strong>Seed:</strong> {{ $plan->planned_seed_date ? $plan->planned_seed_date->format('M j') : 'TBD' }}
                                            <br><strong>Transplant:</strong> {{ $plan->planned_transplant_date ? $plan->planned_transplant_date->format('M j') : 'N/A' }}
                                            <br><strong>Harvest:</strong> {{ $plan->expected_harvest_date ? $plan->expected_harvest_date->format('M j') : 'TBD' }}
                                        </small>
                                    </td>
                                    <td>
                                        <small>
                                            <strong>Seeded:</strong> {{ $plan->actual_seed_date ? $plan->actual_seed_date->format('M j') : '-' }}
                                            <br><strong>Transplanted:</strong> {{ $plan->actual_transplant_date ? $plan->actual_transplant_date->format('M j') : '-' }}
                                            <br><strong>Harvested:</strong> {{ $plan->actual_harvest_date ? $plan->actual_harvest_date->format('M j') : '-' }}
                                        </small>
                                    </td>
                                    <td>
                                        @if($plan->location)
                                            <i class="fas fa-map-marker-alt text-info me-1"></i>
                                            {{ $plan->location }}
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($plan->expected_yield)
                                            <span class="badge bg-primary">{{ number_format($plan->expected_yield, 1) }} {{ $plan->yield_unit }}</span>
                                        @else
                                            <span class="text-muted">Not set</span>
                                        @endif
                                    </td>
                                    <td>
                                        @php
                                            $statusClass = match($plan->status) {
                                                'planned' => 'bg-info',
                                                'seeded' => 'bg-warning',
                                                'transplanted', 'growing' => 'bg-primary',
                                                'ready_to_harvest' => 'bg-success',
                                                'completed' => 'bg-success',
                                                'cancelled' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $statusClass }}">{{ ucwords(str_replace('_', ' ', $plan->status)) }}</span>
                                    </td>
                                    <td>
                                        @php
                                            $progress = $plan->calculateProgress();
                                            $progressClass = $progress >= 75 ? 'bg-success' : ($progress >= 50 ? 'bg-warning' : 'bg-info');
                                        @endphp
                                        <div class="progress" style="height: 20px;">
                                            <div class="progress-bar {{ $progressClass }}" style="width: {{ $progress }}%">
                                                {{ $progress }}%
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <button type="button" class="btn btn-outline-primary edit-plan-btn"
                                                    data-plan-id="{{ $plan->id }}"
                                                    title="Edit plan">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-success update-status-btn"
                                                    data-plan-id="{{ $plan->id }}"
                                                    title="Update status">
                                                <i class="fas fa-tasks"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-info view-details-btn"
                                                    data-plan-id="{{ $plan->id }}"
                                                    title="View details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="text-muted">
                                            <i class="fas fa-calendar-alt fa-2x mb-3"></i>
                                            <p>No crop plans found. Start planning your next growing season!</p>
                                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCropPlanModal">
                                                <i class="fas fa-plus"></i> Create Crop Plan
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($cropPlans->hasPages())
                <div class="card-footer">
                    {{ $cropPlans->links() }}
                </div>
                @endif
            </div>

            {{-- Calendar View --}}
            <div id="calendarView" class="card" style="{{ request('view') != 'calendar' ? 'display: none;' : '' }}">
                <div class="card-header">
                    <h5 class="mb-0">Crop Planning Calendar</h5>
                </div>
                <div class="card-body">
                    <div id="cropPlanningCalendar"></div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add Crop Plan Modal --}}
<div class="modal fade" id="addCropPlanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Crop Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="addCropPlanForm">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="crop_type" class="form-label">Crop Type *</label>
                            <input type="text" class="form-control" id="crop_type" name="crop_type" required>
                        </div>
                        <div class="col-md-6">
                            <label for="variety" class="form-label">Variety</label>
                            <input type="text" class="form-control" id="variety" name="variety">
                        </div>
                        <div class="col-md-3">
                            <label for="season" class="form-label">Season *</label>
                            <select class="form-select" id="season" name="season" required>
                                <option value="">Select season</option>
                                <option value="spring">Spring</option>
                                <option value="summer">Summer</option>
                                <option value="fall">Fall</option>
                                <option value="winter">Winter</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="year" class="form-label">Year *</label>
                            <input type="number" class="form-control" id="year" name="year" 
                                   value="{{ date('Y') }}" min="{{ date('Y') }}" max="{{ date('Y') + 2 }}" required>
                        </div>
                        <div class="col-md-6">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location">
                        </div>
                        
                        <div class="col-12"><hr><h6>Planned Dates</h6></div>
                        <div class="col-md-4">
                            <label for="planned_seed_date" class="form-label">Planned Seed Date</label>
                            <input type="date" class="form-control" id="planned_seed_date" name="planned_seed_date">
                        </div>
                        <div class="col-md-4">
                            <label for="planned_transplant_date" class="form-label">Planned Transplant Date</label>
                            <input type="date" class="form-control" id="planned_transplant_date" name="planned_transplant_date">
                        </div>
                        <div class="col-md-4">
                            <label for="expected_harvest_date" class="form-label">Expected Harvest Date</label>
                            <input type="date" class="form-control" id="expected_harvest_date" name="expected_harvest_date">
                        </div>
                        
                        <div class="col-12"><hr><h6>Expected Yield</h6></div>
                        <div class="col-md-6">
                            <label for="expected_yield" class="form-label">Expected Yield</label>
                            <input type="number" step="0.1" class="form-control" id="expected_yield" name="expected_yield">
                        </div>
                        <div class="col-md-6">
                            <label for="yield_unit" class="form-label">Yield Unit</label>
                            <select class="form-select" id="yield_unit" name="yield_unit">
                                <option value="">Select unit</option>
                                <option value="lbs">lbs</option>
                                <option value="kg">kg</option>
                                <option value="oz">oz</option>
                                <option value="g">g</option>
                                <option value="bunches">bunches</option>
                                <option value="pieces">pieces</option>
                                <option value="boxes">boxes</option>
                            </select>
                        </div>
                        
                        <div class="col-12">
                            <label for="notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Crop Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Edit Crop Plan Modal --}}
<div class="modal fade" id="editCropPlanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Crop Plan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="editCropPlanForm">
                <div class="modal-body">
                    <div id="editCropPlanContent">
                        <!-- Content will be loaded via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Crop Plan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Update Status Modal --}}
<div class="modal fade" id="updateStatusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Crop Plan Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="updateStatusForm">
                <div class="modal-body">
                    <div id="updateStatusContent">
                        <!-- Content will be loaded via AJAX -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>

<script>
$(document).ready(function() {
    // View mode toggle
    $('#view_mode').change(function() {
        const mode = $(this).val();
        if (mode === 'calendar') {
            $('#listView').hide();
            $('#calendarView').show();
            initCalendar();
        } else {
            $('#calendarView').hide();
            $('#listView').show();
        }
    });

    // Initialize calendar if in calendar view
    if ($('#view_mode').val() === 'calendar') {
        initCalendar();
    }

    function initCalendar() {
        if (typeof FullCalendar !== 'undefined') {
            const calendarEl = document.getElementById('cropPlanningCalendar');
            if (calendarEl && !calendarEl.hasCalendar) {
                const calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,timeGridWeek,listMonth'
                    },
                    events: '{{ route("admin.farmos.crop-plans") }}?format=calendar',
                    eventClick: function(info) {
                        // Handle event click
                        console.log('Event clicked:', info.event);
                    }
                });
                calendar.render();
                calendarEl.hasCalendar = true;
            }
        }
    }

    // Add crop plan form
    $('#addCropPlanForm').submit(function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        submitBtn.html('<i class="fas fa-spinner fa-spin"></i> Creating...').prop('disabled', true);
        
        $.ajax({
            url: '{{ route("admin.farmos.crop-plans") }}',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .done(function(response) {
            if (response.success) {
                toastr.success('Crop plan created successfully');
                $('#addCropPlanModal').modal('hide');
                location.reload();
            } else {
                toastr.error(response.message || 'Failed to create crop plan');
            }
        })
        .fail(function(xhr) {
            toastr.error('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
        })
        .always(function() {
            submitBtn.html(originalText).prop('disabled', false);
        });
    });

    // Edit crop plan
    $('.edit-plan-btn').click(function() {
        const planId = $(this).data('plan-id');
        
        $('#editCropPlanContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        $('#editCropPlanModal').modal('show');
        
        $.get('{{ route("admin.farmos.crop-plans") }}/' + planId + '/edit')
        .done(function(response) {
            $('#editCropPlanContent').html(response);
        })
        .fail(function() {
            $('#editCropPlanContent').html('<div class="alert alert-danger">Error loading crop plan details</div>');
        });
    });

    // Update status
    $('.update-status-btn').click(function() {
        const planId = $(this).data('plan-id');
        
        $('#updateStatusContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
        $('#updateStatusModal').modal('show');
        
        $.get('{{ route("admin.farmos.crop-plans") }}/' + planId + '/status')
        .done(function(response) {
            $('#updateStatusContent').html(response);
        })
        .fail(function() {
            $('#updateStatusContent').html('<div class="alert alert-danger">Error loading status form</div>');
        });
    });
});

function applyFilters() {
    const params = new URLSearchParams();
    
    const season = $('#season').val();
    const cropType = $('#crop_type').val();
    const status = $('#status').val();
    const location = $('#location').val();
    const view = $('#view_mode').val();
    
    if (season) params.set('season', season);
    if (cropType) params.set('crop_type', cropType);
    if (status) params.set('status', status);
    if (location) params.set('location', location);
    if (view === 'calendar') params.set('view', 'calendar');
    
    window.location.href = '{{ route("admin.farmos.crop-plans") }}' + 
        (params.toString() ? '?' + params.toString() : '');
}

function sortBy(field) {
    const params = new URLSearchParams(window.location.search);
    params.set('sort', field);
    window.location.href = '{{ route("admin.farmos.crop-plans") }}?' + params.toString();
}
</script>
@endpush
