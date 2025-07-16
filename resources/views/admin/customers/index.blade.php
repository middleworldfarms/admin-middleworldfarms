@extends('layouts.app')

@section('title', 'Customer Management')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>
            <i class="fas fa-users"></i> Customer Management
        </h1>
    </div>
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-search"></i> Search Customers
            </h5>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="mb-3">
                <div class="input-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, email, or username...">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </div>
            </form>
            <!-- TODO: Display search results -->
            <div class="alert alert-info">Customer search results will appear here.</div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">
                <i class="fas fa-users"></i> Recent Customers & Subscribers
            </h5>
        </div>
        <div class="card-body">
            @if(isset($recentCustomers) && count($recentCustomers) > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Subscribed</th>
                                <th>Orders</th>
                                <th>Last Order</th>
                                <th>Joined</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentCustomers as $customer)
                                <tr>
                                    <td>{{ $customer['name'] }}</td>
                                    <td>{{ $customer['email'] }}</td>
                                    <td>
                                        @if($customer['subscribed'])
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-secondary">No</span>
                                        @endif
                                    </td>
                                    <td>{{ $customer['orders_count'] ?? 0 }}</td>
                                    <td>{{ $customer['last_order'] ?? '' }}</td>
                                    <td>{{ $customer['joined'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="alert alert-warning">No recent customers found.</div>
                @if(isset($debug))
                <div class="mt-3">
                    <h6>Debug Info</h6>
                    <pre style="background:#f8f9fa;border:1px solid #ccc;padding:10px;font-size:12px;max-height:300px;overflow:auto;">{{ print_r($debug, true) }}</pre>
                </div>
                @endif
            @endif
        </div>
    </div>
        </div>
    </div>
    <!-- TODO: Add customer list, action buttons, etc. -->
</div>
@endsection
