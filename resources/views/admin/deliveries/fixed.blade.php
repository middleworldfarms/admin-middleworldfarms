@extends('layouts.app')

@section('title', 'Delivery Schedule Management')
@section('page-title', 'Delivery Schedule Management')

@section('content')
<div class="mb-4">
    {{-- Fortnightly Week Information --}}
    <div class="card mb-4" style="border-left: 4px solid #27ae60;">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h6 class="mb-1">
                        <i class="fas fa-calendar-week me-2" style="color: #27ae60;"></i>
                        Fortnightly Delivery Schedule
                    </h6>
                    @php
                        $currentWeek = (int) date('W');
                        $weekType = ($currentWeek % 2 === 1) ? 'A' : 'B';
                        $nextWeekType = ($weekType === 'A') ? 'B' : 'A';
                    @endphp
                    <p class="mb-0">
                        <strong>Current Week:</strong> 
                        <span class="badge {{ $weekType === 'A' ? 'bg-success' : 'bg-info' }} me-2">
                            Week {{ $weekType }}
                        </span>
                        <small class="text-muted">
                            (ISO Week {{ $currentWeek }} - {{ $currentWeek % 2 === 1 ? 'Odd' : 'Even' }} weeks = Week {{ $weekType }})
                        </small>
                    </p>
                    <small class="text-muted">
                        Next week will be <strong>Week {{ $nextWeekType }}</strong> fortnightly deliveries
                    </small>
                </div>
                <div class="col-md-4 text-end">
                    <button class="btn btn-outline-secondary btn-sm" onclick="toggleFortnightlyInfo()">
                        <i class="fas fa-info-circle me-1"></i>
                        Week Logic
                    </button>
                </div>
            </div>
            
            {{-- Fortnightly Logic Explanation (Initially Hidden) --}}
            <div id="fortnightlyInfo" class="mt-3" style="display: none;">
                <div class="alert alert-light border">
                    <h6 class="fw-bold">📅 Fortnightly Delivery Logic:</h6>
                    <ul class="mb-0 small">
                        <li><strong>Week A:</strong> Odd ISO week numbers (1, 3, 5, 7, 9, 11, 13, etc.)</li>
                        <li><strong>Week B:</strong> Even ISO week numbers (2, 4, 6, 8, 10, 12, 14, etc.)</li>
                        <li><strong>Weekly subscribers:</strong> Deliver every week regardless of A/B</li>
                        <li><strong>Fortnightly subscribers:</strong> Deliver only on their assigned week (A or B)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    
    {{-- User Search and Switching --}}
    @if(isset($userSwitchingAvailable) && $userSwitchingAvailable)
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">👤 User Management</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-8">
                    <div class="input-group">
                        <input type="text" id="userSearch" class="form-control" placeholder="Search for customers by name or email...">
                        <button class="btn btn-outline-secondary" type="button" id="searchButton">
                            <i class="fas fa-search"></i> Search
                        </button>
                    </div>
                    <div id="searchResults" class="mt-2" style="display: none;"></div>
                </div>
                <div class="col-md-4">
                    <button class="btn btn-info" type="button" data-bs-toggle="collapse" data-bs-target="#recentUsers" aria-expanded="false">
                        <i class="fas fa-clock"></i> Recent Users
                    </button>
                </div>
            </div>
            
            {{-- Recent Users Collapsible Section --}}
            <div class="collapse mt-3" id="recentUsers">
                <div class="card card-body">
                    <div id="recentUsersList">
                        <div class="text-center">
                            <div class="spinner-border spinner-border-sm" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            Loading recent users...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- Error Display --}}
    @if(isset($error) && $error)
        <div class="alert alert-danger">
            <strong>Error:</strong> {{ $error }}
        </div>
    @endif
    
    {{-- Schedule Data --}}
    @if(isset($scheduleData) && $scheduleData)
        {{-- Week Navigation (moved here) --}}
        <div class="d-flex align-items-center mb-3">
            <form method="GET" id="weekNavForm" class="d-flex align-items-center">
                @php
                    $selectedWeek = isset($selectedWeek) ? $selectedWeek : date('W');
                    $currentWeek = date('W');
                    $year = date('Y');
                @endphp
                <button type="submit" name="week" value="{{ $selectedWeek - 1 }}" class="btn btn-outline-secondary me-2" @if($selectedWeek <= 1) disabled @endif>
                    &laquo; Previous Week
                </button>
                <button type="submit" name="week" value="{{ $currentWeek }}" class="btn btn-outline-primary me-2 @if($selectedWeek == $currentWeek) active fw-bold @endif">
                    Current Week ({{ $currentWeek }})
                </button>
                <button type="submit" name="week" value="{{ $selectedWeek + 1 }}" class="btn btn-outline-secondary me-2">
                    Next Week &raquo;
                </button>
                <label for="weekSelect" class="ms-2 me-1 mb-0">Go to week:</label>
                <select id="weekSelect" name="week" class="form-select w-auto" onchange="document.getElementById('weekNavForm').submit();">
                    @for($w = 1; $w <= 53; $w++)
                        <option value="{{ $w }}" @if($selectedWeek == $w) selected @endif>Week {{ $w }}</option>
                    @endfor
                </select>
            </form>
        </div>
        {{-- End Week Navigation --}}
        
        @if(isset($scheduleData['success']) && $scheduleData['success'] && isset($scheduleData['data']))
            @php
                $currentWeek = date('W');
                $currentWeekType = ($currentWeek % 2 === 1) ? 'A' : 'B';  // Odd = A, Even = B (fixed!)
            @endphp
            
            <div class="card">
                <div class="card-header">
                    <h3>Schedule Management 
                        <small class="text-muted">({{ $totalDeliveries }} deliveries, {{ $totalCollections }} collections)</small>
                    </h3>
                    
                    {{-- Navigation Tabs --}}
                    <ul class="nav nav-tabs mt-3" id="scheduleTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">
                                � All ({{ $totalDeliveries + $totalCollections }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="deliveries-tab" data-bs-toggle="tab" data-bs-target="#deliveries" type="button" role="tab" aria-controls="deliveries" aria-selected="false">
                                🚚 Deliveries ({{ $totalDeliveries }})
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="collections-tab" data-bs-toggle="tab" data-bs-target="#collections" type="button" role="tab" aria-controls="collections" aria-selected="false">
                                📦 Collections ({{ $totalCollections }})
                            </button>
                        </li>
                    </ul>
                </div>
                <div class="card-body">
                    <div class="tab-content" id="scheduleTabContent">
                        {{-- ALL TAB WITH STATUS SUBTABS (DELIVERIES + COLLECTIONS) --}}
                        <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                            @if($totalCollections > 0)
                                {{-- Status Subtabs --}}
                                <ul class="nav nav-pills mt-3 mb-4" id="allStatusTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="all-all-tab" data-bs-toggle="pill" data-bs-target="#all-all" type="button" role="tab" aria-controls="all-all" aria-selected="false">
                                            📋 All ({{ $totalDeliveries + $totalCollections }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="all-active-tab" data-bs-toggle="pill" data-bs-target="#all-active" type="button" role="tab" aria-controls="all-active" aria-selected="true">
                                            ✅ Active ({{ $statusCounts['active'] }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="all-on-hold-tab" data-bs-toggle="pill" data-bs-target="#all-on-hold" type="button" role="tab" aria-controls="all-on-hold" aria-selected="false">
                                            ⏸️ On Hold ({{ $statusCounts['on-hold'] }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="all-cancelled-tab" data-bs-toggle="pill" data-bs-target="#all-cancelled" type="button" role="tab" aria-controls="all-cancelled" aria-selected="false">
                                            ❌ Cancelled ({{ $statusCounts['cancelled'] }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="all-pending-tab" data-bs-toggle="pill" data-bs-target="#all-pending" type="button" role="tab" aria-controls="all-pending" aria-selected="false">
                                            ⏳ Pending ({{ $statusCounts['pending'] }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="all-other-tab" data-bs-toggle="pill" data-bs-target="#all-other" type="button" role="tab" aria-controls="all-other" aria-selected="false">
                                            📋 Other ({{ $statusCounts['other'] }})
                                        </button>
                                    </li>
                                </ul>

                                {{-- Status Tab Content - BOTH DELIVERIES AND COLLECTIONS --}}
                                <div class="tab-content" id="allStatusTabContent">
                                    {{-- All (Deliveries + Collections) --}}
                                    <div class="tab-pane fade" id="all-all" role="tabpanel" aria-labelledby="all-all-tab">
                                        @foreach($scheduleData['data'] as $date => $dateData)
                                            @if(count($dateData['deliveries'] ?? []) > 0 || count($dateData['collections'] ?? []) > 0)
                                                <h5 class="mt-3 mb-3 text-muted">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @if(count($dateData['deliveries'] ?? []) > 0)
                                                    @include('admin.deliveries.partials.collection-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                                @endif
                                                @if(count($dateData['collections'] ?? []) > 0)
                                                    @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                                @endif
                                            @endif
                                        @endforeach
                                    </div>

                                    {{-- Active (Deliveries + Collections) - DEFAULT --}}
                                    <div class="tab-pane fade show active" id="all-active" role="tabpanel" aria-labelledby="all-active-tab">
                                        @if($statusCounts['active'] > 0 && isset($scheduleData['collectionsByStatus']['active']))
                                            @foreach($scheduleData['collectionsByStatus']['active'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-success">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No active items found.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- On Hold (Deliveries + Collections) --}}
                                    <div class="tab-pane fade" id="all-on-hold" role="tabpanel" aria-labelledby="all-on-hold-tab">
                                        @if($statusCounts['on-hold'] > 0 && isset($scheduleData['collectionsByStatus']['on-hold']))
                                            @foreach($scheduleData['collectionsByStatus']['on-hold'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-warning">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No on-hold items found.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Cancelled (Deliveries + Collections) --}}
                                    <div class="tab-pane fade" id="all-cancelled" role="tabpanel" aria-labelledby="all-cancelled-tab">
                                        @if($statusCounts['cancelled'] > 0 && isset($scheduleData['collectionsByStatus']['cancelled']))
                                            @foreach($scheduleData['collectionsByStatus']['cancelled'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-danger">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No cancelled items found.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Pending (Deliveries + Collections) --}}
                                    <div class="tab-pane fade" id="all-pending" role="tabpanel" aria-labelledby="all-pending-tab">
                                        @if($statusCounts['pending'] > 0 && isset($scheduleData['collectionsByStatus']['pending']))
                                            @foreach($scheduleData['collectionsByStatus']['pending'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-info">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No pending items found.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Other Status (Deliveries + Collections) --}}
                                    <div class="tab-pane fade" id="all-other" role="tabpanel" aria-labelledby="all-other-tab">
                                        @if($statusCounts['other'] > 0 && isset($scheduleData['collectionsByStatus']['other']))
                                            @foreach($scheduleData['collectionsByStatus']['other'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-secondary">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No other status items found.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-box"></i> No items scheduled for the current period.
                                </div>
                            @endif
                        </div>

                        {{-- DELIVERIES TAB - ONLY DELIVERIES --}}
                        <div class="tab-pane fade" id="deliveries" role="tabpanel" aria-labelledby="deliveries-tab">
                            @if($totalDeliveries > 0)
                                {{-- Same status subtabs structure --}}
                                <ul class="nav nav-pills mt-3 mb-4" id="deliveriesStatusTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="deliveries-all-tab" data-bs-toggle="pill" data-bs-target="#deliveries-all" type="button" role="tab" aria-controls="deliveries-all" aria-selected="false">
                                            🚚 All ({{ $totalDeliveries }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="deliveries-active-tab" data-bs-toggle="pill" data-bs-target="#deliveries-active" type="button" role="tab" aria-controls="deliveries-active" aria-selected="true">
                                            ✅ Active ({{ $totalDeliveries }})
                                        </button>
                                    </li>
                                </ul>

                                {{-- ONLY DELIVERY DATA --}}
                                <div class="tab-content" id="deliveriesStatusTabContent">
                                    <div class="tab-pane fade" id="deliveries-all" role="tabpanel" aria-labelledby="deliveries-all-tab">
                                        @foreach($scheduleData['data'] as $date => $dateData)
                                            @if(count($dateData['deliveries'] ?? []) > 0)
                                                <h5 class="mt-3 mb-3 text-muted">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                            @endif
                                        @endforeach
                                    </div>

                                    <div class="tab-pane fade show active" id="deliveries-active" role="tabpanel" aria-labelledby="deliveries-active-tab">
                                        @foreach($scheduleData['data'] as $date => $dateData)
                                            @if(count($dateData['deliveries'] ?? []) > 0)
                                                <h5 class="mt-3 mb-3 text-success">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-box"></i> No deliveries scheduled for the current period.
                                </div>
                            @endif
                        </div>

                        {{-- Collections Only Tab --}}
                        <div class="tab-pane fade" id="collections" role="tabpanel" aria-labelledby="collections-tab">
                            @if($totalCollections > 0)
                                {{-- Collections Status Subtabs (Same order as All tab) --}}
                                <ul class="nav nav-pills mt-3 mb-4" id="collectionsStatusTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="collections-all-tab" data-bs-toggle="pill" data-bs-target="#collections-all" type="button" role="tab" aria-controls="collections-all" aria-selected="false">
                                            📦 All ({{ $totalCollections }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="collections-active-tab" data-bs-toggle="pill" data-bs-target="#collections-active" type="button" role="tab" aria-controls="collections-active" aria-selected="true">
                                            ✅ Active ({{ $statusCounts['active'] }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $statusCounts['on-hold'] == 0 ? 'text-muted' : '' }}" id="collections-on-hold-tab" data-bs-toggle="pill" data-bs-target="#collections-on-hold" type="button" role="tab" aria-controls="collections-on-hold" aria-selected="false" {{ $statusCounts['on-hold'] == 0 ? 'title="No on-hold collections"' : '' }}>
                                            ⏸️ On Hold ({{ $statusCounts['on-hold'] }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $statusCounts['cancelled'] == 0 ? 'text-muted' : '' }}" id="collections-cancelled-tab" data-bs-toggle="pill" data-bs-target="#collections-cancelled" type="button" role="tab" aria-controls="collections-cancelled" aria-selected="false" {{ $statusCounts['cancelled'] == 0 ? 'title="No cancelled collections"' : '' }}>
                                            ❌ Cancelled ({{ $statusCounts['cancelled'] }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $statusCounts['pending'] == 0 ? 'text-muted' : '' }}" id="collections-pending-tab" data-bs-toggle="pill" data-bs-target="#collections-pending" type="button" role="tab" aria-controls="collections-pending" aria-selected="false" {{ $statusCounts['pending'] == 0 ? 'title="No pending collections"' : '' }}>
                                            ⏳ Pending ({{ $statusCounts['pending'] }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $statusCounts['other'] == 0 ? 'text-muted' : '' }}" id="collections-other-tab" data-bs-toggle="pill" data-bs-target="#collections-other" type="button" role="tab" aria-controls="collections-other" aria-selected="false" {{ $statusCounts['other'] == 0 ? 'title="No other status collections"' : '' }}>
                                            📋 Other ({{ $statusCounts['other'] }})
                                        </button>
                                    </li>
                                </ul>

                                {{-- Collections Status Tab Content (Same order as All tab) --}}
                                <div class="tab-content" id="collectionsStatusTabContent">
                                    {{-- All Collections --}}
                                    <div class="tab-pane fade" id="collections-all" role="tabpanel" aria-labelledby="collections-all-tab">
                                        @foreach($scheduleData['data'] as $date => $dateData)
                                            @if(count($dateData['collections'] ?? []) > 0)
                                                <h5 class="mt-3 mb-3 text-muted">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endif
                                        @endforeach
                                    </div>

                                    {{-- Active Collections (DEFAULT) --}}
                                    <div class="tab-pane fade show active" id="collections-active" role="tabpanel" aria-labelledby="collections-active-tab">
                                        @if($statusCounts['active'] > 0 && isset($scheduleData['collectionsByStatus']['active']))
                                            @foreach($scheduleData['collectionsByStatus']['active'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-success">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No active collections found.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- On Hold Collections --}}
                                    <div class="tab-pane fade" id="collections-on-hold" role="tabpanel" aria-labelledby="collections-on-hold-tab">
                                        @if($statusCounts['on-hold'] > 0 && isset($scheduleData['collectionsByStatus']['on-hold']))
                                            @foreach($scheduleData['collectionsByStatus']['on-hold'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-warning">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No on-hold collections found.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Cancelled Collections --}}
                                    <div class="tab-pane fade" id="collections-cancelled" role="tabpanel" aria-labelledby="collections-cancelled-tab">
                                        @if($statusCounts['cancelled'] > 0 && isset($scheduleData['collectionsByStatus']['cancelled']))
                                            @foreach($scheduleData['collectionsByStatus']['cancelled'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-danger">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No cancelled collections found.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Pending Collections --}}
                                    <div class="tab-pane fade" id="collections-pending" role="tabpanel" aria-labelledby="collections-pending-tab">
                                        @if($statusCounts['pending'] > 0 && isset($scheduleData['collectionsByStatus']['pending']))
                                            @foreach($scheduleData['collectionsByStatus']['pending'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-info">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No pending collections found.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Other Status Collections --}}
                                    <div class="tab-pane fade" id="collections-other" role="tabpanel" aria-labelledby="collections-other-tab">
                                        @if($statusCounts['other'] > 0 && isset($scheduleData['collectionsByStatus']['other']))
                                            @foreach($scheduleData['collectionsByStatus']['other'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-secondary">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No other status collections found.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-box"></i> No collections scheduled for the current period.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-info">
                <h5><i class="fas fa-database"></i> Direct Database Connection Active</h5>
                <p><strong>Data Source:</strong> {{ $scheduleData['data_source'] ?? 'direct_database' }}</p>
                <p><strong>Message:</strong> {{ $scheduleData['message'] ?? 'Successfully connected to WordPress/WooCommerce database' }}</p>
                @if($totalDeliveries > 0 || $totalCollections > 0)
                    <p><strong>Available Data:</strong> {{ $totalDeliveries }} deliveries, {{ $totalCollections }} collections</p>
                    <p>The data is being processed but the view structure needs to be updated to display it properly.</p>
                @endif
            </div>
        @endif
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> No schedule data available.
        </div>
    @endif
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle user switching buttons
    document.querySelectorAll('.user-switch-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            const email = this.getAttribute('data-email');
            const name = this.getAttribute('data-name');
            
            if (!email) {
                alert('No email address found for this customer.');
                return;
            }
            
            if (confirm(`Switch to user: ${name} (${email})?`)) {
                // Show loading state
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Switching...';
                this.disabled = true;
                
                // Make AJAX request to switch user
                fetch('/admin/users/switch-by-email', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        email: email
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert(`Successfully switched to ${name}. Redirecting to their profile...`);
                        
                        // Redirect to the user's profile
                        if (data.redirect_url) {
                            window.open(data.redirect_url, '_blank');
                        } else {
                            window.open('https://middleworldfarms.org/my-account/', '_blank');
                        }
                    } else {
                        alert('Failed to switch user: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while switching users.');
                })
                .finally(() => {
                    // Restore button state
                    this.innerHTML = originalText;
                    this.disabled = false;
                });
            }
        });
    });

    // Handle week change buttons
    document.querySelectorAll('.week-change-btn').forEach(function(button) {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const customerId = this.getAttribute('data-customer-id');
            const currentWeek = this.getAttribute('data-current-week');
            const newWeek = this.getAttribute('data-new-week');
            
            if (currentWeek === newWeek) {
                alert('Customer is already on Week ' + newWeek);
                return;
            }
            
            if (confirm(`Change customer from Week ${currentWeek} to Week ${newWeek}?`)) {
                // Show loading state
                const dropdownBtn = this.closest('.dropdown').querySelector('.dropdown-toggle');
                const originalText = dropdownBtn.innerHTML;
                dropdownBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                dropdownBtn.disabled = true;
                
                // Make AJAX request to update customer week
                fetch('/admin/customers/update-week', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({
                        customer_id: customerId,
                        week_type: newWeek
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert(`Successfully changed customer to Week ${newWeek}. Refreshing page...`);
                        
                        // Refresh the page to show updated data
                        window.location.reload();
                    } else {
                        alert('Failed to update customer week: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while updating customer week.');
                })
                .finally(() => {
                    // Restore button state
                    dropdownBtn.innerHTML = originalText;
                    dropdownBtn.disabled = false;
                });
            }
        });
    });
});

// Fortnightly week information toggle
function toggleFortnightlyInfo() {
    const infoDiv = document.getElementById('fortnightlyInfo');
    if (infoDiv.style.display === 'none') {
        infoDiv.style.display = 'block';
    } else {
        infoDiv.style.display = 'none';
    }
}
</script>
@endsection
