<!-- Command Palette (Cmd+K) -->
<div x-data="commandPalette()"
     x-show="open"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0"
     @keydown.escape.window="open = false"
     x-cloak
     class="fixed inset-0 z-[70] flex items-start justify-center pt-[10vh] sm:pt-[15vh]"
     role="dialog"
     aria-modal="true"
     aria-label="Command Palette">

    <!-- Backdrop -->
    <div class="fixed inset-0 bg-gray-900/50" @click="open = false" aria-hidden="true"></div>

    <!-- Palette Panel -->
    <div class="relative w-full max-w-lg mx-4 bg-white dark:bg-gray-800 rounded-xl shadow-2xl overflow-hidden"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         @keydown="handleKeydown($event)">

        <!-- Search Input -->
        <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <i class="fas fa-search text-gray-400 flex-shrink-0"></i>
            <input type="text"
                   x-ref="paletteInput"
                   x-model="query"
                   @input.debounce.250ms="search()"
                   x-init="$watch('open', val => val && focusInput())"
                   class="flex-1 bg-transparent border-0 text-gray-900 dark:text-white placeholder-gray-400 focus:ring-0 text-sm"
                   placeholder="Type a command or search..."
                   aria-label="Search command palette"
                   autocomplete="off">
            <kbd class="hidden sm:inline-flex items-center px-2 py-0.5 text-[10px] font-mono bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded flex-shrink-0">ESC</kbd>
        </div>

        <!-- Loading Indicator -->
        <div x-show="loading" class="px-4 py-3 text-center">
            <i class="fas fa-spinner fa-spin text-gray-400 text-sm"></i>
        </div>

        <!-- Results -->
        <div class="max-h-80 overflow-y-auto p-2" x-show="!loading && Object.keys(results).length > 0">
            <template x-for="(items, group) in results" :key="group">
                <div class="mb-2">
                    <p class="px-3 py-1 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase flex items-center gap-2">
                        <i :class="groups[group]?.icon || 'fas fa-folder'"></i>
                        <span x-text="groups[group]?.label || group"></span>
                        <span class="ml-auto text-[10px]" x-text="items.length"></span>
                    </p>
                    <template x-for="item in items" :key="item.id">
                        <button @click="navigate(item)"
                                class="w-full flex items-center gap-3 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors text-left">
                            <div class="w-7 h-7 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                <i :class="(item.icon || 'fas fa-file') + ' text-indigo-600 text-xs'"></i>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="truncate" x-text="item.title"></p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 truncate" x-text="item.subtitle"></p>
                            </div>
                            <kbd class="text-[10px] text-gray-400 bg-gray-100 dark:bg-gray-700 px-1.5 py-0.5 rounded">↵</kbd>
                        </button>
                    </template>
                </div>
            </template>
        </div>

        <!-- Recent Searches (when no query) -->
        <div class="max-h-80 overflow-y-auto p-2" x-show="!loading && query.length < 2 && recent.length > 0">
            <p class="px-3 py-1 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">Recent Searches</p>
            <template x-for="item in recent" :key="item.id">
                <button @click="query = item.query; search()"
                        class="w-full flex items-center gap-3 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors text-left">
                    <div class="w-7 h-7 bg-gray-100 dark:bg-gray-700 rounded-lg flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-history text-gray-400 text-xs"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="truncate" x-text="item.query"></p>
                        <p class="text-xs text-gray-400 dark:text-gray-500" x-text="item.type"></p>
                    </div>
                </button>
            </template>
        </div>

        <!-- Quick Links (when no query) -->
        <div class="max-h-80 overflow-y-auto p-2" x-show="!loading && query.length < 2 && recent.length === 0">
            <p class="px-3 py-1 text-xs font-semibold text-gray-400 dark:text-gray-500 uppercase">Quick Links</p>
            <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <i class="fas fa-th-large w-4 text-gray-400"></i> Dashboard
            </a>
            <a href="{{ route('social.posts.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <i class="fas fa-pen-nib w-4 text-gray-400"></i> Posts
            </a>
            <a href="{{ route('campaigns.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <i class="fas fa-bullhorn w-4 text-gray-400"></i> Campaigns
            </a>
            <a href="{{ route('clients.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <i class="fas fa-users w-4 text-gray-400"></i> Clients
            </a>
            <a href="{{ route('analytics.index') }}" class="flex items-center gap-3 px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg">
                <i class="fas fa-chart-line w-4 text-gray-400"></i> Analytics
            </a>
        </div>

        <!-- No Results -->
        <div x-show="!loading && query.length >= 2 && Object.keys(results).length === 0"
             class="px-4 py-6 text-center">
            <i class="fas fa-search text-gray-300 text-2xl mb-2"></i>
            <p class="text-sm text-gray-500 dark:text-gray-400">No results found</p>
        </div>

        <!-- Footer -->
        <div class="flex items-center justify-between px-4 py-2 border-t border-gray-200 dark:border-gray-700 text-[10px] text-gray-400">
            <span>
                <kbd class="px-1 bg-gray-100 dark:bg-gray-700 rounded">↑↓</kbd> Navigate
                <kbd class="px-1 bg-gray-100 dark:bg-gray-700 rounded ml-2">↵</kbd> Select
                <kbd class="px-1 bg-gray-100 dark:bg-gray-700 rounded ml-2">ESC</kbd> Close
            </span>
            <span class="text-indigo-600 dark:text-indigo-400 font-medium">v7.0</span>
        </div>
    </div>
</div>
