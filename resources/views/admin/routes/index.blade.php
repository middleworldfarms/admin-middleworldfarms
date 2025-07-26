@extends('layouts.app')

@section('title', 'Route Planning & Optimization')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-route"></i> Route Planning & Optimization
                    </h3>
                    <div class="card-tools">
                        <input type="date" id="delivery-date" class="form-control" value="{{ $delivery_date }}" onchange="loadDeliveriesForDate(this.value)">
                    </div>
                </div>
                
                <div class="card-body">
                    @if(isset($error))
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle"></i> {{ $error }}
                        </div>
                    @endif

                    @if(isset($message))
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> {{ $message }}
                        </div>
                    @endif

                    @if(!empty($deliveries))
                        <div class="row">
                            <!-- Deliveries List -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-truck"></i> Deliveries ({{ count($deliveries) }})</h5>
                                        <button type="button" class="btn btn-primary btn-sm" onclick="optimizeRouteEnhanced()">
                                            <i class="fas fa-magic"></i> Optimize Route (WP Go Maps Pro)
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="optimizeRoute()">
                                            <i class="fas fa-route"></i> Standard Optimize
                                        </button>
                                    </div>
                                    <div class="card-body" style="max-height: 400px; overflow-y: auto;">
                                        <div id="deliveries-list">
                                            @foreach($deliveries as $index => $delivery)
                                                <div class="delivery-item border rounded p-2 mb-2" data-index="{{ $index }}">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div>
                                                            <strong>{{ $delivery['name'] ?? 'Unknown Customer' }}</strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                @if(is_array($delivery['address'] ?? null))
                                                                    {{ implode(', ', $delivery['address']) }}
                                                                @else
                                                                    {{ $delivery['address'] ?? 'No address' }}
                                                                @endif
                                                            </small>
                                                            @if(isset($delivery['products']) && !empty($delivery['products']))
                                                                <br>
                                                                <small class="text-info">
                                                                    @foreach($delivery['products'] as $product)
                                                                        {{ $product['name'] ?? 'Product' }}{{ !$loop->last ? ', ' : '' }}
                                                                    @endforeach
                                                                </small>
                                                            @endif
                                                        </div>
                                                        <span class="badge badge-primary">{{ $index + 1 }}</span>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>

                                <!-- Route Actions -->
                                <div class="card mt-3" id="route-actions" style="display: none;">
                                    <div class="card-header">
                                        <h5><i class="fas fa-paper-plane"></i> Send to Driver</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="driver-email">Driver Email:</label>
                                            <input type="email" id="driver-email" class="form-control" placeholder="driver@example.com">
                                        </div>
                                        <div class="form-group">
                                            <label for="driver-phone">Driver Phone (optional):</label>
                                            <input type="tel" id="driver-phone" class="form-control" placeholder="+44 7xxx xxx xxx">
                                        </div>
                                        <div class="btn-group w-100">
                                            <button type="button" class="btn btn-success" onclick="sendToDriverEmail()">
                                                <i class="fas fa-envelope"></i> Send Email
                                            </button>
                                            <button type="button" class="btn btn-info" onclick="sendToDriverSMS()">
                                                <i class="fas fa-sms"></i> Send SMS
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- WP Go Maps Integration -->
                                <div class="card mt-3" id="wp-maps-integration">
                                    <div class="card-header">
                                        <h5><i class="fas fa-wordpress"></i> WP Go Maps Pro</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <button type="button" class="btn btn-info btn-block" onclick="createShareableMap()">
                                                <i class="fas fa-share-alt"></i> Create Shareable Map
                                            </button>
                                        </div>
                                        <div id="shareable-map-result" style="display: none;">
                                            <div class="alert alert-success">
                                                <strong>Shareable Map Created!</strong><br>
                                                <a id="shareable-link" href="#" target="_blank" class="btn btn-sm btn-success">
                                                    <i class="fas fa-external-link-alt"></i> View Public Map
                                                </a>
                                                <br><small class="text-muted">
                                                    Shortcode: <code id="map-shortcode">[wpgmza id=""]</code>
                                                </small>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label>Enhanced Features:</label>
                                            <ul class="list-unstyled">
                                                <li><i class="fas fa-check text-success"></i> Customer location history</li>
                                                <li><i class="fas fa-check text-success"></i> Advanced route optimization</li>
                                                <li><i class="fas fa-check text-success"></i> Public shareable maps</li>
                                                <li><i class="fas fa-check text-success"></i> WordPress integration</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Map -->
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header">
                                        <h5><i class="fas fa-map"></i> Route Map</h5>
                                        <div id="route-summary" style="display: none;">
                                            <small class="text-muted">
                                                <span id="total-distance">-</span> | <span id="total-duration">-</span>
                                            </small>
                                        </div>
                                    </div>
                                    <div class="card-body p-0">
                                        <div id="map" style="height: 500px; width: 100%;"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-5">
                            <i class="fas fa-truck fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No deliveries found</h4>
                            <p class="text-muted">Select a different date or check your delivery schedule.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Hidden data -->
