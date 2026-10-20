<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'CertTrack') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }

        html, body {
            margin: 0;
            padding: 0;
            height: 100vh;
            width: 100vw;
            overflow: hidden;
        }

        body {
            font-family: 'Nunito', sans-serif;
            background-color: #f2f9f9;
            background-image: 
                radial-gradient(at 0% 0%, hsla(186, 33%, 94%, 1) 0, transparent 50%), 
                radial-gradient(at 50% 0%, hsla(193, 85%, 28%, 0.03) 0, transparent 50%), 
                radial-gradient(at 100% 0%, hsla(175, 100%, 33%, 0.04) 0, transparent 50%);
            position: relative;
        }
        
        body::before {
            content: '';
            position: absolute;
            top: -20%; left: -10%; width: 50%; height: 50%;
            background: radial-gradient(circle, rgba(0, 163, 154, 0.1) 0%, rgba(255,255,255,0) 70%);
            z-index: -1; pointer-events: none;
        }
        body::after {
            content: '';
            position: absolute;
            bottom: -20%; right: -10%; width: 60%; height: 60%;
            background: radial-gradient(circle, rgba(18, 78, 120, 0.1) 0%, rgba(255,255,255,0) 70%);
            z-index: -1; pointer-events: none;
        }
        
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: rgba(155, 163, 175, 0.5); border-radius: 20px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(155, 163, 175, 0.8); }

        .bubble-card {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 2px solid rgba(255, 255, 255, 0.9);
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.08), inset 0 4px 10px rgba(255, 255, 255, 0.8);
            border-radius: 2rem;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .bubble-card:hover {
            transform: translateY(-2px) scale(1.005);
            box-shadow: 0 15px 50px -10px rgba(0, 163, 154, 0.15), inset 0 4px 10px rgba(255, 255, 255, 1);
        }

        .bubble-btn {
            background-color: #00A39A;
            color: white;
            font-weight: 700;
            border-radius: 9999px;
            padding: 0.5rem 1.25rem;
            font-size: 0.875rem;
            transition: all 0.2s ease-in-out;
            box-shadow: 0 4px 12px rgba(0, 163, 154, 0.25);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            text-decoration: none;
        }
        .bubble-btn:hover {
            background-color: #008B83;
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(0, 163, 154, 0.35);
            color: white;
        }

        .nav-item {
            display: flex;
            align-items: center;
            border-radius: 9999px;
            transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
            position: relative;
            color: #64748b;
            font-weight: 700;
            margin-bottom: 0.25rem;
            text-decoration: none;
            cursor: pointer;
        }
        
        .nav-item.active {
            background: linear-gradient(135deg, #124E78, #00A39A);
            color: #ffffff !important;
            box-shadow: 0 8px 20px -4px rgba(0, 163, 154, 0.4), inset 0 2px 4px rgba(255,255,255,0.4);
        }

        .nav-item.active i {
            color: #ffffff !important;
        }

        .nav-item:not(.active):hover {
            background-color: rgba(255, 255, 255, 0.9);
            color: #00A39A;
            box-shadow: 0 4px 15px -3px rgba(0, 0, 0, 0.05);
            transform: translateX(4px);
        }
        
        .nav-icon-wrapper {
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
            flex-shrink: 0;
        }
        
        .nav-item:not(.active) .nav-icon-wrapper {
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }

        .submenu-item {
            transition: all 0.2s ease;
            border-radius: 8px;
            padding: 0.3rem 0.5rem;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
        }
        .submenu-item.active {
            color: #00A39A !important;
            background-color: rgba(0, 163, 154, 0.1);
        }
        .submenu-item:not(.active):hover {
            color: #00A39A !important;
            background-color: rgba(0, 0, 0, 0.03);
            transform: translateX(2px);
        }
        
        /* Layout */
        .wrapper {
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        
        .sidebar {
            width: 260px;
            flex-shrink: 0;
            transition: width 0.3s ease, padding 0.3s ease;
            padding: 1rem;
            z-index: 1040;
            position: relative;
        }

        .sidebar.collapsed {
            width: 108px;
            padding: 1rem 0.5rem;
        }

        .sidebar.collapsed .sidebar-text {
            display: none !important;
        }

        .sidebar.collapsed .nav-item {
            justify-content: center;
            padding-left: 0 !important;
            padding-right: 0 !important;
        }

        .sidebar.collapsed .nav-icon-wrapper {
            margin-right: 0 !important;
        }

        .sidebar.collapsed .collapse {
            display: none !important;
        }

        /* Toggle Button (Inside Card) */
        .sidebar-toggle-btn {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            background: rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #64748b;
            padding: 0;
            flex-shrink: 0;
        }

        .sidebar-toggle-btn:hover {
            background-color: #00A39A;
            color: #ffffff;
            transform: scale(1.08);
        }

        /* Side-by-side icon and arrow when collapsed */
        .sidebar.collapsed .sidebar-header-box {
            flex-direction: row !important;
            justify-content: center !important;
            gap: 0.4rem;
            padding-left: 0.2rem !important;
            padding-right: 0.2rem !important;
        }

        .sidebar.collapsed .sidebar-toggle-btn {
            width: 22px;
            height: 22px;
        }

        .sidebar-inner {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(20px);
            border: 2px solid white;
            border-radius: 2rem;
            height: 100%;
            display: flex;
            flex-direction: column;
            box-shadow: 0 10px 40px -10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
        }
        
        .main-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
            position: relative;
        }

        .top-header {
            margin: 1rem 1rem 0;
            background: rgba(255, 255, 255, 0.4);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            border: 1px solid rgba(255, 255, 255, 0.6);
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.5rem 1rem;
            height: 4rem;
        }

        .content-area {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                position: fixed;
                left: -260px;
                top: 0;
                bottom: 0;
                padding: 0;
                border-radius: 0;
            }
            .sidebar.show {
                left: 0;
            }
            .sidebar-inner {
                border-radius: 0;
            }
            .sidebar-toggle-btn {
                display: none;
            }
        }
    </style>
