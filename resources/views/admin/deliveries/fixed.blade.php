@extends('layouts.app')

@section('title', 'Delivery Schedule Management')
@section('page-title', 'Delivery Schedule Management')

@section('content')
<div class="mb-4">
    <p class="text-muted">Real-time delivery data from WooCommerce database</p>
    
    {{-- Database Connection Status --}}
    <div class="alert alert-success mb-4">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h6 class="mb-1"><i class="fas fa-database me-2"></i>Direct Database Connection Active</h6>
                <p class="mb-0"><strong>Data Source:</strong> WooCommerce Database | <strong>Status:</strong> Connected</p>
                @if($totalDeliveries > 0 || $totalCollections > 0)
                    <small class="text-muted">Live data: {{ $totalDeliveries }} deliveries, {{ $totalCollections }} collections</small>
                @endif
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-success">{{ $totalDeliveries }} deliveries</span>
                <span class="badge bg-primary ms-1">{{ $totalCollections }} collections</span>
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
        @if(isset($scheduleData['success']) && $scheduleData['success'] && isset($scheduleData['data']))
            @php
                $currentWeek = date('W');
                $currentWeekType = ($currentWeek % 2 === 0) ? 'A' : 'B';
            @endphp
            
            {{-- Week Information Banner --}}
            <div class="alert alert-info mb-4">
                <div class="row">
                    <div class="col-md-8">
                        <h5 class="mb-1">📅 Current Week Information</h5>
                        <p class="mb-0">
                            <strong>Week {{ $currentWeek }} of {{ date('Y') }}</strong> - 
                            <span class="badge bg-{{ $currentWeekType === 'A' ? 'success' : 'warning' }} ms-1">Week {{ $currentWeekType }}</span>
                        </p>
                        <small class="text-muted">
                            Even weeks = Week A (Fortnightly deliveries) | Odd weeks = Week B (Skip fortnightly)
                        </small>
                    </div>
                    <div class="col-md-4 text-end">
                        <span class="badge bg-primary">{{ $totalDeliveries }} deliveries</span>
                        <span class="badge bg-success ms-1">{{ $totalCollections }} collections</span>
                    </div>
                </div>
            </div>
            
            <div class="card">`
                <div class="card-header">
                    <h3>Schedule Management 
                        <small class="text-muted">({{ $totalDeliveries }} deliveries, {{ $totalCollections }} collections)</small>
                    </h3>
                    
                    {{-- Navigation Tabs --}}
                    <ul class="nav nav-tabs mt-3" id="scheduleTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="all-tab" data-bs-toggle="tab" data-bs-target="#all" type="button" role="tab" aria-controls="all" aria-selected="true">
                                📋 All ({{ $totalDeliveries + $totalCollections }})
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
                    <div class="tab-content" id="scheduleTabContent">
                        {{-- All Tab with Status Subtabs --}}
                        <div class="tab-pane fade show active" id="all" role="tabpanel" aria-labelledby="all-tab">
                            @if($totalDeliveries + $totalCollections > 0)
                                {{-- All Status Subtabs --}}
                                <ul class="nav nav-pills mt-3 mb-4" id="allStatusTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="all-all-tab" data-bs-toggle="pill" data-bs-target="#all-all" type="button" role="tab" aria-controls="all-all" aria-selected="false">
                                            📋 All ({{ $totalDeliveries + $totalCollections }})
                                        </button>
                                    </li>
                                    @if($statusCounts['active'] > 0 || $totalDeliveries > 0)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="all-active-tab" data-bs-toggle="pill" data-bs-target="#all-active" type="button" role="tab" aria-controls="all-active" aria-selected="true">
                                            ✅ Active ({{ $statusCounts['active'] + $totalDeliveries }})
                                        </button>
                                    </li>
                                    @endif
                                    @if($statusCounts['on-hold'] > 0)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="all-on-hold-tab" data-bs-toggle="pill" data-bs-target="#all-on-hold" type="button" role="tab" aria-controls="all-on-hold" aria-selected="false">
                                            ⏸️ On Hold ({{ $statusCounts['on-hold'] }})
                                        </button>
                                    </li>
                                    @endif
                                    @if($statusCounts['cancelled'] > 0)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="all-cancelled-tab" data-bs-toggle="pill" data-bs-target="#all-cancelled" type="button" role="tab" aria-controls="all-cancelled" aria-selected="false">
                                            ❌ Cancelled ({{ $statusCounts['cancelled'] }})
                                        </button>
                                    </li>
                                    @endif
                                    @if($statusCounts['pending'] > 0)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="all-pending-tab" data-bs-toggle="pill" data-bs-target="#all-pending" type="button" role="tab" aria-controls="all-pending" aria-selected="false">
                                            ⏳ Pending ({{ $statusCounts['pending'] }})
                                        </button>
                                    </li>
                                    @endif
                                    @if($statusCounts['processing'] > 0)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="all-processing-tab" data-bs-toggle="pill" data-bs-target="#all-processing" type="button" role="tab" aria-controls="all-processing" aria-selected="false">
                                            ⚡ Processing ({{ $statusCounts['processing'] }})
                                        </button>
                                    </li>
                                    @endif
                                    @if($statusCounts['completed'] > 0)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="all-completed-tab" data-bs-toggle="pill" data-bs-target="#all-completed" type="button" role="tab" aria-controls="all-completed" aria-selected="false">
                                            ✅ Completed ({{ $statusCounts['completed'] }})
                                        </button>
                                    </li>
                                    @endif
                                    @if($statusCounts['refunded'] > 0)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="all-refunded-tab" data-bs-toggle="pill" data-bs-target="#all-refunded" type="button" role="tab" aria-controls="all-refunded" aria-selected="false">
                                            💰 Refunded ({{ $statusCounts['refunded'] }})
                                        </button>
                                    </li>
                                    @endif
                                    @if($statusCounts['other'] > 0)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="all-other-tab" data-bs-toggle="pill" data-bs-target="#all-other" type="button" role="tab" aria-controls="all-other" aria-selected="false">
                                            📋 Other ({{ $statusCounts['other'] }})
                                        </button>
                                    </li>
                                    @endif
                                </ul>

                                {{-- All Status Tab Content --}}
                                <div class="tab-content" id="allStatusTabContent">
                                    {{-- All Combined --}}
                                    <div class="tab-pane fade" id="all-all" role="tabpanel" aria-labelledby="all-all-tab">
                                        @foreach($scheduleData['data'] as $date => $dateData)
                                            @if(count($dateData['deliveries'] ?? []) > 0 || count($dateData['collections'] ?? []) > 0)
                                                <h5 class="mt-3 mb-3 text-muted">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                
                                                {{-- Deliveries for this date --}}
                                                @if(count($dateData['deliveries'] ?? []) > 0)
                                                    <h6 class="text-primary">🚚 Deliveries ({{ count($dateData['deliveries']) }})</h6>
                                                    @include('admin.deliveries.partials.delivery-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                                @endif
                                                
                                                {{-- Collections for this date --}}
                                                @if(count($dateData['collections'] ?? []) > 0)
                                                    <h6 class="text-success">📦 Collections ({{ count($dateData['collections']) }})</h6>
                                                    @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                                @endif
                                            @endif
                                        @endforeach
                                    </div>

                                    {{-- Active Only (Deliveries + Active Collections) --}}
                                    @if($statusCounts['active'] > 0 || $totalDeliveries > 0)
                                    <div class="tab-pane fade show active" id="all-active" role="tabpanel" aria-labelledby="all-active-tab">
                                        {{-- Show all deliveries (they're all active by nature) --}}
                                        @if($totalDeliveries > 0)
                                            @foreach($scheduleData['data'] as $date => $dateData)
                                                @if(count($dateData['deliveries'] ?? []) > 0)
                                                    <h5 class="mt-3 mb-3 text-primary">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                    <h6 class="text-primary">🚚 Active Deliveries ({{ count($dateData['deliveries']) }})</h6>
                                                    @include('admin.deliveries.partials.delivery-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                                @endif
                                            @endforeach
                                        @endif
                                        
                                        {{-- Show only active collections --}}
                                        @if($statusCounts['active'] > 0 && isset($scheduleData['collectionsByStatus']['active']))
                                            @foreach($scheduleData['collectionsByStatus']['active'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-success">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                <h6 class="text-success">📦 Active Collections ({{ count($dateData['collections']) }})</h6>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @endif
                                    </div>
                                    @endif

                                    {{-- On Hold Collections Only --}}
                                    @if($statusCounts['on-hold'] > 0)
                                    <div class="tab-pane fade" id="all-on-hold" role="tabpanel" aria-labelledby="all-on-hold-tab">
                                        @if(isset($scheduleData['collectionsByStatus']['on-hold']))
                                            @foreach($scheduleData['collectionsByStatus']['on-hold'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-warning">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @endif
                                    </div>
                                    @endif

                                    {{-- Cancelled Collections Only --}}
                                    @if($statusCounts['cancelled'] > 0)
                                    <div class="tab-pane fade" id="all-cancelled" role="tabpanel" aria-labelledby="all-cancelled-tab">
                                        @if(isset($scheduleData['collectionsByStatus']['cancelled']))
                                            @foreach($scheduleData['collectionsByStatus']['cancelled'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-danger">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @endif
                                    </div>
                                    @endif

                                    {{-- Pending Collections Only --}}
                                    @if($statusCounts['pending'] > 0)
                                    <div class="tab-pane fade" id="all-pending" role="tabpanel" aria-labelledby="all-pending-tab">
                                        @if(isset($scheduleData['collectionsByStatus']['pending']))
                                            @foreach($scheduleData['collectionsByStatus']['pending'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-info">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @endif
                                    </div>
                                    @endif

                                    {{-- Pending Collections Only --}}
                                    @if($statusCounts['pending'] > 0)
                                    <div class="tab-pane fade" id="all-pending" role="tabpanel" aria-labelledby="all-pending-tab">
                                        @if(isset($scheduleData['collectionsByStatus']['pending']))
                                            @foreach($scheduleData['collectionsByStatus']['pending'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-info">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @endif
                                    </div>
                                    @endif

                                    {{-- Processing Deliveries Only --}}
                                    @if($statusCounts['processing'] > 0)
                                    <div class="tab-pane fade" id="all-processing" role="tabpanel" aria-labelledby="all-processing-tab">
                                        @if(isset($scheduleData['deliveriesByStatus']['processing']))
                                            @foreach($scheduleData['deliveriesByStatus']['processing'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-primary">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.delivery-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                            @endforeach
                                        @endif
                                    </div>
                                    @endif

                                    {{-- Completed Deliveries Only --}}
                                    @if($statusCounts['completed'] > 0)
                                    <div class="tab-pane fade" id="all-completed" role="tabpanel" aria-labelledby="all-completed-tab">
                                        @if(isset($scheduleData['deliveriesByStatus']['completed']))
                                            @foreach($scheduleData['deliveriesByStatus']['completed'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-success">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.delivery-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                            @endforeach
                                        @endif
                                    </div>
                                    @endif

                                    {{-- Refunded Deliveries Only --}}
                                    @if($statusCounts['refunded'] > 0)
                                    <div class="tab-pane fade" id="all-refunded" role="tabpanel" aria-labelledby="all-refunded-tab">
                                        @if(isset($scheduleData['deliveriesByStatus']['refunded']))
                                            @foreach($scheduleData['deliveriesByStatus']['refunded'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-danger">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.delivery-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                            @endforeach
                                        @endif
                                    </div>
                                    @endif

                                    {{-- Other Status Collections Only --}}
                                    @if($statusCounts['other'] > 0)
                                    <div class="tab-pane fade" id="all-other" role="tabpanel" aria-labelledby="all-other-tab">
                                        @if(isset($scheduleData['collectionsByStatus']['other']))
                                            @foreach($scheduleData['collectionsByStatus']['other'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-secondary">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @endif
                                    </div>
                                    @endif
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No deliveries or collections scheduled for the current period.
                                </div>
                            @endif
                        </div>
                        
                        {{-- Deliveries Only Tab with Status Subtabs --}}
                        <div class="tab-pane fade" id="deliveries" role="tabpanel" aria-labelledby="deliveries-tab">
                            @if($totalDeliveries > 0)
                                {{-- Delivery Status Subtabs (Same order as All tab) --}}
                                <ul class="nav nav-pills mt-3 mb-4" id="deliveriesStatusTab" role="tablist">
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link" id="deliveries-all-tab" data-bs-toggle="pill" data-bs-target="#deliveries-all" type="button" role="tab" aria-controls="deliveries-all" aria-selected="false">
                                            🚚 All ({{ $totalDeliveries }})
                                        </button>
                                    </li>
                                    @if($deliveryStatusCounts['active'] > 0)
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link active" id="deliveries-active-tab" data-bs-toggle="pill" data-bs-target="#deliveries-active" type="button" role="tab" aria-controls="deliveries-active" aria-selected="true">
                                            ✅ Active ({{ $deliveryStatusCounts['active'] }})
                                        </button>
                                    </li>
                                    @endif
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $deliveryStatusCounts['on-hold'] == 0 ? 'text-muted' : '' }}" id="deliveries-on-hold-tab" data-bs-toggle="pill" data-bs-target="#deliveries-on-hold" type="button" role="tab" aria-controls="deliveries-on-hold" aria-selected="false" {{ $deliveryStatusCounts['on-hold'] == 0 ? 'title="No on-hold deliveries"' : '' }}>
                                            ⏸️ On Hold ({{ $deliveryStatusCounts['on-hold'] }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $deliveryStatusCounts['cancelled'] == 0 ? 'text-muted' : '' }}" id="deliveries-cancelled-tab" data-bs-toggle="pill" data-bs-target="#deliveries-cancelled" type="button" role="tab" aria-controls="deliveries-cancelled" aria-selected="false" {{ $deliveryStatusCounts['cancelled'] == 0 ? 'title="No cancelled deliveries"' : '' }}>
                                            ❌ Cancelled ({{ $deliveryStatusCounts['cancelled'] }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $deliveryStatusCounts['pending'] == 0 ? 'text-muted' : '' }}" id="deliveries-pending-tab" data-bs-toggle="pill" data-bs-target="#deliveries-pending" type="button" role="tab" aria-controls="deliveries-pending" aria-selected="false" {{ $deliveryStatusCounts['pending'] == 0 ? 'title="No pending deliveries"' : '' }}>
                                            ⏳ Pending ({{ $deliveryStatusCounts['pending'] }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $deliveryStatusCounts['processing'] == 0 ? 'text-muted' : '' }}" id="deliveries-processing-tab" data-bs-toggle="pill" data-bs-target="#deliveries-processing" type="button" role="tab" aria-controls="deliveries-processing" aria-selected="false" {{ $deliveryStatusCounts['processing'] == 0 ? 'title="No processing deliveries"' : '' }}>
                                            ⚡ Processing ({{ $deliveryStatusCounts['processing'] }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $deliveryStatusCounts['completed'] == 0 ? 'text-muted' : '' }}" id="deliveries-completed-tab" data-bs-toggle="pill" data-bs-target="#deliveries-completed" type="button" role="tab" aria-controls="deliveries-completed" aria-selected="false" {{ $deliveryStatusCounts['completed'] == 0 ? 'title="No completed deliveries"' : '' }}>
                                            ✅ Completed ({{ $deliveryStatusCounts['completed'] }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $deliveryStatusCounts['refunded'] == 0 ? 'text-muted' : '' }}" id="deliveries-refunded-tab" data-bs-toggle="pill" data-bs-target="#deliveries-refunded" type="button" role="tab" aria-controls="deliveries-refunded" aria-selected="false" {{ $deliveryStatusCounts['refunded'] == 0 ? 'title="No refunded deliveries"' : '' }}>
                                            💰 Refunded ({{ $deliveryStatusCounts['refunded'] }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $deliveryStatusCounts['other'] == 0 ? 'text-muted' : '' }}" id="deliveries-other-tab" data-bs-toggle="pill" data-bs-target="#deliveries-other" type="button" role="tab" aria-controls="deliveries-other" aria-selected="false" {{ $deliveryStatusCounts['other'] == 0 ? 'title="No other status deliveries"' : '' }}>
                                            📋 Other ({{ $deliveryStatusCounts['other'] }})
                                        </button>
                                    </li>
                                </ul>

                                {{-- Delivery Status Tab Content (Same order as All tab) --}}
                                <div class="tab-content" id="deliveriesStatusTabContent">
                                    {{-- All Deliveries --}}
                                    <div class="tab-pane fade" id="deliveries-all" role="tabpanel" aria-labelledby="deliveries-all-tab">
                                        @foreach($scheduleData['data'] as $date => $dateData)
                                            @if(count($dateData['deliveries'] ?? []) > 0)
                                                <h5 class="mt-3 mb-3 text-muted">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.delivery-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                            @endif
                                        @endforeach
                                    </div>

                                    {{-- Active Deliveries (DEFAULT) --}}
                                    @if($deliveryStatusCounts['active'] > 0)
                                    <div class="tab-pane fade show active" id="deliveries-active" role="tabpanel" aria-labelledby="deliveries-active-tab">
                                        @if(isset($scheduleData['deliveriesByStatus']['processing']))
                                            @foreach($scheduleData['deliveriesByStatus']['processing'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-success">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.delivery-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                            @endforeach
                                        @endif
                                    </div>
                                    @endif

                                    {{-- On Hold Deliveries --}}
                                    <div class="tab-pane fade" id="deliveries-on-hold" role="tabpanel" aria-labelledby="deliveries-on-hold-tab">
                                        @if($deliveryStatusCounts['on-hold'] > 0 && isset($scheduleData['deliveriesByStatus']['on-hold']))
                                            @foreach($scheduleData['deliveriesByStatus']['on-hold'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-warning">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.delivery-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No on-hold deliveries found.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Cancelled Deliveries --}}
                                    <div class="tab-pane fade" id="deliveries-cancelled" role="tabpanel" aria-labelledby="deliveries-cancelled-tab">
                                        @if($deliveryStatusCounts['cancelled'] > 0 && isset($scheduleData['deliveriesByStatus']['cancelled']))
                                            @foreach($scheduleData['deliveriesByStatus']['cancelled'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-danger">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.delivery-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No cancelled deliveries found.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Pending Deliveries --}}
                                    <div class="tab-pane fade" id="deliveries-pending" role="tabpanel" aria-labelledby="deliveries-pending-tab">
                                        @if($deliveryStatusCounts['pending'] > 0 && isset($scheduleData['deliveriesByStatus']['pending']))
                                            @foreach($scheduleData['deliveriesByStatus']['pending'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-warning">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.delivery-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No pending deliveries found.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Processing Deliveries --}}
                                    <div class="tab-pane fade" id="deliveries-processing" role="tabpanel" aria-labelledby="deliveries-processing-tab">
                                        @if($deliveryStatusCounts['processing'] > 0 && isset($scheduleData['deliveriesByStatus']['processing']))
                                            @foreach($scheduleData['deliveriesByStatus']['processing'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-primary">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.delivery-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No processing deliveries found.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Completed Deliveries --}}
                                    <div class="tab-pane fade" id="deliveries-completed" role="tabpanel" aria-labelledby="deliveries-completed-tab">
                                        @if($deliveryStatusCounts['completed'] > 0 && isset($scheduleData['deliveriesByStatus']['completed']))
                                            @foreach($scheduleData['deliveriesByStatus']['completed'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-success">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.delivery-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No completed deliveries found.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Refunded Deliveries --}}
                                    <div class="tab-pane fade" id="deliveries-refunded" role="tabpanel" aria-labelledby="deliveries-refunded-tab">
                                        @if($deliveryStatusCounts['refunded'] > 0 && isset($scheduleData['deliveriesByStatus']['refunded']))
                                            @foreach($scheduleData['deliveriesByStatus']['refunded'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-secondary">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.delivery-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No refunded deliveries found.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Other Status Deliveries --}}
                                    <div class="tab-pane fade" id="deliveries-other" role="tabpanel" aria-labelledby="deliveries-other-tab">
                                        @if($deliveryStatusCounts['other'] > 0 && isset($scheduleData['deliveriesByStatus']['other']))
                                            @foreach($scheduleData['deliveriesByStatus']['other'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-secondary">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.delivery-table', ['items' => $dateData['deliveries'], 'type' => 'delivery'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No other status deliveries found.
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @else
                                <div class="alert alert-info">
                                    <i class="fas fa-truck"></i> No deliveries scheduled for the current period.
                                </div>
                            @endif
                        </div>
                        
                        {{-- Collections Only Tab with Status Subtabs --}}
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
                                        <button class="nav-link {{ $statusCounts['active'] == 0 ? 'text-muted' : 'active' }}" id="collections-active-tab" data-bs-toggle="pill" data-bs-target="#collections-active" type="button" role="tab" aria-controls="collections-active" aria-selected="true" {{ $statusCounts['active'] == 0 ? 'title="No active collections"' : '' }}>
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
                                        <button class="nav-link {{ $statusCounts['processing'] == 0 ? 'text-muted' : '' }}" id="collections-processing-tab" data-bs-toggle="pill" data-bs-target="#collections-processing" type="button" role="tab" aria-controls="collections-processing" aria-selected="false" {{ $statusCounts['processing'] == 0 ? 'title="No processing collections"' : '' }}>
                                            ⚡ Processing ({{ $statusCounts['processing'] }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $statusCounts['completed'] == 0 ? 'text-muted' : '' }}" id="collections-completed-tab" data-bs-toggle="pill" data-bs-target="#collections-completed" type="button" role="tab" aria-controls="collections-completed" aria-selected="false" {{ $statusCounts['completed'] == 0 ? 'title="No completed collections"' : '' }}>
                                            ✅ Completed ({{ $statusCounts['completed'] }})
                                        </button>
                                    </li>
                                    <li class="nav-item" role="presentation">
                                        <button class="nav-link {{ $statusCounts['refunded'] == 0 ? 'text-muted' : '' }}" id="collections-refunded-tab" data-bs-toggle="pill" data-bs-target="#collections-refunded" type="button" role="tab" aria-controls="collections-refunded" aria-selected="false" {{ $statusCounts['refunded'] == 0 ? 'title="No refunded collections"' : '' }}>
                                            💰 Refunded ({{ $statusCounts['refunded'] }})
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

                                    {{-- Processing Collections --}}
                                    <div class="tab-pane fade" id="collections-processing" role="tabpanel" aria-labelledby="collections-processing-tab">
                                        @if($statusCounts['processing'] > 0 && isset($scheduleData['collectionsByStatus']['processing']))
                                            @foreach($scheduleData['collectionsByStatus']['processing'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-primary">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No processing collections found.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Completed Collections --}}
                                    <div class="tab-pane fade" id="collections-completed" role="tabpanel" aria-labelledby="collections-completed-tab">
                                        @if($statusCounts['completed'] > 0 && isset($scheduleData['collectionsByStatus']['completed']))
                                            @foreach($scheduleData['collectionsByStatus']['completed'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-success">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No completed collections found.
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Refunded Collections --}}
                                    <div class="tab-pane fade" id="collections-refunded" role="tabpanel" aria-labelledby="collections-refunded-tab">
                                        @if($statusCounts['refunded'] > 0 && isset($scheduleData['collectionsByStatus']['refunded']))
                                            @foreach($scheduleData['collectionsByStatus']['refunded'] as $date => $dateData)
                                                <h5 class="mt-3 mb-3 text-secondary">{{ $dateData['date_formatted'] ?? $date }}</h5>
                                                @include('admin.deliveries.partials.collection-table', ['items' => $dateData['collections'], 'type' => 'collection'])
                                            @endforeach
                                        @else
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i> No refunded collections found.
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
});
</script>
@endsection
