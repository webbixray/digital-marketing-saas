<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') | {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    @vite(['resources/css/modern.css', 'resources/js/modern.js'])
    @stack('styles')
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100" style="font-family: 'Inter', sans-serif;">
    <div class="flex h-full" x-data="{ sidebarOpen: false, darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark': darkMode }">
        
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
                    <button @click="sidebarOpen = false" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400">
                        <i class="fas fa-times"></i>
                    </button>
                </div>

                <!-- Nav -->
                <nav class="flex-1 overflow-y-auto px-3 py-4">
                    <div class="space-y-1">
                        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                            <i class="fas fa-th-large w-4 text-center"></i>
                            Dashboard
                        </a>
                    </div>

                    <div class="mt-6">
                        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Social Media</p>
                        <div class="space-y-1">
                            <a href="{{ route('social.posts.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('social.posts.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <i class="fas fa-pen-nib w-4 text-center"></i> Posts
                            </a>
                            <a href="{{ route('social.accounts.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('social.accounts.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <i class="fas fa-share-alt w-4 text-center"></i> Accounts
                            </a>
                            <a href="{{ route('unified-inbox.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('unified-inbox.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <i class="fas fa-inbox w-4 text-center"></i> Inbox
                                @if(isset($unreadCount) && $unreadCount > 0)
                                    <span class="ml-auto inline-flex items-center justify-center px-2 py-0.5 text-xs font-bold leading-none text-white bg-red-500 rounded-full">{{ $unreadCount }}</span>
                                @endif
                            </a>
                            <a href="{{ route('calendar.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('calendar.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <i class="fas fa-calendar w-4 text-center"></i> Calendar
                            </a>
                            <a href="{{ route('analytics.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('analytics.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <i class="fas fa-chart-line w-4 text-center"></i> Analytics
                            </a>
                        </div>
                    </div>

                    <div class="mt-6">
                        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Marketing</p>
                        <div class="space-y-1">
                            <a href="{{ route('campaigns.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('campaigns.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <i class="fas fa-bullhorn w-4 text-center"></i> Campaigns
                            </a>
                            <a href="{{ route('clients.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('clients.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <i class="fas fa-users w-4 text-center"></i> Clients
                            </a>
                            <a href="{{ route('content.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('content.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <i class="fas fa-folder-open w-4 text-center"></i> Content
                            </a>
                            <a href="{{ route('landing-pages.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('landing-pages.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <i class="fas fa-file-lines w-4 text-center"></i> Landing Pages
                            </a>
                        </div>
                    </div>

                    <div class="mt-6">
                        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">AI & Automation</p>
                        <div class="space-y-1">
                            <a href="{{ route('agents.dashboard') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('agents.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <i class="fas fa-robot w-4 text-center"></i> AI Agents
                            </a>
                            <a href="{{ route('ai.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('ai.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <i class="fas fa-sparkles w-4 text-center"></i> AI Content
                            </a>
                            <a href="{{ route('workflows.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('workflows.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <i class="fas fa-project-diagram w-4 text-center"></i> Workflows
                            </a>
                        </div>
                    </div>

                    <div class="mt-6">
                        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 mb-2">Business</p>
                        <div class="space-y-1">
                            <a href="{{ route('invoices.index') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('invoices.*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <i class="fas fa-file-invoice w-4 text-center"></i> Invoices
                            </a>
                            <a href="{{ route('agency.billing') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('agency.billing') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <i class="fas fa-credit-card w-4 text-center"></i> Billing
                            </a>
                            <a href="{{ route('agency.team') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('agency.team*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <i class="fas fa-user-friends w-4 text-center"></i> Team
                            </a>
                            <a href="{{ route('agency.settings') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors {{ request()->routeIs('agency.settings*') ? 'bg-indigo-50 text-indigo-700 dark:bg-indigo-900/20 dark:text-indigo-400' : 'text-gray-700 hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800' }}">
                                <i class="fas fa-cog w-4 text-center"></i> Settings
                            </a>
                        </div>
                    </div>
                </nav>

                <!-- User panel -->
                <div class="border-t border-gray-200 dark:border-gray-800 p-4">
                    <div class="flex items-center gap-3">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()?->name ?? 'User') }}&background=6366f1&color=fff&size=32" class="w-8 h-8 rounded-full" alt="">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ auth()->user()?->name ?? 'User' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()?->email ?? '' }}</p>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" title="Logout">
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
                <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400">
                    <i class="fas fa-bars text-xl"></i>
                </button>
                <div class="flex-1">
                    <h1 class="text-lg font-semibold text-gray-900 dark:text-white">@yield('title', 'Dashboard')</h1>
                </div>
                <div class="flex items-center gap-4">
                    <button @click="darkMode = !darkMode; localStorage.setItem('darkMode', darkMode)" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-colors">
                        <i x-show="!darkMode" class="fas fa-moon"></i>
                        <i x-show="darkMode" class="fas fa-sun"></i>
                    </button>
                    <button class="relative text-gray-500 hover:text-gray-700 dark:text-gray-400">
                        <i class="fas fa-bell"></i>
                        <span class="absolute -top-1 -right-1 w-2 h-2 bg-red-500 rounded-full"></span>
                    </button>
                    <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()?->name ?? 'User') }}&background=6366f1&color=fff&size=32" class="w-8 h-8 rounded-full cursor-pointer" alt="">
                </div>
            </header>

            <!-- Flash messages -->
            <main class="flex-1 overflow-auto p-6">
                @if(session('success'))
                    <div class="mb-4 flex items-center gap-3 rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4" x-data="{ show: true }" x-show="show" x-transition>
                        <i class="fas fa-check-circle text-green-600 dark:text-green-400"></i>
                        <span class="text-sm text-green-800 dark:text-green-200 flex-1">{{ session('success') }}</span>
                        <button @click="show = false" class="text-green-600 hover:text-green-800 dark:text-green-400"><i class="fas fa-times"></i></button>
                    </div>
                @endif
                @if(session('error'))
                    <div class="mb-4 flex items-center gap-3 rounded-lg bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4" x-data="{ show: true }" x-show="show" x-transition>
                        <i class="fas fa-exclamation-circle text-red-600 dark:text-red-400"></i>
                        <span class="text-sm text-red-800 dark:text-red-200 flex-1">{{ session('error') }}</span>
                        <button @click="show = false" class="text-red-600 hover:text-red-800 dark:text-red-400"><i class="fas fa-times"></i></button>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    <!-- Toastr fallback -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/js/all.min.js"></script>
    <script>
        @if(session('success'))
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof toastr !== 'undefined') toastr.success('{{ session('success') }}');
            });
        @endif
        @if(session('error'))
            document.addEventListener('DOMContentLoaded', function() {
                if (typeof toastr !== 'undefined') toastr.error('{{ session('error') }}');
            });
        @endif
    </script>
    @stack('scripts')
</body>
</html>
