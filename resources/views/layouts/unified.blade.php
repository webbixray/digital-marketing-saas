<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ config('app.name') }}</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Tailwind CSS -->
    @vite(['resources/css/unified.css', 'resources/js/unified.js'])
    
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6366f1">

    @stack('styles')
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100" style="font-family: 'Inter', sans-serif;">

    <!-- Service Worker -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => {
                navigator.serviceWorker.register('/sw.js').catch(() => {});
            });
        }
    </script>
    
    <!-- Skip to main content -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[100] focus:bg-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg">
        Skip to main content
    </a>
    
    <!-- Mobile sidebar overlay -->
    <div x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden" @click="sidebarOpen = false"></div>
    
    <!-- Sidebar -->
    <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 dark:bg-gray-900 dark:border-gray-800 lg:static lg:translate-x-0 transition-transform duration-300 ease-in-out">
        <div class="flex h-full flex-col">
            <!-- Logo -->
            <div class="flex h-16 items-center justify-between px-6 border-b border-gray-200 dark:border-gray-800">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center">
                        <i class="fas fa-bolt text-white text-sm"></i>
                    </div>
                    <span class="font-bold text-gray-900 dark:text-white">DMSaaS</span>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400" aria-label="Close menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto px-3 py-4">
                <!-- Main Navigation -->
                <div class="space-y-1">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" aria-label="Dashboard">
                        <i class="fas fa-th-large w-4"></i> Dashboard
                    </a>
                </div>

                <!-- Social Media -->
                <div class="mt-6">
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Social Media</p>
                    <div class="space-y-1">
                        <a href="{{ route('social.posts.index') }}" class="nav-link {{ request()->routeIs('social.posts.*') ? 'active' : '' }}">
                            <i class="fas fa-pen-nib w-4"></i> Posts
                        </a>
                        <a href="{{ route('social.accounts.index') }}" class="nav-link {{ request()->routeIs('social.accounts.*') ? 'active' : '' }}">
                            <i class="fas fa-share-alt w-4"></i> Accounts
                        </a>
                        <a href="{{ route('unified-inbox.index') }}" class="nav-link {{ request()->routeIs('unified-inbox.*') ? 'active' : '' }}">
                            <i class="fas fa-inbox w-4"></i> Inbox
                        </a>
                        <a href="{{ route('calendar.index') }}" class="nav-link {{ request()->routeIs('calendar.*') ? 'active' : '' }}">
                            <i class="fas fa-calendar w-4"></i> Calendar
                        </a>
                        <a href="{{ route('analytics.index') }}" class="nav-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}">
                            <i class="fas fa-chart-line w-4"></i> Analytics
                        </a>
                    </div>
                </div>

                <!-- Marketing -->
                <div class="mt-6">
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Marketing</p>
                    <div class="space-y-1">
                        <a href="{{ route('campaigns.index') }}" class="nav-link {{ request()->routeIs('campaigns.*') ? 'active' : '' }}">
                            <i class="fas fa-bullhorn w-4"></i> Campaigns
                        </a>
                        <a href="{{ route('clients.index') }}" class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}">
                            <i class="fas fa-users w-4"></i> Clients
                        </a>
                        <a href="{{ route('content.index') }}" class="nav-link {{ request()->routeIs('content.*') ? 'active' : '' }}">
                            <i class="fas fa-folder-open w-4"></i> Content
                        </a>
                    </div>
                </div>

                <!-- AI & Automation -->
                <div class="mt-6">
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">AI & Automation</p>
                    <div class="space-y-1">
                        <a href="{{ route('agents.dashboard') }}" class="nav-link {{ request()->routeIs('agents.*') ? 'active' : '' }}">
                            <i class="fas fa-robot w-4"></i> AI Agents
                        </a>
                        <a href="{{ route('ai.index') }}" class="nav-link {{ request()->routeIs('ai.*') ? 'active' : '' }}">
                            <i class="fas fa-sparkles w-4"></i> AI Content
                        </a>
                        <a href="{{ route('workflows.index') }}" class="nav-link {{ request()->routeIs('workflows.*') ? 'active' : '' }}">
                            <i class="fas fa-project-diagram w-4"></i> Workflows
                        </a>
                    </div>
                </div>

                <!-- Business -->
                <div class="mt-6">
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Business</p>
                    <div class="space-y-1">
                        <a href="{{ route('invoices.index') }}" class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}">
                            <i class="fas fa-file-invoice w-4"></i> Invoices
                        </a>
                        <a href="{{ route('agency.billing') }}" class="nav-link {{ request()->routeIs('agency.billing') ? 'active' : '' }}">
                            <i class="fas fa-credit-card w-4"></i> Billing
                        </a>
                        <a href="{{ route('agency.team') }}" class="nav-link {{ request()->routeIs('agency.team*') ? 'active' : '' }}">
                            <i class="fas fa-user-friends w-4"></i> Team
                        </a>
                        <a href="{{ route('agency.settings') }}" class="nav-link {{ request()->routeIs('agency.settings*') ? 'active' : '' }}">
                            <i class="fas fa-cog w-4"></i> Settings
                        </a>
                    </div>
                </div>
            </nav>

            <!-- User panel -->
            <div class="border-t border-gray-200 dark:border-gray-800 p-4">
                <div class="flex items-center gap-3">
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()?->name ?? 'User') }}&background=6366f1&color=fff&size=32" class="w-8 h-8 rounded-full" alt="{{ auth()->user()?->name ?? 'User' }} avatar">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ auth()->user()?->name ?? 'User' }}</p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()?->email ?? '' }}</p>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Logout" aria-label="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main content -->
    <div class="flex flex-1 flex-col lg:pl-0">
        <!-- Top bar -->
        <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-gray-200 bg-white/80 dark:bg-gray-900/80 dark:border-gray-800 px-6 backdrop-blur-sm">
            <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400" aria-label="Open menu">
                <i class="fas fa-bars text-xl"></i>
            </button>
            <div class="flex-1">
                <h1 class="text-lg font-semibold text-gray-900 dark:text-white">@yield('title', 'Dashboard')</h1>
            </div>
            <div class="flex items-center gap-4">
                <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-colors" aria-label="Toggle dark mode">
                    <i x-show="!darkMode" class="fas fa-moon"></i>
                    <i x-show="darkMode" class="fas fa-sun"></i>
                </button>
                <button class="relative text-gray-500 hover:text-gray-700 dark:text-gray-400" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    <span class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                </button>
                <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()?->name ?? 'User') }}&background=6366f1&color=fff&size=32" class="w-8 h-8 rounded-full cursor-pointer" alt="{{ auth()->user()?->name ?? 'User' }} avatar">
            </div>
        </header>

        <!-- Page content -->
        <main class="flex-1 overflow-auto p-6" id="main-content">
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
