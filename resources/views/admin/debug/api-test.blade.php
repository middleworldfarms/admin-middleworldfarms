@extends('layouts.app')

@section('title', 'API Debugging')
@section('page-title', 'API Debugging Tool')

@section('content')
<div class="container">
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">API Configuration</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>WordPress REST API</h6>
                            <ul class="list-unstyled">
                                <li><strong>URL:</strong> {{ $wp_api_url ?? 'Not configured' }}</li>
                                <li><strong>Has Keys:</strong> {!! $has_wp_keys ? '<span class="text-success">✓ Yes</span>' : '<span class="text-danger">✗ No</span>' !!}</li>
                                <li><strong>Status:</strong> 
                                @if(isset($tests['wp_connection']['success']) && $tests['wp_connection']['success'])
                                    <span class="text-success">✓ Connected</span>
                                @else
                                    <span class="text-danger">✗ Failed</span>
                                @endif
                                </li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>WooCommerce REST API</h6>
                            <ul class="list-unstyled">
                                <li><strong>URL:</strong> {{ $wc_api_url ?? 'Not configured' }}</li>
                                <li><strong>Has Keys:</strong> {!! $has_wc_keys ? '<span class="text-success">✓ Yes</span>' : '<span class="text-danger">✗ No</span>' !!}</li>
                                <li><strong>Status:</strong> 
                                @if(isset($tests['wp_connection']['woocommerce']) && $tests['wp_connection']['woocommerce'])
                                    <span class="text-success">✓ Connected</span>
                                @else
                                    <span class="text-danger">✗ Failed</span>
                                @endif
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mb-4">
        <div class="col">
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">Subscription Details</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-3">
                        <form class="form-inline">
                            <div class="input-group">
                                <input type="text" class="form-control" name="subscription_id" value="{{ request()->get('subscription_id', '227736') }}" placeholder="Subscription ID">
                                <button type="submit" class="btn btn-primary">Load</button>
                            </div>
                        </form>
                    </div>
                    
                    @if(isset($subscription))
                        <div class="table-responsive">
                            <table class="table">
                                <tr>
                                    <th>ID:</th>
                                    <td>{{ $subscription['id'] }}</td>
                                    <th>Status:</th>
                                    <td>
                                        <span class="badge bg-{{ $subscription['status'] === 'active' ? 'success' : 'warning' }}">
                                            {{ ucfirst($subscription['status']) }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <th>Customer:</th>
                                    <td>{{ $subscription['customer_name'] }}</td>
                                    <th>Email:</th>
                                    <td>{{ $subscription['email'] }}</td>
                                </tr>
                                <tr>
                                    <th>Week Type:</th>
                                    <td>
                                        @if(isset($meta_data['customer_week_type']))
                                            <span class="badge bg-{{ $meta_data['customer_week_type'] === 'A' ? 'success' : 'info' }}">
                                                Week {{ $meta_data['customer_week_type'] }}
                                            </span>
                                        @else
                                            <span class="badge bg-secondary">Not set</span>
                                        @endif
                                    </td>
                                    <th>Frequency:</th>
                                    <td>{{ $frequency ?? 'Unknown' }}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="mt-4">
                            <h6>Update Week Type</h6>
                            <form method="get" class="form-inline">
                                <input type="hidden" name="subscription_id" value="{{ $subscription['id'] }}">
                                <input type="hidden" name="update_week" value="1">
                                <div class="input-group">
                                    <select name="week_type" class="form-control">
                                        <option value="A" {{ isset($meta_data['customer_week_type']) && $meta_data['customer_week_type'] === 'A' ? 'selected' : '' }}>Week A (Odd weeks)</option>
                                        <option value="B" {{ isset($meta_data['customer_week_type']) && $meta_data['customer_week_type'] === 'B' ? 'selected' : '' }}>Week B (Even weeks)</option>
                                    </select>
                                    <button type="submit" class="btn btn-warning">Update Week Type</button>
                                </div>
                            </form>
                        </div>
                        
                        @if(isset($tests['update_week']))
                            <div class="mt-4">
                                <h6>Update Results</h6>
                                <div class="alert {{ $tests['update_week']['success'] ? 'alert-success' : 'alert-danger' }}">
                                    <strong>WooCommerce API:</strong> {{ $tests['update_week']['success'] ? 'Success' : 'Failed' }} (HTTP {{ $tests['update_week']['status'] }})
                                </div>
                                
                                <div class="alert {{ $tests['update_week_mwf']['success'] ? 'alert-success' : 'alert-danger' }}">
                                    <strong>MWF Plugin API:</strong> {{ $tests['update_week_mwf']['success'] ? 'Success' : 'Failed' }} (HTTP {{ $tests['update_week_mwf']['status'] }})
                                </div>
                                
                                <div class="mt-3">
                                    <a href="{{ url()->current() }}?subscription_id={{ $subscription['id'] }}" class="btn btn-primary">Refresh to See Changes</a>
                                </div>
                            </div>
                        @endif
                        
                        <div class="mt-4">
                            <h6>Meta Data</h6>
                            <div class="table-responsive">
                                <table class="table table-striped table-sm">
                                    <thead>
                                        <tr>
                                            <th>Key</th>
                                            <th>Value</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($meta_data as $key => $value)
                                            <tr>
                                                <td>{{ $key }}</td>
                                                <td>
                                                    @if(is_array($value))
                                                        <pre>{{ json_encode($value, JSON_PRETTY_PRINT) }}</pre>
                                                    @else
                                                        {{ $value }}
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="2" class="text-center">No meta data available</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            No subscription data available. Please enter a valid subscription ID and click Load.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(isset($tests))
        <div class="row">
            <div class="col">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">Raw Test Results</h5>
                    </div>
                    <div class="card-body">
                        <pre>{{ json_encode($tests, JSON_PRETTY_PRINT) }}</pre>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
