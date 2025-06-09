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

<style>
    .activity-icon {
        width: 35px;
        height: 35px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
    }
</style>
@endsection
