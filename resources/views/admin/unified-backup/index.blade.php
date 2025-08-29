@extends('layouts.app')

@section('title', 'Unified Backup Dashboard')

@section('content')
<style>
/* Ensure modal displays properly above all other elements */
.modal-backdrop {
    z-index: 9998 !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
}
.modal {
    z-index: 9999 !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: 100% !important;
    display: none !important;
    overflow: hidden !important;
    outline: 0 !important;
}
.modal.show {
    display: block !important;
}
.modal-dialog {
    z-index: 10000 !important;
    position: relative !important;
    width: auto !important;
    max-width: 500px !important;
    margin: 1.75rem auto !important;
    pointer-events: none !important;
}
.modal-content {
    z-index: 10001 !important;
    position: relative !important;
    display: flex !important;
    flex-direction: column !important;
    width: 100% !important;
    pointer-events: auto !important;
    background-color: #fff !important;
    background-clip: padding-box !important;
    border: 1px solid rgba(0, 0, 0, 0.2) !important;
    border-radius: 0.3rem !important;
    outline: 0 !important;
}
</style>
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
                        <button type="button" class="btn btn-info btn-sm" onclick="testModal()">
                            <i class="fas fa-test"></i> Test Modal
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-info">
                                <div class="inner" style="padding: 5px;">
                                    <h3>{{ $summary['total_sites'] }}</h3>
                                    <p>Total Sites</p>
                                </div>
                                <div class="icon" style="padding: 5px;">
                                    <i class="fas fa-globe"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-success">
                                <div class="inner" style="padding: 5px;">
                                    <h3>{{ $summary['total_backups'] }}</h3>
                                    <p>Total Backups</p>
                                </div>
                                <div class="icon" style="padding: 5px;">
                                    <i class="fas fa-archive"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-warning">
                                <div class="inner" style="padding: 5px;">
                                    <h3>{{ $summary['total_size'] }}</h3>
                                    <p>Total Size</p>
                                </div>
                                <div class="icon" style="padding: 5px;">
                                    <i class="fas fa-hdd"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-6">
                            <div class="small-box bg-danger">
                                <div class="inner" style="padding: 5px;">
                                    <h3>{{ $summary['last_backup'] }}</h3>
                                    <p>Last Backup</p>
                                </div>
                                <div class="icon" style="padding: 5px;">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Site Backups -->
                    <div class="row">
                        @foreach($sites as $siteName => $siteConfig)
                            <div class="col-lg-6 col-12 mb-4">
                                <div class="card {{ !$siteConfig['enabled'] ? 'border-warning' : '' }}">
                                    <div class="card-header {{ !$siteConfig['enabled'] ? 'bg-light' : '' }}">
                                        <h5 class="card-title">
                                            <i class="fas fa-server"></i> {{ $siteConfig['label'] }}
                                            <small class="text-muted">({{ $siteName }})</small>
                                            @if(!$siteConfig['enabled'])
                                                <span class="badge badge-warning ml-2">Disabled</span>
                                            @endif
                                        </h5>
                                        <div class="card-tools">
                                            @if($siteConfig['enabled'])
                                                <!-- Auto Backup Controls -->
                                                <div class="d-inline-flex align-items-center mr-2">
                                                    <div class="form-check mr-2">
                                                        <input class="form-check-input" type="checkbox" 
                                                               id="auto-backup-{{ $siteName }}" 
                                                               {{ isset($backupSettings[$siteName]['auto_backup']) && $backupSettings[$siteName]['auto_backup'] ? 'checked' : '' }}
                                                               onchange="toggleAutoBackup('{{ $siteName }}', this.checked)">
                                                        <label class="form-check-label small" for="auto-backup-{{ $siteName }}">
                                                            Auto Backup
                                                        </label>
                                                    </div>
                                                    <select class="form-control form-control-sm" 
                                                            id="backup-time-{{ $siteName }}" 
                                                            onchange="updateBackupTime('{{ $siteName }}', this.value)"
                                                            style="width: auto; min-width: 80px;">
                                                        @for($hour = 0; $hour < 24; $hour++)
                                                            @php
                                                                $timeValue = str_pad($hour, 2, '0', STR_PAD_LEFT) . ':00';
                                                                $isSelected = isset($backupSettings[$siteName]['backup_time']) 
                                                                    ? $backupSettings[$siteName]['backup_time'] === $timeValue
                                                                    : ($hour == 2); // Default to 2 AM
                                                            @endphp
                                                            <option value="{{ $timeValue }}" {{ $isSelected ? 'selected' : '' }}>
                                                                {{ str_pad($hour, 2, '0', STR_PAD_LEFT) }}:00
                                                            </option>
                                                        @endfor
                                                    </select>
                                                </div>
                                                
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
                                                @elseif($siteConfig['type'] === 'farmos')
                                                    <button type="button" class="btn btn-warning btn-sm"
                                                            onclick="createBackup('{{ $siteName }}')">
                                                        <i class="fas fa-plus"></i> Create FarmOS Backup
                                                    </button>
                                                @else
                                                    <span class="badge badge-secondary">Remote API</span>
                                                @endif
                                            @else
                                                <span class="badge badge-secondary">{{ $siteConfig['type'] === 'remote_api' ? 'External Service' : 'Disabled' }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        @if(!$siteConfig['enabled'])
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle"></i>
                                                <strong>{{ $siteConfig['label'] }}</strong> is not actively backed up.
                                                @if(strpos($siteConfig['label'], 'FarmOS') !== false)
                                                    FarmOS data is synced to the admin database and backed up as part of the Admin site backup.
                                                @else
                                                    This service may be backed up through other means or may not require regular backups.
                                                @endif
                                            </div>
                                        @elseif(isset($backups[$siteName]) && count($backups[$siteName]) > 0)
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
                                                                    <div class="btn-group" role="group">
                                                                        <a href="{{ route('admin.unified-backup.download', [$siteName, $backup['filename']]) }}"
                                                                           class="btn btn-sm btn-info" title="Download">
                                                                            <i class="fas fa-download"></i>
                                                                        </a>
                                                                        @if($siteConfig['type'] === 'farmos' || $siteConfig['type'] === 'spatie' || $siteConfig['type'] === 'plesk')
                                                                            @if($siteConfig['type'] === 'farmos')
                                                                                <button type="button" class="btn btn-sm btn-warning"
                                                                                        onclick="showRestoreModal('{{ $siteName }}', '{{ $backup['filename'] }}', '{{ $siteConfig['type'] }}')" title="Restore FarmOS">
                                                                                    <i class="fas fa-undo"></i>
                                                                                </button>
                                                                            @elseif($siteConfig['type'] === 'spatie')
                                                                                <button type="button" class="btn btn-sm btn-success"
                                                                                        onclick="showRestoreModal('{{ $siteName }}', '{{ $backup['filename'] }}', '{{ $siteConfig['type'] }}')" title="Restore Laravel">
                                                                                    <i class="fas fa-undo"></i>
                                                                                </button>
                                                                            @elseif($siteConfig['type'] === 'plesk')
                                                                                <button type="button" class="btn btn-sm btn-primary"
                                                                                        onclick="showRestoreModal('{{ $siteName }}', '{{ $backup['filename'] }}', '{{ $siteConfig['type'] }}')" title="Restore Website">
                                                                                    <i class="fas fa-undo"></i>
                                                                                </button>
                                                                            @endif
                                                                        @endif
                                                                        <button type="button" class="btn btn-sm btn-secondary"
                                                                                onclick="showRenameModal('{{ $siteName }}', '{{ $backup['filename'] }}')" title="Rename Backup">
                                                                            <i class="fas fa-edit"></i>
                                                                        </button>
                                                                        <button type="button" class="btn btn-sm btn-danger"
                                                                                onclick="showDeleteModal('{{ $siteName }}', '{{ $backup['filename'] }}')" title="Delete Backup">
                                                                            <i class="fas fa-trash"></i>
                                                                        </button>
                                                                    </div>
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
                                                @elseif($siteConfig['type'] === 'farmos')
                                                    <button type="button" class="btn btn-primary"
                                                            onclick="createBackup('{{ $siteName }}')">
                                                        Create First FarmOS Backup
                                                    </button>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Custom Restore Modal (Non-Bootstrap) -->
<div id="customRestoreModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; padding: 20px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
            <h5 style="margin: 0; color: #333;">
                <i class="fas fa-undo" style="color: #ffc107;"></i> Restore FarmOS Backup
            </h5>
            <button onclick="hideCustomModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #666;">&times;</button>
        </div>
        
        <div style="margin-bottom: 20px;">
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px; padding: 12px; margin-bottom: 15px;">
                <i class="fas fa-exclamation-triangle" style="color: #856404;"></i>
                <strong style="color: #856404;">Warning:</strong> Restoring will overwrite existing data. Make sure you have a current backup before proceeding.
            </div>
            
            <p><strong>Backup:</strong> <span id="customRestoreBackupName"></span></p>
            <p><strong>Site:</strong> <span id="customRestoreSiteName"></span></p>
            <p><strong>Type:</strong> <span id="customRestoreSiteType"></span></p>
            <div id="restoreDescription" style="background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 10px; margin-bottom: 15px; font-size: 14px;"></div>
            
            <div style="margin-bottom: 15px;">
                <label for="customRestoreType" style="display: block; margin-bottom: 5px; font-weight: bold;">Restore Type:</label>
                <select id="customRestoreType" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                    <option value="full">Full Restore</option>
                    <option value="files">Files Only</option>
                    <option value="database">Database Only</option>
                </select>
            </div>
            
            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" id="customConfirmRestore" style="margin-right: 8px;">
                    <span>I understand this will overwrite existing data</span>
                </label>
            </div>
        </div>
        
        <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #eee; padding-top: 15px;">
            <button onclick="hideCustomModal()" style="padding: 8px 16px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 4px; cursor: pointer;">Cancel</button>
            <button id="customConfirmRestoreBtn" onclick="performCustomRestore()" disabled style="padding: 8px 16px; border: none; background: #ffc107; color: #212529; border-radius: 4px; cursor: pointer; opacity: 0.6;">
                <i class="fas fa-undo"></i> Restore
            </button>
        </div>
    </div>
</div>

<!-- Rename Modal -->
<div id="renameModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; padding: 20px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
            <h5 style="margin: 0; color: #333;">
                <i class="fas fa-edit" style="color: #6c757d;"></i> Rename Backup
            </h5>
            <button onclick="hideRenameModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #666;">&times;</button>
        </div>
        
        <div style="margin-bottom: 20px;">
            <p><strong>Current Name:</strong> <span id="renameCurrentName"></span></p>
            <p><strong>Site:</strong> <span id="renameSiteName"></span></p>
            
            <div style="margin-bottom: 15px;">
                <label for="newBackupName" style="display: block; margin-bottom: 5px; font-weight: bold;">New Name:</label>
                <input type="text" id="newBackupName" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="Enter new backup name">
                <small style="color: #666; display: block; margin-top: 5px;">Include file extension (.tar.gz, .zip, etc.)</small>
            </div>
        </div>
        
        <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #eee; padding-top: 15px;">
            <button onclick="hideRenameModal()" style="padding: 8px 16px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 4px; cursor: pointer;">Cancel</button>
            <button id="renameConfirmBtn" onclick="performRename()" style="padding: 8px 16px; border: none; background: #6c757d; color: white; border-radius: 4px; cursor: pointer;">
                <i class="fas fa-edit"></i> Rename
            </button>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 8px; padding: 20px; max-width: 500px; width: 90%; max-height: 80vh; overflow-y: auto; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #eee; padding-bottom: 15px;">
            <h5 style="margin: 0; color: #dc3545;">
                <i class="fas fa-trash" style="color: #dc3545;"></i> Delete Backup
            </h5>
            <button onclick="hideDeleteModal()" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #666;">&times;</button>
        </div>
        
        <div style="margin-bottom: 20px;">
            <div style="background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; padding: 12px; margin-bottom: 15px;">
                <i class="fas fa-exclamation-triangle" style="color: #721c24;"></i>
                <strong style="color: #721c24;">Warning:</strong> This action cannot be undone. The backup file will be permanently deleted.
            </div>
            
            <p><strong>Backup:</strong> <span id="deleteBackupName"></span></p>
            <p><strong>Site:</strong> <span id="deleteSiteName"></span></p>
            <p><strong>Size:</strong> <span id="deleteBackupSize"></span></p>
            
            <div style="margin-bottom: 20px;">
                <label style="display: flex; align-items: center; cursor: pointer;">
                    <input type="checkbox" id="confirmDelete">
                    <span style="margin-left: 8px;">I understand this backup will be permanently deleted</span>
                </label>
            </div>
        </div>
        
        <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #eee; padding-top: 15px;">
            <button onclick="hideDeleteModal()" style="padding: 8px 16px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 4px; cursor: pointer;">Cancel</button>
            <button id="deleteConfirmBtn" onclick="performDelete()" disabled style="padding: 8px 16px; border: none; background: #dc3545; color: white; border-radius: 4px; cursor: pointer; opacity: 0.6;">
                <i class="fas fa-trash"></i> Delete Permanently
            </button>
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

function showRestoreModal(siteName, backupFilename, siteType) {
    console.log('Opening custom restore modal for:', siteName, backupFilename, siteType);
    
    // Update modal content
    document.getElementById('customRestoreSiteName').textContent = siteName;
    document.getElementById('customRestoreBackupName').textContent = backupFilename;
    document.getElementById('customRestoreSiteType').textContent = getSiteTypeLabel(siteType);
    document.getElementById('customConfirmRestore').checked = false;
    document.getElementById('customConfirmRestoreBtn').disabled = true;
    
    // Store values for restore
    window.restoreSite = siteName;
    window.restoreBackup = backupFilename;
    window.restoreSiteType = siteType;
    
    // Update restore options based on site type
    updateRestoreOptions(siteType);
    
    // Update description area
    updateRestoreDescription(siteType);
    
    // Show modal
    const modal = document.getElementById('customRestoreModal');
    modal.style.display = 'flex';
    
    console.log('Custom modal should now be visible');
}

function getSiteTypeLabel(siteType) {
    switch(siteType) {
        case 'farmos': return 'FarmOS (Drupal)';
        case 'spatie': return 'Laravel (Spatie)';
        case 'plesk': return 'Plesk Website';
        default: return siteType;
    }
}

function updateRestoreOptions(siteType) {
    const select = document.getElementById('customRestoreType');
    const description = document.getElementById('restoreDescription');
    select.innerHTML = '';
    
    if (siteType === 'farmos') {
        select.innerHTML = `
            <option value="both">Files + Database (Full Restore)</option>
            <option value="files">Files Only</option>
            <option value="database">Database Only</option>
        `;
        description.innerHTML = '<strong>FarmOS Restore:</strong> Restores the Drupal FarmOS installation including all files, modules, and database content. Files include the web application, uploaded media, and configuration. Database includes all farm data, user accounts, and system settings.';
    } else if (siteType === 'spatie') {
        select.innerHTML = `
            <option value="full">Full Laravel Restore</option>
        `;
        description.innerHTML = '<strong>Laravel Restore:</strong> Restores the complete Laravel application including all files, database, configuration, and uploaded content. This will restore the admin system to its previous state.';
    } else if (siteType === 'plesk') {
        select.innerHTML = `
            <option value="full">Full Website Restore</option>
        `;
        description.innerHTML = '<strong>Website Restore:</strong> Restores the complete website including all HTML, CSS, JavaScript, images, and other web assets. Database content (if applicable) will also be restored.';
    } else {
        select.innerHTML = `
            <option value="full">Full Restore</option>
        `;
        description.innerHTML = '<strong>Full Restore:</strong> Restores all available components for this site type.';
    }
}

function updateRestoreDescription(siteType) {
    const descriptionDiv = document.getElementById('restoreDescription');
    let description = '';
    
    switch(siteType) {
        case 'farmos':
            description = 'This will restore the FarmOS files and database. It will overwrite existing FarmOS data.';
            break;
        case 'spatie':
            description = 'This will restore the Laravel application files. It will not affect the database.';
            break;
        case 'plesk':
            description = 'This will restore the entire website including files and database. It will overwrite all website data.';
            break;
        default:
            description = 'This will perform a full restore, overwriting all existing data.';
    }
    
    descriptionDiv.innerHTML = description;
}

function hideCustomModal() {
    console.log('Hiding custom modal');
    const modal = document.getElementById('customRestoreModal');
    modal.style.display = 'none';
}

function performCustomRestore() {
    console.log('performCustomRestore called for site type:', window.restoreSiteType);
    
    if (!document.getElementById('customConfirmRestore').checked) {
        console.log('Confirmation checkbox not checked');
        alert('Please confirm that you understand this will overwrite existing data.');
        return;
    }

    const restoreType = document.getElementById('customRestoreType').value;
    console.log('Restore type:', restoreType);
    
    // Get confirmation message based on site type
    const confirmMessage = getRestoreConfirmMessage(window.restoreSiteType, restoreType);
    
    if (!confirm(confirmMessage)) {
        console.log('User cancelled restore');
        return;
    }

    console.log('Proceeding with restore...');
    
    // Show loading state
    const button = document.getElementById('customConfirmRestoreBtn');
    const originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Restoring...';
    button.disabled = true;

    fetch('{{ route("admin.unified-backup.restore") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            site: window.restoreSite,
            backup: window.restoreBackup,
            type: restoreType
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Restore completed successfully!');
            hideCustomModal();
            location.reload();
        } else {
            alert('Restore failed: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error during restore: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalHtml;
        button.disabled = false;
    });
}

function getRestoreConfirmMessage(siteType, restoreType) {
    const siteLabel = getSiteTypeLabel(siteType);
    const typeLabel = restoreType === 'full' ? 'full' : restoreType;
    
    return `Are you sure you want to restore ${typeLabel} for ${siteLabel} from ${window.restoreBackup}? This action cannot be undone.`;
}

// Enable/disable restore button based on confirmation checkbox
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM loaded, initializing custom modal functionality');
    
    const confirmCheckbox = document.getElementById('customConfirmRestore');
    const restoreBtn = document.getElementById('customConfirmRestoreBtn');
    
    if (confirmCheckbox && restoreBtn) {
        confirmCheckbox.addEventListener('change', function() {
            restoreBtn.disabled = !this.checked;
            restoreBtn.style.opacity = this.checked ? '1' : '0.6';
            console.log('Checkbox changed, button disabled:', restoreBtn.disabled);
        });
    } else {
        console.error('Custom modal elements not found:', { confirmCheckbox, restoreBtn });
    }
    
    // Handle delete confirmation checkbox
    const deleteCheckbox = document.getElementById('confirmDelete');
    const deleteBtn = document.getElementById('deleteConfirmBtn');
    
    if (deleteCheckbox && deleteBtn) {
        deleteCheckbox.addEventListener('change', function() {
            deleteBtn.disabled = !this.checked;
            deleteBtn.style.opacity = this.checked ? '1' : '0.6';
        });
    }
    
    // Add keyboard support for custom modal
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            const restoreModal = document.getElementById('customRestoreModal');
            const renameModal = document.getElementById('renameModal');
            const deleteModal = document.getElementById('deleteModal');
            
            if (restoreModal && restoreModal.style.display === 'flex') {
                hideCustomModal();
            } else if (renameModal && renameModal.style.display === 'flex') {
                hideRenameModal();
            } else if (deleteModal && deleteModal.style.display === 'flex') {
                hideDeleteModal();
            }
        }
    });
    
    // Add click outside to close
    document.addEventListener('click', function(event) {
        const restoreModal = document.getElementById('customRestoreModal');
        const renameModal = document.getElementById('renameModal');
        const deleteModal = document.getElementById('deleteModal');
        
        if (restoreModal && restoreModal.style.display === 'flex' && event.target === restoreModal) {
            hideCustomModal();
        }
        if (renameModal && renameModal.style.display === 'flex' && event.target === renameModal) {
            hideRenameModal();
        }
        if (deleteModal && deleteModal.style.display === 'flex' && event.target === deleteModal) {
            hideDeleteModal();
        }
    });
});

