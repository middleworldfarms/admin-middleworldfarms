@extends('layouts.app')

@section('title', 'Delivery Schedule Management')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Delivery Schedule Management</h1>
        <div>
            <button onclick="printDeliveries()" class="btn btn-success me-2">
                <i class="fas fa-print"></i> Print Deliveries
            </button>
            <button onclick="printCollections()" class="btn btn-info me-2">
                <i class="fas fa-print"></i> Print Collections
            </button>
            <a href="{{ url('/admin/deliveries?refresh=1') }}" class="btn btn-outline-primary">
                <i class="fas fa-refresh"></i> Refresh Data
            </a>
        </div>
    </div>
    
    @if(isset($scheduleData) && $scheduleData && isset($scheduleData['success']) && $scheduleData['success'])
        <div class="alert alert-success">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <i class="fas fa-check-circle"></i> Successfully connected to MWF Delivery Schedule API
                </div>
                <small class="text-muted">
                    Source: {{ ucfirst($scheduleData['source'] ?? 'unknown') }} | 
                    Last updated: {{ now()->format('H:i:s') }}
                </small>
            </div>
        </div>
        
        @if(isset($scheduleData['data']) && !empty($scheduleData['data']))
            @php
                $totalDeliveries = collect($scheduleData['data'])->sum(function($day) {
                    return count($day['deliveries'] ?? []);
                });
                $totalCollections = collect($scheduleData['data'])->sum(function($day) {
                    return count($day['collections'] ?? []);
                });
            @endphp
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <h3 class="text-primary">{{ $totalDeliveries }}</h3>
                            <p class="card-text">Total Deliveries</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <h3 class="text-success">{{ $totalCollections }}</h3>
                            <p class="card-text">Total Collections</p>
                        </div>
                    </div>
                </div>
            </div>
            
            @foreach($scheduleData['data'] as $date => $dayData)
                <div class="card mb-3">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-calendar"></i> {{ $dayData['date_formatted'] ?? $date }}
                            </h5>
                            <small class="text-muted">
                                {{ count($dayData['deliveries'] ?? []) + count($dayData['collections'] ?? []) }} total items
                            </small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            @if(!empty($dayData['deliveries']))
                                <div class="col-md-6">
                                    <h6 class="text-primary">
                                        <i class="fas fa-truck"></i> Deliveries ({{ count($dayData['deliveries']) }})
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Customer</th>
                                                    <th>Address</th>
                                                    <th>Products</th>
                                                    <th>Contact</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($dayData['deliveries'] as $delivery)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $delivery['name'] ?? 'Unknown' }}</strong>
                                                            @if(isset($delivery['status']) && $delivery['status'] !== 'active')
                                                                <span class="badge bg-warning">{{ $delivery['status'] }}</span>
                                                            @endif
                                                            @if(isset($delivery['customer_id']))
                                                                <br><small class="text-muted">ID: {{ $delivery['customer_id'] }}</small>
                                                                <br>
                                                                <button class="btn btn-sm btn-outline-primary mt-1" 
                                                                        onclick="switchToCustomer({{ $delivery['customer_id'] }}, '{{ addslashes($delivery['name'] ?? 'Unknown') }}')" 
                                                                        title="Switch to this customer">
                                                                    <i class="fas fa-sign-in-alt"></i> Switch
                                                                </button>
                                                            @elseif(isset($delivery['id']))
                                                                <br><small class="text-muted">ID: {{ $delivery['id'] }}</small>
                                                                <br>
                                                                <button class="btn btn-sm btn-outline-primary mt-1" 
                                                                        onclick="switchToCustomer({{ $delivery['id'] }}, '{{ addslashes($delivery['name'] ?? 'Unknown') }}')" 
                                                                        title="Switch to this customer">
                                                                    <i class="fas fa-sign-in-alt"></i> Switch
                                                                </button>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(isset($delivery['address']) && is_array($delivery['address']))
                                                                @php
                                                                    $address = array_filter($delivery['address']);
                                                                    echo implode(', ', array_slice($address, 0, 2));
                                                                @endphp
                                                                @if(isset($delivery['address'][4]) && !empty($delivery['address'][4]))
                                                                    <br><small class="text-muted">{{ $delivery['address'][4] }}</small>
                                                                @endif
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(isset($delivery['products']) && is_array($delivery['products']))
                                                                @foreach($delivery['products'] as $product)
                                                                    <small class="d-block">{{ $product['quantity'] ?? 1 }}x {{ $product['name'] ?? 'Unknown' }}</small>
                                                                @endforeach
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(!empty($delivery['phone']))
                                                                <small class="d-block">{{ $delivery['phone'] }}</small>
                                                            @endif
                                                            @if(!empty($delivery['email']))
                                                                <small class="d-block text-muted">{{ $delivery['email'] }}</small>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                            
                            @if(!empty($dayData['collections']))
                                <div class="{{ !empty($dayData['deliveries']) ? 'col-md-6' : 'col-md-12' }}">
                                    <h6 class="text-success">
                                        <i class="fas fa-store"></i> Collections ({{ count($dayData['collections']) }})
                                    </h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Customer</th>
                                                    <th>Location</th>
                                                    <th>Products</th>
                                                    <th>Contact</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($dayData['collections'] as $collection)
                                                    <tr>
                                                        <td>
                                                            <strong>{{ $collection['name'] ?? 'Unknown' }}</strong>
                                                            @if(isset($collection['status']) && $collection['status'] !== 'active')
                                                                <span class="badge bg-warning">{{ $collection['status'] }}</span>
                                                            @endif
                                                            @if(isset($collection['customer_id']))
                                                                <br><small class="text-muted">ID: {{ $collection['customer_id'] }}</small>
                                                                <br>
                                                                <button class="btn btn-sm btn-outline-primary mt-1" 
                                                                        onclick="switchToCustomer({{ $collection['customer_id'] }}, '{{ addslashes($collection['name'] ?? 'Unknown') }}')" 
                                                                        title="Switch to this customer">
                                                                    <i class="fas fa-sign-in-alt"></i> Switch
                                                                </button>
                                                            @elseif(isset($collection['id']))
                                                                <br><small class="text-muted">ID: {{ $collection['id'] }}</small>
                                                                <br>
                                                                <button class="btn btn-sm btn-outline-primary mt-1" 
                                                                        onclick="switchToCustomer({{ $collection['id'] }}, '{{ addslashes($collection['name'] ?? 'Unknown') }}')" 
                                                                        title="Switch to this customer">
                                                                    <i class="fas fa-sign-in-alt"></i> Switch
                                                                </button>
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(isset($collection['address']) && is_array($collection['address']))
                                                                @php
                                                                    $address = array_filter($collection['address']);
                                                                    echo implode(', ', array_slice($address, 0, 2));
                                                                @endphp
                                                                @if(isset($collection['address'][4]) && !empty($collection['address'][4]))
                                                                    <br><small class="text-muted">{{ $collection['address'][4] }}</small>
                                                                @endif
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(isset($collection['products']) && is_array($collection['products']))
                                                                @foreach($collection['products'] as $product)
                                                                    <small class="d-block">{{ $product['quantity'] ?? 1 }}x {{ $product['name'] ?? 'Unknown' }}</small>
                                                                @endforeach
                                                            @endif
                                                        </td>
                                                        <td>
                                                            @if(!empty($collection['phone']))
                                                                <small class="d-block">{{ $collection['phone'] }}</small>
                                                            @endif
                                                            @if(!empty($collection['email']))
                                                                <small class="d-block text-muted">{{ $collection['email'] }}</small>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif
                            
                            @if(empty($dayData['deliveries']) && empty($dayData['collections']))
                                <div class="col-12">
                                    <p class="text-muted text-center">No deliveries or collections scheduled for this day.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        @else
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> No schedule data available for the current period.
            </div>
        @endif
    @else
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> Failed to fetch delivery schedule
            @if(isset($error))
                <br><strong>Error:</strong> {{ $error }}
            @endif
            @if(isset($scheduleData['message']))
                <br><strong>Message:</strong> {{ $scheduleData['message'] }}
            @endif
        </div>
    @endif

    <div class="mt-4">
        <a href="{{ url('/admin') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
        <a href="{{ url('/admin/deliveries') }}" class="btn btn-primary ms-2">
            <i class="fas fa-refresh"></i> Reload Page
        </a>
    </div>
