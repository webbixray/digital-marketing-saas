@extends('layouts.unified')

@section('title', 'LinkedIn Profile')

@section('breadcrumb')
    <li><i class="fas fa-chevron-right text-[10px]"></i></li>
    <li><a href="{{ route('linkedin.index') }}" class="hover:text-gray-700 dark:hover:text-gray-200">LinkedIn</a></li>
    <li><i class="fas fa-chevron-right text-[10px]"></i></li>
    <li class="text-gray-900 dark:text-white font-medium">Metrics</li>
@endsection

@section('content')
    <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 p-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">LinkedIn Profile: {{ $account->platform_display_name }}</h1>

        @if($profile['success'])
            <div class="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-200 dark:border-gray-600">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3">Profile Details</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-gray-700 dark:text-gray-300">
                    <p><span class="font-medium">Name:</span> {{ $profile['data']['name'] ?? 'N/A' }}</p>
                    <p><span class="font-medium">Email:</span> {{ $profile['data']['email'] ?? 'N/A' }}</p>
                </div>
            </div>
        @endif
    </div>
@endsection
