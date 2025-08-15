@extends('layouts.app')

@section('title', 'Plesk Backup Management')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            @if(isset($error_message))
                <div class="alert alert-danger">
                    <strong>Error:</strong> {{ $error_message }}
                </div>
            @endif
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800">Plesk Backup Dashboard</h1>
                <button class="btn btn-primary" onclick="refreshBackupData()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>

            <!-- Status Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Backups
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $backup_summary['plesk_total'] ?? 0 }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-archive fa-2x text-gray-300"></i>
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
                                        Total Size
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ $backup_summary['total_plesk_size'] ?? '0 B' }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-hdd fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-{{ $backup_summary['health_status'] === 'healthy' ? 'success' : ($backup_summary['health_status'] === 'warning' ? 'warning' : 'danger') }} shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-{{ $backup_summary['health_status'] === 'healthy' ? 'success' : ($backup_summary['health_status'] === 'warning' ? 'warning' : 'danger') }} text-uppercase mb-1">
                                        Backup Health
                                    </div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        {{ ucfirst($backup_summary['health_status'] ?? 'Unknown') }}
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-heartbeat fa-2x text-gray-300"></i>
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
                                        Last Backup
                                    </div>
                                    <div class="h6 mb-0 font-weight-bold text-gray-800">
                                        @if(isset($plesk_status['last_backup']))
                                            {{ $plesk_status['last_backup']['created'] }}
                                        @else
                                            No recent backups
                                        @endif
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Backup Categories -->
            @if(isset($plesk_status['by_type']) && !empty($plesk_status['by_type']))
            <div class="row mb-4">
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Backup Types</h6>
                        </div>
                        <div class="card-body">
                            @foreach($plesk_status['by_type'] as $type => $data)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="badge badge-{{ $type === 'Extension' ? 'primary' : ($type === 'System' ? 'success' : ($type === 'Database' ? 'info' : 'secondary')) }}">
                                    {{ $type }}
                                </span>
                                <div class="text-right">
                                    <small class="text-muted">{{ $data['count'] }} files</small><br>
                                    <small class="text-muted">{{ number_format($data['size'] / 1024 / 1024, 1) }} MB</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Top Components</h6>
                        </div>
                        <div class="card-body">
                            @php
                                $topComponents = collect($plesk_status['by_component'] ?? [])->sortByDesc('count')->take(8);
                            @endphp
                            @foreach($topComponents as $component => $data)
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <code class="small">{{ $component }}</code>
                                <div class="text-right">
                                    <small class="text-muted">{{ $data['count'] }} files</small><br>
                                    <small class="text-muted">{{ number_format($data['size'] / 1024 / 1024, 1) }} MB</small>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Recent Backups Table -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Backup Files</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="backupsTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Component</th>
                                    <th>Type</th>
                                    <th>Created</th>
                                    <th>Size</th>
                                    <th>Extension</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($plesk_backups as $backup)
                                <tr>
                                    <td>
                                        <code class="small">{{ $backup['component'] }}</code>
                                        @if($backup['is_incremental'])
                                            <span class="badge badge-info badge-sm ml-1">Incremental</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $backup['type'] === 'Extension' ? 'primary' : ($backup['type'] === 'System' ? 'success' : ($backup['type'] === 'Database' ? 'info' : 'secondary')) }}">
                                            {{ $backup['type'] }}
                                        </span>
                                    </td>
                                    <td>{{ $backup['created'] }}</td>
                                    <td>{{ $backup['size_formatted'] }}</td>
                                    <td><code>.{{ $backup['extension'] }}</code></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" onclick="viewBackupDetails('{{ $backup['filename'] }}')">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-success" onclick="downloadBackup('{{ $backup['filename'] }}')">
                                            <i class="fas fa-download"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No backup files found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Backup Details Modal -->
<div class="modal fade" id="backupDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Backup Details</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="backupDetailsContent">
                <!-- Content loaded via AJAX -->
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#backupsTable').DataTable({
        "order": [[ 2, "desc" ]],
        "pageLength": 25,
        "responsive": true
    });
});

function refreshBackupData() {
    location.reload();
}

function viewBackupDetails(filename) {
    $('#backupDetailsContent').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading...</div>');
    $('#backupDetailsModal').modal('show');
    
    $.get(`/admin/plesk-backup/details/${filename}`)
        .done(function(data) {
            $('#backupDetailsContent').html(data);
        })
        .fail(function() {
            $('#backupDetailsContent').html('<div class="alert alert-danger">Failed to load backup details</div>');
        });
}

function downloadBackup(filename) {
    if (confirm('Are you sure you want to download this backup file? Large files may take time to download.')) {
        window.location.href = `/admin/plesk-backup/download/${filename}`;
    }
}
</script>
@endsection