</head>
<body>

    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar d-none d-lg-block" id="desktopSidebar">
            <div class="sidebar-inner py-3">
                <!-- Header / Logo + Toggle Button beside each other -->
                <div class="px-3 d-flex align-items-center justify-content-between mb-4 mt-2 sidebar-header-box">
                    <div class="d-flex align-items-center flex-shrink-0">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 38px; height: 38px; background: linear-gradient(135deg, #124E78, #00A39A); color: white;">
                            <i class="bi bi-patch-check-fill fs-5"></i>
                        </div>
                        <span class="fs-4 fw-bolder ms-2 text-dark sidebar-text">CertTrack</span>
                    </div>

                    <button type="button" class="sidebar-toggle-btn" id="sidebarToggleBtn" title="Toggle Sidebar">
                        <i class="bi bi-chevron-left" id="sidebarToggleIcon" style="font-size: 11px;"></i>
                    </button>
                </div>

                <!-- Navigation Links -->
                <nav class="flex-grow-1 overflow-y-auto px-2">
                    <div class="text-uppercase fw-bolder text-muted mb-2 ms-2 sidebar-text" style="font-size: 10px; letter-spacing: 1px;">Menu</div>
                    
                    <a href="{{ route('dashboard') }}" class="nav-item px-3 py-2 {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-grid-fill fs-5"></i>
                        </div>
                        <span class="sidebar-text">Dashboard</span>
                    </a>

                    @role('super_admin')
                    @php
                        $isUsersActive = request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') || request()->routeIs('cpsc-tokens.*');
                    @endphp
                    <a class="nav-item px-3 py-2 {{ $isUsersActive ? 'active' : '' }}" 
                       data-bs-toggle="collapse" 
                       href="#usersCollapse" 
                       role="button" 
                       aria-expanded="{{ $isUsersActive ? 'true' : 'false' }}" 
                       aria-controls="usersCollapse" 
                       title="Users">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-people-fill fs-5"></i>
                        </div>
                        <span class="sidebar-text flex-grow-1">Users</span>
                        <i class="bi bi-chevron-down ms-auto sidebar-text" style="font-size: 10px;"></i>
                    </a>
                    <div class="collapse {{ $isUsersActive ? 'show' : '' }}" id="usersCollapse">
                        <div class="ms-5 ps-2 border-start border-2 border-light py-1 mb-2">
                            <a href="{{ route('users.index') }}" class="text-decoration-none fw-bold submenu-item {{ request()->routeIs('users.*') ? 'active' : 'text-muted' }}" style="font-size: 13px;">
                                @if(request()->routeIs('users.*')) <i class="bi bi-circle-fill me-2" style="font-size: 5px;"></i> @endif User Account
                            </a>
                            <a href="{{ route('roles.index') }}" class="text-decoration-none fw-bold submenu-item {{ request()->routeIs('roles.*') ? 'active' : 'text-muted' }}" style="font-size: 13px;">
                                @if(request()->routeIs('roles.*')) <i class="bi bi-circle-fill me-2" style="font-size: 5px;"></i> @endif User Type / Roles
                            </a>
                            <a href="{{ route('permissions.grid') }}" class="text-decoration-none fw-bold submenu-item {{ request()->routeIs('permissions.*') ? 'active' : 'text-muted' }}" style="font-size: 13px;">
                                @if(request()->routeIs('permissions.*')) <i class="bi bi-circle-fill me-2" style="font-size: 5px;"></i> @endif Permissions Grid
                            </a>
                            <a href="{{ route('cpsc-tokens.index') }}" class="text-decoration-none fw-bold submenu-item {{ request()->routeIs('cpsc-tokens.*') ? 'active' : 'text-muted' }}" style="font-size: 13px;">
                                @if(request()->routeIs('cpsc-tokens.*')) <i class="bi bi-circle-fill me-2" style="font-size: 5px;"></i> @endif CPSC API Tokens
                            </a>
                        </div>
                    </div>
                    
                    <a href="{{ route('labs.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('labs.*') ? 'active' : '' }}" title="Labs">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-flask-fill fs-5"></i>
                        </div>
                        <span class="sidebar-text flex-grow-1">Labs</span>
                    </a>

                    @php
                        $isFactoriesActive = request()->routeIs('factories.*') || request()->routeIs('certificates.*');
                    @endphp
                    <a class="nav-item px-3 py-2 {{ $isFactoriesActive ? 'active' : '' }}" 
                       data-bs-toggle="collapse" 
                       href="#factoriesCollapse" 
                       role="button" 
                       aria-expanded="{{ $isFactoriesActive ? 'true' : 'false' }}" 
                       aria-controls="factoriesCollapse" 
                       title="Factories">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-buildings-fill fs-5"></i>
                        </div>
                        <span class="sidebar-text flex-grow-1">Factories</span>
                        <i class="bi bi-chevron-down ms-auto sidebar-text" style="font-size: 10px;"></i>
                    </a>
                    <div class="collapse {{ $isFactoriesActive ? 'show' : '' }}" id="factoriesCollapse">
                        <div class="ms-5 ps-2 border-start border-2 border-light py-1 mb-2">
                            <a href="{{ route('factories.index') }}" class="text-decoration-none fw-bold submenu-item {{ request()->routeIs('factories.index') ? 'active' : 'text-muted' }}" style="font-size: 13px;">
                                @if(request()->routeIs('factories.index')) <i class="bi bi-circle-fill me-2" style="font-size: 5px;"></i> @endif All Factories
                            </a>
                            
                            @if(request()->routeIs('factories.show') || request()->routeIs('certificates.*'))
                                @if(request()->routeIs('certificates.*'))
                                    <div class="text-decoration-none fw-bold submenu-item text-muted opacity-50" style="font-size: 13px; cursor: not-allowed;">
                                        Factory Details
                                    </div>
                                    <a href="#" class="text-decoration-none fw-bold submenu-item active" style="font-size: 13px;">
                                        <i class="bi bi-circle-fill me-2" style="font-size: 5px;"></i> Certificate Details
                                    </a>
                                @else
                                    <a href="#" class="text-decoration-none fw-bold submenu-item active" style="font-size: 13px;">
                                        <i class="bi bi-circle-fill me-2" style="font-size: 5px;"></i> Factory Details
                                    </a>
                                @endif
                            @endif
                        </div>
                    </div>
                    
                    <a href="{{ route('standards.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('standards.*') ? 'active' : '' }}" title="Standards">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-file-earmark-text-fill fs-5"></i>
                        </div>
                        <span class="sidebar-text">Standards</span>
                    </a>

                    <a href="{{ route('standard-stages.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('standard-stages.*') ? 'active' : '' }}" title="Renewal Stages">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-sign-turn-right-fill fs-5 text-success"></i>
                        </div>
                        <span class="sidebar-text">Renewal Stages</span>
                    </a>

                    <a href="{{ route('stafford-certs.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('stafford-certs.*') ? 'active' : '' }}" title="CPSC e-Filing">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <img src="{{ asset('images/cpsc-icon.png') }}" alt="CPSC e-Filing" style="width: 28px; height: 28px; object-fit: contain; border-radius: 4px;">
                        </div>
                        <span class="sidebar-text">CPSC e-Filing</span>
                    </a>

                    <a href="{{ route('test-report-types.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('test-report-types.*') ? 'active' : '' }}" title="Test Report Types">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-card-checklist fs-5 text-info"></i>
                        </div>
                        <span class="sidebar-text">Test Report Types</span>
                    </a>

                    <a href="{{ route('rfid-generation.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('rfid-generation.*') ? 'active' : '' }}">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-upc-scan fs-5 text-primary"></i>
                        </div>
                        <span class="sidebar-text">RFID Generation</span>
                    </a>



                    <a href="{{ route('purchase-orders.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" title="Purchase Orders">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-file-earmark-text-fill fs-5" style="color: #10b981;"></i>
                        </div>
                        <span class="sidebar-text">Purchase Orders</span>
                    </a>

                        <!-- Master Data Dropdown (Desktop) -->
                    @php
                        $isMasterDataActive = request()->is('master-data*');
                    @endphp
                    <a class="nav-item px-3 py-2 {{ $isMasterDataActive ? 'active' : '' }}" 
                       data-bs-toggle="collapse" 
                       href="#masterDataCollapse" 
                       role="button" 
                       aria-expanded="{{ $isMasterDataActive ? 'true' : 'false' }}" 
                       aria-controls="masterDataCollapse" 
                       title="Master Data">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-database-fill-gear fs-5"></i>
                        </div>
                        <span class="sidebar-text flex-grow-1">Master Data</span>
                        <i class="bi bi-chevron-down ms-auto sidebar-text" style="font-size: 10px;"></i>
                    </a>
                    <div class="collapse {{ $isMasterDataActive ? 'show' : '' }}" id="masterDataCollapse">
                        <div class="ms-5 ps-2 border-start border-2 border-light py-1 mb-2">
                            <a href="{{ route('master-data.seasons.index') }}" class="text-decoration-none fw-bold submenu-item {{ request()->routeIs('master-data.seasons.*') ? 'active' : 'text-muted' }}" style="font-size: 13px;">
                                @if(request()->routeIs('master-data.seasons.*')) <i class="bi bi-circle-fill me-2" style="font-size: 5px;"></i> @endif Season
                            </a>
                            {{-- Future items will go here:
                            <a href="{{ route('master-data.programs.index') }}" class="text-decoration-none fw-bold submenu-item {{ request()->routeIs('master-data.programs.*') ? 'active' : 'text-muted' }}" style="font-size: 13px;">
                                Program
                            </a>
                            --}}
                        </div>
                    </div>
                    <a href="{{ route('activity-logs.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('activity-logs.*') ? 'active' : '' }}" title="Activity Logs">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-clock-history fs-5"></i>
                        </div>
                        <span class="sidebar-text">Activity Logs</span>
                    </a>
                    @endrole

                     @role('factory')
                    <a href="{{ route('factory-portal.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('factory-portal.*') ? 'active' : '' }}" title="My Allocations">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-box-seam-fill fs-5" style="color: #00A39A;"></i>
                        </div>
                        <span class="sidebar-text flex-grow-1">My Allocations</span>
                    </a>

                    <a href="{{ route('purchase-orders.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}" title="Purchase Orders">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-file-earmark-text-fill fs-5" style="color: #10b981;"></i>
                        </div>
                        <span class="sidebar-text flex-grow-1">Purchase Orders</span>
                    </a>
                    @endrole

                    @role('lab')
                    <a href="{{ route('lab.pos.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('lab.pos.*') ? 'active' : '' }}" title="All Details">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-card-list fs-5 text-primary"></i>
                        </div>
                        <span class="sidebar-text flex-grow-1">All Details</span>
                    </a>

                    <a href="{{ route('lab.tasks.completed') }}" class="nav-item px-3 py-2 {{ request()->routeIs('lab.tasks.*') ? 'active' : '' }}" title="Submitted Reports">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-check2-circle fs-5 text-success"></i>
                        </div>
                        <span class="sidebar-text flex-grow-1">Submitted Reports</span>
                    </a>

                    <a href="{{ route('test-report-types.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('test-report-types.*') ? 'active' : '' }}" title="Test Report Types">
                        <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                            <i class="bi bi-card-checklist fs-5 text-info"></i>
                        </div>
                        <span class="sidebar-text">Test Report Types</span>
                    </a>
                    @endrole
                </nav>

                <!-- User Profile Bubble -->
                <div class="p-2 mt-auto">
                    <div class="d-flex align-items-center bg-white rounded-pill p-2 shadow-sm border">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()->name ?? 'User') }}&background=0D8ABC&color=fff" alt="User Avatar" class="rounded-circle border flex-shrink-0" style="width: 36px; height: 36px;">
                        <div class="ms-2 sidebar-text overflow-hidden">
                            <div class="fw-bold text-dark text-truncate" style="font-size: 13px;">{{ auth()->user()->name ?? 'User' }}</div>
                            <div class="text-muted fw-semibold text-truncate" style="font-size: 11px;">{{ auth()->user()->roles->first()?->name ?? 'Role' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <!-- Top Header -->
            <header class="top-header mx-3 mt-3 shadow-sm">
                <div class="d-flex align-items-center">
                    <button class="btn btn-light rounded-circle d-lg-none me-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" style="width: 40px; height: 40px;">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <h1 class="h4 mb-0 fw-bolder text-dark text-capitalize">@yield('header', 'CertTrack')</h1>
                </div>
                
                <div class="d-flex align-items-center gap-3">
                    @unless(request()->routeIs('dashboard'))
                    <div class="d-none d-md-flex align-items-center position-relative">
                        <input type="text" placeholder="Quick search..." class="form-control rounded-pill pe-5 shadow-sm border-white" style="width: 250px;">
                        <i class="bi bi-search position-absolute text-muted" style="right: 15px;"></i>
                    </div>
                    @endunless

                    @if(Route::has('logout'))
                    <form method="POST" action="{{ route('logout') }}" class="m-0">
                        @csrf
                        <button type="submit" class="btn btn-light rounded-circle shadow-sm border-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;" title="Log Out">
                            <i class="bi bi-box-arrow-right fs-5 text-secondary"></i>
                        </button>
                    </form>
                    @endif
                </div>
            </header>

            <!-- Content Area -->
            <div class="content-area px-4 py-4">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show rounded-4 fw-semibold border-0 shadow-sm" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show rounded-4 fw-semibold border-0 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show rounded-4 fw-semibold border-0 shadow-sm" role="alert">
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
                
                @yield('content')
            </div>
        </main>
    </div>

    <!-- Mobile Sidebar Offcanvas -->
    <div class="offcanvas offcanvas-start border-0" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel" style="width: 280px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);">
        <div class="offcanvas-header pb-0">
            <div class="d-flex align-items-center">
                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px; background: linear-gradient(135deg, #124E78, #00A39A); color: white;">
                    <i class="bi bi-patch-check-fill fs-5"></i>
                </div>
                <span class="fs-4 fw-bolder ms-3 text-dark">CertTrack</span>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            <nav class="flex-grow-1">
                <div class="text-uppercase fw-bolder text-muted mb-2 ms-2" style="font-size: 10px; letter-spacing: 1px;">Menu</div>
                
                <a href="{{ route('dashboard') }}" class="nav-item px-3 py-2 {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                        <i class="bi bi-grid-fill fs-5"></i>
                    </div>
                    <span>Dashboard</span>
                </a>

                @role('super_admin')
                @php
                    $isMobileUsersActive = request()->routeIs('users.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') || request()->routeIs('cpsc-tokens.*');
                @endphp
                <a class="nav-item px-3 py-2 {{ $isMobileUsersActive ? 'active' : '' }}" 
                   data-bs-toggle="collapse" 
                   href="#mobileUsersCollapse" 
                   role="button" 
                   aria-expanded="{{ $isMobileUsersActive ? 'true' : 'false' }}" 
                   aria-controls="mobileUsersCollapse">
                    <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                        <i class="bi bi-people-fill fs-5"></i>
                    </div>
                    <span class="flex-grow-1">Users</span>
                    <i class="bi bi-chevron-down ms-auto" style="font-size: 10px;"></i>
                </a>
                <div class="collapse {{ $isMobileUsersActive ? 'show' : '' }}" id="mobileUsersCollapse">
                    <div class="ms-5 ps-2 border-start border-2 border-light py-1 mb-2">
                        <a href="{{ route('users.index') }}" class="text-decoration-none fw-bold submenu-item {{ request()->routeIs('users.*') ? 'active' : 'text-muted' }}" style="font-size: 13px;">
                            @if(request()->routeIs('users.*')) <i class="bi bi-circle-fill me-2" style="font-size: 5px;"></i> @endif User Account
                        </a>
                        <a href="{{ route('roles.index') }}" class="text-decoration-none fw-bold submenu-item {{ request()->routeIs('roles.*') ? 'active' : 'text-muted' }}" style="font-size: 13px;">
                            @if(request()->routeIs('roles.*')) <i class="bi bi-circle-fill me-2" style="font-size: 5px;"></i> @endif User Type / Roles
                        </a>
                        <a href="{{ route('permissions.grid') }}" class="text-decoration-none fw-bold submenu-item {{ request()->routeIs('permissions.*') ? 'active' : 'text-muted' }}" style="font-size: 13px;">
                            @if(request()->routeIs('permissions.*')) <i class="bi bi-circle-fill me-2" style="font-size: 5px;"></i> @endif Permissions Grid
                        </a>
                        <a href="{{ route('cpsc-tokens.index') }}" class="text-decoration-none fw-bold submenu-item {{ request()->routeIs('cpsc-tokens.*') ? 'active' : 'text-muted' }}" style="font-size: 13px;">
                            @if(request()->routeIs('cpsc-tokens.*')) <i class="bi bi-circle-fill me-2" style="font-size: 5px;"></i> @endif CPSC API Tokens
                        </a>
                    </div>
                </div>
                
                @php
                    $isMobileFactoriesActive = request()->routeIs('factories.*') || request()->routeIs('certificates.*');
                @endphp
                <a class="nav-item px-3 py-2 {{ $isMobileFactoriesActive ? 'active' : '' }}" 
                   data-bs-toggle="collapse" 
                   href="#mobileFactoriesCollapse" 
                   role="button" 
                   aria-expanded="{{ $isMobileFactoriesActive ? 'true' : 'false' }}" 
                   aria-controls="mobileFactoriesCollapse">
                    <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                        <i class="bi bi-buildings-fill fs-5"></i>
                    </div>
                    <span class="flex-grow-1">Factories</span>
                    <i class="bi bi-chevron-down ms-auto" style="font-size: 10px;"></i>
                </a>
                <div class="collapse {{ $isMobileFactoriesActive ? 'show' : '' }}" id="mobileFactoriesCollapse">
                    <div class="ms-5 ps-2 border-start border-2 border-light py-1 mb-2">
                        <a href="{{ route('factories.index') }}" class="text-decoration-none fw-bold submenu-item {{ request()->routeIs('factories.index') ? 'active' : 'text-muted' }}" style="font-size: 13px;">
                            @if(request()->routeIs('factories.index')) <i class="bi bi-circle-fill me-2" style="font-size: 5px;"></i> @endif All Factories
                        </a>
                    </div>
                </div>

                <a href="{{ route('standards.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('standards.*') ? 'active' : '' }}">
                    <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                        <i class="bi bi-file-earmark-text-fill fs-5"></i>
                    </div>
                    <span>Standards</span>
                </a>

                <a href="{{ route('standard-stages.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('standard-stages.*') ? 'active' : '' }}">
                    <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                        <i class="bi bi-sign-turn-right-fill fs-5 text-success"></i>
                    </div>
                    <span>Renewal Stages</span>
                </a>

                <a href="{{ route('stafford-certs.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('stafford-certs.*') ? 'active' : '' }}">
                    <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                        <img src="{{ asset('images/cpsc-icon.png') }}" alt="CPSC e-Filing" style="width: 28px; height: 28px; object-fit: contain; border-radius: 4px;">
                    </div>
                    <span>CPSC e-Filing</span>
                </a>

                <a href="{{ route('test-report-types.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('test-report-types.*') ? 'active' : '' }}">
                    <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                        <i class="bi bi-card-checklist fs-5 text-info"></i>
                    </div>
                    <span>Test Report Types</span>
                </a>

                <a href="{{ route('rfid-generation.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('rfid-generation.*') ? 'active' : '' }}">
                    <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                        <i class="bi bi-upc-scan fs-5 text-primary"></i>
                    </div>
                    <span>RFID Generation</span>
                </a>



                <a href="{{ route('purchase-orders.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}">
                    <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                        <i class="bi bi-file-earmark-text-fill fs-5" style="color: #10b981;"></i>
                    </div>
                    <span>Purchase Orders</span>
                </a>
                <!-- Master Data Dropdown (Mobile) -->
                @php
                    $isMobileMasterDataActive = request()->is('master-data*');
                @endphp
                <a class="nav-item px-3 py-2 {{ $isMobileMasterDataActive ? 'active' : '' }}" 
                   data-bs-toggle="collapse" 
                   href="#mobileMasterDataCollapse" 
                   role="button" 
                   aria-expanded="{{ $isMobileMasterDataActive ? 'true' : 'false' }}" 
                   aria-controls="mobileMasterDataCollapse">
                    <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                        <i class="bi bi-database-fill-gear fs-5"></i>
                    </div>
                    <span class="flex-grow-1">Master Data</span>
                    <i class="bi bi-chevron-down ms-auto" style="font-size: 10px;"></i>
                </a>
                <div class="collapse {{ $isMobileMasterDataActive ? 'show' : '' }}" id="mobileMasterDataCollapse">
                    <div class="ms-5 ps-2 border-start border-2 border-light py-1 mb-2">
                        <a href="{{ route('master-data.seasons.index') }}" class="text-decoration-none fw-bold submenu-item {{ request()->routeIs('master-data.seasons.*') ? 'active' : 'text-muted' }}" style="font-size: 13px;">
                            @if(request()->routeIs('master-data.seasons.*')) <i class="bi bi-circle-fill me-2" style="font-size: 5px;"></i> @endif Season
                        </a>
                    </div>
                </div>
                @endrole

                 @role('factory')
                <a href="{{ route('factory-portal.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('factory-portal.*') ? 'active' : '' }}">
                    <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                        <i class="bi bi-box-seam-fill fs-5" style="color: #00A39A;"></i>
                    </div>
                    <span>My Allocations</span>
                </a>

                <a href="{{ route('purchase-orders.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('purchase-orders.*') ? 'active' : '' }}">
                    <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                        <i class="bi bi-file-earmark-text-fill fs-5" style="color: #10b981;"></i>
                    </div>
                    <span>Purchase Orders</span>
                </a>
                @endrole

                @role('lab')
                <a href="{{ route('lab.pos.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('lab.pos.*') ? 'active' : '' }}">
                    <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                        <i class="bi bi-card-list fs-5 text-primary"></i>
                    </div>
                    <span>All Details</span>
                </a>

                <a href="{{ route('lab.tasks.completed') }}" class="nav-item px-3 py-2 {{ request()->routeIs('lab.tasks.*') ? 'active' : '' }}">
                    <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                        <i class="bi bi-check2-circle fs-5 text-success"></i>
                    </div>
                    <span>Submitted Reports</span>
                </a>

                <a href="{{ route('test-report-types.index') }}" class="nav-item px-3 py-2 {{ request()->routeIs('test-report-types.*') ? 'active' : '' }}">
                    <div class="nav-icon-wrapper me-3" style="width: 32px; height: 32px;">
                        <i class="bi bi-card-checklist fs-5 text-info"></i>
                    </div>
                    <span>Test Report Types</span>
                </a>
                @endrole
            </nav>
        </div>
    </div>

    <!-- Generic Confirm Modal -->
    <div class="modal fade" id="confirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="modal-header border-bottom-0 pb-0">
                    <h5 class="modal-title fw-bolder text-dark">Confirm Action</h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <div class="bg-danger bg-opacity-10 text-danger rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 60px; height: 60px;" id="confirmModalIconContainer">
                        <i class="bi bi-exclamation-triangle-fill fs-3" id="confirmModalIcon"></i>
                    </div>
                    <h6 class="fw-bold text-dark mb-2" id="confirmModalMessage">Are you sure?</h6>
                    <p class="text-muted small mb-0" id="confirmModalSubmessage">This action cannot be undone.</p>
                </div>
                <div class="modal-footer border-top-0 d-flex justify-content-center pt-0 pb-4">
                    <button type="button" class="btn btn-light rounded-pill px-4 fw-bold" data-bs-dismiss="modal">Cancel</button>
                    <form id="confirmForm" method="POST" class="d-inline m-0">
                        @csrf
                        <input type="hidden" name="_method" id="confirmMethod" value="POST">
                        <div id="confirmHiddenInputs"></div>
                        <button type="submit" class="btn btn-danger rounded-pill px-4 fw-bold" id="confirmSubmitBtn">Yes, Proceed</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @yield('scripts')
    @stack('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Desktop Sidebar Collapse Toggle
            const desktopSidebar = document.getElementById('desktopSidebar');
            const sidebarToggleBtn = document.getElementById('sidebarToggleBtn');
            const sidebarToggleIcon = document.getElementById('sidebarToggleIcon');

            if (sidebarToggleBtn && desktopSidebar) {
                const isCollapsed = localStorage.getItem('sidebar-collapsed') === 'true';
                if (isCollapsed) {
                    desktopSidebar.classList.add('collapsed');
                    sidebarToggleIcon.classList.replace('bi-chevron-left', 'bi-chevron-right');
                }

                sidebarToggleBtn.addEventListener('click', function () {
                    desktopSidebar.classList.toggle('collapsed');
                    const currentlyCollapsed = desktopSidebar.classList.contains('collapsed');
                    
                    if (currentlyCollapsed) {
                        sidebarToggleIcon.classList.replace('bi-chevron-left', 'bi-chevron-right');
                    } else {
                        sidebarToggleIcon.classList.replace('bi-chevron-right', 'bi-chevron-left');
                    }

                    localStorage.setItem('sidebar-collapsed', currentlyCollapsed);
                });
            }

            // Confirm Modal Handler
            var confirmModal = document.getElementById('confirmModal');
            if (confirmModal) {
                confirmModal.addEventListener('show.bs.modal', function (event) {
                    var button = event.relatedTarget;
                    var action = button.getAttribute('data-action');
                    var message = button.getAttribute('data-message') || 'Are you sure?';
                    var submessage = button.getAttribute('data-submessage') || 'This action cannot be undone.';
                    var method = button.getAttribute('data-method') || 'DELETE';
                    var btnText = button.getAttribute('data-btn-text') || 'Yes, Delete';
                    var btnClass = button.getAttribute('data-btn-class') || 'btn-danger';
                    
                    var form = confirmModal.querySelector('#confirmForm');
                    form.action = action;
                    form.querySelector('#confirmMethod').value = method;
                    
                    confirmModal.querySelector('#confirmModalMessage').textContent = message;
                    confirmModal.querySelector('#confirmModalSubmessage').textContent = submessage;
                    
                    var submitBtn = confirmModal.querySelector('#confirmSubmitBtn');
                    submitBtn.textContent = btnText;
                    submitBtn.className = 'btn rounded-pill px-4 fw-bold ' + btnClass;
                    
                    var hiddenInputsContainer = confirmModal.querySelector('#confirmHiddenInputs');
                    hiddenInputsContainer.innerHTML = '';
                    var inputsData = button.getAttribute('data-inputs');
                    if (inputsData) {
                        try {
                            var inputs = JSON.parse(inputsData);
                            for (var key in inputs) {
                                var input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = key;
                                input.value = inputs[key];
                                hiddenInputsContainer.appendChild(input);
                            }
                        } catch (e) {
                            console.error('Invalid JSON for data-inputs', e);
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>