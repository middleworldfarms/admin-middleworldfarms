<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MWF Admin')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-bg: #2c3e50;
            --sidebar-hover: #34495e;
            --sidebar-active: #27ae60;
        }
        
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
            padding: 20px;
            background: #1a252f;
            text-align: center;
            border-bottom: 1px solid #34495e;
        }
        
        .sidebar.collapsed .sidebar-header {
            padding: 20px 10px;
        }
        
        .sidebar .sidebar-header h4 {
            margin: 0;
            font-size: 1.2rem;
            transition: all 0.3s ease;
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
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
            min-height: 100vh;
        }
        
        .main-content.expanded {
            margin-left: 60px;
        }
        
        .top-navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 15px 30px;
            margin-bottom: 0;
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            color: #2c3e50;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px 10px;
            border-radius: 4px;
            transition: all 0.3s ease;
        }
        
        .sidebar-toggle:hover {
            background: #f8f9fa;
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
                margin-left: 0;
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
        }
        
        .badge-notification {
            background: #e74c3c;
            color: white;
            border-radius: 10px;
            padding: 2px 6px;
            font-size: 0.7rem;
            margin-left: auto;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h4><i class="fas fa-leaf me-2"></i>MWF Admin</h4>
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
            
            <a href="/admin/users" class="nav-link {{ request()->is('admin/users*') ? 'active' : '' }}">
                <i class="fas fa-users"></i>
                <span>Customer Management</span>
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
            
            <div class="nav-section">System</div>
            <a href="/admin/settings" class="nav-link {{ request()->is('admin/settings*') ? 'active' : '' }}">
                <i class="fas fa-cog"></i>
                <span>Settings</span>
            </a>
            
            <a href="/admin/logs" class="nav-link {{ request()->is('admin/logs*') ? 'active' : '' }}">
                <i class="fas fa-file-alt"></i>
                <span>System Logs</span>
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
        </nav>
    </div>
    
    <!-- Sidebar overlay for mobile -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <!-- Main content -->
    <div class="main-content" id="mainContent">
        <!-- Top navigation bar -->
        <nav class="top-navbar">
            <div class="d-flex justify-content-between align-items-center">
                <div class="d-flex align-items-center">
                    <button class="sidebar-toggle" id="sidebarToggle">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h5 class="mb-0 ms-3">@yield('page-title', 'Dashboard')</h5>
                </div>
                <div class="d-flex align-items-center">
                    <span class="text-muted">{{ date('l, F j, Y') }}</span>
                </div>
            </div>
        </nav>
        
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
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebarOverlay = document.getElementById('sidebarOverlay');
            
            // Toggle sidebar
            sidebarToggle.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    // Mobile toggle
                    sidebar.classList.toggle('mobile-open');
                    sidebarOverlay.classList.toggle('show');
                } else {
                    // Desktop toggle
                    sidebar.classList.toggle('collapsed');
                    mainContent.classList.toggle('expanded');
                }
            });
            
            // Close sidebar on overlay click (mobile)
            sidebarOverlay.addEventListener('click', function() {
                sidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('show');
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    sidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.remove('show');
                }
            });
        });
    </script>
    
    @yield('scripts')
</body>
</html>