function testModal() {
    console.log('Testing custom modal functionality');
    showRestoreModal('test-site', 'test-backup-2025-08-29.tar.gz', 'farmos');
}

function showRenameModal(siteName, backupFilename) {
    console.log('Opening rename modal for:', siteName, backupFilename);
    
    document.getElementById('renameSiteName').textContent = siteName;
    document.getElementById('renameCurrentName').textContent = backupFilename;
    document.getElementById('newBackupName').value = backupFilename;
    
    window.renameSite = siteName;
    window.renameBackup = backupFilename;
    
    const modal = document.getElementById('renameModal');
    modal.style.display = 'flex';
}

function hideRenameModal() {
    const modal = document.getElementById('renameModal');
    modal.style.display = 'none';
}

function performRename() {
    const newName = document.getElementById('newBackupName').value.trim();
    
    if (!newName) {
        alert('Please enter a new name for the backup.');
        return;
    }
    
    if (newName === window.renameBackup) {
        alert('The new name is the same as the current name.');
        return;
    }
    
    if (!confirm(`Rename backup from "${window.renameBackup}" to "${newName}"?`)) {
        return;
    }
    
    const button = document.getElementById('renameConfirmBtn');
    const originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Renaming...';
    button.disabled = true;
    
    fetch('{{ route("admin.unified-backup.rename") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            site: window.renameSite,
            current_name: window.renameBackup,
            new_name: newName
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Backup renamed successfully!');
            hideRenameModal();
            location.reload();
        } else {
            alert('Rename failed: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error during rename: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalHtml;
        button.disabled = false;
    });
}

