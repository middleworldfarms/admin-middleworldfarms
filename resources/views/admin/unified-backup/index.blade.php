@extends('layouts.app')

@section('title', 'Unified Backup Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-server"></i> Unified Backup Dashboard
                    </h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-success btn-sm" onclick="refreshStatus()">
                            <i class="fas fa-sync-alt"></i> Refresh
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner">
                                    <h3>{{ $summary['total_sites'] }}</h3>
                                    <p>Total Sites</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-globe"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ $summary['total_backups'] }}</h3>
                                    <p>Total Backups</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-archive"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $summary['total_size'] }}</h3>
                                    <p>Total Size</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-hdd"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $summary['last_backup'] }}</h3>
                                    <p>Last Backup</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Site Backups -->
                    <div class="row">
                        @foreach($sites as $siteName => $siteConfig)
                            @if($siteConfig['enabled'])
                                <div class="col-lg-6 col-12 mb-4">
                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="card-title">
                                                <i class="fas fa-server"></i> {{ $siteConfig['label'] }}
                                                <small class="text-muted">({{ $siteName }})</small>
                                            </h5>
                                            <div class="card-tools">
                                                @if($siteConfig['type'] === 'spatie')
                                                    <button type="button" class="btn btn-primary btn-sm"
                                                            onclick="createBackup('{{ $siteName }}')">
                                                        <i class="fas fa-plus"></i> Create Backup
                                                    </button>
                                                @elseif($siteConfig['type'] === 'plesk')
                                                    <button type="button" class="btn btn-success btn-sm"
                                                            onclick="createBackup('{{ $siteName }}')">
                                                        <i class="fas fa-plus"></i> Create Plesk Backup
                                                    </button>
                                                @else
                                                    <span class="badge badge-secondary">Remote API</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="card-body">
                                            @if(isset($backups[$siteName]) && count($backups[$siteName]) > 0)
                                                <div class="table-responsive">
                                                    <table class="table table-striped">
                                                        <thead>
                                                            <tr>
                                                                <th>Filename</th>
                                                                <th>Created</th>
                                                                <th>Size</th>
                                                                <th>Actions</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($backups[$siteName] as $backup)
                                                                <tr>
                                                                    <td>{{ $backup['filename'] }}</td>
                                                                    <td>{{ $backup['created'] }}</td>
                                                                    <td>{{ $backup['size_formatted'] }}</td>
                                                                    <td>
                                                                        <a href="{{ route('admin.unified-backup.download', [$siteName, $backup['filename']]) }}"
                                                                           class="btn btn-sm btn-info">
                                                                            <i class="fas fa-download"></i>
                                                                        </a>
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="text-center text-muted">
                                                    <i class="fas fa-archive fa-3x mb-3"></i>
                                                    <p>No backups found</p>
                                                    @if($siteConfig['type'] === 'spatie')
                                                        <button type="button" class="btn btn-primary"
                                                                onclick="createBackup('{{ $siteName }}')">
                                                            Create First Backup
                                                        </button>
                                                    @elseif($siteConfig['type'] === 'plesk')
                                                        <button type="button" class="btn btn-success"
                                                                onclick="createBackup('{{ $siteName }}')">
                                                            Create First Plesk Backup
                                                        </button>
                                                    @endif
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function createBackup(siteName) {
    if (!confirm('Create backup for ' + siteName + '?')) {
        return;
    }

    // Show loading state
    const button = event.target;
    const originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
    button.disabled = true;

    fetch('{{ route("admin.unified-backup.create") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            site: siteName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Backup created successfully!');
            location.reload();
        } else {
            alert('Backup failed: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error creating backup: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalHtml;
        button.disabled = false;
    });
}

function refreshStatus() {
    location.reload();
}
</script>
@endsection
