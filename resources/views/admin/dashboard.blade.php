@extends('layouts.app')

@section('title', 'MWF Admin Dashboard')
@section('page-title', 'Dashboard Overview')

@section('content')
<div class="row">
    <!-- Stats Cards -->
    <div class="col-md-3 mb-4">
        <div class="card bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Active Deliveries</h6>
                        <h2 class="mb-0">{{ $deliveryStats['active'] ?? '0' }}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-truck fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="/admin/deliveries" class="text-white text-decoration-none">
                    <small>View Details <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Active Collections</h6>
                        <h2 class="mb-0">{{ $deliveryStats['collections'] ?? '0' }}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-box fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="/admin/deliveries?tab=collections" class="text-white text-decoration-none">
                    <small>View Details <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">Total Customers</h6>
                        <h2 class="mb-0">{{ $customerStats['total'] ?? '0' }}</h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-users fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="/admin/users" class="text-white text-decoration-none">
                    <small>View Details <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h6 class="card-title">System Status</h6>
                        <h2 class="mb-0"><i class="fas fa-check-circle"></i></h2>
                    </div>
                    <div class="align-self-center">
                        <i class="fas fa-server fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="/admin/settings" class="text-white text-decoration-none">
                    <small>View Details <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Quick Actions -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-bolt me-2"></i>Quick Actions
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <a href="/admin/deliveries" class="btn btn-outline-primary w-100 p-3">
                            <i class="fas fa-truck mb-2 d-block fa-2x"></i>
                            <h6>Manage Deliveries</h6>
                            <small class="text-muted">View and manage delivery schedules</small>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="/admin/users" class="btn btn-outline-success w-100 p-3">
                            <i class="fas fa-users mb-2 d-block fa-2x"></i>
                            <h6>Customer Management</h6>
                            <small class="text-muted">Search and manage customers</small>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="/admin/reports" class="btn btn-outline-info w-100 p-3">
                            <i class="fas fa-chart-bar mb-2 d-block fa-2x"></i>
                            <h6>Generate Reports</h6>
                            <small class="text-muted">View delivery and sales reports</small>
                        </a>
                    </div>
                    <div class="col-md-6 mb-3">
                        <a href="/admin/settings" class="btn btn-outline-warning w-100 p-3">
                            <i class="fas fa-cog mb-2 d-block fa-2x"></i>
                            <h6>System Settings</h6>
                            <small class="text-muted">Configure system preferences</small>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-clock me-2"></i>Recent Activity
                </h5>
            </div>
            <div class="card-body">
                <div class="activity-item d-flex align-items-center mb-3">
                    <div class="activity-icon bg-primary text-white rounded-circle me-3">
                        <i class="fas fa-truck"></i>
                    </div>
                    <div>
                        <div class="fw-bold">New delivery scheduled</div>
                        <small class="text-muted">2 minutes ago</small>
                    </div>
                </div>
                <div class="activity-item d-flex align-items-center mb-3">
                    <div class="activity-icon bg-success text-white rounded-circle me-3">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div>
                        <div class="fw-bold">Customer registration</div>
                        <small class="text-muted">15 minutes ago</small>
                    </div>
                </div>
                <div class="activity-item d-flex align-items-center mb-3">
                    <div class="activity-icon bg-info text-white rounded-circle me-3">
                        <i class="fas fa-check"></i>
                    </div>
                    <div>
                        <div class="fw-bold">Order completed</div>
                        <small class="text-muted">1 hour ago</small>
                    </div>
                </div>
                <div class="text-center">
                    <a href="/admin/logs" class="btn btn-sm btn-outline-secondary">View All Activity</a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- System Information -->
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-info-circle me-2"></i>System Information
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="text-center">
                            <i class="fas fa-database fa-2x text-success mb-2"></i>
                            <h6>Database Status</h6>
                            <span class="badge bg-success">Connected</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <i class="fas fa-server fa-2x text-primary mb-2"></i>
                            <h6>Laravel Version</h6>
                            <span class="badge bg-primary">{{ app()->version() }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <i class="fas fa-calendar fa-2x text-info mb-2"></i>
                            <h6>Last Updated</h6>
                            <span class="badge bg-info">{{ date('M j, Y') }}</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="text-center">
                            <i class="fas fa-leaf fa-2x text-success mb-2"></i>
                            <h6>Environment</h6>
                            <span class="badge bg-{{ app()->environment() === 'production' ? 'success' : 'warning' }}">
                                {{ ucfirst(app()->environment()) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Fortnightly Delivery Information --}}
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card" style="border-left: 4px solid #27ae60;">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-calendar-week me-2" style="color: #27ae60;"></i>
                    Fortnightly Delivery Schedule
                </h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <h6 class="mb-1">Current Week Information</h6>
                            <p class="mb-1">
                                <strong>ISO Week {{ $fortnightlyInfo['current_iso_week'] ?? date('W') }} of {{ date('Y') }}</strong> - 
                                <span class="badge bg-{{ ($fortnightlyInfo['current_week'] ?? 'A') === 'A' ? 'success' : 'info' }} ms-1">
                                    Week {{ $fortnightlyInfo['current_week'] ?? 'A' }}
                                </span>
                            </p>
                            <small class="text-muted">
                                {{ ($fortnightlyInfo['current_iso_week'] ?? date('W')) % 2 === 1 ? 'Odd' : 'Even' }} week numbers = Week {{ $fortnightlyInfo['current_week'] ?? 'A' }}
                            </small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="stat-item">
                                    <h4 class="mb-0 text-primary">{{ $fortnightlyInfo['weekly_count'] ?? 0 }}</h4>
                                    <small class="text-muted">Weekly Subscriptions</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="stat-item">
                                    <h4 class="mb-0 text-success">{{ $fortnightlyInfo['fortnightly_count'] ?? 0 }}</h4>
                                    <small class="text-muted">Fortnightly (Active This Week)</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 text-end">
                        <div class="bg-light p-3 rounded">
                            <h6 class="mb-2">Next Week</h6>
                            <span class="badge bg-{{ ($fortnightlyInfo['next_week_type'] ?? 'B') === 'A' ? 'success' : 'info' }} fs-6">
                                Week {{ $fortnightlyInfo['next_week_type'] ?? 'B' }}
                            </span>
                            <div class="mt-2">
                                <small class="text-muted">Fortnightly deliveries</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                @if(isset($fortnightlyInfo['error']))
                <div class="alert alert-warning mt-3 mb-0">
                    <small><i class="fas fa-exclamation-triangle me-1"></i>
                    Unable to load fortnightly data: {{ $fortnightlyInfo['error'] }}</small>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- FarmOS Map Integration -->
<div class="row">
    <div class="col-md-12 mb-4">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-map me-2"></i>FarmOS Map
                </h5>
            </div>
            <div class="card-body">
                <div id="farmos-map-error" class="alert alert-warning d-none"></div>
                <div id="farmos-map" style="height: 400px; width: 100%;"></div>
            </div>
        </div>
    </div>
</div>

<!-- WordPress Integration -->
<div class="row">
    <div class="col-md-8 mb-4">
        <div class="card border-success">
            <div class="card-header bg-success text-white">
                <h5 class="card-title mb-0">
                    <i class="fab fa-wordpress me-2"></i>WordPress Integration
                </h5>
            </div>
            <div class="card-body">
                @if(session('wp_authenticated'))
                    <div class="alert alert-success mb-3">
                        <i class="fas fa-check-circle me-2"></i>
                        <strong>WordPress Access Integrated!</strong> 
                        You're automatically logged into WordPress admin.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <a href="{{ session('wp_admin_url') }}" target="_blank" class="btn btn-success w-100 p-3">
                                <i class="fab fa-wordpress mb-2 d-block fa-2x"></i>
                                <h6>WordPress Admin</h6>
                                <small class="text-white-50">Manage WordPress backend</small>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ str_replace('/wp-admin/', '/wp-admin/edit.php?post_type=product', session('wp_admin_url')) }}" target="_blank" class="btn btn-outline-success w-100 p-3">
                                <i class="fas fa-shopping-cart mb-2 d-block fa-2x"></i>
                                <h6>WooCommerce Products</h6>
                                <small class="text-muted">Manage products & orders</small>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ str_replace('/wp-admin/', '/wp-admin/users.php', session('wp_admin_url')) }}" target="_blank" class="btn btn-outline-success w-100 p-3">
                                <i class="fas fa-users mb-2 d-block fa-2x"></i>
                                <h6>WordPress Users</h6>
                                <small class="text-muted">Manage WordPress users</small>
                            </a>
                        </div>
                        <div class="col-md-6 mb-3">
                            <a href="{{ str_replace('/wp-admin/', '/wp-admin/plugins.php', session('wp_admin_url')) }}" target="_blank" class="btn btn-outline-success w-100 p-3">
                                <i class="fas fa-plug mb-2 d-block fa-2x"></i>
                                <h6>Plugins</h6>
                                <small class="text-muted">Manage WordPress plugins</small>
                            </a>
                        </div>
                    </div>
                @else
                    <div class="alert alert-warning mb-3">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>WordPress Integration Unavailable</strong><br>
                        WordPress authentication failed during login. You can still access WordPress manually.
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <a href="{{ config('services.wp_api.url') ? str_replace('/wp-json', '/wp-admin/', config('services.wp_api.url')) : '#' }}" target="_blank" class="btn btn-outline-secondary w-100 p-3">
                                <i class="fab fa-wordpress mb-2 d-block fa-2x"></i>
                                <h6>WordPress Admin</h6>
                                <small class="text-muted">Manual login required</small>
                            </a>
                        </div>
                        <div class="col-md-6">
                            <button class="btn btn-info w-100 p-3" onclick="retryWordPressAuth()">
                                <i class="fas fa-sync mb-2 d-block fa-2x"></i>
                                <h6>Retry Integration</h6>
                                <small class="text-white-50">Attempt WordPress login again</small>
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<style>
    .activity-icon {
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
    }
    
    /* Enhanced map layer control styling */
    .leaflet-control-layers {
        background: rgba(255, 255, 255, 0.95) !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 10px rgba(0,0,0,0.2) !important;
        border: 1px solid #ccc !important;
    }
    
    .leaflet-control-layers-toggle {
        background-color: #27ae60 !important;
        width: 36px !important;
        height: 36px !important;
    }
    
    .leaflet-control-layers-expanded {
        padding: 8px 12px !important;
        min-width: 150px;
    }
    
    .leaflet-control-layers label {
        font-weight: 500 !important;
        margin: 6px 0 !important;
        cursor: pointer !important;
    }
    
    #farmos-map {
        border-radius: 8px;
        overflow: hidden;
    }
