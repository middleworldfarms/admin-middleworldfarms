@extends('layouts.app')

@section('title', 'Safe Backup Management')

@section('content')
<style>
    .backup-card {
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
        background: white;
        transition: all 0.3s;
        padding: 5px;
    }
    
    .backup-card:hover {
        box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
    }
    
    .status-indicator {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
        margin-right: 8px;
    }
    
    .status-success { background-color: #1cc88a; }
    .status-warning { background-color: #f6c23e; }
    .status-danger { background-color: #e74a3b; }
    
    .backup-log {
        background: #f8f9fc;
        border: 1px solid #e3e6f0;
        border-radius: 0.35rem;
        max-height: 300px;
        overflow-y: auto;
        font-family: 'Courier New', monospace;
        font-size: 0.8rem;
        padding: 1.25rem;
        margin: 5px;
    }
    
    .backup-file {
        padding: 0.75rem;
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s;
        margin: 5px 0;
    }
    
    .backup-file:hover {
        background-color: #f8f9fa;
    }
    
    .backup-file:last-child {
        border-bottom: none;
    }
    
    .action-btn {
        margin: 0.25rem;
        padding: 0.5rem 1rem;
    }
    
    .card-body {
        padding: 1.25rem !important;
    }
    
    .card-header {
        padding: 0.75rem 1.25rem !important;
    }
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-0 text-gray-800">
                <i class="fas fa-shield-alt text-success me-2"></i>Safe Backup Management
            </h1>
            <p class="text-muted">Secure, isolated backup system - no cross-site contamination possible</p>
        </div>
        <div>
            <button id="runBackupBtn" class="btn btn-success btn-lg">
                <i class="fas fa-play"></i> Run Backup Now
            </button>
        </div>
    </div>

    <!-- Status Overview -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="backup-card shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">System Status</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <span class="status-indicator {{ $status['backup_script_exists'] && $status['backup_directory_exists'] ? 'status-success' : 'status-danger' }}"></span>
                                {{ $status['backup_script_exists'] && $status['backup_directory_exists'] ? 'Operational' : 'Error' }}
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cog fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="backup-card shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Backups</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $status['backup_count'] ?? 0 }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-archive fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="backup-card shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Storage Used</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $status['total_backup_size'] ?? '0B' }}</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hdd fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="backup-card shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Disk Free</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $status['disk_usage']['free'] ?? 'Unknown' }}</div>
                            <div class="text-xs text-muted">{{ $status['disk_usage']['percentage'] ?? 0 }}% used</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-database fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Backup Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="backup-card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Quick Actions</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <p class="text-muted mb-3">
                                <i class="fas fa-shield-alt text-success"></i>
                                <strong>Safe Backup System:</strong> Each site is backed up in complete isolation. 
                                No risk of cross-contamination between sites.
                            </p>
                            <div class="mb-3">
                                <span class="status-indicator {{ $status['cron_scheduled'] ? 'status-success' : 'status-warning' }}"></span>
                                <strong>Automated Backups:</strong> 
                                {{ $status['cron_scheduled'] ? 'Scheduled daily at 2:00 AM' : 'Not scheduled' }}
                            </div>
                        </div>
                        <div class="col-md-4 text-right">
                            <button id="refreshStatusBtn" class="btn btn-outline-primary action-btn">
                                <i class="fas fa-sync"></i> Refresh Status
                            </button>
                            <button id="cleanBackupsBtn" class="btn btn-outline-warning action-btn">
                                <i class="fas fa-broom"></i> Clean Old Backups
                            </button>
                            <button id="viewLogsBtn" class="btn btn-outline-info action-btn">
                                <i class="fas fa-file-alt"></i> View Logs
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Site Backups -->
    <div class="row">
        @foreach($backupSummary as $siteName => $siteData)
        <div class="col-lg-6 mb-4">
            <div class="backup-card shadow h-100">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        @if($siteName === 'admin.middleworldfarms.org')
                            <i class="fas fa-tachometer-alt"></i> Admin Dashboard
                        @elseif($siteName === 'middleworldfarms.org') 
                            <i class="fab fa-wordpress"></i> WordPress Site
                        @elseif($siteName === 'farmos.middleworldfarms.org')
                            <i class="fas fa-seedling"></i> FarmOS Site
                        @else
                            <i class="fas fa-database"></i> {{ ucfirst($siteName) }}
                        @endif
                    </h6>
                    <span class="badge badge-{{ $siteData['count'] > 0 ? 'success' : 'secondary' }}">
                        {{ $siteData['count'] }} backup{{ $siteData['count'] !== 1 ? 's' : '' }}
                    </span>
                </div>
                <div class="card-body">
                    @if($siteData['count'] > 0)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <small class="text-muted">Latest backup:</small><br>
                                    <strong>{{ $siteData['latest']['name'] ?? 'Unknown' }}</strong><br>
                                    <small class="text-info">{{ $siteData['latest']['size'] ?? '0B' }} • {{ $siteData['latest']['age'] ?? 'Unknown age' }}</small>
                                </div>
                                <div class="btn-group-vertical" role="group">
                                    <button class="btn btn-sm btn-success" 
                                            onclick="downloadBackup('{{ $siteName }}', '{{ $siteData['latest']['name'] ?? '' }}')"
                                            title="Download Latest">
                                        <i class="fas fa-download"></i>
                                    </button>
                                    @if($siteName !== 'databases')
                                    <button class="btn btn-sm btn-warning" 
                                            onclick="restoreBackup('{{ $siteName }}', '{{ $siteData['latest']['name'] ?? '' }}')"
                                            title="Restore Latest">
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">Total storage: <strong>{{ $siteData['total_size'] }}</strong></small>
                        </div>
                        
                        @if(count($siteData['backups']) > 1)
                        <details>
                            <summary class="text-primary" style="cursor: pointer;">
                                <small>View all {{ count($siteData['backups']) }} backups</small>
                            </summary>
                            <div class="mt-2">
                                @foreach($siteData['backups'] as $backup)
                                <div class="backup-file">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="flex-grow-1">
                                            <strong>{{ $backup['name'] }}</strong><br>
                                            <small class="text-muted">{{ $backup['date'] }} • {{ $backup['size'] }}</small>
                                        </div>
                                        <div class="d-flex align-items-center">
                                            <span class="badge badge-light me-3">{{ $backup['age'] }}</span>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-success" 
                                                        onclick="downloadBackup('{{ $siteName }}', '{{ $backup['name'] }}')"
                                                        title="Download Backup">
                                                    <i class="fas fa-download"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-primary" 
                                                        onclick="renameBackup('{{ $siteName }}', '{{ $backup['name'] }}')"
                                                        title="Rename Backup">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                @if($siteName !== 'databases')
                                                <button class="btn btn-sm btn-outline-warning" 
                                                        onclick="restoreBackup('{{ $siteName }}', '{{ $backup['name'] }}')"
                                                        title="Restore Backup">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                                @endif
                                                <button class="btn btn-sm btn-outline-danger" 
                                                        onclick="deleteBackup('{{ $siteName }}', '{{ $backup['name'] }}')"
                                                        title="Delete Backup">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </details>
                        @endif
                    @else
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                            <p>No backups found for this site</p>
                            <small>Run a backup to create the first backup for {{ $siteName }}</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Recent Logs -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="backup-card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Recent Backup Activity</h6>
                </div>
                <div class="card-body">
                    <div id="backupLogs" class="backup-log">
                        @if(count($recentLogs) > 0)
                            @foreach($recentLogs as $log)
                                <div>{{ $log }}</div>
                            @endforeach
                        @else
                            <div class="text-muted">No backup activity logged yet.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Rename Modal -->
<div class="modal fade" id="renameModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rename Backup</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Rename backup: <strong id="renameBackupName"></strong></p>
                <div class="mb-3">
                    <label for="newBackupName" class="form-label">New Name:</label>
                    <input type="text" class="form-control" id="newBackupName" placeholder="Enter new backup name">
                    <small class="form-text text-muted">Include .tar.gz or .sql.gz extension</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmRename">Rename</button>
            </div>
        </div>
    </div>
</div>

<!-- Restore Confirmation Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-warning">
                    <i class="fas fa-exclamation-triangle"></i> Restore Backup
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <strong>⚠️ WARNING:</strong> This will overwrite the current site files!
                </div>
                <p>You are about to restore:</p>
                <ul>
                    <li><strong>Site:</strong> <span id="restoreSiteName"></span></li>
                    <li><strong>Backup:</strong> <span id="restoreBackupName"></span></li>
                </ul>
                <p>This action cannot be undone. Make sure you have a recent backup of the current state.</p>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="confirmRestore">
                    <label class="form-check-label" for="confirmRestore">
                        I understand this will overwrite existing files
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmRestoreBtn" disabled>
                    <i class="fas fa-undo"></i> Restore Site
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-trash"></i> Delete Backup
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-danger">
                    <strong>🗑️ PERMANENT DELETION:</strong> This backup will be permanently deleted!
                </div>
                <p>You are about to delete:</p>
                <ul>
                    <li><strong>Site:</strong> <span id="deleteSiteName"></span></li>
                    <li><strong>Backup:</strong> <span id="deleteBackupName"></span></li>
                </ul>
                <p>This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="fas fa-trash"></i> Delete Permanently
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast notifications -->
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div id="successToast" class="toast" role="alert">
        <div class="toast-header">
            <i class="fas fa-check-circle text-success me-2"></i>
            <strong class="me-auto">Success</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="successMessage">
            Operation completed successfully.
        </div>
    </div>
    
    <div id="errorToast" class="toast" role="alert">
        <div class="toast-header">
            <i class="fas fa-exclamation-triangle text-danger me-2"></i>
            <strong class="me-auto">Error</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="errorMessage">
            An error occurred.
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Run backup button
    document.getElementById('runBackupBtn').addEventListener('click', function() {
        const btn = this;
        const originalText = btn.innerHTML;
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Running Backup...';
        
        fetch('{{ route("admin.safe-backup.run") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('successToast', 'Backup started successfully! Check logs for progress.');
                // Refresh the page after a delay to show updated status
                setTimeout(() => location.reload(), 3000);
            } else {
                showToast('errorToast', data.message || 'Failed to start backup');
            }
        })
        .catch(error => {
            showToast('errorToast', 'Network error: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = originalText;
        });
    });

    // Clean backups button
    document.getElementById('cleanBackupsBtn').addEventListener('click', function() {
        if (!confirm('Are you sure you want to clean old backups? This will remove all but the last 5 backups per site.')) {
            return;
        }
        
        const btn = this;
        btn.disabled = true;
        
        fetch('{{ route("admin.safe-backup.clean") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('successToast', 'Old backups cleaned successfully!');
                setTimeout(() => location.reload(), 2000);
            } else {
                showToast('errorToast', data.message || 'Failed to clean backups');
            }
        })
        .catch(error => {
            showToast('errorToast', 'Network error: ' + error.message);
        })
        .finally(() => {
            btn.disabled = false;
        });
    });

    // Refresh status button
    document.getElementById('refreshStatusBtn').addEventListener('click', function() {
        location.reload();
    });

    // View logs button
    document.getElementById('viewLogsBtn').addEventListener('click', function() {
        fetch('{{ route("admin.safe-backup.logs") }}')
        .then(response => response.json())
        .then(data => {
            const logsContainer = document.getElementById('backupLogs');
            logsContainer.innerHTML = data.logs.length > 0 
                ? data.logs.map(log => `<div>${log}</div>`).join('')
                : '<div class="text-muted">No logs available.</div>';
        })
        .catch(error => {
            showToast('errorToast', 'Failed to load logs: ' + error.message);
        });
    });

    // Restore confirmation checkbox handler
    document.getElementById('confirmRestore').addEventListener('change', function() {
        document.getElementById('confirmRestoreBtn').disabled = !this.checked;
    });

    function showToast(toastId, message) {
        const toast = document.getElementById(toastId);
        const messageElement = toastId === 'successToast' ? 
            document.getElementById('successMessage') : 
            document.getElementById('errorMessage');
        
        messageElement.textContent = message;
        
        // Initialize and show toast (Bootstrap 5 syntax)
        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }
});

