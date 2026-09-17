@extends('layouts.unified')
@section('title', 'White-Label Settings')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <!-- Breadcrumb -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">White-Label Settings</h1>
            <ol class="flex gap-2 text-sm text-gray-500 dark:text-gray-400 mt-1">
                <li><a href="{{ route('dashboard') }}" class="hover:text-indigo-600">Home</a></li>
                <li>/</li>
                <li class="text-gray-900 dark:text-white font-medium">White-Label</li>
            </ol>
        </div>
    </div>

    <!-- Settings Card -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white">Configuration</h3>
        </div>
        <div class="p-6">
            <div class="space-y-6">
                <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 border border-gray-200 dark:border-gray-600">
                    <p class="text-sm font-medium text-gray-700 dark:text-gray-300">Agency ID</p>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white">{{ $agencyId }}</p>
                </div>

                @if(isset($whiteLabel))
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Brand Name</label>
                        <p class="text-gray-900 dark:text-white">{{ $whiteLabel->brand_name ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Custom Domain</label>
                        <p class="text-gray-900 dark:text-white">{{ $whiteLabel->custom_domain ?? 'Not set' }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Custom CSS</label>
                        @if($whiteLabel->custom_css)
                            <pre class="bg-gray-900 text-green-400 rounded-lg p-4 text-xs overflow-x-auto"><code>{{ $whiteLabel->custom_css }}</code></pre>
                        @else
                            <p class="text-gray-500 dark:text-gray-400 text-sm">No custom CSS</p>
                        @endif
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Logo URL</label>
                        <p class="text-gray-900 dark:text-white">{{ $whiteLabel->logo_url ?? 'Not set' }}</p>
                    </div>
                </div>
                @else
                <div class="text-center py-8">
                    <i class="fas fa-palette fa-3x text-gray-300 dark:text-gray-600 mb-3"></i>
                    <p class="text-gray-500 dark:text-gray-400">No white-label settings configured yet.</p>
                </div>
                @endif
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center gap-2">
            <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 text-sm font-medium transition-colors inline-flex items-center gap-2">
                <i class="fas fa-edit"></i> Edit Settings
            </button>
            <button class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-sm font-medium transition-colors inline-flex items-center gap-2">
                <i class="fas fa-upload"></i> Upload Logo
            </button>
        </div>
    </div>
</div>

@if(isset($whiteLabel) && $whiteLabel->custom_css)
@push('styles')
<style>{{ $whiteLabel->custom_css }}</style>
@endpush
@endif
@endsection
