@extends('layouts.unified')
@section('title', 'Agent Marketplace')
@section('breadcrumb')
 <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
 <li class="text-gray-900 font-medium">Agent Marketplace</li>
@endsection

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Agent Marketplace</h2>
            <p class="text-gray-500 dark:text-gray-400 mt-1">Discover and install AI agents to supercharge your workflows.</p>
        </div>
        <a href="{{ route('agent-marketplace.create') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors self-start">
            <i class="fas fa-plus mr-1"></i> Submit Agent
        </a>
    </div>

    <!-- Search & Filters -->
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <form method="GET" action="{{ route('agent-marketplace.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Search</label>
                    <input type="text" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search agents..." class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Category</label>
                    <select name="category_id" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="">All Categories</option>
                        @foreach($categories as $category)
                        <option value="{{ $category->id }}" {{ ($filters['category_id'] ?? '') == $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Pricing</label>
                    <select name="pricing_type" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-indigo-500 dark:bg-gray-700 dark:text-white">
                        <option value="">All</option>
                        <option value="free" {{ ($filters['pricing_type'] ?? '') === 'free' ? 'selected' : '' }}>Free</option>
                        <option value="paid" {{ ($filters['pricing_type'] ?? '') === 'paid' ? 'selected' : '' }}>Paid</option>
                        <option value="pricing_tiers" {{ ($filters['pricing_type'] ?? '') === 'pricing_tiers' ? 'selected' : '' }}>Tiered</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <div class="flex gap-2">
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 text-sm font-medium transition-colors">
                        <i class="fas fa-search mr-1"></i> Search
                    </button>
                    <a href="{{ route('agent-marketplace.index') }}" class="bg-gray-200 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-300 inline-flex items-center gap-2 text-sm font-medium transition-colors dark:bg-gray-700 dark:text-gray-200 dark:hover:bg-gray-600">
                        <i class="fas fa-times mr-1"></i> Clear
                    </a>
                </div>
                <a href="{{ route('agent-marketplace.my-agents') }}" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 text-sm font-medium">
                    <i class="fas fa-robot mr-1"></i> My Agents
                </a>
            </div>
        </form>
    </div>

    <!-- Featured Section -->
    @if($featured->isNotEmpty() && !($filters['search'] ?? null) && !($filters['category_id'] ?? null))
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4"><i class="fas fa-star text-yellow-500 mr-2"></i>Featured Agents</h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            @foreach($featured as $item)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <i class="{{ $item->icon ?? 'fas fa-robot' }} text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold text-gray-900 dark:text-white truncate">{{ $item->name }}</h4>
                            <span class="text-xs bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300 px-2 py-0.5 rounded-full">{{ $item->category->name ?? 'Uncategorized' }}</span>
                        </div>
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-2">{{ Str::limit($item->description, 100) }}</p>
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-1 text-yellow-500 text-sm">
                            <i class="fas fa-star"></i>
                            <span class="text-gray-700 dark:text-gray-300">{{ number_format($item->rating_avg, 1) }}</span>
                            <span class="text-gray-500 dark:text-gray-400">({{ $item->rating_count }})</span>
                        </div>
                        <a href="{{ route('agent-marketplace.show', $item->slug) }}" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 text-sm font-medium">View &rarr;</a>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- All Agents Grid -->
    <div>
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
            @if($featured->isNotEmpty() && !($filters['search'] ?? null))
            All Agents
            @else
            {{ $items->total() }} {{ Str::plural('Agent', $items->total()) }} Found
            @endif
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($items as $item)
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden hover:shadow-lg transition-shadow">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-3">
                        <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900 rounded-lg flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                            <i class="{{ $item->icon ?? 'fas fa-robot' }} text-xl"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <h4 class="font-semibold text-gray-900 dark:text-white truncate">{{ $item->name }}</h4>
                            <span class="text-xs bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 px-2 py-0.5 rounded-full">{{ $item->category->name ?? 'Uncategorized' }}</span>
                        </div>
                        @if($item->is_featured)
                        <span class="text-yellow-500"><i class="fas fa-star" title="Featured"></i></span>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-4 line-clamp-3">{{ Str::limit($item->description, 150) }}</p>
                    <div class="flex flex-wrap gap-1 mb-4">
                        @foreach(array_slice($item->tags ?? [], 0, 3) as $tag)
                        <span class="text-xs bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300 px-2 py-0.5 rounded-full">{{ $tag }}</span>
                        @endforeach
                    </div>
                    <div class="flex items-center justify-between border-t border-gray-100 dark:border-gray-700 pt-4">
                        <div class="flex items-center gap-3 text-sm">
                            <span class="text-yellow-500"><i class="fas fa-star"></i> {{ number_format($item->rating_avg, 1) }}</span>
                            <span class="text-gray-500 dark:text-gray-400"><i class="fas fa-download mr-1"></i>{{ number_format($item->install_count) }}</span>
                            <span class="text-xs px-2 py-0.5 rounded-full {{ $item->pricing_type === 'free' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300' }}">
                                {{ $item->pricing_type === 'free' ? 'Free' : ($item->pricing_type === 'paid' ? 'Paid' : 'Tiers') }}
                            </span>
                        </div>
                        <a href="{{ route('agent-marketplace.show', $item->slug) }}" class="bg-indigo-600 text-white px-3 py-1.5 rounded-lg hover:bg-indigo-700 text-sm font-medium transition-colors">
                            View
                        </a>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-12">
                <i class="fas fa-search text-4xl text-gray-300 dark:text-gray-600 mb-4"></i>
                <p class="text-gray-500 dark:text-gray-400">No agents found matching your criteria.</p>
            </div>
            @endforelse
        </div>

        <!-- Pagination -->
        @if($items->hasPages())
        <div class="mt-6">
            {{ $items->withQueryString()->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