<script>
window.deliveriesData = @json($deliveries ?? []);
window.googleMapsKey = '{{ $google_maps_key ?? '' }}';
window.deliveryDate = '{{ $delivery_date }}';
</script>
@endsection

@section('scripts')
<script async defer src="https://maps.googleapis.com/maps/api/js?key={{ $google_maps_key ?? '' }}&callback=initMap"></script>

<script>
let map;
let directionsService;
let directionsRenderer;
let markers = [];
let optimizedRoute = null;

// Initialize Google Map
function initMap() {
    map = new google.maps.Map(document.getElementById('map'), {
        zoom: 11,
        center: { lat: 53.2307, lng: -0.5406 } // Lincoln, Lincolnshire center
    });

    directionsService = new google.maps.DirectionsService();
    directionsRenderer = new google.maps.DirectionsRenderer({
        draggable: false,
        suppressMarkers: false
    });
    directionsRenderer.setMap(map);

    // Load initial markers
    loadMapMarkers();
}

// Load markers for deliveries
function loadMapMarkers() {
    clearMarkers();
    
    if (!window.deliveriesData || window.deliveriesData.length === 0) {
        return;
    }

    // Add depot marker (Middleworld Farms location - adjust coordinates as needed)
    const depotMarker = new google.maps.Marker({
        position: { lat: 53.2307, lng: -0.5406 }, // Lincoln area - adjust to your farm location
        map: map,
        title: 'Middleworld Farms (Depot)',
        icon: {
            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                    <circle cx="16" cy="16" r="14" fill="#28a745" stroke="white" stroke-width="2"/>
                    <text x="16" y="21" text-anchor="middle" fill="white" font-size="12" font-weight="bold">D</text>
                </svg>
            `),
            scaledSize: new google.maps.Size(32, 32),
            anchor: new google.maps.Point(16, 16)
        }
    });
    markers.push(depotMarker);

    // Geocode delivery addresses and add markers
    const geocoder = new google.maps.Geocoder();
    const bounds = new google.maps.LatLngBounds();
    
    let geocodedCount = 0;
    
    window.deliveriesData.forEach((delivery, index) => {
        if (delivery.address) {
            geocoder.geocode({ address: delivery.address }, (results, status) => {
                if (status === 'OK' && results[0]) {
                    const position = results[0].geometry.location;
                    
                    const marker = new google.maps.Marker({
                        position: position,
                        map: map,
                        title: `${delivery.name || 'Customer'} - ${delivery.address}`,
                        icon: {
                            url: 'data:image/svg+xml;charset=UTF-8,' + encodeURIComponent(`
                                <svg width="32" height="32" viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">
                                    <circle cx="16" cy="16" r="14" fill="#007bff" stroke="white" stroke-width="2"/>
                                    <text x="16" y="21" text-anchor="middle" fill="white" font-size="10" font-weight="bold">${index + 1}</text>
                                </svg>
                            `),
                            scaledSize: new google.maps.Size(32, 32),
                            anchor: new google.maps.Point(16, 16)
                        }
                    });
                    
                    markers.push(marker);
                    bounds.extend(position);
                    
                    // Add info window
                    const infoWindow = new google.maps.InfoWindow({
                        content: `
                            <div>
                                <strong>${delivery.name || 'Customer'}</strong><br>
                                <small>${delivery.address}</small><br>
                                Phone: ${delivery.phone || 'N/A'}<br>
                                ${delivery.products ? delivery.products.map(p => `${p.quantity}x ${p.name}`).join('<br>') : ''}
                            </div>
                        `
                    });
                    
                    marker.addListener('click', () => {
                        infoWindow.open(map, marker);
                    });
                    
                    geocodedCount++;
                    
                    // Fit map to show all markers after all geocoding is complete
                    if (geocodedCount === window.deliveriesData.length) {
                        bounds.extend(depotMarker.getPosition());
                        map.fitBounds(bounds);
                        
                        // Ensure minimum zoom level
                        const listener = google.maps.event.addListener(map, "idle", function() {
                            if (map.getZoom() > 15) map.setZoom(15);
                            google.maps.event.removeListener(listener);
                        });
                    }
                } else {
                    console.error('Geocoding failed for address: ' + delivery.address + ', Status: ' + status);
                    
                    // Still count failed geocodes to complete the bounds fitting
                    geocodedCount++;
                    if (geocodedCount === window.deliveriesData.length) {
                        bounds.extend(depotMarker.getPosition());
                        if (!bounds.isEmpty()) {
                            map.fitBounds(bounds);
                        }
                    }
                }
            });
        } else {
            console.warn('No address provided for delivery:', delivery);
            geocodedCount++;
        }
    });
}

// Clear all markers
function clearMarkers() {
    markers.forEach(marker => marker.setMap(null));
    markers = [];
}

// Optimize route
function optimizeRoute() {
    if (!window.deliveriesData || window.deliveriesData.length === 0) {
        alert('No deliveries to optimize');
        return;
    }

    showLoading('Optimizing route...');

    fetch('/admin/routes/optimize', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            deliveries: window.deliveriesData
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            optimizedRoute = data;
            updateDeliveriesList(data.optimized_deliveries);
            updateRouteMap(data);
            showRouteActions();
            showRouteStats(data.total_distance, data.total_duration);
        } else {
            alert('Route optimization failed: ' + data.error);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Route optimization error:', error);
        alert('Route optimization failed. Please try again.');
    });
}

// Update deliveries list with optimized order
function updateDeliveriesList(optimizedDeliveries) {
    const listContainer = document.getElementById('deliveries-list');
    listContainer.innerHTML = '';
    
    optimizedDeliveries.forEach((delivery, index) => {
        const item = document.createElement('div');
        item.className = 'delivery-item border rounded p-2 mb-2';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <strong>${delivery.name || 'Unknown Customer'}</strong>
                    <br>
                    <small class="text-muted">
                        ${Array.isArray(delivery.address) ? delivery.address.join(', ') : (delivery.address || 'No address')}
                    </small>
                    ${delivery.products && delivery.products.length > 0 ? `
                        <br>
                        <small class="text-info">
                            ${delivery.products.map(p => p.name || 'Product').join(', ')}
                        </small>
                    ` : ''}
                </div>
                <span class="badge badge-success">${index + 1}</span>
            </div>
        `;
        listContainer.appendChild(item);
    });
}

// Update route on map
function updateRouteMap(routeData) {
    // This would show the optimized route on the map
    // For now, we'll just update the summary
    console.log('Route data:', routeData);
}

// Show route actions panel
function showRouteActions() {
    document.getElementById('route-actions').style.display = 'block';
}

// Show route statistics
function showRouteStats(distance, duration) {
    document.getElementById('total-distance').textContent = distance;
    document.getElementById('total-duration').textContent = duration;
    document.getElementById('route-summary').style.display = 'block';
}

// Send route to driver via email
function sendToDriverEmail() {
    const email = document.getElementById('driver-email').value;
    if (!email) {
        alert('Please enter driver email');
        return;
    }

    if (!optimizedRoute) {
        alert('Please optimize route first');
        return;
    }

    showLoading('Sending email...');

    fetch('/admin/routes/send-to-driver', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            driver_email: email,
            deliveries: optimizedRoute.optimized_deliveries,
            route_details: optimizedRoute.route_details,
            delivery_date: window.deliveryDate
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            alert('Route sent to driver successfully!');
        } else {
            alert('Failed to send route: ' + data.error);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Send email error:', error);
        alert('Failed to send email. Please try again.');
    });
}

