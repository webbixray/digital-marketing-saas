@extends('layouts.unified')
@section('title', 'A/B Testing')

@section('content')
<x-flash-messages />

<div class="space-y-6">
    <!-- Breadcrumb -->
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <h1 class="m-0 text-2xl font-bold text-gray-900 dark:text-white">A/B Testing</h1>
        </div>
        <div class="flex items-center justify-start sm:justify-end">
            <ol class="flex gap-2 text-sm text-gray-500 dark:text-gray-400">
                <li><a href="{{ route('dashboard') }}" class="hover:text-indigo-600">Home</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">A/B Testing</li>
            </ol>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <i class="fas fa-flask text-indigo-600 dark:text-indigo-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Total Tests</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-yellow-100 dark:bg-yellow-900/30 flex items-center justify-center">
                    <i class="fas fa-file-alt text-yellow-600 dark:text-yellow-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Drafts</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['draft'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                    <i class="fas fa-play text-green-600 dark:text-green-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Running</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['running'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center">
                    <i class="fas fa-check text-purple-600 dark:text-purple-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-500 dark:text-gray-400">Completed</p>
                    <p class="text-xl font-bold text-gray-900 dark:text-white">{{ $stats['completed'] }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-4">
        <form action="{{ route('ab-testing.index') }}" method="GET" class="flex flex-wrap gap-4 items-center">
            @csrf
            <div class="flex items-center gap-2">
                <label for="status" class="text-sm font-medium text-gray-700 dark:text-gray-300">Status:</label>
                <select name="status" id="status" class="px-3 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" onchange="this.form.submit()">
                    <option value="all" {{ $status === 'all' ? 'selected' : '' }}>All</option>
                    <option value="draft" {{ $status === 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="running" {{ $status === 'running' ? 'selected' : '' }}>Running</option>
                    <option value="paused" {{ $status === 'paused' ? 'selected' : '' }}>Paused</option>
                    <option value="completed" {{ $status === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
            <div class="flex items-center gap-2">
                <label for="type" class="text-sm font-medium text-gray-700 dark:text-gray-300">Type:</label>
                <select name="type" id="type" class="px-3 py-2 border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 dark:text-white text-sm" onchange="this.form.submit()">
                    <option value="all" {{ $type === 'all' ? 'selected' : '' }}>All Types</option>
                    <option value="content" {{ $type === 'content' ? 'selected' : '' }}>Content</option>
                    <option value="timing" {{ $type === 'timing' ? 'selected' : '' }}>Timing</option>
                    <option value="hashtag" {{ $type === 'hashtag' ? 'selected' : '' }}>Hashtag</option>
                    <option value="media" {{ $type === 'media' ? 'selected' : '' }}>Media</option>
                </select>
            </div>
            <a href="{{ route('ab-testing.create') }}" class="ml-auto bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors text-sm">
                <i class="fas fa-plus"></i>New Test
            </a>
        </form>
    </div>

    <!-- Tests Table -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Your A/B Tests</h3>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Platform</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Engagement</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Confidence</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Winner</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($tests as $test)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                <a href="{{ route('ab-testing.show', $test) }}" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">{{ $test->name }}</a>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($test->platform) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">{{ ucfirst($test->type) }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                    $statusClass = match($test->status) {
                                        'running' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'completed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'paused' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'
                                    };
                                @endphp
                                <span class="{{ $statusClass }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ ucfirst($test->status) }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                A: {{ number_format($test->engagementRate('a'), 1) }}% | B: {{ number_format($test->engagementRate('b'), 1) }}%
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm">
                                @if($test->confidence > 0)
                                    <span class="{{ $test->confidence >= 95 ? 'text-green-600 dark:text-green-400' : 'text-gray-500 dark:text-gray-400' }}">{{ number_format($test->confidence, 1) }}%</span>
                                @else
                                    <span class="text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($test->winner && $test->winner !== 'inconclusive')
                                    <span class="bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400 text-xs font-medium px-2.5 py-0.5 rounded-full">Variant {{ strtoupper($test->winner) }}</span>
                                @elseif($test->winner === 'inconclusive')
                                    <span class="bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 text-xs font-medium px-2.5 py-0.5 rounded-full">Inconclusive</span>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <a href="{{ route('ab-testing.show', $test) }}" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400"><i class="fas fa-eye"></i></a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center gap-2">
                                    <i class="fas fa-flask text-4xl text-gray-300 dark:text-gray-600"></i>
                                    <p>No A/B tests found</p>
                                    <a href="{{ route('ab-testing.create') }}" class="text-indigo-600 hover:text-indigo-700 dark:text-indigo-400">Create your first test</a>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if($tests->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $tests->links() }}
        </div>
        @endif
    </div>
</div>
@endsection
