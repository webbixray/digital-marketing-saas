@extends('layouts.unified')

@section('title', 'Search')

@section('content')
<div class="max-w-6xl mx-auto" x-data="searchPage()">
    <!-- Search Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Global Search</h1>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Search across all your posts, campaigns, clients, and content
        </p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Main Search Area -->
        <div class="lg:col-span-3 space-y-6">
            <!-- Search Input -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <form @submit.prevent="performSearch()" class="space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="relative flex-1">
                            <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                            <input type="text"
                                   x-model="searchQuery"
                                   @input.debounce.300ms="performSearch()"
                                   @keydown.enter.prevent="performSearch()"
                                   class="w-full pl-10 pr-4 py-3 text-sm bg-gray-50 dark:bg-gray-900 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:text-white"
                                   placeholder="Search posts, campaigns, clients, content..."
                                   aria-label="Search">
                            <kbd class="hidden sm:inline-flex absolute right-3 top-1/2 -translate-y-1/2 items-center px-2 py-0.5 text-[10px] font-mono bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded">
                                <span x-text="isMac ? '\u2318' : 'Ctrl'"></span>K
                            </kbd>
                        </div>
                        <button type="submit"
                                class="px-5 py-3 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors min-h-[44px]">
                            Search
                        </button>
                    </div>

                    <!-- Type Filters -->
                    <div class="flex flex-wrap gap-2" role="group" aria-label="Search type filters">
                        @foreach(['all' => 'All', 'posts' => 'Posts', 'campaigns' => 'Campaigns', 'clients' => 'Clients', 'content' => 'Content', 'analytics' => 'Analytics'] as $key => $label)
                            <button type="button"
                                    @click="searchType = '{{ $key }}'; performSearch()"
                                    :class="searchType === '{{ $key }}' ? 'bg-indigo-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600'"
                                    class="px-3 py-1.5 text-xs font-medium rounded-full transition-colors"
                                    aria-pressed="{{ $type === $key ? 'true' : 'false' }}">
                                {{ $label }}
                            </button>
                        @endforeach
                    </div>
                </form>
            </div>

            <!-- Results -->
            <div x-show="searchQuery.length >= 2" class="space-y-4">
                <template x-for="(items, group) in results" :key="group">
                    <div x-show="items.length > 0" class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white capitalize" x-text="group"></h3>
                            <span class="text-xs text-gray-500 dark:text-gray-400" x-text="items.length + ' results'"></span>
                        </div>
                        <div class="divide-y divide-gray-100 dark:divide-gray-700">
                            <template x-for="item in items" :key="item.id">
                                <a :href="item.url"
                                   @click="trackClick(item)"
                                   class="flex items-start gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <div class="w-8 h-8 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <i :class="item.icon + ' text-indigo-600 text-xs'"></i>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate" x-text="item.title"></p>
                                        <p class="text-xs text-gray-500 dark:text-gray-400" x-text="item.subtitle"></p>
                                    </div>
                                    <i class="fas fa-chevron-right text-gray-400 text-xs mt-1"></i>
                                </a>
                            </template>
                        </div>
                    </div>
                </template>

                <!-- No Results -->
                <div x-show="Object.keys(results).length === 0 && !loading"
                     class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
                    <i class="fas fa-search text-gray-300 dark:text-gray-600 text-4xl mb-3"></i>
                    <p class="text-sm text-gray-500 dark:text-gray-400">No results found for "<span x-text="searchQuery"></span>"</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Try a different keyword or filter</p>
                </div>
            </div>

            <!-- Empty State -->
            <div x-show="searchQuery.length < 2 && !loading"
                 class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-8 text-center">
                <i class="fas fa-search text-gray-300 dark:text-gray-600 text-4xl mb-3"></i>
                <p class="text-sm text-gray-500 dark:text-gray-400">Start typing to search</p>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Minimum 2 characters required</p>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="space-y-4">
            <!-- Recent Searches -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <i class="fas fa-history text-gray-400 text-xs"></i> Recent Searches
                </h3>
                @if($recent && $recent->count() > 0)
                    <div class="space-y-1">
                        @foreach($recent as $item)
                            <a href="{{ route('search.index', ['q' => $item->query, 'type' => $item->type]) }}"
                               class="flex items-center gap-2 px-2 py-1.5 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                <i class="fas fa-search text-gray-400 text-[10px]"></i>
                                <span class="truncate flex-1">{{ $item->query }}</span>
                                <span class="text-[10px] text-gray-400 capitalize">{{ $item->type }}</span>
                            </a>
                        @endforeach
                    </div>
                @else
                    <p class="text-xs text-gray-400 dark:text-gray-500">No recent searches</p>
                @endif
            </div>

            <!-- Search Tips -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-4">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 flex items-center gap-2">
                    <i class="fas fa-lightbulb text-gray-400 text-xs"></i> Search Tips
                </h3>
                <ul class="space-y-2 text-xs text-gray-500 dark:text-gray-400">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-500 mt-0.5 text-[10px]"></i>
                        <span>Use at least 2 characters</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-500 mt-0.5 text-[10px]"></i>
                        <span>Filter by type for better results</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-500 mt-0.5 text-[10px]"></i>
                        <span>Press <kbd class="px-1 bg-gray-200 dark:bg-gray-700 rounded text-[10px]">Cmd+K</kbd> for quick search</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-500 mt-0.5 text-[10px]"></i>
                        <span>Search content, names, emails, and more</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function searchPage() {
    return {
        searchQuery: '{{ addslashes($query) }}',
        searchType: '{{ $type }}',
        results: {},
        loading: false,
        isMac: navigator.platform.includes('Mac'),

        init() {
            @if(strlen($query) >= 2)
                this.performSearch();
            @endif

            // Cmd+K listener
            document.addEventListener('keydown', (e) => {
                if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                    e.preventDefault();
                    const url = new URL('{{ route('search.index') }}');
                    url.searchParams.set('q', this.searchQuery);
                    window.location.href = url.toString();
                }
            });
        },

        async performSearch() {
            if (this.searchQuery.length < 2) {
                this.results = {};
                return;
            }

            this.loading = true;

            try {
                const response = await fetch('{{ route('search.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        query: this.searchQuery,
                        type: this.searchType,
                    }),
                });

                const data = await response.json();
                this.results = data.results || {};
            } catch (err) {
                console.error('Search failed:', err);
            } finally {
                this.loading = false;
            }
        },

        trackClick(item) {
            // Analytics tracking could go here
        },
    };
}
</script>
@endpush
@endsection
