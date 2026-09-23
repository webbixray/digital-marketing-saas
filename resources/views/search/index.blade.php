@extends('layouts.unified')
@section('title', 'Search')

@section('content')
<x-flash-messages />
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-search text-indigo-600 mr-2"></i>Global Search</h3>
        </div>
        <div class="p-6">
            <form action="{{ route('search.index') }}" method="GET" class="mb-6">
                @csrf
                <div class="flex gap-2">
                    <div class="flex-1 relative">
                        <input type="text" name="q" value="{{ $query }}" placeholder="Search posts, campaigns, clients, content..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                    </div>
                    <label for="search-type" class="sr-only">Filter by type</label>
                    <select id="search-type" name="type" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                        <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All</option>
                        <option value="posts" {{ $type === 'posts' ? 'selected' : '' }}>Posts</option>
                        <option value="campaigns" {{ $type === 'campaigns' ? 'selected' : '' }}>Campaigns</option>
                        <option value="clients" {{ $type === 'clients' ? 'selected' : '' }}>Clients</option>
                        <option value="content" {{ $type === 'content' ? 'selected' : '' }}>Content</option>
                        <option value="invoices" {{ $type === 'invoices' ? 'selected' : '' }}>Invoices</option>
                        <option value="workflows" {{ $type === 'workflows' ? 'selected' : '' }}>Workflows</option>
                    </select>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-colors">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            @if(empty($query))
                <div class="text-center py-10">
                    <i class="fas fa-search fa-3x text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">Enter a search term to find posts, campaigns, clients, content, invoices, and workflows.</p>
                </div>
            @elseif(collect($results)->every(fn($items) => $items->isEmpty()))
                <div class="text-center py-10">
                    <i class="fas fa-inbox fa-3x text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">No results found for "{{ $query }}"</p>
                </div>
            @else
                @foreach($results as $type => $items)
                    @if($items->count() > 0)
                        <div class="mb-6">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-semibold text-gray-900 dark:text-white">{{ ucfirst($type) }}</h4>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $items->count() }} result{{ $items->count() !== 1 ? 's' : '' }}</span>
                            </div>
                            <div class="space-y-2">
                                @foreach($items as $item)
                                    @php
                                        $url = '#';
                                        if ($type === 'posts') $url = route('social.posts.show', $item);
                                        elseif ($type === 'campaigns') $url = route('campaigns.show', $item);
                                        elseif ($type === 'clients') $url = route('clients.show', $item);
                                        elseif ($type === 'content') $url = route('content.show', $item);
                                        elseif ($type === 'invoices') $url = route('invoices.show', $item);
                                        elseif ($type === 'workflows') $url = route('workflows.show', $item);
                                        $title = $item->name ?? $item->invoice_number ?? Str::limit($item->content ?? $item->description ?? '', 50);
                                    @endphp
                                    <a href="{{ $url }}" class="block bg-gray-50 dark:bg-gray-700 rounded-lg p-3 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                                        <div class="flex justify-between items-center">
                                            <span class="font-medium text-gray-900 dark:text-white">{{ $title }}</span>
                                            <small class="text-gray-400">{{ $item->created_at->diffForHumans() }}</small>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endforeach
            @endif
        </div>
    </div>
</div>
@endsection