function showDeleteModal(siteName, backupFilename) {
    console.log('Opening delete modal for:', siteName, backupFilename);
    
    document.getElementById('deleteSiteName').textContent = siteName;
    document.getElementById('deleteBackupName').textContent = backupFilename;
    document.getElementById('deleteBackupSize').textContent = 'Calculating...';
    document.getElementById('confirmDelete').checked = false;
    document.getElementById('deleteConfirmBtn').disabled = true;
    
    window.deleteSite = siteName;
    window.deleteBackup = backupFilename;
    
    // Get backup size
    fetch(`/admin/unified-backup/download/${siteName}/${backupFilename}`, {
        method: 'HEAD'
    })
    .then(response => {
        const size = response.headers.get('content-length');
        if (size) {
            const sizeMB = (size / (1024 * 1024)).toFixed(2);
            document.getElementById('deleteBackupSize').textContent = `${sizeMB} MB`;
        } else {
            document.getElementById('deleteBackupSize').textContent = 'Unknown';
        }
    })
    .catch(() => {
        document.getElementById('deleteBackupSize').textContent = 'Unknown';
    });
    
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'flex';
}

function hideDeleteModal() {
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'none';
}

function performDelete() {
    if (!document.getElementById('confirmDelete').checked) {
        alert('Please confirm that you understand this backup will be permanently deleted.');
        return;
    }
    
    if (!confirm(`Are you sure you want to permanently delete "${window.deleteBackup}"? This action cannot be undone.`)) {
        return;
    }
    
    const button = document.getElementById('deleteConfirmBtn');
    const originalHtml = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
    button.disabled = true;
    
    fetch('{{ route("admin.unified-backup.delete") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            site: window.deleteSite,
            filename: window.deleteBackup
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Backup deleted successfully!');
            hideDeleteModal();
            location.reload();
        } else {
            alert('Delete failed: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error during delete: ' + error.message);
    })
    .finally(() => {
        button.innerHTML = originalHtml;
        button.disabled = false;
    });
}