// Global variables for modal data
let currentSite = '';
let currentBackup = '';

// Download backup function
function downloadBackup(siteName, backupName) {
    const url = `{{ route('admin.safe-backup.download', ['siteName' => '__SITE__', 'backupName' => '__BACKUP__']) }}`
        .replace('__SITE__', siteName)
        .replace('__BACKUP__', backupName);
    window.open(url, '_blank');
}

// Rename backup function
function renameBackup(siteName, backupName) {
    currentSite = siteName;
    currentBackup = backupName;
    
    document.getElementById('renameBackupName').textContent = backupName;
    document.getElementById('newBackupName').value = backupName;
    
    const modal = new bootstrap.Modal(document.getElementById('renameModal'));
    modal.show();
}

// Restore backup function
function restoreBackup(siteName, backupName) {
    currentSite = siteName;
    currentBackup = backupName;
    
    document.getElementById('restoreSiteName').textContent = siteName;
    document.getElementById('restoreBackupName').textContent = backupName;
    document.getElementById('confirmRestore').checked = false;
    document.getElementById('confirmRestoreBtn').disabled = true;
    
    const modal = new bootstrap.Modal(document.getElementById('restoreModal'));
    modal.show();
}

// Delete backup function
function deleteBackup(siteName, backupName) {
    currentSite = siteName;
    currentBackup = backupName;
    
    document.getElementById('deleteSiteName').textContent = siteName;
    document.getElementById('deleteBackupName').textContent = backupName;
    
    const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}

