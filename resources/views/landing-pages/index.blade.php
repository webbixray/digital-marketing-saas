@extends('layouts.unified')
@section('title', 'Landing Pages')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Landing Pages</h1>
            <ol class="flex gap-2 text-sm text-gray-500 dark:text-gray-400 mt-1">
                <li><a href="{{ route('dashboard') }}" class="hover:text-indigo-600">Home</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">Landing Pages</li>
            </ol>
        </div>
        <a href="{{ route('landing-pages.create') }}" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors text-sm">
            <i class="fas fa-plus mr-1"></i> New Landing Page
        </a>
    </div>

    <!-- Pages Card -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-file-alt mr-2"></i>Landing Pages</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Headline</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Views</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Conversions</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($landingPages ?? [] as $page)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-indigo-600 dark:text-indigo-400">
                                <a href="{{ route('landing-pages.show', $page) }}">{{ $page->name }}</a>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">{{ Str::limit($page->headline ?? '-', 50) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                    $statusClass = $page->status === 'published' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : ($page->status === 'draft' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300');
                                @endphp
                                <span class="{{ $statusClass }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ ucfirst($page->status ?? 'draft') }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ number_format($page->views_count ?? 0) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ number_format($page->conversions_count ?? 0) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $page->created_at ? $page->created_at->diffForHumans() : '-' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap flex items-center gap-2">
                                <a href="{{ route('landing-pages.edit', $page) }}" class="px-2 py-0.5 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center gap-1 font-medium transition-colors text-xs"><i class="fas fa-edit"></i></a>
                                <form action="{{ route('landing-pages.destroy', $page) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="bg-red-600 text-white px-2 py-0.5 rounded hover:bg-red-700 inline-flex items-center gap-1 font-medium transition-colors text-xs" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <i class="fas fa-inbox fa-3x mb-3 block text-gray-300 dark:text-gray-600"></i>
                                <p>No landing pages yet. <a href="{{ route('landing-pages.create') }}" class="text-indigo-600 hover:text-indigo-700">Create your first one</a></p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
