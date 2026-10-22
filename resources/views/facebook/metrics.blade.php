@extends('layouts.unified')

@section('title', 'Facebook Metrics')

@section('breadcrumb')
    <li><i class="fas fa-chevron-right text-[10px]"></i></li>
    <li><a href="{{ route('facebook.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">Facebook</a></li>
    <li><i class="fas fa-chevron-right text-[10px]"></i></li>
    <li class="text-gray-900 dark:text-white font-medium">Metrics</li>
@endsection

@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Facebook Metrics: {{ $account->platform_display_name }}</h1>

        @if($page['success'])
            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Page Profile</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-gray-700 dark:text-gray-300">
                    <p><span class="font-medium">Name:</span> {{ $page['data']['name'] ?? 'N/A' }}</p>
                    <p><span class="font-medium">Category:</span> {{ $page['data']['category'] ?? 'N/A' }}</p>
                    <p><span class="font-medium">Likes:</span> {{ $page['data']['fan_count'] ?? 0 }}</p>
                    <p><span class="font-medium">Website:</span> {{ $page['data']['website'] ?? 'N/A' }}</p>
                </div>
            </div>
        @endif

        @if($insights['success'])
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Insights</h2>
                <div class="space-y-2 text-gray-700 dark:text-gray-300">
                    @foreach($insights['data'] as $name => $metric)
                        <p>{{ $metric['title'] }}: {{ is_array($metric['values']) ? json_encode($metric['values']) : $metric['values'] }}</p>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endsection
