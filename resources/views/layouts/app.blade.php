<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $agency?->name ?? 'Agency' }} — @yield('title', 'Dashboard') | {{ config('app.name') }}</title>

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- AdminLTE -->
    <link rel="stylesheet" href="/vendor/css/adminlte.min.css">
    <!-- overlayScrollBars -->
    <link rel="stylesheet" href="/vendor/css/OverlayScrollbars.min.css">
    <!-- Toastr -->
    <link rel="stylesheet" href="/vendor/css/toastr.min.css">

    <style>
        .sidebar-dark-primary .nav-sidebar > .nav-item > .nav-link.active,
        .sidebar-light-primary .nav-sidebar > .nav-item > .nav-link.active {
            background-color: #007bff;
            color: #fff;
        }
        .brand-link {
            text-align: center;
        }
        .brand-text {
            font-weight: 600 !important;
        }
        .content-wrapper {
            background-color: #f4f6f9;
        }
        .small-box {
            border-radius: 0.5rem;
        }
        .card {
            border-radius: 0.5rem;
            box-shadow: 0 0 1px rgba(0,0,0,.125), 0 1px 3px rgba(0,0,0,.2);
        }
        .btn {
            border-radius: 0.25rem;
        }
        .table td, .table th {
            vertical-align: middle;
        }
        .badge {
            font-size: 85%;
            padding: 0.35em 0.65em;
        }
        .main-sidebar {
            background-color: #343a40;
        }
        .nav-link {
            border-radius: 0.25rem;
            margin: 0.1rem 0.5rem;
        }
        .nav-sidebar > .nav-item {
            margin-bottom: 0.1rem;
        }
        .sidebar-form, .nav-header {
            padding: 0 0.5rem;
        }
        .sidebar-form .form-control {
            background-color: #3f474e;
            border: 1px solid #565e65;
            color: #fff;
        }
        .sidebar-form .form-control::placeholder {
            color: #adb5bd;
        }
        .sidebar-form .btn-sidebar {
            background-color: #3f474e;
            border: 1px solid #565e65;
            color: #fff;
        }
        .sidebar-mini .main-sidebar .nav-link p,
        .sidebar-mini-md .main-sidebar .nav-link p,
        .sidebar-mini-xs .main-sidebar .nav-link p {
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .sidebar-collapse .main-sidebar .nav-link p {
            display: none;
        }
        .sidebar-collapse .main-sidebar .nav-link {
            text-align: center;
            padding: 0.8rem 1rem;
        }
        .sidebar-collapse .main-sidebar .nav-link i {
            margin-right: 0 !important;
            font-size: 1.1rem;
        }
        .sidebar-collapse .main-sidebar .nav-link .right {
            display: none;
        }
        .sidebar-collapse .main-sidebar .brand-text {
            display: none;
        }
        .sidebar-collapse .main-sidebar .nav-header {
            display: none;
        }
        .sidebar-collapse .main-sidebar .nav-treeview {
            display: none !important;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview {
            display: block !important;
            position: absolute;
            left: 70px;
            top: 0;
            width: 200px;
            background-color: #343a40;
            z-index: 1048;
            padding: 0.5rem;
            border-radius: 0 0.25rem 0.25rem 0;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link {
            padding: 0.5rem 1rem;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link p {
            display: block;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link i {
            margin-right: 0.5rem !important;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link .right {
            display: block;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-header {
            display: block;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-treeview {
            display: none !important;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview {
            display: block !important;
            left: 200px;
            top: 0;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-link {
            padding: 0.5rem 1rem;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-link p {
            display: block;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-link i {
            margin-right: 0.5rem !important;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-link .right {
            display: block;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-header {
            display: block;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-treeview {
            display: none !important;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview {
            display: block !important;
            left: 200px;
            top: 0;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-link {
            padding: 0.5rem 1rem;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-link p {
            display: block;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-link i {
            margin-right: 0.5rem !important;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-link .right {
            display: block;
        }
        .sidebar-collapse .main-sidebar .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-link:hover .nav-treeview .nav-header {
            display: block;
        }
    </style>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed layout-navbar-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto">
            <!-- Notifications Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                    <span class="dropdown-item dropdown-header">Notifications</span>
                    <div class="dropdown-divider"></div>
                    <span class="dropdown-item text-center text-muted">No new notifications</span>
                </div>
            </li>

            <!-- User Dropdown Menu -->
            <li class="nav-item dropdown">
                <a class="nav-link" data-toggle="dropdown" href="#">
                    <i class="fas fa-user-circle"></i> {{ auth()->user()?->name ?? 'User' }}
                </a>
                <div class="dropdown-menu dropdown-menu-right">
                    <a href="{{ route('agency.settings') }}" class="dropdown-item">
                        <i class="fas fa-cog mr-2"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item">
                            <i class="fas fa-sign-out-alt mr-2"></i> Logout
                        </button>
                    </form>
                </div>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="{{ route('dashboard') }}" class="brand-link">
            <span class="brand-text font-weight-light">{{ config('app.name') }}</span>
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel (optional) -->
            <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                <div class="image">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()?->name ?? 'User') }}&background=007bff&color=fff" class="img-circle elevation-2" alt="{{ auth()->user()?->name ?? 'User' }}">
                </div>
                <div class="info">
                    <a href="#" class="d-block">{{ auth()->user()?->name ?? 'User' }}</a>
                    <span class="text-muted text-sm">{{ ucfirst(auth()->user()?->getRoleNames()[0] ?? 'Member') }}</span>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">

                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>

                    <li class="nav-header">SOCIAL MEDIA</li>

                    <li class="nav-item">
                        <a href="{{ route('social.posts.index') }}" class="nav-link {{ request()->routeIs('social.posts.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-pen-fancy"></i>
                            <p>Posts</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('social.accounts.index') }}" class="nav-link {{ request()->routeIs('social.accounts.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-share-alt"></i>
                            <p>Accounts</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('inbox.index') }}" class="nav-link {{ request()->routeIs('inbox.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-inbox"></i>
                            <p>Inbox</p>
                            @if(isset($unreadCount) && $unreadCount > 0)
                                <span class="right badge badge-danger">{{ $unreadCount }}</span>
                            @endif
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('calendar.index') }}" class="nav-link {{ request()->routeIs('calendar.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-calendar-alt"></i>
                            <p>Calendar</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('analytics.index') }}" class="nav-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-bar"></i>
                            <p>Analytics</p>
                        </a>
                    </li>

                    <li class="nav-header">MARKETING</li>

                    <li class="nav-item">
                        <a href="{{ route('campaigns.index') }}" class="nav-link {{ request()->routeIs('campaigns.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-bullhorn"></i>
                            <p>Campaigns</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('clients.index') }}" class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-users"></i>
                            <p>Clients</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('content.index') }}" class="nav-link {{ request()->routeIs('content.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-folder-open"></i>
                            <p>Content Library</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('landing-pages.index') }}" class="nav-link {{ request()->routeIs('landing-pages.*') ? 'active' : '' }}">
                            <i class="nav-icon fa-solid fa-file-lines"></i>
                            <p>Landing Pages</p>
                        </a>
                    </li>

                    <li class="nav-header">AI & AUTOMATION</li>

                    <li class="nav-item">
                        <a href="{{ route('agents.dashboard') }}" class="nav-link {{ request()->routeIs('agents.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-robot"></i>
                            <p>AI Agents</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('ai.index') }}" class="nav-link {{ request()->routeIs('ai.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-sparkles"></i>
                            <p>AI Content</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('workflows.index') }}" class="nav-link {{ request()->routeIs('workflows.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-project-diagram"></i>
                            <p>Workflows</p>
                        </a>
                    </li>

                    <li class="nav-header">BUSINESS</li>

                    <li class="nav-item">
                        <a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-file-invoice-dollar"></i>
                            <p>Invoices</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('agency.billing') }}" class="nav-link {{ request()->routeIs('agency.billing') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-credit-card"></i>
                            <p>Billing</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('agency.team') }}" class="nav-link {{ request()->routeIs('agency.team*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-friends"></i>
                            <p>Team</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('ab-testing.index') }}" class="nav-link {{ request()->routeIs('ab-testing.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-flask"></i>
                            <p>A/B Testing</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('support.index') }}" class="nav-link {{ request()->routeIs('support.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-life-ring"></i>
                            <p>Support</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('referrals.index') }}" class="nav-link {{ request()->routeIs('referrals.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-gift"></i>
                            <p>Referrals</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('system.status') }}" class="nav-link {{ request()->routeIs('system.status') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-heartbeat"></i>
                            <p>System Status</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('system.backup.index') }}" class="nav-link {{ request()->routeIs('system.backup*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-database"></i>
                            <p>Backups</p>
                        </a>
                    </li>

                    <li class="nav-item">
                        <a href="{{ route('agency.settings') }}" class="nav-link {{ request()->routeIs('agency.settings*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cog"></i>
                            <p>Settings</p>
                        </a>
                    </li>

                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper. Contains page content -->
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1 class="m-0">@yield('title', 'Dashboard')</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                            @yield('breadcrumb')
                            <li class="breadcrumb-item active">@yield('title', 'Dashboard')</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.content-header -->

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">

                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle mr-2"></i> {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('warning') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @yield('content')

            </div>
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->

    <!-- Main Footer -->
    <footer class="main-footer">
        <strong>Copyright &copy; {{ date('Y') }} <a href="#">{{ config('app.name') }}</a>.</strong> All rights reserved.
    </footer>

    <!-- Control Sidebar -->
    <aside class="control-sidebar control-sidebar-dark">
    </aside>
    <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->

<!-- jQuery -->
<script src="/vendor/js/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="/vendor/js/bootstrap.bundle.min.js"></script>
<!-- overlayScrollBars -->
<script src="/vendor/js/OverlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="/vendor/js/adminlte.min.js"></script>
<!-- Toastr -->
<script src="/vendor/js/toastr.min.js"></script>

<script>
    $(function() {
        // Toastr options
        toastr.options = {
            closeButton: true,
            progressBar: true,
            positionClass: 'toast-top-right',
            timeOut: 5000,
        };

        // Show flash messages via toastr
        @if(session('success'))
            toastr.success('{{ session('success') }}');
        @endif
        @if(session('error'))
            toastr.error('{{ session('error') }}');
        @endif
        @if(session('warning'))
            toastr.warning('{{ session('warning') }}');
        @endif
    });
</script>

@stack('scripts')

<!-- Cookie Consent Banner -->
<div id="cookie-banner" class="fixed-bottom bg-dark text-white p-3 d-none" style="z-index: 9999;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <p class="mb-0">We use cookies to enhance your experience. By continuing to visit this site you agree to our use of cookies. <a href="/privacy" class="text-info">Learn more</a></p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-primary btn-sm" onclick="acceptCookies()">Accept</button>
                <button class="btn btn-outline-light btn-sm" onclick="declineCookies()">Decline</button>
            </div>
        </div>
    </div>
</div>

<script>
function acceptCookies() {
    localStorage.setItem('cookie_consent', 'accepted');
    document.getElementById('cookie-banner').classList.add('d-none');
}

function declineCookies() {
    localStorage.setItem('cookie_consent', 'declined');
    document.getElementById('cookie-banner').classList.add('d-none');
}

document.addEventListener('DOMContentLoaded', function() {
    if (!localStorage.getItem('cookie_consent')) {
        document.getElementById('cookie-banner').classList.remove('d-none');
    }
});
</script>

</body>
</html>