// Modal action handlers
document.addEventListener('DOMContentLoaded', function() {
    // Rename confirmation
    document.getElementById('confirmRename').addEventListener('click', function() {
        const newName = document.getElementById('newBackupName').value.trim();
        if (!newName) {
            showToast('errorToast', 'Please enter a new name');
            return;
        }
        
        performBackupAction('rename', {
            site: currentSite,
            oldName: currentBackup,
            newName: newName
        });
    });
    
    // Restore confirmation
    document.getElementById('confirmRestoreBtn').addEventListener('click', function() {
        performBackupAction('restore', {
            site: currentSite,
            backup: currentBackup
        });
    });
    
    // Delete confirmation
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        performBackupAction('delete', {
            site: currentSite,
            backup: currentBackup
        });
    });
});

// Perform backup actions
function performBackupAction(action, data) {
    const routes = {
        rename: '{{ route("admin.safe-backup.rename") }}',
        restore: '{{ route("admin.safe-backup.restore") }}',
        delete: '{{ route("admin.safe-backup.delete") }}'
    };
    
    fetch(routes[action], {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            showToast('successToast', result.message);
            // Close modal
            const modalElement = document.querySelector('.modal.show');
            if (modalElement) {
                const modal = bootstrap.Modal.getInstance(modalElement);
                modal.hide();
            }
            // Refresh page after short delay
            setTimeout(() => location.reload(), 2000);
        } else {
            showToast('errorToast', result.message || `Failed to ${action} backup`);
        }
    })
    .catch(error => {
        showToast('errorToast', `Network error: ${error.message}`);
    });
}
</script>
@endsection
