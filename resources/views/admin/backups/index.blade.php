@extends('layouts.app')

@section('title', 'Backup Management')

@section('styles')
<style>
    .backup-card {
        border: 1px solid #e0e0e0;
        border-radius: 8px;
        transition: all 0.2s ease;
    }
    
    .backup-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        border-color: #27ae60;
    }
    
    .backup-name {
        font-weight: 600;
        color: #2c3e50;
    }
    
    .backup-size {
        color: #7f8c8d;
        font-size: 0.9rem;
    }
    
    .backup-date {
        color: #95a5a6;
        font-size: 0.85rem;
    }
    
    .backup-actions {
        gap: 8px;
    }
    
    .schedule-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-left: 4px solid #27ae60;
    }
    
    .create-backup-btn {
        background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
        border: none;
        padding: 12px 24px;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .create-backup-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(39, 174, 96, 0.3);
    }
    
    .create-backup-btn:disabled {
        opacity: 0.6;
        transform: none;
        box-shadow: none;
    }
    
    .spinner-border-sm {
        width: 1rem;
        height: 1rem;
    }
    
    .backup-progress {
        display: none;
    }
    
    .backup-progress.show {
        display: block;
    }
    
    .custom-file-name {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 0.9rem;
    }
    
    .schedule-frequency select {
        background-color: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        padding: 8px 12px;
    }
    
    .backup-status {
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 0.8rem;
        font-weight: 500;
    }
    
    .status-success {
        background-color: #d4edda;
        color: #155724;
    }
    
    .status-warning {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .status-error {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    /* Fix modal z-index issues */
    .modal {
        z-index: 9999 !important;
    }
    
    .modal-backdrop {
        z-index: 9998 !important;
    }
    
    .modal-dialog {
        z-index: 10000 !important;
        position: relative;
    }
    
    /* Ensure modal content is clickable */
    .modal-content {
        position: relative;
        z-index: 10001 !important;
        pointer-events: auto !important;
        background-color: #fff;
        border-radius: 0.375rem;
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    }
    
    /* Ensure buttons in modal are clickable */
    .modal-footer .btn {
        z-index: 10002 !important;
        position: relative;
        pointer-events: auto !important;
    }
    
    /* Override any sidebar z-index conflicts */
    .sidebar {
        z-index: 1050 !important;
    }
</style>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">
                    <i class="fas fa-database text-primary me-2"></i>
                    Backup Management
                </h1>
                <div class="d-flex gap-2">
                    <button class="btn btn-info" id="uploadBackupBtn">
                        <i class="fas fa-upload me-2"></i>
                        Upload Backup
                    </button>
                    <button class="btn btn-success create-backup-btn" id="createBackupBtn">
                        <span class="btn-text">
                            <i class="fas fa-plus me-2"></i>
                            Create Backup
                        </span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </div>

            <!-- Backup Schedule Configuration -->
            <div class="card schedule-card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-clock me-2"></i>
                        Automatic Backup Schedule
                    </h5>
                </div>
                <div class="card-body">
                    <form id="scheduleForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-4">
                                <label for="frequency" class="form-label">Backup Frequency</label>
                                <select class="form-select schedule-frequency" id="frequency" name="frequency">
                                    <option value="disabled" selected>Disabled</option>
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly">Monthly</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="time" class="form-label">Backup Time</label>
                                <input type="time" class="form-control" id="time" name="time" value="02:00">
                            </div>
                            <div class="col-md-4">
                                <label for="retention" class="form-label">Keep Backups (days)</label>
                                <input type="number" class="form-control" id="retention" name="retention" value="30" min="1" max="365">
                            </div>
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>
                                Update Schedule
                            </button>
                            <small class="text-muted ms-3">
                                <i class="fas fa-info-circle me-1"></i>
                                Next scheduled backup: <span id="nextBackup">None scheduled</span>
                            </small>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Manual Backup Creation -->
            <div class="card mb-4" id="createBackupCard" style="display: none;">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus me-2"></i>
                        Create Manual Backup
                    </h5>
                </div>
                <div class="card-body">
                    <form id="manualBackupForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-8">
                                <label for="customName" class="form-label">Backup Name (optional)</label>
                                <input type="text" class="form-control custom-file-name" id="customName" name="custom_name" 
                                       placeholder="Leave empty for auto-generated name">
                                <small class="text-muted">
                                    Auto format: backup_YYYY-MM-DD_HH-MM-SS.zip
                                </small>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-success w-100">
                                    <i class="fas fa-database me-2"></i>
                                    Create Backup Now
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="backup-progress mt-3" id="backupProgress">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <i class="fas fa-cog fa-spin me-1"></i>
                            Creating backup... This may take a few minutes.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Upload Backup -->
            <div class="card mb-4" id="uploadBackupCard" style="display: none;">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-upload me-2"></i>
                        Upload Backup
                    </h5>
                </div>
                <div class="card-body">
                    <form id="uploadBackupForm">
                        @csrf
                        <div class="row">
                            <div class="col-md-8">
                                <label for="backupFile" class="form-label">Select Backup File</label>
                                <input type="file" class="form-control" id="backupFile" name="backup_file" 
                                       accept=".zip" required>
                                <small class="text-muted">
                                    Only ZIP files are supported. Maximum size: 500MB
                                </small>
                            </div>
                            <div class="col-md-4 d-flex align-items-end">
                                <button type="submit" class="btn btn-info w-100">
                                    <i class="fas fa-upload me-2"></i>
                                    Upload Backup
                                </button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="backup-progress mt-3" id="uploadProgress">
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-info" 
                                 role="progressbar" style="width: 0%"></div>
                        </div>
                        <small class="text-muted mt-2 d-block">
                            <i class="fas fa-upload fa-spin me-1"></i>
                            Uploading backup... Please wait.
                        </small>
                    </div>
                </div>
            </div>

            <!-- Existing Backups -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-archive me-2"></i>
                        Existing Backups
                        <span class="badge bg-primary ms-2" id="backupCount">Loading...</span>
                    </h5>
                </div>
                <div class="card-body">
                    <div id="backupsList">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2 text-muted">Loading backups...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<!-- Modals moved outside main content to avoid z-index conflicts -->
<!-- Rename Modal -->
<div class="modal fade" id="renameModal" tabindex="-1" aria-labelledby="renameModalLabel" aria-hidden="true" style="z-index: 10000;">
    <div class="modal-dialog" style="z-index: 10001;">
        <div class="modal-content" style="z-index: 10002;">
            <div class="modal-header">
                <h5 class="modal-title" id="renameModalLabel">Rename Backup</h5>
                <button type="button" class="btn-close" onclick="closeRenameModal()" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="renameForm">
                    @csrf
                    <input type="hidden" id="renameFilename" name="filename">
                    <div class="mb-3">
                        <label for="newName" class="form-label">New Name</label>
                        <input type="text" class="form-control" id="newName" name="new_name" required>
                        <small class="text-muted">Don't include .zip extension</small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRenameModal()">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmRename">Rename</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true" style="z-index: 10000;">
    <div class="modal-dialog" style="z-index: 10001;">
        <div class="modal-content" style="z-index: 10002;">
            <div class="modal-header">
                <h5 class="modal-title text-danger" id="deleteModalLabel">Delete Backup</h5>
                <button type="button" class="btn-close" onclick="closeDeleteModal()" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this backup?</p>
                <p class="fw-bold" id="deleteBackupName"></p>
                <p class="text-danger">
                    <i class="fas fa-exclamation-triangle me-1"></i>
                    This action cannot be undone!
                </p>
                <input type="hidden" id="deleteFilename">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash me-2"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true" style="z-index: 10000;">
    <div class="modal-dialog modal-lg" style="z-index: 10001;">
        <div class="modal-content" style="z-index: 10002;">
            <div class="modal-header">
                <h5 class="modal-title" id="previewModalLabel">Backup Preview</h5>
                <button type="button" class="btn-close" onclick="closePreviewModal()" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">Loading backup information...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closePreviewModal()">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Restore Modal -->
<div class="modal fade" id="restoreModal" tabindex="-1" aria-labelledby="restoreModalLabel" aria-hidden="true" style="z-index: 10000;">
    <div class="modal-dialog modal-lg" style="z-index: 10001;">
        <div class="modal-content" style="z-index: 10002;">
            <div class="modal-header">
                <h5 class="modal-title text-warning" id="restoreModalLabel">Restore Backup</h5>
                <button type="button" class="btn-close" onclick="closeRestoreModal()" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> Restoring will overwrite your current data. This action cannot be undone!
                </div>
                
                <h6>Backup to restore:</h6>
                <p class="fw-bold" id="restoreBackupName"></p>
                
                <h6>Restore Options:</h6>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="restoreDatabase" checked>
                    <label class="form-check-label" for="restoreDatabase">
                        Restore Database
                        <small class="text-muted d-block">This will replace all current database data</small>
                    </label>
                </div>
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="restoreFiles">
                    <label class="form-check-label" for="restoreFiles">
                        Restore Application Files
                        <small class="text-muted d-block">This will replace app/, config/, routes/ directories</small>
                    </label>
                </div>
                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="createBackupBeforeRestore" checked>
                    <label class="form-check-label" for="createBackupBeforeRestore">
                        Create backup before restore
                        <small class="text-muted d-block">Recommended: backup current state first</small>
                    </label>
                </div>
                
                <input type="hidden" id="restoreFilename">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeRestoreModal()">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmRestore">
                    <i class="fas fa-history me-2"></i>Restore Now
                </button>
            </div>
        </div>
    </div>
</div>

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    let backups = [];

    // Load initial data
    loadBackups();
    loadScheduleStatus();

    // Toggle create backup form
    document.getElementById('createBackupBtn').addEventListener('click', function() {
        const card = document.getElementById('createBackupCard');
        const isVisible = card.style.display !== 'none';
        card.style.display = isVisible ? 'none' : 'block';
        
        if (!isVisible) {
            document.getElementById('customName').focus();
        }
    });

    // Toggle upload backup form
    document.getElementById('uploadBackupBtn').addEventListener('click', function() {
        const card = document.getElementById('uploadBackupCard');
        const isVisible = card.style.display !== 'none';
        card.style.display = isVisible ? 'none' : 'block';
        
        if (!isVisible) {
            document.getElementById('backupFile').focus();
        }
    });

    // Handle manual backup creation
    document.getElementById('manualBackupForm').addEventListener('submit', function(e) {
        e.preventDefault();
        createBackup();
    });

    // Handle schedule update
    document.getElementById('scheduleForm').addEventListener('submit', function(e) {
        e.preventDefault();
        updateSchedule();
    });

    // Handle rename confirmation
    document.getElementById('confirmRename').addEventListener('click', function() {
        renameBackup();
    });

    // Handle delete confirmation
    document.getElementById('confirmDelete').addEventListener('click', function() {
        deleteBackup();
    });

    // Handle upload backup
    document.getElementById('uploadBackupForm').addEventListener('submit', function(e) {
        e.preventDefault();
        uploadBackup();
    });

    // Handle restore confirmation
    document.getElementById('confirmRestore').addEventListener('click', function() {
        restoreBackup();
    });

    function loadBackups() {
        fetch(window.location.origin + '/admin/backups', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            backups = data.backups || [];
            renderBackups();
        })
        .catch(error => {
            console.error('Error loading backups:', error);
            document.getElementById('backupsList').innerHTML = 
                '<div class="alert alert-danger">Error loading backups: ' + error.message + '</div>';
        });
    }

    function renderBackups() {
        const container = document.getElementById('backupsList');
        const countBadge = document.getElementById('backupCount');
        
        countBadge.textContent = backups.length;

        if (backups.length === 0) {
            container.innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-archive fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">No backups found</h5>
                    <p class="text-muted">Create your first backup to get started</p>
                </div>
            `;
            return;
        }

        const html = backups.map(backup => `
            <div class="backup-card p-3 mb-3">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <div class="backup-name">${backup.filename}</div>
                        <div class="backup-size">Size: ${backup.size}</div>
                        <div class="backup-date">Created: ${new Date(backup.created_at).toLocaleString()}</div>
                    </div>
                    <div class="col-md-3">
                        <span class="backup-status status-success">
                            <i class="fas fa-check-circle me-1"></i>
                            Complete
                        </span>
                    </div>
                    <div class="col-md-3">
                        <div class="d-flex backup-actions justify-content-end">
                            <button class="btn btn-sm btn-outline-info me-1" onclick="previewBackup('${backup.filename}'); event.stopPropagation();" 
                                    title="Preview Contents" type="button">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-warning me-1" onclick="openRestoreModal('${backup.filename}', '${backup.filename.replace(/'/g, "\\'")}'); event.stopPropagation();" 
                                    title="Restore" type="button">
                                <i class="fas fa-history"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-primary me-1" onclick="downloadBackup('${backup.filename}'); event.stopPropagation();" 
                                    title="Download" type="button">
                                <i class="fas fa-download"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger" onclick="openDeleteModal('${backup.filename}', '${backup.filename.replace(/'/g, "\\'")}'); event.stopPropagation();" 
                                    title="Delete" type="button">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `).join('');

        container.innerHTML = html;
    }

    function createBackup() {
        const form = document.getElementById('manualBackupForm');
        const formData = new FormData(form);
        const progress = document.getElementById('backupProgress');
        const progressBar = progress.querySelector('.progress-bar');
        
        progress.classList.add('show');
        progressBar.style.width = '20%';

        fetch(window.location.origin + '/admin/backups/create', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            progressBar.style.width = '100%';
            
            setTimeout(() => {
                progress.classList.remove('show');
                if (data.success) {
                    showAlert('Backup created successfully!', 'success');
                    form.reset();
                    document.getElementById('createBackupCard').style.display = 'none';
                    loadBackups();
                } else {
                    showAlert(data.message || 'Error creating backup', 'danger');
                }
            }, 1000);
        })
        .catch(error => {
            progress.classList.remove('show');
            showAlert('Error creating backup', 'danger');
            console.error('Error:', error);
        });
    }

    function updateSchedule() {
        const form = document.getElementById('scheduleForm');
        const formData = new FormData(form);
        
        fetch(window.location.origin + '/admin/backups/schedule', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('Schedule updated successfully!', 'success');
                loadScheduleStatus();
            } else {
                showAlert(data.message || 'Error updating schedule', 'danger');
            }
        })
        .catch(error => {
            showAlert('Error updating schedule', 'danger');
            console.error('Error:', error);
        });
    }

    function loadScheduleStatus() {
        fetch(window.location.origin + '/admin/backups/status', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('frequency').value = data.frequency || 'disabled';
            document.getElementById('time').value = data.time || '02:00';
            document.getElementById('retention').value = data.retention || 30;
            
            const nextBackup = document.getElementById('nextBackup');
            if (data.next_backup) {
                nextBackup.textContent = new Date(data.next_backup).toLocaleString();
            } else {
                nextBackup.textContent = 'None scheduled';
            }
        })
        .catch(error => {
            console.error('Error loading schedule status:', error);
        });
    }

    // Global functions for button actions
    window.downloadBackup = function(filename) {
        window.location.href = window.location.origin + `/admin/backups/download/${encodeURIComponent(filename)}`;
    };

    window.previewBackup = function(filename) {
        console.log('Previewing backup:', filename);
        
        // Show modal with loading state
        const modalElement = document.getElementById('previewModal');
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.style.zIndex = '9998';
        
        modalElement.style.display = 'block';
        modalElement.style.zIndex = '10000';
        modalElement.classList.add('show');
        document.body.appendChild(backdrop);
        document.body.classList.add('modal-open');
        
        // Load backup info
        fetch(window.location.origin + `/admin/backups/preview/${encodeURIComponent(filename)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                renderBackupPreview(data.info);
            } else {
                document.getElementById('previewContent').innerHTML = 
                    '<div class="alert alert-danger">Error loading backup info: ' + data.message + '</div>';
            }
        })
        .catch(error => {
            document.getElementById('previewContent').innerHTML = 
                '<div class="alert alert-danger">Error loading backup info: ' + error.message + '</div>';
        });
    };

    window.closePreviewModal = function() {
        const modalElement = document.getElementById('previewModal');
        const backdrop = document.querySelector('.modal-backdrop');
        
        modalElement.style.display = 'none';
        modalElement.classList.remove('show');
        if (backdrop) backdrop.remove();
        document.body.classList.remove('modal-open');
    };

    window.openRestoreModal = function(filename, name) {
        console.log('Opening restore modal for:', filename);
        document.getElementById('restoreFilename').value = filename;
        document.getElementById('restoreBackupName').textContent = name;
        
        const modalElement = document.getElementById('restoreModal');
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.style.zIndex = '9998';
        
        modalElement.style.display = 'block';
        modalElement.style.zIndex = '10000';
        modalElement.classList.add('show');
        document.body.appendChild(backdrop);
        document.body.classList.add('modal-open');
        
        // Add click handler to backdrop to close modal
        backdrop.addEventListener('click', function() {
            closeRestoreModal();
        });
    };
    
    window.closeRestoreModal = function() {
        const modalElement = document.getElementById('restoreModal');
        const backdrop = document.querySelector('.modal-backdrop');
        
        modalElement.style.display = 'none';
        modalElement.classList.remove('show');
        if (backdrop) backdrop.remove();
        document.body.classList.remove('modal-open');
    };

    window.openRenameModal = function(filename, name) {
        document.getElementById('renameFilename').value = filename;
        document.getElementById('newName').value = '';
        document.getElementById('renameModalLabel').textContent = 'Rename Backup: ' + name;
        const modalElement = document.getElementById('renameModal');
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.style.zIndex = '9998';
        modalElement.style.display = 'block';
        modalElement.style.zIndex = '10000';
        modalElement.classList.add('show');
        document.body.appendChild(backdrop);
        document.body.classList.add('modal-open');
        backdrop.addEventListener('click', function() { closeRenameModal(); });
    };
    window.closeRenameModal = function() {
        const modalElement = document.getElementById('renameModal');
        const backdrop = document.querySelector('.modal-backdrop');
        modalElement.style.display = 'none';
        modalElement.classList.remove('show');
        if (backdrop) backdrop.remove();
        document.body.classList.remove('modal-open');
    };
    window.openDeleteModal = function(filename, name) {
        document.getElementById('deleteFilename').value = filename;
        document.getElementById('deleteBackupName').textContent = name;
        const modalElement = document.getElementById('deleteModal');
        const backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.style.zIndex = '9998';
        modalElement.style.display = 'block';
        modalElement.style.zIndex = '10000';
        modalElement.classList.add('show');
        document.body.appendChild(backdrop);
        document.body.classList.add('modal-open');
        backdrop.addEventListener('click', function() { closeDeleteModal(); });
    };
    window.closeDeleteModal = function() {
        const modalElement = document.getElementById('deleteModal');
        const backdrop = document.querySelector('.modal-backdrop');
        modalElement.style.display = 'none';
        modalElement.classList.remove('show');
        if (backdrop) backdrop.remove();
        document.body.classList.remove('modal-open');
    };

    function renameBackup() {
        const filename = document.getElementById('renameFilename').value;
        const newName = document.getElementById('newName').value;
        const renameBtn = document.getElementById('confirmRename');
        renameBtn.disabled = true;
        renameBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Renaming...';
        const formData = new FormData();
        formData.append('new_name', newName);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        fetch(window.location.origin + `/admin/backups/rename/${encodeURIComponent(filename)}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(response => response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                showAlert('Rename response error: ' + text, 'danger');
                throw new Error('Invalid JSON response: ' + text);
            }
        }))
        .then(data => {
            if (data.success) {
                showAlert('Backup renamed successfully!', 'success');
                closeRenameModal();
            } else {
                showAlert(data.message || 'Error renaming backup', 'danger');
            }
            loadBackups();
        })
        .catch(error => {
            console.error('Rename error:', error);
            showAlert('Error renaming backup: ' + error.message, 'danger');
            loadBackups();
        })
        .finally(() => {
            renameBtn.disabled = false;
            renameBtn.innerHTML = 'Rename';
        });
    }

    function deleteBackup() {
        const filename = document.getElementById('deleteFilename').value;
        const deleteBtn = document.getElementById('confirmDelete');
        deleteBtn.disabled = true;
        deleteBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Deleting...';
        fetch(window.location.origin + `/admin/backups/delete/${encodeURIComponent(filename)}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                showAlert('Delete response error: ' + text, 'danger');
                throw new Error('Invalid JSON response: ' + text);
            }
        }))
        .then(data => {
            if (data.success) {
                showAlert('Backup deleted successfully!', 'success');
                closeDeleteModal();
            } else {
                showAlert(data.message || 'Error deleting backup', 'danger');
            }
            loadBackups();
        })
        .catch(error => {
            showAlert('Error deleting backup: ' + error.message, 'danger');
            console.error('Error:', error);
            loadBackups();
        })
        .finally(() => {
            deleteBtn.disabled = false;
            deleteBtn.innerHTML = '<i class="fas fa-trash me-2"></i>Delete';
        });
    }

    function uploadBackup() {
        const form = document.getElementById('uploadBackupForm');
        const formData = new FormData(form);
        const progress = document.getElementById('uploadProgress');
        const progressBar = progress.querySelector('.progress-bar');
        
        progress.classList.add('show');
        progressBar.style.width = '20%';

        fetch(window.location.origin + '/admin/backups/upload', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            progressBar.style.width = '100%';
            
            setTimeout(() => {
                progress.classList.remove('show');
                if (data.success) {
                    showAlert('Backup uploaded successfully!', 'success');
                    form.reset();
                    document.getElementById('uploadBackupCard').style.display = 'none';
                    loadBackups();
                } else {
                    showAlert(data.message || 'Error uploading backup', 'danger');
                }
            }, 1000);
        })
        .catch(error => {
            progress.classList.remove('show');
            showAlert('Error uploading backup', 'danger');
            console.error('Error:', error);
        });
    }

    function renderBackupPreview(info) {
        const content = document.getElementById('previewContent');
        
        let html = `
            <div class="row">
                <div class="col-md-6">
                    <h6>Backup Information</h6>
                    <table class="table table-sm">
                        <tr><td><strong>Filename:</strong></td><td>${info.filename}</td></tr>
                        <tr><td><strong>Size:</strong></td><td>${info.size_formatted}</td></tr>
                        <tr><td><strong>Files:</strong></td><td>${info.file_count}</td></tr>
                        <tr><td><strong>Modified:</strong></td><td>${info.created_at}</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h6>Contains</h6>
                    <ul class="list-unstyled">
        `;
        
        info.contains.forEach(item => {
            html += `<li><i class="fas fa-check text-success me-2"></i>${item}</li>`;
        });
        
        html += '</ul></div></div>';
        
        if (info.backup_info) {
            html += `
                <h6>Backup Details</h6>
                <div class="row">
                    <div class="col-md-6">
                        <small><strong>Created:</strong> ${info.backup_info.created_at || 'Unknown'}</small><br>
                        <small><strong>Type:</strong> ${info.backup_info.type || 'Unknown'}</small><br>
                        <small><strong>Name:</strong> ${info.backup_info.name || 'Unknown'}</small>
                    </div>
                    <div class="col-md-6">
                        <small><strong>Laravel:</strong> ${info.backup_info.laravel_version || 'Unknown'}</small><br>
                        <small><strong>PHP:</strong> ${info.backup_info.php_version || 'Unknown'}</small><br>
                        <small><strong>Server:</strong> ${info.backup_info.server || 'Unknown'}</small>
                    </div>
                </div>
            `;
        }
        
        content.innerHTML = html;
    }

    function restoreBackup() {
        const filename = document.getElementById('restoreFilename').value;
        const restoreDatabase = document.getElementById('restoreDatabase').checked;
        const restoreFiles = document.getElementById('restoreFiles').checked;
        const createBackupFirst = document.getElementById('createBackupBeforeRestore').checked;

        if (!restoreDatabase && !restoreFiles) {
            showAlert('Please select at least one restore option', 'warning');
            return;
        }

        // Disable the button to prevent double-clicks
        const restoreBtn = document.getElementById('confirmRestore');
        restoreBtn.disabled = true;
        restoreBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Restoring...';

        // Show a visible loading indicator in the modal
        let restoreModalBody = restoreBtn.closest('.modal-content').querySelector('.modal-body');
        let loadingDiv = document.createElement('div');
        loadingDiv.className = 'alert alert-info';
        loadingDiv.id = 'restoreLoadingIndicator';
        loadingDiv.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Restore in progress...';
        restoreModalBody.appendChild(loadingDiv);

        const formData = new FormData();
        formData.append('restore_database', restoreDatabase ? '1' : '0');
        formData.append('restore_files', restoreFiles ? '1' : '0');
        formData.append('create_backup_before_restore', createBackupFirst ? '1' : '0');
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);

        fetch(window.location.origin + `/admin/backups/restore/${encodeURIComponent(filename)}`, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        })
        .then(async response => {
            // Try to parse JSON, fallback to text for debugging
            let data;
            try {
                data = await response.json();
            } catch (e) {
                let text = await response.text();
                showAlert('Restore response error: ' + text, 'danger');
                console.error('Restore response not valid JSON:', text);
                throw new Error('Invalid JSON response: ' + text);
            }
            console.log('Restore response:', data);
            if (data.success) {
                showAlert('Backup restored successfully!', 'success');
                if (data.pre_restore_backup) {
                    showAlert(`Pre-restore backup created: ${data.pre_restore_backup}`, 'info');
                }
                closeRestoreModal();
                loadBackups();

                // Show restart recommendation
                setTimeout(() => {
                    showAlert('Restore complete. Consider restarting the application to ensure all changes take effect.', 'warning');
                }, 2000);
            } else {
                showAlert(data.message || 'Error restoring backup', 'danger');
            }
        })
        .catch(error => {
            showAlert('Error restoring backup: ' + error.message, 'danger');
            console.error('Restore error:', error);
        })
        .finally(() => {
            // Remove loading indicator
            let loadingDiv = document.getElementById('restoreLoadingIndicator');
            if (loadingDiv) loadingDiv.remove();
            // Re-enable the button
            restoreBtn.disabled = false;
            restoreBtn.innerHTML = '<i class="fas fa-history me-2"></i>Restore Now';
        });
    }

    function showAlert(message, type) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.row'));
        // If type is 'danger', keep the alert longer for visibility
        setTimeout(() => {
            alertDiv.remove();
        }, type === 'danger' ? 10000 : 5000);
    }
});
</script>
@endsection
