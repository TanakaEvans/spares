<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'System Management') }} - @yield('title', 'Admin Dashboard')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700" rel="stylesheet" />
    
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Admin Dashboard CSS -->
    <link href="{{ asset('assets/css/admin-dashboard.css') }}" rel="stylesheet">
    
    @stack('styles')
</head>
<body>
    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('dashboard') }}" class="sidebar-logo">
                <div class="sidebar-logo-icon">
                    <i class="fas fa-cogs"></i>
                </div>
                <span>System Management</span>
            </a>
        </div>
        
        <div class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-section-title">Main</div>
                <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </a>
                <a href="{{ route('modules.index') }}" class="nav-item {{ request()->routeIs('modules.*') ? 'active' : '' }}">
                    <i class="fas fa-th-large"></i>
                    Modules
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">Authentication & Access</div>
                <a href="{{ route('auth.users.index') }}" class="nav-item {{ request()->routeIs('auth.users.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i>
                    User Management
                </a>
                <a href="{{ route('auth.roles.index') }}" class="nav-item {{ request()->routeIs('auth.roles.*') ? 'active' : '' }}">
                    <i class="fas fa-user-shield"></i>
                    Role Management
                </a>
            </div>

            <div class="nav-section">
                <div class="nav-section-title">System</div>
                <a href="{{ route('system.settings') }}" class="nav-item {{ request()->routeIs('system.*') ? 'active' : '' }}">
                    <i class="fas fa-cog"></i>
                    Settings
                </a>
                <a href="{{ route('system.logs') }}" class="nav-item {{ request()->routeIs('logs.*') ? 'active' : '' }}">
                    <i class="fas fa-file-alt"></i>
                    System Logs
                </a>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-content" id="mainContent">
        <!-- Header -->
        <header class="header">
            <div class="header-left">
                <button class="sidebar-toggle" id="sidebarToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1 class="page-title">@yield('page-title', 'Dashboard')</h1>
            </div>
            
            <div class="header-right">
                <!-- Search Box -->
                <div class="search-box">
                    <input type="text" class="search-input" placeholder="Search...">
                    <i class="fas fa-search search-icon"></i>
                </div>
                
                <!-- Notifications -->
                <button class="notification-btn" data-tooltip="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                
                <!-- Profile Dropdown -->
                <div class="profile-dropdown">
                    <button class="profile-btn" data-tooltip="Profile Menu" onclick="toggleProfileMenu()">
                        <div class="profile-avatar">
                            {{ substr(auth()->user()->name ?? 'A', 0, 1) }}
                        </div>
                    </button>
                    <div class="profile-menu" id="profileMenu">
                        <div class="profile-info">
                            <div class="profile-name">{{ auth()->user()->name ?? 'User' }}</div>
                            <div class="profile-email">{{ auth()->user()->email ?? '' }}</div>
                            <div class="profile-roles">
                                @if(auth()->user() && auth()->user()->roles)
                                    @foreach(auth()->user()->roles as $role)
                                        <span class="role-badge">{{ $role->name }}</span>
                                    @endforeach
                                @endif
                            </div>
                        </div>
                        <hr>
                        <a href="#" class="profile-menu-item">
                            <i class="fas fa-user"></i>
                            Profile Settings
                        </a>
                        <a href="#" class="profile-menu-item">
                            <i class="fas fa-cog"></i>
                            Account Settings
                        </a>
                        <hr>
                        <form method="POST" action="{{ route('logout') }}" class="profile-menu-form">
                            @csrf
                            <button type="submit" class="profile-menu-item logout-btn">
                                <i class="fas fa-sign-out-alt"></i>
                                Sign Out
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="content">
            @if(session('success'))
                <div class="alert alert-success mb-3">
                    <i class="fas fa-check-circle"></i>
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger mb-3">
                    <i class="fas fa-exclamation-circle"></i>
                    {{ session('error') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="alert alert-warning mb-3">
                    <i class="fas fa-exclamation-triangle"></i>
                    {{ session('warning') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/admin-dashboard.js') }}"></script>
    
    @stack('scripts')
    
    <script>
        // Flash message auto-hide
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                setTimeout(() => {
                    alert.style.opacity = '0';
                    setTimeout(() => {
                        alert.remove();
                    }, 300);
                }, 5000);
            });
        });

        // Profile dropdown toggle
        function toggleProfileMenu() {
            const menu = document.getElementById('profileMenu');
            menu.classList.toggle('show');
        }

        // Close profile menu when clicking outside
        document.addEventListener('click', function(event) {
            const profileDropdown = document.querySelector('.profile-dropdown');
            const profileMenu = document.getElementById('profileMenu');
            
            if (!profileDropdown.contains(event.target)) {
                profileMenu.classList.remove('show');
            }
        });
    </script>
    
    <style>
        .profile-dropdown {
            position: relative;
        }

        .profile-menu {
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 0.5rem;
            box-shadow: var(--shadow-lg);
            width: 280px;
            padding: 1rem;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s ease;
            z-index: 1000;
        }

        .profile-menu.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .profile-info {
            margin-bottom: 1rem;
        }

        .profile-name {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .profile-email {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .profile-roles {
            display: flex;
            flex-wrap: wrap;
            gap: 0.25rem;
        }

        .role-badge {
            background: var(--primary-color);
            color: white;
            font-size: 0.75rem;
            padding: 0.125rem 0.5rem;
            border-radius: 1rem;
            font-weight: 500;
        }

        .profile-menu hr {
            border: none;
            border-top: 1px solid var(--border-color);
            margin: 0.75rem 0;
        }

        .profile-menu-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem;
            color: var(--text-primary);
            text-decoration: none;
            border-radius: 0.375rem;
            transition: background-color 0.2s ease;
            width: 100%;
            border: none;
            background: none;
            font-size: 0.875rem;
            cursor: pointer;
        }

        .profile-menu-item:hover {
            background: var(--light-bg);
        }

        .profile-menu-form {
            margin: 0;
        }

        .logout-btn {
            color: var(--danger-color) !important;
        }

        .logout-btn:hover {
            background: rgba(239, 68, 68, 0.1) !important;
        }

        .alert {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
            border: 1px solid transparent;
        }

        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            color: var(--success-color);
            border-color: rgba(16, 185, 129, 0.2);
        }

        .alert-danger {
            background: rgba(239, 68, 68, 0.1);
            color: var(--danger-color);
            border-color: rgba(239, 68, 68, 0.2);
        }

        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            color: var(--warning-color);
            border-color: rgba(245, 158, 11, 0.2);
        }
    </style>
</body>
</html>
