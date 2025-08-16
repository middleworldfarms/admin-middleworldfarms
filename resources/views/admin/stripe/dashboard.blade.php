@extends('layouts.app')

@section('title', 'Stripe Payments Dashboard')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-credit-card me-2"></i>Stripe Payments
        </h1>
        <div class="d-flex gap-2">
            <select id="timeRange" class="form-select form-select-sm">
                <option value="7">Last 7 days</option>
                <option value="30" selected>Last 30 days</option>
                <option value="90">Last 90 days</option>
            </select>
            <button class="btn btn-primary btn-sm" onclick="refreshData()">
                <i class="fas fa-sync-alt"></i> Refresh
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Revenue
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalRevenue">
                                £{{ number_format($statistics['total_revenue'], 2) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-pound-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Successful Transactions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalTransactions">
                                {{ number_format($statistics['total_transactions']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Average Transaction
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="averageTransaction">
                                £{{ number_format($statistics['average_transaction'], 2) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Failed Transactions
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="failedTransactions">
                                {{ number_format($statistics['failed_transactions']) }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="row mb-4">
        <!-- Daily Revenue Chart -->
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Daily Revenue</h6>
                </div>
                <div class="card-body">
                    <canvas id="revenueChart" height="80"></canvas>
                </div>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top Customers</h6>
                </div>
                <div class="card-body">
                    <div id="topCustomers">
                        @foreach($statistics['top_customers'] as $customer)
                        <div class="d-flex align-items-center border-bottom pb-2 mb-2">
                            <div class="flex-grow-1">
                                <div class="font-weight-bold">{{ $customer['email'] }}</div>
                                <div class="text-muted small">{{ $customer['count'] }} transactions</div>
                            </div>
                            <div class="text-success font-weight-bold">
                                £{{ number_format($customer['total'], 2) }}
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Payments Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Recent Payments</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="paymentsTable">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Status</th>
                            <th>Payment Method</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="paymentsTableBody">
                        @foreach($recentPayments as $payment)
                        <tr>
                            <td>{{ $payment['created']->format('M j, Y g:i A') }}</td>
                            <td>
                                <div class="font-weight-bold">{{ $payment['customer_name'] }}</div>
                                <div class="text-muted small">{{ $payment['customer_email'] }}</div>
                            </td>
                            <td class="font-weight-bold text-success">
                                £{{ number_format($payment['amount'], 2) }} {{ $payment['currency'] }}
                            </td>
                            <td>
                                <span class="badge badge-{{ $payment['status'] === 'succeeded' ? 'success' : ($payment['status'] === 'failed' ? 'danger' : 'warning') }}">
                                    {{ ucfirst($payment['status']) }}
                                </span>
                            </td>
                            <td>
                                @if($payment['payment_method']['type'] === 'Card')
                                    {{ $payment['payment_method']['brand'] }} •••• {{ $payment['payment_method']['last4'] }}
                                @else
                                    {{ $payment['payment_method']['type'] }}
                                @endif
                            </td>
                            <td>{{ $payment['description'] ?? 'No description' }}</td>
                            <td>
                                @if($payment['receipt_url'])
                                <a href="{{ $payment['receipt_url'] }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-receipt"></i> Receipt
                                </a>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Subscriptions Section -->
    @if($subscriptions->count() > 0)
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Active Subscriptions</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Customer ID</th>
                            <th>Amount</th>
                            <th>Interval</th>
                            <th>Status</th>
                            <th>Current Period</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($subscriptions as $subscription)
                        <tr>
                            <td>{{ $subscription['customer_id'] }}</td>
                            <td class="font-weight-bold">
                                £{{ number_format($subscription['amount'], 2) }} {{ $subscription['currency'] }}
                            </td>
                            <td>{{ ucfirst($subscription['interval']) }}ly</td>
                            <td>
                                <span class="badge badge-{{ $subscription['status'] === 'active' ? 'success' : 'warning' }}">
                                    {{ ucfirst($subscription['status']) }}
                                </span>
                            </td>
                            <td>
                                {{ $subscription['current_period_start']->format('M j') }} - 
                                {{ $subscription['current_period_end']->format('M j, Y') }}
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Initialize revenue chart
const ctx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctx, {
    type: 'line',
    data: {
        labels: [
            @foreach($statistics['daily_revenue'] as $day)
                '{{ Carbon\Carbon::parse($day['date'])->format('M j') }}',
            @endforeach
        ],
        datasets: [{
            label: 'Daily Revenue',
            data: [
                @foreach($statistics['daily_revenue'] as $day)
                    {{ $day['revenue'] }},
                @endforeach
            ],
            borderColor: 'rgb(75, 192, 192)',
            backgroundColor: 'rgba(75, 192, 192, 0.1)',
            tension: 0.1
        }]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value, index, values) {
                        return '£' + value;
                    }
                }
            }
        }
    }
});

// Refresh data function
function refreshData() {
    const days = document.getElementById('timeRange').value;
    
    // Show loading state
    document.querySelector('.btn[onclick="refreshData()"]').innerHTML = 
        '<i class="fas fa-spinner fa-spin"></i> Loading...';
    
    // Fetch updated statistics
    fetch(`/admin/stripe/statistics?days=${days}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateStatistics(data.statistics);
                updateChart(data.statistics.daily_revenue);
            }
        })
        .catch(error => console.error('Error:', error))
        .finally(() => {
            document.querySelector('.btn[onclick="refreshData()"]').innerHTML = 
                '<i class="fas fa-sync-alt"></i> Refresh';
        });
}

function updateStatistics(stats) {
    document.getElementById('totalRevenue').textContent = 
        '£' + new Intl.NumberFormat().format(stats.total_revenue);
    document.getElementById('totalTransactions').textContent = 
        new Intl.NumberFormat().format(stats.total_transactions);
    document.getElementById('averageTransaction').textContent = 
        '£' + new Intl.NumberFormat().format(stats.average_transaction);
    document.getElementById('failedTransactions').textContent = 
        new Intl.NumberFormat().format(stats.failed_transactions);
}

function updateChart(dailyRevenue) {
    revenueChart.data.labels = dailyRevenue.map(day => {
        return new Date(day.date).toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    revenueChart.data.datasets[0].data = dailyRevenue.map(day => day.revenue);
    revenueChart.update();
}

// Auto-refresh every 5 minutes
setInterval(refreshData, 5 * 60 * 1000);
</script>
@endsection