</div>

<style>
.table th {
    border-top: none;
    font-size: 0.875rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #6c757d;
}
.table td {
    vertical-align: top;
    font-size: 0.875rem;
}
.card-header {
    border-bottom: 2px solid #dee2e6;
}

/* Print styles */
@media print {
    body {
        margin: 30px !important;
        font-family: Arial, sans-serif !important;
    }
    @page {
        margin: 25mm;
        size: A4;
    }
    .no-print {
        display: none !important;
    }
    .card {
        border: 1px solid #000 !important;
        page-break-inside: avoid;
        margin-bottom: 20px !important;
    }
    .table {
        border-collapse: collapse !important;
    }
    .table th, .table td {
        border: 1px solid #000 !important;
        padding: 8px !important;
    }
    .table thead th {
        background-color: #f8f9fa !important;
        font-weight: bold !important;
    }
    .badge {
        border: 1px solid #000 !important;
        padding: 2px 6px !important;
        font-size: 10px !important;
    }
}
</style>

<script>
function printDeliveries() {
    // Create a new window for printing
    var printWindow = window.open('', '_blank');
    
    // Get current date for the header
    var today = new Date();
    var tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    var dateStr = tomorrow.toLocaleDateString('en-GB', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    // Start building the HTML content
    var printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Delivery Schedule - ${dateStr}</title>
            <style>
                body { 
                    margin: 30px; 
                    font-family: Arial, sans-serif; 
                    font-size: 12px;
                }
                @page { 
                    margin: 25mm; 
                    size: A4; 
                }
                h1 { 
                    text-align: center; 
                    margin-bottom: 20px; 
                    font-size: 18px;
                }
                h2 { 
                    color: #0066cc; 
                    border-bottom: 2px solid #0066cc; 
                    padding-bottom: 5px; 
                    font-size: 16px;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-bottom: 20px; 
                }
                th, td { 
                    border: 1px solid #000; 
                    padding: 8px; 
                    text-align: left; 
                    vertical-align: top;
                }
                th { 
                    background-color: #f8f9fa; 
                    font-weight: bold; 
                }
                .badge { 
                    border: 1px solid #000; 
                    padding: 2px 6px; 
                    font-size: 10px; 
                    border-radius: 3px;
                }
                .page-break { 
                    page-break-before: always; 
                }
                .date-header {
                    background-color: #f8f9fa;
                    padding: 10px;
                    margin: 20px 0 10px 0;
                    border: 1px solid #000;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <h1>🚚 DELIVERY SCHEDULE - ${dateStr}</h1>
    `;
    
    // Get all delivery data from the page
    var dayCards = document.querySelectorAll('.card.mb-3');
    var hasDeliveries = false;
    
    dayCards.forEach(function(card, index) {
        var dateHeader = card.querySelector('.card-header h5');
        var deliverySection = card.querySelector('.col-md-6:first-child');
        
        if (deliverySection && deliverySection.querySelector('h6.text-primary')) {
            hasDeliveries = true;
            
            if (index > 0) {
                printContent += '<div class="page-break"></div>';
            }
            
            var dateText = dateHeader ? dateHeader.textContent.replace('📅', '').trim() : 'Unknown Date';
            printContent += `<div class="date-header">📅 ${dateText}</div>`;
            
            var deliveryTable = deliverySection.querySelector('table');
            if (deliveryTable) {
                var deliveryCount = deliverySection.querySelector('h6.text-primary').textContent.match(/\\((\\d+)\\)/);
                printContent += `<h2>🚚 Deliveries${deliveryCount ? ' (' + deliveryCount[1] + ')' : ''}</h2>`;
                printContent += deliveryTable.outerHTML;
            }
        }
    });
    
    if (!hasDeliveries) {
        printContent += '<p style="text-align: center; font-size: 16px; margin-top: 50px;">No deliveries scheduled for tomorrow.</p>';
    }
    
    printContent += `
            <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
                Generated on ${new Date().toLocaleString()} | Middleworld Farms Admin
            </div>
        </body>
        </html>
    `;
    
    // Write content and print
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();
    
    setTimeout(function() {
        printWindow.print();
        printWindow.close();
    }, 500);
}

function printCollections() {
    // Create a new window for printing
    var printWindow = window.open('', '_blank');
    
    // Get current date for the header
    var today = new Date();
    var tomorrow = new Date(today);
    tomorrow.setDate(tomorrow.getDate() + 1);
    var dateStr = tomorrow.toLocaleDateString('en-GB', { 
        weekday: 'long', 
        year: 'numeric', 
        month: 'long', 
        day: 'numeric' 
    });
    
    // Start building the HTML content
    var printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Collection Schedule - ${dateStr}</title>
            <style>
                body { 
                    margin: 30px; 
                    font-family: Arial, sans-serif; 
                    font-size: 12px;
                }
                @page { 
                    margin: 25mm; 
                    size: A4; 
                }
                h1 { 
                    text-align: center; 
                    margin-bottom: 20px; 
                    font-size: 18px;
                }
                h2 { 
                    color: #28a745; 
                    border-bottom: 2px solid #28a745; 
                    padding-bottom: 5px; 
                    font-size: 16px;
                }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-bottom: 20px; 
                }
                th, td { 
                    border: 1px solid #000; 
                    padding: 8px; 
                    text-align: left; 
                    vertical-align: top;
                }
                th { 
                    background-color: #f8f9fa; 
                    font-weight: bold; 
                }
                .badge { 
                    border: 1px solid #000; 
                    padding: 2px 6px; 
                    font-size: 10px; 
                    border-radius: 3px;
                }
                .page-break { 
                    page-break-before: always; 
                }
                .date-header {
                    background-color: #f8f9fa;
                    padding: 10px;
                    margin: 20px 0 10px 0;
                    border: 1px solid #000;
                    font-weight: bold;
                }
            </style>
        </head>
        <body>
            <h1>🏪 COLLECTION SCHEDULE - ${dateStr}</h1>
    `;
    
    // Get all collection data from the page
    var dayCards = document.querySelectorAll('.card.mb-3');
    var hasCollections = false;
    
    dayCards.forEach(function(card, index) {
        var dateHeader = card.querySelector('.card-header h5');
        var collectionSection = card.querySelector('.col-md-6:last-child');
        
        // Handle both cases: when deliveries exist (collections in 2nd column) or when only collections exist (in full width)
        if (!collectionSection) {
            collectionSection = card.querySelector('.col-md-12');
        }
        
        if (collectionSection && collectionSection.querySelector('h6.text-success')) {
            hasCollections = true;
            
            if (index > 0) {
                printContent += '<div class="page-break"></div>';
            }
            
            var dateText = dateHeader ? dateHeader.textContent.replace('📅', '').trim() : 'Unknown Date';
            printContent += `<div class="date-header">📅 ${dateText}</div>`;
            
            var collectionTable = collectionSection.querySelector('table');
            if (collectionTable) {
                var collectionCount = collectionSection.querySelector('h6.text-success').textContent.match(/\\((\\d+)\\)/);
                printContent += `<h2>🏪 Collections${collectionCount ? ' (' + collectionCount[1] + ')' : ''}</h2>`;
                printContent += collectionTable.outerHTML;
            }
        }
    });
    
    if (!hasCollections) {
        printContent += '<p style="text-align: center; font-size: 16px; margin-top: 50px;">No collections scheduled for tomorrow.</p>';
    }
    
    printContent += `
            <div style="margin-top: 30px; text-align: center; font-size: 10px; color: #666;">
                Generated on ${new Date().toLocaleString()} | Middleworld Farms Admin
            </div>
        </body>
        </html>
    `;
    
    // Write content and print
    printWindow.document.write(printContent);
    printWindow.document.close();
    printWindow.focus();
    
    setTimeout(function() {
        printWindow.print();
        printWindow.close();
    }, 500);
}

// Customer switching function
function switchToCustomer(customerId, customerName) {
    if (!customerId) {
        alert('No customer ID available for switching.');
        return;
    }
    
    if (confirm(`Switch to customer "${customerName}"?\n\nThis will open a new tab with you logged in as this customer.`)) {
        // Show loading state
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        btn.disabled = true;
        
        // Make API call to get switch URL
        fetch(`/admin/user-switching/switch/${customerId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCSRFToken()
            },
            body: JSON.stringify({
                redirect_to: '/my-account/'
            })
        })
        .then(response => response.json())
        .then(data => {
            // Restore button state
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            
            if (data.success && data.switch_url) {
                // Open switch URL in new tab
                window.open(data.switch_url, '_blank');
                
                // Show success message
                showSuccessMessage(`Successfully switched to customer "${customerName}". Check the new tab that opened.`);
            } else {
                alert('Failed to switch to customer: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Switch error:', error);
            // Restore button state
            btn.innerHTML = originalHtml;
            btn.disabled = false;
            alert('Error switching to customer. Please try again.');
        });
    }
}

// Helper function to get CSRF token
function getCSRFToken() {
    let token = document.querySelector('meta[name="csrf-token"]');
    if (!token) {
        // Create CSRF token meta tag if it doesn't exist
        token = document.createElement('meta');
        token.name = 'csrf-token';
        token.content = '{{ csrf_token() }}';
        document.head.appendChild(token);
        return token.content;
    }
    return token.getAttribute('content');
}

// Helper function to show success messages
function showSuccessMessage(message) {
    // Remove any existing success messages
    const existingAlert = document.querySelector('.alert-success');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Create and show new success message
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show';
    alertDiv.innerHTML = `
        <i class="fas fa-check-circle"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insert at the top of the container
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}
</script>
@endsection