</style>
@endsection

@section('scripts')
<script>
// Retry WordPress authentication
function retryWordPressAuth() {
    // Show loading state
    const button = event.target.closest('button');
    const originalHTML = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<i class="fas fa-spinner fa-spin mb-2 d-block fa-2x"></i><h6>Connecting...</h6><small class="text-white-50">Please wait...</small>';
    
    // Make request to retry WordPress authentication
    fetch('/admin/auth/retry-wordpress', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to show updated status
            location.reload();
        } else {
            // Show error message
            alert('WordPress authentication failed: ' + (data.error || 'Unknown error'));
            // Restore button
            button.disabled = false;
            button.innerHTML = originalHTML;
        }
    })
    .catch(error => {
        console.error('WordPress auth retry error:', error);
        alert('Connection failed. Please try again.');
        // Restore button
        button.disabled = false;
        button.innerHTML = originalHTML;
    });
}

// Show WordPress integration status in console
@if(session('wp_authenticated'))
console.log('✅ WordPress Integration: Active');
console.log('🔗 WordPress Admin URL:', '{{ session('wp_admin_url') }}');
@else
console.log('⚠️ WordPress Integration: Not Available');
@endif

// --- FarmOS Map Integration ---
document.addEventListener('DOMContentLoaded', function() {
    function loadLeafletAssets(callback) {
        if (window.L && window.L.map) { callback(); return; }
        
        // Load Leaflet CSS
        var leafletCss = document.createElement('link');
        leafletCss.rel = 'stylesheet';
        leafletCss.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
        document.head.appendChild(leafletCss);
        
        // Load Fullscreen plugin CSS
        var fullscreenCss = document.createElement('link');
        fullscreenCss.rel = 'stylesheet';
        fullscreenCss.href = 'https://unpkg.com/leaflet-fullscreen@1.0.1/dist/leaflet.fullscreen.css';
        document.head.appendChild(fullscreenCss);
        
        // Load Leaflet JS
        var leafletJs = document.createElement('script');
        leafletJs.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
        leafletJs.onload = function() {
            // Load Fullscreen plugin JS
            var fullscreenJs = document.createElement('script');
            fullscreenJs.src = 'https://unpkg.com/leaflet-fullscreen@1.0.1/dist/Leaflet.fullscreen.min.js';
            fullscreenJs.onload = callback;
            document.body.appendChild(fullscreenJs);
        };
        document.body.appendChild(leafletJs);
    }

    function showMapError(msg) {
        var err = document.getElementById('farmos-map-error');
        if (err) { err.textContent = msg; err.classList.remove('d-none'); }
    }

    function initFarmOSMap() {
        var map = L.map('farmos-map').setView([53.215252, -0.419950], 15); // Middle World Farms front gates
        
        // Base layers
        var openStreetMap = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors'
        });
        
        var satellite = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            attribution: '© Esri, Maxar, Earthstar Geographics, and the GIS User Community'
        });
        
        var hybrid = L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            attribution: '© Esri, Maxar, Earthstar Geographics, and the GIS User Community'
        });
        
        // Add labels overlay for hybrid view
        var labels = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '© OpenStreetMap contributors',
            opacity: 0.3
        });
        
        // Add default layer
        openStreetMap.addTo(map);
        
        // Layer control
        var baseLayers = {
            "🗺️ Street Map": openStreetMap,
            "🛰️ Satellite": satellite,
            "🌍 Hybrid": L.layerGroup([hybrid, labels])
        };
        
        var layerControl = L.control.layers(baseLayers, null, {
            position: 'topright',
            collapsed: false
        }).addTo(map);
        
        // Add fullscreen control
        map.addControl(new L.Control.Fullscreen({
            title: {
                'false': 'View Fullscreen',
                'true': 'Exit Fullscreen'
            }
        }));
        
        // Add scale control
        L.control.scale({
            position: 'bottomleft',
            metric: true,
            imperial: false
        }).addTo(map);

        fetch('/admin/farmos-map-data')
            .then(function(response) { 
                console.log('farmOS map response status:', response.status);
                return response.json(); 
            })
            .then(function(data) {
                console.log('farmOS map data received:', data);
                if (!data || !data.features || !Array.isArray(data.features)) {
                    console.warn('Invalid or empty features data:', data);
                    if (data && data.warning) {
                        showMapError('Warning: ' + data.warning);
                    } else if (data && data.error) {
                        showMapError('Error: ' + data.error);
                    } else {
                        showMapError('No geometry data received from FarmOS.');
                    }
                    return;
                }
                
                console.log('Adding', data.features.length, 'features to map');
                
                // Use standard Leaflet GeoJSON processing
                var geojson = L.geoJSON(data, {
                    style: function(feature) {
                        return { color: '#27ae60', weight: 2, fillOpacity: 0.2 };
                    },
                    onEachFeature: function(feature, layer) {
                        if (feature.properties && feature.properties.name) {
                            var popupContent = feature.properties.name;
                            if (feature.properties.land_type) {
                                popupContent += '<br><small>Type: ' + feature.properties.land_type + '</small>';
                            }
                            if (feature.properties.status) {
                                popupContent += '<br><small>Status: ' + feature.properties.status + '</small>';
                            }
                            layer.bindPopup(popupContent);
                        }
                    }
                }).addTo(map);
                
                // Fit map to show all features
                if (geojson.getBounds && geojson.getBounds().isValid()) {
                    map.fitBounds(geojson.getBounds(), { padding: [10, 10] });
                } else {
                    console.log('Using default zoom for Middle World Farms');
                    map.setView([53.215252, -0.419950], 15);
                }
            })
            .catch(function(err) {
                console.error('FarmOS Map Error:', err);
                showMapError('Failed to load map data: ' + err.message);
            });
    }

    if (document.getElementById('farmos-map')) {
        loadLeafletAssets(initFarmOSMap);
    }
});
</script>
@endsection