// Toggle auto backup setting
function toggleAutoBackup(siteName, enabled) {
    console.log('Toggling auto backup for', siteName, 'to', enabled);
    
    fetch('{{ route("admin.unified-backup.toggle-auto-backup") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            site: siteName,
            enabled: enabled
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Auto backup setting updated successfully!');
            location.reload();
        } else {
            alert('Failed to update auto backup setting: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error updating auto backup setting: ' + error.message);
    });
}

// Update backup time
function updateBackupTime(siteName, time) {
    console.log('Updating backup time for', siteName, 'to', time);
    
    fetch('{{ route("admin.unified-backup.update-backup-time") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({
            site: siteName,
            time: time
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Backup time updated successfully!');
        } else {
            alert('Failed to update backup time: ' + data.message);
        }
    })
    .catch(error => {
        alert('Error updating backup time: ' + error.message);
    });
}
</script>
@endsection

<style>
/* Custom styles for the modal */
.modal-lg {
    max-width: 800px;
}

.modal-header {
    background-color: #f7f7f9;
    border-bottom: 1px solid #dee2e6;
}

.modal-title {
    font-size: 1.25rem;
    font-weight: 500;
}

.modal-body {
    padding: 2rem;
}

.modal-footer {
    background-color: #f7f7f9;
    border-top: 1px solid #dee2e6;
}
</style>
