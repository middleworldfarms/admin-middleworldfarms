<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Symbiosis')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="/css/global.css">
    @yield('styles')
    <style>
        body {
            overflow-x: hidden;
        }
        
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: var(--sidebar-bg);
            color: white;
            transition: all 0.3s ease;
            z-index: 1050;
            overflow-y: auto;
        }
        
        .sidebar.collapsed {
            width: 60px;
        }
        
        .sidebar .sidebar-header {
            padding: 15px 20px 20px;
            background: linear-gradient(135deg, #27ae60 0%, #213b2e 100%);
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            position: relative;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .sidebar.collapsed .sidebar-header {
            padding: 15px 10px 20px;
        }
        
        .sidebar-toggle-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(255,255,255,0.1);
            border: none;
            color: white;
            width: 30px;
            height: 30px;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            z-index: 1060;
        }
        
        .logo-container {
            transition: all 0.3s ease;
            padding: 10px;
            margin: 10px auto;
            max-width: 120px;
            text-align: center;
        }
        
        .logo-container img,
        .rounded-logo {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            filter: drop-shadow(0 3px 8px rgba(0,0,0,0.3));
            border: 3px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
        }
        
        .logo-container img:hover,
        .rounded-logo:hover {
            transform: scale(1.05);
            filter: drop-shadow(0 4px 12px rgba(0,0,0,0.4));
            border-color: rgba(255,255,255,0.3);
        }
        
        .sidebar.collapsed .logo-container {
            opacity: 0;
            transform: scale(0.5);
        }
        
        .sidebar-toggle-btn:hover {
            background: rgba(255,255,255,0.2);
            transform: scale(1.1);
        }
        
        .sidebar-toggle-btn:active {
            transform: scale(0.95);
        }
        
        .sidebar.collapsed .sidebar-toggle-btn {
            position: fixed;
            top: 15px;
            left: 15px;
            width: 30px;
            height: 30px;
            background: rgba(33,59,46,0.9);
            border-radius: 50%;
        }
        
        .sidebar.collapsed .sidebar-toggle-btn:hover {
            background: rgba(33,59,46,1);
            transform: scale(1.1);
        }
        
        .sidebar .sidebar-header h4 {
            margin: 0;
            font-size: 1.2rem;
            transition: all 0.3s ease;
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
            font-weight: 600;
        }
        
        .sidebar.collapsed .sidebar-header h4 {
            opacity: 0;
        }
        
        .sidebar .nav-link {
            color: #bdc3c7;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
        }
        
        .sidebar .nav-link:hover {
            background: var(--sidebar-hover);
            color: white;
        }
        
        .sidebar .nav-link.active {
            background: var(--sidebar-active);
            color: white;
        }
        
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .sidebar.collapsed .nav-link {
            padding: 15px 20px;
            justify-content: center;
        }
        
        .sidebar.collapsed .nav-link span {
            display: none;
        }
        
        .sidebar.collapsed .nav-link i {
            margin-right: 0;
        }
        
        .main-content {
            margin-left: var(--sidebar-width) !important;
            transition: all 0.3s ease;
            min-height: 100vh;
            position: relative;
            z-index: 1;
        }
        
        .main-content.expanded {
            margin-left: 60px !important;
        }
        
        .nav-section {
            padding: 15px 20px 5px;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #7f8c8d;
            border-bottom: 1px solid #34495e;
            margin-bottom: 5px;
        }
        
        .sidebar.collapsed .nav-section {
            display: none;
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0 !important;
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 1040;
                display: none;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
        }
        
        .content-wrapper {
            padding: 30px;
            width: 100%;
            box-sizing: border-box;
            margin-left: 0;
            transition: all 0.3s ease;
        }
        
        .badge-notification {
            background: #e74c3c;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 0.7rem;
            margin-left: auto;
        }
        
        .admin-info {
            background: rgba(255,255,255,0.1);
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 15px !important;
        }
        
        .admin-info .text-muted {
            color: rgba(255,255,255,0.7) !important;
            font-size: 0.75rem;
        }
        
        .admin-info .text-white {
            color: white !important;
            text-shadow: 0 1px 2px rgba(0,0,0,0.3);
        }

        @media (max-width: 768px) {
        }
    </style>
</head>
<body class="has-sidebar">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <button class="sidebar-toggle-btn" id="sidebarToggleBtn" title="Toggle Sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <div class="logo-container mt-4">
                <img src="/Middle World Logo Image White - PNG FOR SCREENS.png" alt="Middle World Farms" class="rounded-logo">
            </div>
            <h4 class="mb-0 mt-2">Symbiosis</h4>
            @php
                $adminUser = \App\Http\Controllers\Auth\LoginController::getAdminUser();
            @endphp
            @if($adminUser)
                <div class="admin-info mt-2">
                    <small class="text-muted d-block">Welcome back,</small>
                    <small class="text-white fw-bold">{{ $adminUser['name'] ?? 'Admin' }}</small>
                </div>
            @endif
        </div>
        
        <nav class="nav flex-column">
            <div class="nav-section">Dashboard</div>
            <a href="/admin" class="nav-link {{ request()->is('admin') && !request()->is('admin/*') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i>
                <span>Overview</span>
            </a>
            
            <div class="nav-section">Operations</div>
            <a href="/admin/deliveries" class="nav-link {{ request()->is('admin/deliveries*') ? 'active' : '' }}">
                <i class="fas fa-truck"></i>
                <span>Delivery Schedule</span>
                @if(isset($totalDeliveries) && $totalDeliveries > 0)
                    <span class="badge-notification">{{ $totalDeliveries }}</span>
                @endif
            </a>
            
            <a href="/admin/customers" class="nav-link {{ request()->is('admin/customers*') ? 'active' : '' }}">
                <i class="fas fa-users"></i>
                <span>Customer Management</span>
            </a>
            
            <a href="/admin/routes" class="nav-link {{ request()->is('admin/routes*') ? 'active' : '' }}">
                <i class="fas fa-route"></i>
                <span>Route Planner</span>
            </a>
            
            <div class="nav-section">Analytics</div>
            <a href="/admin/reports" class="nav-link {{ request()->is('admin/reports*') ? 'active' : '' }}">
                <i class="fas fa-chart-bar"></i>
                <span>Reports</span>
            </a>
            
            <a href="/admin/analytics" class="nav-link {{ request()->is('admin/analytics*') ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i>
                <span>Analytics</span>
            </a>
            
            <div class="nav-section">Farm Management</div>
            <a href="/admin/farmos" class="nav-link {{ request()->is('admin/farmos') ? 'active' : '' }}">
                <i class="fas fa-seedling"></i>
                <span>farmOS Dashboard</span>
            </a>
            
            <a href="/admin/farmos/planting-chart" class="nav-link {{ request()->is('admin/farmos/planting-chart*') ? 'active' : '' }}">
                <i class="fas fa-calendar-alt"></i>
                <span>Planting Chart</span>
            </a>
            
            <a href="/admin/farmos/succession-planning" class="nav-link {{ request()->is('admin/farmos/succession-planning*') ? 'active' : '' }}">
                <i class="fas fa-layer-group"></i>
                <span>Succession Planning</span>
            </a>
            
            <a href="/admin/farmos/crop-plans" class="nav-link {{ request()->is('admin/farmos/crop-plans*') ? 'active' : '' }}">
                <i class="fas fa-tasks"></i>
                <span>Crop Plans</span>
            </a>
            
            <a href="/admin/farmos/harvests" class="nav-link {{ request()->is('admin/farmos/harvests*') ? 'active' : '' }}">
                <i class="fas fa-apple-alt"></i>
                <span>Harvest Logs</span>
            </a>
            
            <a href="/admin/farmos/stock" class="nav-link {{ request()->is('admin/farmos/stock*') ? 'active' : '' }}">
                <i class="fas fa-boxes"></i>
                <span>Stock Management</span>
            </a>
            
            <div class="nav-section">System</div>
            <a href="/admin/settings" class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            
            <a href="/admin/logs" class="nav-link {{ request()->is('admin/logs*') ? 'active' : '' }}">
                <i class="fas fa-file-alt"></i>
                <span>System Logs</span>
            </a>
            
            <a href="/admin/backups" class="nav-link {{ request()->is('admin/backups*') ? 'active' : '' }}">
                <i class="fas fa-database"></i>
                <span>Backup Management</span>
            </a>
            
            <div class="nav-section">External</div>
            <a href="https://middleworldfarms.org" target="_blank" class="nav-link">
                <i class="fas fa-external-link-alt"></i>
                <span>Visit Website</span>
            </a>
            
            <a href="https://middleworldfarms.org/wp-admin" target="_blank" class="nav-link">
                <i class="fab fa-wordpress"></i>
                <span>WordPress Admin</span>
            </a>

            <!-- Logout Section -->
            <div class="nav-section mt-4">Account</div>
            <form method="POST" action="{{ route('admin.logout') }}" class="d-inline">
                @csrf
                <button type="submit" class="nav-link text-start border-0 bg-transparent w-100" style="color: inherit;">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </button>
            </form>
        </nav>
    </div>
    
    <!-- Sidebar overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Top Header -->
    <div class="top-header">
        <div class="d-flex justify-content-between align-items-center">
            <div class="header-spacer">
                <h2 class="header-brand-name">Symbiosis</h2>
            </div>
            <div class="header-content">
                @hasSection('page-header')
                    @yield('page-header')
                @else
                    <h1>Farm Management System</h1>
                    <p class="lead">Integrated agricultural operations</p>
                @endif
            </div>
            <div>
                <img src="/Middle_World_Logo_Inverted 350px.png" alt="Middle World Farms" class="header-logo">
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="main-content" id="mainContent">
        <!-- Page content -->
        <div class="content-wrapper">
            @yield('content')
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const mainContent = document.getElementById('mainContent');
            const topHeader = document.querySelector('.top-header');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            const body = document.body;
            
            // Ensure all elements exist before proceeding
            if (!sidebar || !mainContent) {
                console.error('Sidebar or main content element not found');
                return;
            }
            
            // Load saved sidebar state
            const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (sidebarCollapsed && window.innerWidth > 768) {
                sidebar.classList.add('collapsed');
                mainContent.classList.add('expanded');
                body.classList.add('sidebar-collapsed');
                if (topHeader) {
                    topHeader.style.marginLeft = '60px';
                }
            }
            
            // Function to toggle sidebar
            function toggleSidebar() {
                console.log('Toggle sidebar called'); // Debug log
                if (window.innerWidth <= 768) {
                    // Mobile toggle
                    sidebar.classList.toggle('mobile-open');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.toggle('show');
                    }
                } else {
                    // Desktop toggle
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                    body.classList.toggle('sidebar-collapsed');
                    
                    // Update header margin
                    if (topHeader) {
                        if (sidebar.classList.contains('collapsed')) {
                            topHeader.style.marginLeft = '60px';
                        } else {
                            topHeader.style.marginLeft = 'var(--sidebar-width)';
                        }
                    }
                    
                    // Save state to localStorage
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                    console.log('Sidebar collapsed:', sidebar.classList.contains('collapsed')); // Debug log
                }
            }
            
            // Toggle sidebar from top navbar
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleSidebar();
                });
            }
            
            // Toggle sidebar from sidebar button
            if (sidebarToggleBtn) {
                sidebarToggleBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    toggleSidebar();
                });
            }
            
            // Close sidebar on overlay click (mobile)
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    sidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.remove('show');
                });
            }
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('mobile-open');
                    if (sidebarOverlay) {
                        sidebarOverlay.classList.remove('show');
                    }
                    
                    // Restore collapsed state on desktop
                    const sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                    if (sidebarCollapsed) {
                        sidebar.classList.add('collapsed');
                        mainContent.classList.add('expanded');
                        body.classList.add('sidebar-collapsed');
                        if (topHeader) {
                            topHeader.style.marginLeft = '60px';
                        }
                    } else {
                        if (topHeader) {
                            topHeader.style.marginLeft = 'var(--sidebar-width)';
                        }
                    }
                } else {
                    // Remove collapsed state on mobile
                    sidebar.classList.remove('collapsed');
                    mainContent.classList.remove('expanded');
                    body.classList.remove('sidebar-collapsed');
                    if (topHeader) {
                        topHeader.style.marginLeft = '0';
                    }
                }
            });
        });
    </script>
    
    @yield('scripts')
</body>
</html>
