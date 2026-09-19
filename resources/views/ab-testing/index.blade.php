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

    <!-- Tests Card -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900 dark:text-white">Your A/B Tests</h3>
            <a href="{{ route('ab-testing.create') }}" class="bg-indigo-600 text-white px-3 py-1 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-1 font-medium transition-colors text-sm">
                <i class="fas fa-plus mr-1"></i>New Test
            </a>
        </div>
        <div class="p-6">
            <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700 border border-gray-200 dark:border-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Platform</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Winner</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created</th>
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
                                    $statusClass = $test->status === 'running' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : ($test->status === 'completed' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300');
                                @endphp
                                <span class="{{ $statusClass }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ ucfirst($test->status) }}</span>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if($test->winner)
                                    @php
                                        $winnerClass = $test->winner === 'inconclusive' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400';
                                    @endphp
                                    <span class="{{ $winnerClass }} text-xs font-medium px-2.5 py-0.5 rounded-full">{{ ucfirst($test->winner) }}</span>
                                @else
                                    <span class="text-gray-500 dark:text-gray-400">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">{{ $test->created_at->toDateString() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">No A/B tests found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
            {{ $tests->links() }}
        </div>
    </div>
</div>
@endsection