// Send route to driver via SMS
function sendToDriverSMS() {
    const phone = document.getElementById('driver-phone').value;
    if (!phone) {
        alert('Please enter driver phone number');
        return;
    }

    if (!optimizedRoute) {
        alert('Please optimize route first');
        return;
    }

    showLoading('Sending SMS...');

    fetch('/admin/routes/send-to-driver-sms', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            driver_phone: phone,
            deliveries: optimizedRoute.optimized_deliveries,
            route_details: optimizedRoute.route_details,
            delivery_date: window.deliveryDate
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            alert('Route sent to driver via SMS successfully!');
        } else {
            alert('Failed to send SMS: ' + data.error);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Send SMS error:', error);
        alert('Failed to send SMS. Please try again.');
    });
}

// Load deliveries for different date
function loadDeliveriesForDate(date) {
    window.location.href = '/admin/routes?date=' + date;
}

// Loading helpers
function showLoading(message) {
    // You can implement a loading overlay here
    console.log('Loading:', message);
}

function hideLoading() {
    // Hide loading overlay
    console.log('Loading complete');
}

// Create shareable WP Go Maps map
function createShareableMap() {
    if (!optimizedRoute) {
        alert('Please optimize route first');
        return;
    }

    showLoading('Creating shareable map...');

    fetch('/admin/routes/create-shareable-map', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({
            deliveries: optimizedRoute.optimized_deliveries,
            route_details: optimizedRoute.route_details,
            delivery_date: window.deliveryDate
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            // Show shareable map result
            document.getElementById('shareable-link').href = data.shareable_link;
            document.getElementById('map-shortcode').textContent = data.shortcode;
            document.getElementById('shareable-map-result').style.display = 'block';
            
            console.log('WP Go Maps integration:', data);
        } else {
            alert('Failed to create shareable map: ' + data.error);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Shareable map creation error:', error);
        alert('Failed to create shareable map. Please try again.');
    });
}

// Enhanced route optimization with WP Go Maps data
function optimizeRouteEnhanced() {
    if (!window.deliveriesData || window.deliveriesData.length === 0) {
        alert('No deliveries to optimize');
        return;
    }

    showLoading('Optimizing route with WP Go Maps Pro...');

    // First, get WP Go Maps data for customers
    const customerEmails = window.deliveriesData
        .map(d => d.email)
        .filter(email => email);

    Promise.all(customerEmails.map(email => 
        fetch('/admin/routes/wp-go-maps-data?customer_email=' + encodeURIComponent(email))
        .then(response => response.json())
        .catch(() => ({ success: false }))
    ))
    .then(locationResults => {
        // Enhance deliveries with location data
        const enhancedDeliveries = window.deliveriesData.map((delivery, index) => {
            const locationData = locationResults[index];
            if (locationData && locationData.success) {
                delivery.wp_maps_location = locationData.location_data;
            }
            return delivery;
        });

        // Now optimize with enhanced data
        return fetch('/admin/routes/optimize', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({
                deliveries: enhancedDeliveries
            })
        });
    })
    .then(response => response.json())
    .then(data => {
        hideLoading();
        
        if (data.success) {
            optimizedRoute = data;
            updateDeliveriesList(data.optimized_deliveries);
            updateRouteMap(data);
            showRouteActions();
            showRouteStats(data.total_distance, data.total_duration);
            
            // Show optimization source
            if (data.optimization_source) {
                console.log('Route optimized using:', data.optimization_source);
            }
            
            // Show WP Go Maps integration status
            if (data.wp_maps) {
                console.log('WP Go Maps integration available:', data.wp_maps);
            }
        } else {
            alert('Route optimization failed: ' + data.error);
        }
    })
    .catch(error => {
        hideLoading();
        console.error('Enhanced route optimization error:', error);
        
        // Fallback to standard optimization
        console.log('Falling back to standard optimization...');
        optimizeRoute();
    });
}

// ...existing functions...
</script>
@endsection
