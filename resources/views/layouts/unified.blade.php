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

    <!-- Focus-visible styles -->
    <style>
        *:focus-visible {
            outline: 2px solid #6366f1;
            outline-offset: 2px;
            border-radius: 4px;
        }
        button:focus-visible,
        a:focus-visible,
        input:focus-visible {
            outline: 2px solid #6366f1;
            outline-offset: 2px;
        }
        [x-cloak] { display: none !important; }
        /* Smooth dark mode transition */
        html {
            transition: background-color 0.3s ease, color 0.3s ease;
        }
        /* Loading bar */
        #loading-bar {
            position: fixed;
            top: 0;
            left: 0;
            height: 3px;
            background: linear-gradient(90deg, #6366f1, #8b5cf6, #6366f1);
            z-index: 9999;
            transition: width 0.3s ease, opacity 0.3s ease;
            width: 0;
            opacity: 0;
        }
        #loading-bar.loading {
            width: 80%;
            opacity: 1;
        }
        #loading-bar.complete {
            width: 100%;
            opacity: 0;
        }
        /* Tooltip for mini sidebar */
        .sidebar-tooltip {
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            margin-left: 8px;
            padding: 4px 10px;
            background: #1f2937;
            color: white;
            font-size: 12px;
            border-radius: 6px;
            white-space: nowrap;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s;
            z-index: 60;
        }
        .sidebar-tooltip::before {
            content: '';
            position: absolute;
            left: -4px;
            top: 50%;
            transform: translateY(-50%);
            border: 4px solid transparent;
            border-right-color: #1f2937;
        }
        .group:hover .sidebar-tooltip {
            opacity: 1;
        }
        /* Scrollbar styling */
        .sidebar-nav::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-nav::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-nav::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 2px;
        }
        .dark .sidebar-nav::-webkit-scrollbar-thumb {
            background: #374151;
        }
    </style>
    
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6366f1">

    @stack('styles')
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased dark:bg-gray-900 dark:text-gray-100 font-inter overflow-x-hidden flex flex-row" 
      x-data="{
          srAnnouncement: '',
          sidebarOpen: false,
          sidebarMini: localStorage.getItem('sidebarMini') === 'true',
          darkMode: localStorage.getItem('darkMode') === 'true',
          searchOpen: false,
          searchQuery: '',
          notificationsOpen: false,
          userDropdownOpen: false,
          createDropdownOpen: false,
          loading: true,
          expandedSections: JSON.parse(localStorage.getItem('sidebarSections') || '{{ json_encode(["social"=>true,"marketing"=>true,"ai"=>true,"business"=>true]) }}'),
          toggleSection(section) {
              this.expandedSections[section] = !this.expandedSections[section];
              localStorage.setItem('sidebarSections', JSON.stringify(this.expandedSections));
          },
          init() {
              this.$watch('darkMode', val => {
                  localStorage.setItem('darkMode', val);
                  document.documentElement.classList.toggle('dark', val);
              });
              this.$watch('sidebarMini', val => localStorage.setItem('sidebarMini', val));
              // Apply dark mode on init
              document.documentElement.classList.toggle('dark', this.darkMode);
              // Loading simulation
              setTimeout(() => { this.loading = false; }, 500);
              // Keyboard shortcuts
              document.addEventListener('keydown', (e) => {
                  if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                      e.preventDefault();
                      this.searchOpen = true;
                  }
                  if (e.ctrlKey && e.key === 'b') {
                      e.preventDefault();
                      this.sidebarMini = !this.sidebarMini;
                  }
                  if (e.key === 'Escape') {
                      this.searchOpen = false;
                      this.notificationsOpen = false;
                      this.userDropdownOpen = false;
                      this.createDropdownOpen = false;
                  }
              });
              // Scroll active nav into view
              this.$nextTick(() => {
                  const activeNav = document.querySelector('.nav-link.active');
                  if (activeNav) {
                      activeNav.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                  }
              });
          }
      }"
      x-init="init()">

    <!-- Loading bar -->
    <div id="loading-bar" :class="{ 'loading': loading }" aria-hidden="true"></div>

    <!-- Screen reader announcements -->
    <div aria-live="polite" aria-atomic="true" class="sr-only" id="sr-announcements"></div>

    <!-- Skip to main content -->
    <a href="#main-content" class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:z-[100] focus:bg-white focus:px-4 focus:py-2 focus:rounded-lg focus:shadow-lg focus:dark:bg-gray-800">
        Skip to main content
    </a>
    
    <!-- Mobile sidebar overlay -->
    <div x-show="sidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-300"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-40 bg-gray-900/50 lg:hidden" 
         @click="sidebarOpen = false"
         aria-hidden="true">
    </div>
    
    <!-- Sidebar -->
    <aside :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                sidebarMini ? 'lg:w-16' : 'lg:w-64',
                sidebarMini && !sidebarOpen ? 'lg:translate-x-0' : ''
              ]"
           class="fixed inset-y-0 left-0 z-50 w-64 bg-white border-r border-gray-200 dark:bg-gray-900 dark:border-gray-800 lg:static lg:translate-x-0 transition-all duration-300 ease-in-out flex flex-col"
           role="navigation"
           aria-label="Main navigation">
        
        <!-- Logo & Quick Actions -->
        <div class="flex-shrink-0">
            <!-- Logo -->
            <div class="flex h-16 items-center justify-between px-4 border-b border-gray-200 dark:border-gray-800">
                <a href="{{ route('dashboard') }}" class="flex items-center gap-2" aria-label="Dashboard">
                    <div class="w-8 h-8 bg-indigo-600 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-bolt text-white text-sm"></i>
                    </div>
                    <span x-show="!sidebarMini" class="font-bold text-gray-900 dark:text-white transition-opacity duration-300">DMSaaS</span>
                </a>
                <button @click="sidebarOpen = false" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 p-2 min-w-[44px] min-h-[44px] flex items-center justify-center" aria-label="Close menu">
                    <i class="fas fa-times"></i>
                </button>
            </div>

            <!-- Quick Action Buttons -->
            <div x-show="!sidebarMini" class="px-3 py-3 border-b border-gray-200 dark:border-gray-800 space-y-2">
                <a href="{{ route('social.posts.create') }}" class="flex items-center gap-2 w-full px-3 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors min-h-[44px]" aria-label="Create new post">
                    <i class="fas fa-plus text-xs"></i> Create Post
                </a>
                <a href="{{ route('campaigns.create') }}" class="flex items-center gap-2 w-full px-3 py-2.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg transition-colors min-h-[44px]" aria-label="Create new campaign">
                    <i class="fas fa-bullhorn text-xs"></i> Create Campaign
                </a>
            </div>

            <!-- Mini mode quick action icon -->
            <div x-show="sidebarMini" class="px-2 py-3 border-b border-gray-200 dark:border-gray-800 flex flex-col items-center gap-2">
                <a href="{{ route('social.posts.create') }}" class="w-10 h-10 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg flex items-center justify-center transition-colors" aria-label="Create new post" title="Create Post">
                    <i class="fas fa-plus text-xs"></i>
                </a>
                <a href="{{ route('campaigns.create') }}" class="w-10 h-10 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg flex items-center justify-center transition-colors" aria-label="Create new campaign" title="Create Campaign">
                    <i class="fas fa-bullhorn text-xs"></i>
                </a>
            </div>
        </div>

        <!-- Navigation -->
        <nav class="flex-1 overflow-y-auto sidebar-nav px-3 py-4" aria-label="Sidebar navigation">
            <!-- Dashboard -->
            <div class="space-y-1 mb-4">
                <a href="{{ route('dashboard') }}" 
                   class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                   :class="sidebarMini ? 'justify-center px-2' : ''"
                   :title="sidebarMini ? 'Dashboard' : ''"
                   aria-label="Dashboard">
                    <i class="fas fa-th-large w-5 text-center flex-shrink-0"></i>
                    <span x-show="!sidebarMini" class="transition-opacity duration-300">Dashboard</span>
                </a>
            </div>

            <!-- Social Media Section -->
            <div class="mb-4">
                <button @click="toggleSection('social')" 
                        class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 min-h-[44px]"
                        :aria-expanded="expandedSections.social"
                        aria-controls="section-social">
                    <span x-show="!sidebarMini">Social Media</span>
                    <i x-show="!sidebarMini" class="fas fa-chevron-down text-[10px] transition-transform duration-200" :class="expandedSections.social ? 'rotate-0' : '-rotate-90'"></i>
                </button>
                <div x-show="expandedSections.social || sidebarMini" x-transition class="space-y-1" id="section-social">
                    <div class="group relative">
                        <a href="{{ route('social.posts.index') }}" 
                           class="nav-link {{ request()->routeIs('social.posts.*') ? 'active' : '' }}"
                           :class="sidebarMini ? 'justify-center px-2' : ''"
                           aria-label="Posts">
                            <i class="fas fa-pen-nib w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarMini" class="transition-opacity duration-300">Posts</span>
                        </a>
                        <span x-show="sidebarMini" class="sidebar-tooltip">Posts</span>
                    </div>
                    <div class="group relative">
                        <a href="{{ route('social.accounts.index') }}" 
                           class="nav-link {{ request()->routeIs('social.accounts.*') ? 'active' : '' }}"
                           :class="sidebarMini ? 'justify-center px-2' : ''"
                           aria-label="Accounts">
                            <i class="fas fa-share-alt w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarMini" class="transition-opacity duration-300">Accounts</span>
                        </a>
                        <span x-show="sidebarMini" class="sidebar-tooltip">Accounts</span>
                    </div>
                    <div class="group relative">
                        <a href="{{ route('unified-inbox.index') }}" 
                           class="nav-link {{ request()->routeIs('unified-inbox.*') ? 'active' : '' }}"
                           :class="sidebarMini ? 'justify-center px-2' : ''"
                           aria-label="Inbox">
                            <i class="fas fa-inbox w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarMini" class="transition-opacity duration-300">Inbox</span>
                        </a>
                        <span x-show="sidebarMini" class="sidebar-tooltip">Inbox</span>
                    </div>
                    <div class="group relative">
                        <a href="{{ route('calendar.index') }}" 
                           class="nav-link {{ request()->routeIs('calendar.*') ? 'active' : '' }}"
                           :class="sidebarMini ? 'justify-center px-2' : ''"
                           aria-label="Calendar">
                            <i class="fas fa-calendar w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarMini" class="transition-opacity duration-300">Calendar</span>
                        </a>
                        <span x-show="sidebarMini" class="sidebar-tooltip">Calendar</span>
                    </div>
                    <div class="group relative">
                        <a href="{{ route('analytics.index') }}" 
                           class="nav-link {{ request()->routeIs('analytics.*') ? 'active' : '' }}"
                           :class="sidebarMini ? 'justify-center px-2' : ''"
                           aria-label="Analytics">
                            <i class="fas fa-chart-line w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarMini" class="transition-opacity duration-300">Analytics</span>
                        </a>
                        <span x-show="sidebarMini" class="sidebar-tooltip">Analytics</span>
                    </div>
                </div>
            </div>

            <!-- Marketing Section -->
            <div class="mb-4">
                <button @click="toggleSection('marketing')" 
                        class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 min-h-[44px]"
                        :aria-expanded="expandedSections.marketing"
                        aria-controls="section-marketing">
                    <span x-show="!sidebarMini">Marketing</span>
                    <i x-show="!sidebarMini" class="fas fa-chevron-down text-[10px] transition-transform duration-200" :class="expandedSections.marketing ? 'rotate-0' : '-rotate-90'"></i>
                </button>
                <div x-show="expandedSections.marketing || sidebarMini" x-transition class="space-y-1" id="section-marketing">
                    <div class="group relative">
                        <a href="{{ route('campaigns.index') }}" 
                           class="nav-link {{ request()->routeIs('campaigns.*') ? 'active' : '' }}"
                           :class="sidebarMini ? 'justify-center px-2' : ''"
                           aria-label="Campaigns">
                            <i class="fas fa-bullhorn w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarMini" class="transition-opacity duration-300">Campaigns</span>
                        </a>
                        <span x-show="sidebarMini" class="sidebar-tooltip">Campaigns</span>
                    </div>
                    <div class="group relative">
                        <a href="{{ route('clients.index') }}" 
                           class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}"
                           :class="sidebarMini ? 'justify-center px-2' : ''"
                           aria-label="Clients">
                            <i class="fas fa-users w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarMini" class="transition-opacity duration-300">Clients</span>
                        </a>
                        <span x-show="sidebarMini" class="sidebar-tooltip">Clients</span>
                    </div>
                    <div class="group relative">
                        <a href="{{ route('content.index') }}" 
                           class="nav-link {{ request()->routeIs('content.*') ? 'active' : '' }}"
                           :class="sidebarMini ? 'justify-center px-2' : ''"
                           aria-label="Content">
                            <i class="fas fa-folder-open w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarMini" class="transition-opacity duration-300">Content</span>
                        </a>
                        <span x-show="sidebarMini" class="sidebar-tooltip">Content</span>
                    </div>
                </div>
            </div>

            <!-- AI & Automation Section -->
            <div class="mb-4">
                <button @click="toggleSection('ai')" 
                        class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 min-h-[44px]"
                        :aria-expanded="expandedSections.ai"
                        aria-controls="section-ai">
                    <span x-show="!sidebarMini">AI & Automation</span>
                    <i x-show="!sidebarMini" class="fas fa-chevron-down text-[10px] transition-transform duration-200" :class="expandedSections.ai ? 'rotate-0' : '-rotate-90'"></i>
                </button>
                <div x-show="expandedSections.ai || sidebarMini" x-transition class="space-y-1" id="section-ai">
                    <div class="group relative">
                        <a href="{{ route('agents.dashboard') }}" 
                           class="nav-link {{ request()->routeIs('agents.*') ? 'active' : '' }}"
                           :class="sidebarMini ? 'justify-center px-2' : ''"
                           aria-label="AI Agents">
                            <i class="fas fa-robot w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarMini" class="transition-opacity duration-300">AI Agents</span>
                        </a>
                        <span x-show="sidebarMini" class="sidebar-tooltip">AI Agents</span>
                    </div>
                    <div class="group relative">
                        <a href="{{ route('ai.index') }}" 
                           class="nav-link {{ request()->routeIs('ai.*') ? 'active' : '' }}"
                           :class="sidebarMini ? 'justify-center px-2' : ''"
                           aria-label="AI Content">
                            <i class="fas fa-sparkles w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarMini" class="transition-opacity duration-300">AI Content</span>
                        </a>
                        <span x-show="sidebarMini" class="sidebar-tooltip">AI Content</span>
                    </div>
                    <div class="group relative">
                        <a href="{{ route('workflows.index') }}" 
                           class="nav-link {{ request()->routeIs('workflows.*') ? 'active' : '' }}"
                           :class="sidebarMini ? 'justify-center px-2' : ''"
                           aria-label="Workflows">
                            <i class="fas fa-project-diagram w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarMini" class="transition-opacity duration-300">Workflows</span>
                        </a>
                        <span x-show="sidebarMini" class="sidebar-tooltip">Workflows</span>
                    </div>
                </div>
            </div>

            <!-- Business Section -->
            <div class="mb-4">
                <button @click="toggleSection('business')" 
                        class="w-full flex items-center justify-between px-3 py-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 min-h-[44px]"
                        :aria-expanded="expandedSections.business"
                        aria-controls="section-business">
                    <span x-show="!sidebarMini">Business</span>
                    <i x-show="!sidebarMini" class="fas fa-chevron-down text-[10px] transition-transform duration-200" :class="expandedSections.business ? 'rotate-0' : '-rotate-90'"></i>
                </button>
                <div x-show="expandedSections.business || sidebarMini" x-transition class="space-y-1" id="section-business">
                    <div class="group relative">
                        <a href="{{ route('invoices.index') }}" 
                           class="nav-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}"
                           :class="sidebarMini ? 'justify-center px-2' : ''"
                           aria-label="Invoices">
                            <i class="fas fa-file-invoice w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarMini" class="transition-opacity duration-300">Invoices</span>
                        </a>
                        <span x-show="sidebarMini" class="sidebar-tooltip">Invoices</span>
                    </div>
                    <div class="group relative">
                        <a href="{{ route('agency.billing') }}" 
                           class="nav-link {{ request()->routeIs('agency.billing') ? 'active' : '' }}"
                           :class="sidebarMini ? 'justify-center px-2' : ''"
                           aria-label="Billing">
                            <i class="fas fa-credit-card w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarMini" class="transition-opacity duration-300">Billing</span>
                        </a>
                        <span x-show="sidebarMini" class="sidebar-tooltip">Billing</span>
                    </div>
                    <div class="group relative">
                        <a href="{{ route('agency.team') }}" 
                           class="nav-link {{ request()->routeIs('agency.team*') ? 'active' : '' }}"
                           :class="sidebarMini ? 'justify-center px-2' : ''"
                           aria-label="Team">
                            <i class="fas fa-user-friends w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarMini" class="transition-opacity duration-300">Team</span>
                        </a>
                        <span x-show="sidebarMini" class="sidebar-tooltip">Team</span>
                    </div>
                    <div class="group relative">
                        <a href="{{ route('agency.settings') }}" 
                           class="nav-link {{ request()->routeIs('agency.settings*') ? 'active' : '' }}"
                           :class="sidebarMini ? 'justify-center px-2' : ''"
                           aria-label="Settings">
                            <i class="fas fa-cog w-5 text-center flex-shrink-0"></i>
                            <span x-show="!sidebarMini" class="transition-opacity duration-300">Settings</span>
                        </a>
                        <span x-show="sidebarMini" class="sidebar-tooltip">Settings</span>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Sidebar Footer -->
        <div class="flex-shrink-0 border-t border-gray-200 dark:border-gray-800">
            <!-- Plan Badge -->
            <div x-show="!sidebarMini" class="px-4 py-3">
                <div class="flex items-center justify-between p-2 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-lg">
                    <div>
                        <p class="text-xs font-semibold text-gray-900 dark:text-white">Pro Plan</p>
                        <p class="text-[10px] text-gray-500 dark:text-gray-400">2,450/10,000 credits</p>
                    </div>
                    <a href="{{ route('agency.billing') }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400" aria-label="Upgrade plan">Upgrade</a>
                </div>
            </div>
            <!-- Mini plan badge -->
            <div x-show="sidebarMini" class="px-2 py-3 flex justify-center">
                <div class="w-8 h-8 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-lg flex items-center justify-center" title="Pro Plan - Upgrade" aria-label="Pro Plan">
                    <i class="fas fa-crown text-white text-xs"></i>
                </div>
            </div>

            <!-- Collapse Toggle -->
            <div class="px-3 py-2">
                <button @click="sidebarMini = !sidebarMini" 
                        class="hidden lg:flex w-full items-center gap-2 px-3 py-2 text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors min-h-[44px]"
                        :class="sidebarMini ? 'justify-center' : ''"
                        aria-label="Toggle sidebar collapse">
                    <i class="fas fa-chevron-left transition-transform duration-300" :class="sidebarMini ? 'rotate-180' : ''"></i>
                    <span x-show="!sidebarMini" class="transition-opacity duration-300">Collapse</span>
                </button>
            </div>
        </div>
    </aside>

    <!-- Main content -->
    <div class="flex flex-1 flex-col min-h-screen transition-all duration-300" 
         :class="sidebarMini ? 'lg:pl-16' : 'lg:pl-64'">
        <!-- Top bar -->
        <header class="sticky top-0 z-30 flex h-16 items-center gap-4 border-b border-gray-200 bg-white/80 dark:bg-gray-900/80 dark:border-gray-800 px-4 sm:px-6 backdrop-blur-sm">
            <!-- Mobile menu button -->
            <button @click="sidebarOpen = true" class="lg:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 p-2 min-w-[44px] min-h-[44px] flex items-center justify-center" aria-label="Open menu">
                <i class="fas fa-bars text-xl"></i>
            </button>

            <!-- Breadcrumb (integrated) -->
            <nav class="hidden md:flex items-center text-sm text-gray-500 dark:text-gray-400" aria-label="Breadcrumb">
                <ol class="flex items-center gap-2">
                    <li><a href="{{ route('dashboard') }}" class="hover:text-gray-700 dark:hover:text-gray-200">Home</a></li>
                    <li><i class="fas fa-chevron-right text-[10px]"></i></li>
                    <li class="text-gray-900 dark:text-white font-medium">@yield('title', 'Dashboard')</li>
                    @hasSection('breadcrumb')
                        @yield('breadcrumb')
                    @endif
                </ol>
            </nav>

            <!-- Search Bar -->
            <div class="flex-1 flex justify-center px-4">
                <button @click="searchOpen = true" 
                        class="hidden sm:flex items-center gap-2 w-full max-w-md px-4 py-2 text-sm text-gray-400 bg-gray-100 dark:bg-gray-800 rounded-lg border border-transparent hover:border-gray-300 dark:hover:border-gray-600 transition-colors min-h-[44px]"
                        aria-label="Open search (Cmd+K)">
                    <i class="fas fa-search"></i>
                    <span class="hidden md:inline">Search...</span>
                    <kbd class="ml-auto hidden lg:inline-flex items-center gap-1 px-2 py-0.5 text-[10px] font-mono bg-gray-200 dark:bg-gray-700 rounded">
                        <span x-text="navigator.platform.includes('Mac') ? '⌘' : 'Ctrl'"></span>K
                    </kbd>
                </button>
                <!-- Mobile search icon -->
                <button @click="searchOpen = true" class="sm:hidden text-gray-500 hover:text-gray-700 dark:text-gray-400 p-2 min-w-[44px] min-h-[44px] flex items-center justify-center" aria-label="Open search">
                    <i class="fas fa-search text-lg"></i>
                </button>
            </div>

            <!-- Right side actions -->
            <div class="flex items-center gap-2 sm:gap-3">
                <!-- Create Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" 
                            class="hidden sm:flex items-center gap-1 px-3 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors min-h-[44px]"
                            aria-label="Create new"
                            aria-haspopup="true"
                            :aria-expanded="open">
                        <i class="fas fa-plus text-xs"></i>
                        <span class="hidden md:inline">Create</span>
                        <i class="fas fa-chevron-down text-[10px]"></i>
                    </button>
                    <div x-show="open" 
                         @click.away="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50"
                         role="menu">
                        <a href="{{ route('social.posts.create') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 min-h-[44px]" role="menuitem">
                            <i class="fas fa-pen-nib w-4 text-indigo-500"></i> New Post
                        </a>
                        <a href="{{ route('campaigns.create') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 min-h-[44px]" role="menuitem">
                            <i class="fas fa-bullhorn w-4 text-indigo-500"></i> New Campaign
                        </a>
                        <a href="{{ route('content.create') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 min-h-[44px]" role="menuitem">
                            <i class="fas fa-folder-open w-4 text-indigo-500"></i> New Content
                        </a>
                        <a href="{{ route('clients.create') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 min-h-[44px]" role="menuitem">
                            <i class="fas fa-user-plus w-4 text-indigo-500"></i> New Client
                        </a>
                    </div>
                </div>

                <!-- Dark mode toggle -->
                <button @click="darkMode = !darkMode" 
                        class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300 transition-colors p-2 min-w-[44px] min-h-[44px] flex items-center justify-center"
                        aria-label="Toggle dark mode">
                    <i x-show="!darkMode" class="fas fa-moon"></i>
                    <i x-show="darkMode" class="fas fa-sun"></i>
                </button>

                <!-- Notifications Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" 
                            class="relative text-gray-500 hover:text-gray-700 dark:text-gray-400 p-2 min-w-[44px] min-h-[44px] flex items-center justify-center"
                            aria-label="Notifications"
                            aria-haspopup="true"
                            :aria-expanded="open">
                        <i class="fas fa-bell"></i>
                        <span class="absolute top-1 right-1 w-4 h-4 bg-red-500 rounded-full text-[10px] text-white font-bold flex items-center justify-center" aria-label="3 unread notifications">3</span>
                    </button>
                    <div x-show="open" 
                         @click.away="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-50"
                         role="menu">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white">Notifications</h3>
                        </div>
                        <div class="max-h-64 overflow-y-auto">
                            <a href="#" class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors border-b border-gray-100 dark:border-gray-700">
                                <div class="w-8 h-8 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-check text-green-600 text-xs"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900 dark:text-white">Post published successfully</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">2 minutes ago</p>
                                </div>
                            </a>
                            <a href="#" class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors border-b border-gray-100 dark:border-gray-700">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-user-plus text-blue-600 text-xs"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900 dark:text-white">New client "Acme Corp" added</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">1 hour ago</p>
                                </div>
                            </a>
                            <a href="#" class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <div class="w-8 h-8 bg-yellow-100 dark:bg-yellow-900/30 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-exclamation text-yellow-600 text-xs"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm text-gray-900 dark:text-white">Campaign budget at 80%</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400">3 hours ago</p>
                                </div>
                            </a>
                        </div>
                        <div class="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                            <a href="#" class="text-sm text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 font-medium">View all notifications</a>
                        </div>
                    </div>
                </div>

                <!-- User Dropdown -->
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" 
                            class="flex items-center gap-2 p-1 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800 transition-colors min-h-[44px] min-w-[44px]"
                            aria-label="User menu"
                            aria-haspopup="true"
                            :aria-expanded="open">
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(auth()->user()?->name ?? 'User') }}&background=6366f1&color=fff&size=32" class="w-8 h-8 rounded-full" alt="{{ auth()->user()?->name ?? 'User' }} avatar">
                        <i class="fas fa-chevron-down text-[10px] text-gray-500 dark:text-gray-400 hidden sm:block"></i>
                    </button>
                    <div x-show="open" 
                         @click.away="open = false"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-56 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 py-1 z-50"
                         role="menu">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                            <p class="text-sm font-medium text-gray-900 dark:text-white">{{ auth()->user()?->name ?? 'User' }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()?->email ?? '' }}</p>
                        </div>
                        <a href="{{ route('agency.settings') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 min-h-[44px]" role="menuitem">
                            <i class="fas fa-user w-4 text-gray-400"></i> Profile
                        </a>
                        <a href="{{ route('agency.settings') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 min-h-[44px]" role="menuitem">
                            <i class="fas fa-cog w-4 text-gray-400"></i> Settings
                        </a>
                        <a href="{{ route('agency.billing') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 min-h-[44px]" role="menuitem">
                            <i class="fas fa-credit-card w-4 text-gray-400"></i> Billing
                        </a>
                        <div class="border-t border-gray-200 dark:border-gray-700 mt-1 pt-1">
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="flex items-center gap-2 w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-700 min-h-[44px]" role="menuitem">
                                    <i class="fas fa-sign-out-alt w-4"></i> Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page content -->
        <main class="flex-1 overflow-auto overflow-x-hidden p-4 sm:p-6 min-w-0" id="main-content">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="border-t border-gray-200 dark:border-gray-800 bg-white dark:bg-gray-900 px-4 sm:px-6 py-4">
            <div class="flex flex-col sm:flex-row items-center justify-between gap-2 text-sm text-gray-500 dark:text-gray-400">
                <p>&copy; {{ date('Y') }} DMSaaS. All rights reserved. <span class="hidden sm:inline">v2.1.0</span></p>
                <div class="flex items-center gap-4">
                    <a href="#" class="hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Docs</a>
                    <a href="#" class="hover:text-gray-700 dark:hover:text-gray-200 transition-colors">Support</a>
                    <a href="#" class="hover:text-gray-700 dark:hover:text-gray-200 transition-colors">API</a>
                    <span class="hidden sm:inline text-xs text-gray-400">v2.1.0</span>
                </div>
            </div>
        </footer>
    </div>

    <!-- Search Modal -->
    <div x-show="searchOpen" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[60] flex items-start justify-center pt-[10vh] sm:pt-[15vh]"
         role="dialog"
         aria-modal="true"
         aria-label="Search"
         @keydown.escape.window="searchOpen = false">
        <div class="fixed inset-0 bg-gray-900/50" @click="searchOpen = false" aria-hidden="true"></div>
        <div class="relative w-full max-w-lg mx-4 bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">
            <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <i class="fas fa-search text-gray-400"></i>
                <input type="text" 
                       x-model="searchQuery"
                       x-ref="searchInput"
                       x-init="$watch('searchOpen', val => { if(val) $nextTick(() => $refs.searchInput.focus()) })"
                       class="flex-1 bg-transparent border-0 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-0 text-sm"
                       placeholder="Search posts, campaigns, clients..."
                       aria-label="Search input">
                <kbd class="hidden sm:inline-flex items-center px-2 py-0.5 text-[10px] font-mono bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded">ESC</kbd>
            </div>
            <div class="max-h-80 overflow-y-auto p-2">
                <p class="px-3 py-2 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">Quick Links</p>
                <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    <i class="fas fa-th-large w-4 text-gray-400"></i> Dashboard
                </a>
                <a href="{{ route('social.posts.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    <i class="fas fa-pen-nib w-4 text-gray-400"></i> Posts
                </a>
                <a href="{{ route('campaigns.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    <i class="fas fa-bullhorn w-4 text-gray-400"></i> Campaigns
                </a>
                <a href="{{ route('analytics.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                    <i class="fas fa-chart-line w-4 text-gray-400"></i> Analytics
                </a>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div x-data class="fixed bottom-4 right-4 z-[70] space-y-2" aria-live="polite" aria-atomic="true">
        <template x-for="toast in $store.toasts.items" :key="toast.id">
            <div x-show="toast.visible"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-x-8"
                 x-transition:enter-end="opacity-100 translate-x-0"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-x-0"
                 x-transition:leave-end="opacity-0 translate-x-8"
                 class="flex items-center gap-3 px-4 py-3 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 min-w-[300px] max-w-md"
                 role="alert">
                <i :class="toast.type === 'success' ? 'fas fa-check-circle text-green-500' : toast.type === 'error' ? 'fas fa-exclamation-circle text-red-500' : 'fas fa-info-circle text-blue-500'"></i>
                <p class="text-sm text-gray-900 dark:text-white" x-text="toast.message"></p>
            </div>
        </template>
    </div>

    <!-- Toast helper script -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('toasts', {
                items: [],
                toastId: 0,
                show(message, type = 'info') {
                    const id = ++this.toastId;
                    this.items.push({ id, message, type, visible: true });
                    setTimeout(() => {
                        const toast = this.items.find(t => t.id === id);
                        if (toast) toast.visible = false;
                        setTimeout(() => {
                            this.items = this.items.filter(t => t.id !== id);
                        }, 300);
                    }, 4000);
                }
            });
        });
        // Global toast function - uses Alpine.store for reliable global access
        window.showToast = function(message, type = 'info') {
            if (typeof Alpine !== 'undefined' && Alpine.store('toasts')) {
                Alpine.store('toasts').show(message, type);
            }
        };
    </script>

    @stack('scripts')
</body>
</html>
