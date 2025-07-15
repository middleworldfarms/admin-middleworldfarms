@extends('layouts.app')

@section('title', 'System Settings')
@section('page-title', 'System Settings')

@section('content')
<div class="container-fluid">
    {{-- Success Message --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    {{-- Settings Form --}}
    <form method="POST" action="{{ route('admin.settings.update') }}">
        @csrf
        
        <div class="row">
            {{-- Print Settings --}}
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-print"></i> Print Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        {{-- Packing Slips Per Page --}}
                        <div class="mb-3">
                            <label for="packing_slips_per_page" class="form-label">
                                <strong>Packing Slips Per Page</strong>
                                <small class="text-muted">(Perfect for paper guillotine cutting)</small>
                            </label>
                            <select class="form-select" id="packing_slips_per_page" name="packing_slips_per_page">
                                <option value="1" {{ $settings['packing_slips_per_page'] == 1 ? 'selected' : '' }}>1 per page (Full size)</option>
                                <option value="2" {{ $settings['packing_slips_per_page'] == 2 ? 'selected' : '' }}>2 per page (Half size)</option>
                                <option value="4" {{ $settings['packing_slips_per_page'] == 4 ? 'selected' : '' }}>4 per page (Quarter size)</option>
                                <option value="6" {{ $settings['packing_slips_per_page'] == 6 ? 'selected' : '' }}>6 per page (Compact)</option>
                            </select>
                            <div class="form-text">
                                <i class="fas fa-cut"></i> Higher numbers = more slips per sheet to cut with guillotine
                            </div>
                        </div>

                        {{-- Auto Print Mode --}}
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="auto_print_mode" name="auto_print_mode" value="1" {{ $settings['auto_print_mode'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="auto_print_mode">
                                    <strong>Auto Print Mode</strong>
                                </label>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-rocket"></i> Skip preview and send directly to printer queue (recommended for Epson printers)
                            </div>
                        </div>

                        {{-- Paper Size --}}
                        <div class="mb-3">
                            <label for="default_printer_paper_size" class="form-label">
                                <strong>Default Paper Size</strong>
                            </label>
                            <select class="form-select" id="default_printer_paper_size" name="default_printer_paper_size">
                                <option value="A4" {{ $settings['default_printer_paper_size'] == 'A4' ? 'selected' : '' }}>A4 (210 × 297 mm)</option>
                                <option value="Letter" {{ $settings['default_printer_paper_size'] == 'Letter' ? 'selected' : '' }}>Letter (8.5 × 11 in)</option>
                            </select>
                        </div>

                        {{-- Company Logo --}}
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="print_company_logo" name="print_company_logo" value="1" {{ $settings['print_company_logo'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="print_company_logo">
                                    <strong>Include Company Logo</strong>
                                </label>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-image"></i> Print farm logo on packing slips
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Delivery & Collection Settings --}}
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-truck"></i> Delivery & Collection Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        {{-- Route Optimization --}}
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="enable_route_optimization" name="enable_route_optimization" value="1" {{ $settings['enable_route_optimization'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="enable_route_optimization">
                                    <strong>Enable Route Optimization</strong>
                                </label>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-route"></i> Show route planning and optimization features
                            </div>
                        </div>

                        {{-- Delivery Time Slots --}}
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="delivery_time_slots" name="delivery_time_slots" value="1" {{ $settings['delivery_time_slots'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="delivery_time_slots">
                                    <strong>Delivery Time Slots</strong>
                                </label>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-clock"></i> Enable specific delivery time slot selection
                            </div>
                        </div>

                        {{-- Collection Reminder --}}
                        <div class="mb-3">
                            <label for="collection_reminder_hours" class="form-label">
                                <strong>Collection Reminder (Hours Before)</strong>
                            </label>
                            <select class="form-select" id="collection_reminder_hours" name="collection_reminder_hours">
                                <option value="2" {{ $settings['collection_reminder_hours'] == 2 ? 'selected' : '' }}>2 hours before</option>
                                <option value="6" {{ $settings['collection_reminder_hours'] == 6 ? 'selected' : '' }}>6 hours before</option>
                                <option value="24" {{ $settings['collection_reminder_hours'] == 24 ? 'selected' : '' }}>1 day before</option>
                                <option value="48" {{ $settings['collection_reminder_hours'] == 48 ? 'selected' : '' }}>2 days before</option>
                                <option value="72" {{ $settings['collection_reminder_hours'] == 72 ? 'selected' : '' }}>3 days before</option>
                            </select>
                            <div class="form-text">
                                <i class="fas fa-bell"></i> When to send collection reminder emails/notifications
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Notification Settings --}}
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-envelope"></i> Notification Settings
                        </h5>
                    </div>
                    <div class="card-body">
                        {{-- Email Notifications --}}
                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="email_notifications" name="email_notifications" value="1" {{ $settings['email_notifications'] ? 'checked' : '' }}>
                                <label class="form-check-label" for="email_notifications">
                                    <strong>Email Notifications</strong>
                                </label>
                            </div>
                            <div class="form-text">
                                <i class="fas fa-mail-bulk"></i> Send automated email notifications to customers
                            </div>
                        </div>

                        {{-- Future notification settings can go here --}}
                        <div class="alert alert-light">
                            <i class="fas fa-info-circle"></i>
                            <strong>Coming Soon:</strong>
                            <ul class="mb-0 mt-2">
                                <li>SMS notifications</li>
                                <li>Webhook integrations</li>
                                <li>Slack/Discord notifications</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

            {{-- System Info --}}
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle"></i> System Information
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <strong>Settings Storage:</strong>
                            <span class="badge bg-secondary">Session (Temporary)</span>
                            <div class="form-text">
                                Settings are currently stored in session. They will persist until you logout or the session expires.
                            </div>
                        </div>

                        @if(isset($settings['updated_at']))
                        <div class="mb-3">
                            <strong>Last Updated:</strong><br>
                            <small class="text-muted">{{ $settings['updated_at'] }}</small>
                        </div>
                        @endif

                        <div class="alert alert-info">
                            <i class="fas fa-lightbulb"></i>
                            <strong>Future Enhancement:</strong><br>
                            Settings will be moved to database storage for permanent configuration.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Server Performance Monitoring --}}
        <div class="row">
            <div class="col-12 mb-4">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-server"></i> Server Performance Monitoring
                            <small class="ms-2 text-muted">(Diagnose IONOS I/O Throttling)</small>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            {{-- Real-time Metrics --}}
                            <div class="col-lg-6 mb-3">
                                <h6><i class="fas fa-chart-line"></i> Real-time Metrics</h6>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h5 class="text-primary mb-1" id="cpu-usage">--</h5>
                                                <small class="text-muted">CPU Usage</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h5 class="text-info mb-1" id="memory-usage">--</h5>
                                                <small class="text-muted">Memory Usage</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h5 class="text-warning mb-1" id="disk-io">--</h5>
                                                <small class="text-muted">Disk I/O Speed</small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <div class="card bg-light">
                                            <div class="card-body text-center">
                                                <h5 class="text-success mb-1" id="load-average">--</h5>
                                                <small class="text-muted">Load Average</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- I/O Performance Tests --}}
                            <div class="col-lg-6 mb-3">
                                <h6><i class="fas fa-stopwatch"></i> I/O Performance Tests</h6>
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="test-disk-io">
                                        <i class="fas fa-play"></i> Test Disk I/O Speed
                                    </button>
                                    <button type="button" class="btn btn-outline-info btn-sm ms-2" id="test-db-performance">
                                        <i class="fas fa-database"></i> Test Database Performance
                                    </button>
                                </div>
                                <div id="io-test-results" class="alert alert-light" style="display: none;">
                                    <h6>Test Results:</h6>
                                    <div id="test-output"></div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            {{-- System Information --}}
                            <div class="col-lg-4 mb-3">
                                <h6><i class="fas fa-info-circle"></i> System Information</h6>
                                <div class="small">
                                    <div class="d-flex justify-content-between">
                                        <span>PHP Version:</span>
                                        <span id="php-version">{{ PHP_VERSION }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Server Software:</span>
                                        <span id="server-software">{{ $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Memory Limit:</span>
                                        <span id="memory-limit">{{ ini_get('memory_limit') }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Max Execution Time:</span>
                                        <span id="max-execution-time">{{ ini_get('max_execution_time') }}s</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Admin Authenticated:</span>
                                        <span class="badge bg-{{ Session::get('admin_authenticated', false) ? 'success' : 'danger' }}">
                                            {{ Session::get('admin_authenticated', false) ? 'Yes' : 'No' }}
                                        </span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Session ID:</span>
                                        <span class="text-muted small">{{ substr(session()->getId(), 0, 8) }}...</span>
                                    </div>
                                </div>
                            </div>

                            {{-- IONOS Specific Alerts --}}
                            <div class="col-lg-4 mb-3">
                                <h6><i class="fas fa-exclamation-triangle"></i> IONOS I/O Throttling Alerts</h6>
                                <div id="ionos-alerts">
                                    <div class="alert alert-info alert-sm">
                                        <small><i class="fas fa-info-circle"></i> Monitoring for I/O throttling patterns...</small>
                                    </div>
                                </div>
                            </div>

                            {{-- Performance History --}}
                            <div class="col-lg-4 mb-3">
                                <h6><i class="fas fa-history"></i> Performance Trends</h6>
                                <div class="small">
                                    <div class="d-flex justify-content-between">
                                        <span>Last I/O Test:</span>
                                        <span id="last-io-test">--</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Average Response:</span>
                                        <span id="avg-response">--</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Peak Memory:</span>
                                        <span id="peak-memory">{{ round(memory_get_peak_usage(true) / 1024 / 1024, 2) }} MB</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Action Buttons for Monitoring --}}
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="button" class="btn btn-success btn-sm" id="refresh-metrics">
                                        <i class="fas fa-sync"></i> Refresh Metrics
                                    </button>
                                    <button type="button" class="btn btn-info btn-sm" id="export-report">
                                        <i class="fas fa-download"></i> Export Performance Report
                                    </button>
                                    <button type="button" class="btn btn-warning btn-sm" id="check-throttling">
                                        <i class="fas fa-search"></i> Check for Throttling Signs
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Action Buttons --}}
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save"></i> Save Settings
                                </button>
                                <a href="{{ route('admin.settings.reset') }}" class="btn btn-outline-secondary ms-2" 
                                   onclick="return confirm('Are you sure you want to reset all settings to defaults?')">
                                    <i class="fas fa-undo"></i> Reset to Defaults
                                </a>
                            </div>
                            <div>
                                <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-primary">
                                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Show preview of packing slips per page setting
    const packingSlipsSelect = document.getElementById('packing_slips_per_page');
    
    if (packingSlipsSelect) {
        packingSlipsSelect.addEventListener('change', function() {
            const value = this.value;
            let description = '';
            
            switch(value) {
                case '1':
                    description = 'Full page per slip - largest text, easiest to read';
                    break;
                case '2':
                    description = 'Two slips per page - cut in half with guillotine';
                    break;
                case '4':
                    description = 'Four slips per page - cut into quarters';
                    break;
                case '6':
                    description = 'Six slips per page - most compact, cut into sixths';
                    break;
            }
            
            // Update the help text
            const helpText = this.parentNode.querySelector('.form-text');
            helpText.innerHTML = `<i class="fas fa-cut"></i> ${description}`;
        });
    }
    
    // Live preview for auto print mode
    const autoPrintCheck = document.getElementById('auto_print_mode');
    if (autoPrintCheck) {
        autoPrintCheck.addEventListener('change', function() {
            const helpText = this.parentNode.parentNode.querySelector('.form-text');
            if (this.checked) {
                helpText.innerHTML = '<i class="fas fa-rocket text-success"></i> Direct to printer queue - faster printing';
            } else {
                helpText.innerHTML = '<i class="fas fa-eye text-info"></i> Show preview before printing - more control';
            }
        });
    }
    
    // ================== SERVER MONITORING FUNCTIONALITY ==================
    
    // Auto-refresh metrics every 5 seconds
    let metricsInterval;
    let lastIOSpeed = 0;
    let performanceHistory = [];
    
    function startMetricsMonitoring() {
        metricsInterval = setInterval(refreshServerMetrics, 5000);
        refreshServerMetrics(); // Initial load
    }
    
    function refreshServerMetrics() {
        console.log('DEBUG: Attempting to fetch server metrics...');
        fetch('/admin/settings/server-metrics', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            credentials: 'same-origin'
        })
        .then(response => {
            console.log('DEBUG: Server metrics response status:', response.status);
            if (response.status === 302 || response.status === 401 || response.status === 419) {
                throw new Error('Authentication required - please refresh the page and log in again');
            }
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('DEBUG: Server metrics data received:', data);
            if (data.success) {
                updateMetricsDisplay(data.metrics);
                checkForThrottling(data.metrics);
                updatePerformanceHistory(data.metrics);
                console.log('DEBUG: Metrics updated successfully');
            } else {
                console.error('DEBUG: Server metrics request failed:', data);
                displayMetricsError('Server returned error: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('DEBUG: Metrics fetch error:', error);
            if (error.message.includes('Authentication required')) {
                displayMetricsError('Authentication required - please refresh the page and log in');
                // Show login button or link
                const alertsContainer = document.getElementById('ionos-alerts');
                if (alertsContainer) {
                    alertsContainer.innerHTML += `<div class="alert alert-warning alert-sm">
                        <small><i class="fas fa-sign-in-alt"></i> 
                        <a href="/admin/login" class="text-decoration-none">Click here to log in</a> or refresh the page if already logged in.
                        </small>
                    </div>`;
                }
            } else {
                displayMetricsError('Failed to fetch metrics: ' + error.message);
            }
        });
    }
    
    function updateMetricsDisplay(metrics) {
        console.log('DEBUG: Updating metrics display with:', metrics);
        
        // Update CPU usage
        const cpuElement = document.getElementById('cpu-usage');
        if (cpuElement && metrics.cpu_usage !== undefined) {
            cpuElement.textContent = metrics.cpu_usage + '%';
            cpuElement.className = metrics.cpu_usage > 80 ? 'text-danger mb-1' : 
                                  metrics.cpu_usage > 60 ? 'text-warning mb-1' : 'text-primary mb-1';
            console.log('DEBUG: Updated CPU usage to', metrics.cpu_usage + '%');
        } else {
            console.warn('DEBUG: CPU element not found or no CPU data');
        }
        
        // Update Memory usage
        const memoryElement = document.getElementById('memory-usage');
        if (memoryElement && metrics.memory_usage !== undefined) {
            memoryElement.textContent = metrics.memory_usage + '%';
            memoryElement.className = metrics.memory_usage > 85 ? 'text-danger mb-1' : 
                                     metrics.memory_usage > 70 ? 'text-warning mb-1' : 'text-info mb-1';
            console.log('DEBUG: Updated memory usage to', metrics.memory_usage + '%');
        } else {
            console.warn('DEBUG: Memory element not found or no memory data');
        }
        
        // Update Disk I/O
        const diskIOElement = document.getElementById('disk-io');
        if (diskIOElement && metrics.disk_io_speed !== undefined) {
            diskIOElement.textContent = metrics.disk_io_speed + ' MB/s';
            diskIOElement.className = metrics.disk_io_speed < 10 ? 'text-danger mb-1' : 
                                     metrics.disk_io_speed < 50 ? 'text-warning mb-1' : 'text-success mb-1';
            lastIOSpeed = metrics.disk_io_speed;
            console.log('DEBUG: Updated disk I/O to', metrics.disk_io_speed + ' MB/s');
        } else {
            console.warn('DEBUG: Disk I/O element not found or no disk I/O data');
        }
        
        // Update Load Average
        const loadElement = document.getElementById('load-average');
        if (loadElement && metrics.load_average !== undefined) {
            loadElement.textContent = metrics.load_average;
            loadElement.className = metrics.load_average > 2.0 ? 'text-danger mb-1' : 
                                   metrics.load_average > 1.0 ? 'text-warning mb-1' : 'text-success mb-1';
            console.log('DEBUG: Updated load average to', metrics.load_average);
        } else {
            console.warn('DEBUG: Load average element not found or no load data');
        }
        
        // Update average response time
        const avgResponseElement = document.getElementById('avg-response');
        if (avgResponseElement && metrics.response_time !== undefined) {
            avgResponseElement.textContent = metrics.response_time + 'ms';
            console.log('DEBUG: Updated response time to', metrics.response_time + 'ms');
        } else {
            console.warn('DEBUG: Response time element not found or no response time data');
        }
        
        // Clear any previous error messages
        clearMetricsError();
    }
    
    function displayMetricsError(message) {
        console.error('DEBUG: Displaying metrics error:', message);
        // Update all metric displays to show error state
        const elements = ['cpu-usage', 'memory-usage', 'disk-io', 'load-average'];
        elements.forEach(id => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = 'Error';
                element.className = 'text-danger mb-1';
            }
        });
        
        // Show error in alerts container
        const alertsContainer = document.getElementById('ionos-alerts');
        if (alertsContainer) {
            alertsContainer.innerHTML = `<div class="alert alert-danger alert-sm">
                <small><i class="fas fa-exclamation-triangle"></i> ${message}</small>
            </div>`;
        }
    }
    
    function clearMetricsError() {
        // This function can be used to clear error states when metrics load successfully
    }
    
    function checkForThrottling(metrics) {
        const alertsContainer = document.getElementById('ionos-alerts');
        if (!alertsContainer) return;
        
        let alerts = [];
        
        // Check for I/O throttling signs
        if (metrics.disk_io_speed < 5) {
            alerts.push({
                type: 'danger',
                message: '🚨 Very slow disk I/O detected! Possible IONOS throttling.',
                icon: 'exclamation-triangle'
            });
        } else if (metrics.disk_io_speed < 20) {
            alerts.push({
                type: 'warning', 
                message: '⚠️ Slow disk I/O detected. Monitor for patterns.',
                icon: 'exclamation-circle'
            });
        }
        
        // Check for high load with normal CPU (sign of I/O wait)
        if (metrics.load_average > 1.5 && metrics.cpu_usage < 50) {
            alerts.push({
                type: 'warning',
                message: '📊 High load with low CPU usage - possible I/O bottleneck.',
                icon: 'chart-line'
            });
        }
        
        // Check response time degradation
        if (metrics.response_time > 2000) {
            alerts.push({
                type: 'danger',
                message: '🐌 Very slow response times detected.',
                icon: 'clock'
            });
        }
        
        // Update alerts display
        if (alerts.length === 0) {
            alertsContainer.innerHTML = '<div class="alert alert-success alert-sm"><small><i class="fas fa-check-circle"></i> No throttling detected - performance looks good!</small></div>';
        } else {
            alertsContainer.innerHTML = alerts.map(alert => 
                `<div class="alert alert-${alert.type} alert-sm">
                    <small><i class="fas fa-${alert.icon}"></i> ${alert.message}</small>
                </div>`
            ).join('');
        }
    }
    
    function updatePerformanceHistory(metrics) {
        performanceHistory.push({
            timestamp: new Date(),
            ...metrics
        });
        
        // Keep only last 50 entries
        if (performanceHistory.length > 50) {
            performanceHistory.shift();
        }
        
        // Update last test time
        const lastTestElement = document.getElementById('last-io-test');
        if (lastTestElement) {
            lastTestElement.textContent = new Date().toLocaleTimeString();
        }
    }
    
    // Manual I/O Speed Test
    document.getElementById('test-disk-io')?.addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
        
        const startTime = performance.now();
        
        fetch('/admin/settings/test-io-speed', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const endTime = performance.now();
            const testTime = Math.round(endTime - startTime);
            
            const resultsDiv = document.getElementById('io-test-results');
            const outputDiv = document.getElementById('test-output');
            
            if (data.success) {
                outputDiv.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Write Speed:</strong> ${data.write_speed} MB/s<br>
                            <strong>Read Speed:</strong> ${data.read_speed} MB/s<br>
                            <strong>Test Duration:</strong> ${testTime}ms
                        </div>
                        <div class="col-md-6">
                            <strong>File Size:</strong> ${data.test_file_size}<br>
                            <strong>Status:</strong> <span class="badge bg-${data.write_speed > 20 ? 'success' : data.write_speed > 10 ? 'warning' : 'danger'}">${data.write_speed > 20 ? 'Good' : data.write_speed > 10 ? 'Fair' : 'Poor'}</span>
                        </div>
                    </div>
                `;
            } else {
                outputDiv.innerHTML = `<div class="text-danger">Test failed: ${data.error}</div>`;
            }
            
            resultsDiv.style.display = 'block';
        })
        .catch(error => {
            console.error('I/O test error:', error);
            document.getElementById('test-output').innerHTML = `<div class="text-danger">Test failed: ${error.message}</div>`;
            document.getElementById('io-test-results').style.display = 'block';
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-play"></i> Test Disk I/O Speed';
        });
    });
    
    // Database Performance Test
    document.getElementById('test-db-performance')?.addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Testing...';
        
        fetch('/admin/settings/test-db-performance', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            const resultsDiv = document.getElementById('io-test-results');
            const outputDiv = document.getElementById('test-output');
            
            if (data.success) {
                outputDiv.innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Query Time:</strong> ${data.query_time}ms<br>
                            <strong>Connection Time:</strong> ${data.connection_time}ms<br>
                            <strong>Total Queries:</strong> ${data.test_queries}
                        </div>
                        <div class="col-md-6">
                            <strong>Average per Query:</strong> ${data.avg_query_time}ms<br>
                            <strong>Status:</strong> <span class="badge bg-${data.avg_query_time < 50 ? 'success' : data.avg_query_time < 200 ? 'warning' : 'danger'}">${data.avg_query_time < 50 ? 'Fast' : data.avg_query_time < 200 ? 'Moderate' : 'Slow'}</span>
                        </div>
                    </div>
                `;
            } else {
                outputDiv.innerHTML = `<div class="text-danger">Test failed: ${data.error}</div>`;
            }
            
            resultsDiv.style.display = 'block';
        })
        .catch(error => {
            console.error('DB test error:', error);
        })
        .finally(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-database"></i> Test Database Performance';
        });
    });
    
    // Manual refresh metrics
    document.getElementById('refresh-metrics')?.addEventListener('click', function() {
        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Refreshing...';
        
        refreshServerMetrics();
        
        setTimeout(() => {
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-sync"></i> Refresh Metrics';
        }, 1000);
    });
    
    // Export performance report
    document.getElementById('export-report')?.addEventListener('click', function() {
        const reportData = {
            timestamp: new Date().toISOString(),
            current_metrics: {
                cpu: document.getElementById('cpu-usage')?.textContent || 'N/A',
                memory: document.getElementById('memory-usage')?.textContent || 'N/A',
                disk_io: document.getElementById('disk-io')?.textContent || 'N/A',
                load: document.getElementById('load-average')?.textContent || 'N/A'
            },
            performance_history: performanceHistory,
            system_info: {
                php_version: document.getElementById('php-version')?.textContent || 'N/A',
                server_software: document.getElementById('server-software')?.textContent || 'N/A',
                memory_limit: document.getElementById('memory-limit')?.textContent || 'N/A',
                max_execution_time: document.getElementById('max-execution-time')?.textContent || 'N/A'
            }
        };
        
        const blob = new Blob([JSON.stringify(reportData, null, 2)], { type: 'application/json' });
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `server-performance-report-${new Date().toISOString().split('T')[0]}.json`;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    });
    
    // Check for throttling patterns
    document.getElementById('check-throttling')?.addEventListener('click', function() {
        const alertsContainer = document.getElementById('ionos-alerts');
        alertsContainer.innerHTML = '<div class="alert alert-info alert-sm"><small><i class="fas fa-search fa-spin"></i> Analyzing throttling patterns...</small></div>';
        
        // Run a series of I/O tests to detect patterns
        let testCount = 0;
        const maxTests = 5;
        const results = [];
        
        const runTest = () => {
            fetch('/admin/settings/test-io-speed', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    results.push(data.write_speed);
                }
                testCount++;
                
                if (testCount < maxTests) {
                    setTimeout(runTest, 1000); // Wait 1 second between tests
                } else {
                    analyzeThrottlingPattern(results);
                }
            });
        };
        
        runTest();
    });
    
    function analyzeThrottlingPattern(speeds) {
        const alertsContainer = document.getElementById('ionos-alerts');
        const avgSpeed = speeds.reduce((a, b) => a + b, 0) / speeds.length;
        const variation = Math.max(...speeds) - Math.min(...speeds);
        
        let analysis = `<div class="alert alert-info alert-sm">
            <strong>Throttling Analysis Complete:</strong><br>
            <small>Average Speed: ${avgSpeed.toFixed(2)} MB/s | Variation: ${variation.toFixed(2)} MB/s</small>
        </div>`;
        
        if (avgSpeed < 10) {
            analysis += '<div class="alert alert-danger alert-sm"><small><i class="fas fa-exclamation-triangle"></i> <strong>Severe throttling detected!</strong> Contact IONOS support.</small></div>';
        } else if (variation > 20) {
            analysis += '<div class="alert alert-warning alert-sm"><small><i class="fas fa-chart-line"></i> <strong>Inconsistent I/O performance.</strong> May indicate intermittent throttling.</small></div>';
        } else {
            analysis += '<div class="alert alert-success alert-sm"><small><i class="fas fa-check-circle"></i> No clear throttling pattern detected.</small></div>';
        }
        
        alertsContainer.innerHTML = analysis;
    }
    
    // Start monitoring when page loads
    startMetricsMonitoring();
    
    // Debug: Check if all required elements exist
    const requiredElements = ['cpu-usage', 'memory-usage', 'disk-io', 'load-average', 'ionos-alerts'];
    console.log('DEBUG: Checking for required elements...');
    requiredElements.forEach(id => {
        const element = document.getElementById(id);
        console.log(`DEBUG: Element '${id}' ${element ? 'found' : 'NOT FOUND'}`);
    });
    
    // Stop monitoring when leaving page
    window.addEventListener('beforeunload', function() {
        if (metricsInterval) {
            clearInterval(metricsInterval);
        }
    });
    
    // ================== END SERVER MONITORING ==================
    
    });
</script>
@endsection
