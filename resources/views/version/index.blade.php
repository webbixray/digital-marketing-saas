@extends('layouts.unified')
@section('title', 'Changelog')

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between">
                    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-history mr-2"></i>Changelog</h3>
                    <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-blue-900 dark:text-blue-300">v{{ $currentVersion['full'] ?? '1.0.0' }}</span>
                </div>
                <div class="p-6">
                    @forelse($changelog ?? [] as $entry)
                    <div class="changelog-entry mb-4">
                        <h4 class="mb-2">
                            <span class="bg-indigo-100 text-indigo-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-indigo-900 dark:text-indigo-300">v{{ $entry['version'] }}</span>
                            <small class="text-gray-500 dark:text-gray-400 ml-2">{{ $entry['date'] }}</small>
                        </h4>
                        
                        @if(!empty($entry['added']))
                        <div class="mb-2">
                            <strong class="text-green-600 dark:text-green-400"><i class="fas fa-plus-circle mr-1"></i> Added</strong>
                            <ul class="ml-4">
                                @foreach($entry['added'] as $item)
                                <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        
                        @if(!empty($entry['changed']))
                        <div class="mb-2">
                            <strong class="text-blue-600 dark:text-blue-400"><i class="fas fa-sync mr-1"></i> Changed</strong>
                            <ul class="ml-4">
                                @foreach($entry['changed'] as $item)
                                <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        
                        @if(!empty($entry['fixed']))
                        <div class="mb-2">
                            <strong class="text-yellow-600 dark:text-yellow-400"><i class="fas fa-bug mr-1"></i> Fixed</strong>
                            <ul class="ml-4">
                                @foreach($entry['fixed'] as $item)
                                <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        
                        @if(!empty($entry['security']))
                        <div class="mb-2">
                            <strong class="text-red-600 dark:text-red-400"><i class="fas fa-shield-alt mr-1"></i> Security</strong>
                            <ul class="ml-4">
                                @foreach($entry['security'] as $item)
                                <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        
                        @if(!empty($entry['deprecated']))
                        <div class="mb-2">
                            <strong class="text-gray-500 dark:text-gray-400"><i class="fas fa-ban mr-1"></i> Deprecated</strong>
                            <ul class="ml-4">
                                @foreach($entry['deprecated'] as $item)
                                <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                        
                        @if(!empty($entry['removed']))
                        <div class="mb-2">
                            <strong class="text-red-600 dark:text-red-400"><i class="fas fa-trash mr-1"></i> Removed</strong>
                            <ul class="ml-4">
                                @foreach($entry['removed'] as $item)
                                <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                        @endif
                    </div>
                    @if(!$loop->last)<hr>@endif
                    @empty
                    <div class="text-center text-gray-500 dark:text-gray-400 py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No changelog entries yet.</p>
                    </div>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="lg:col-span-1 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border-2 border-indigo-300 dark:bg-gray-800 dark:border-indigo-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-info-circle mr-2"></i>Version Info</h3>
                </div>
                <div class="p-6 space-y-2">
                    <p><strong>Current Version:</strong> v{{ $currentVersion['full'] ?? '1.0.0' }}</p>
                    <p><strong>Codename:</strong> {{ $currentVersion['codename'] ?? 'Genesis' }}</p>
                    <p><strong>Release Date:</strong> {{ $currentVersion['release_date'] ?? '2026-09-05' }}</p>
                    <p><strong>PHP:</strong> {{ $currentVersion['minimum_php'] ?? '8.4' }}+</p>
                    <p><strong>Laravel:</strong> {{ $currentVersion['minimum_laravel'] ?? '13.0' }}+</p>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-code mr-2"></i>API Versions</h3>
                </div>
                <div class="p-6 space-y-2">
                    <p><strong>Latest:</strong> <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-green-900 dark:text-green-300">{{ config('version.api.latest', 'v1') }}</span></p>
                    <p><strong>Supported:</strong> {{ implode(', ', config('version.api.supported', ['v1'])) }}</p>
                    @if(!empty(config('version.api.deprecated', [])))
                    <p><strong>Deprecated:</strong> <span class="bg-yellow-100 text-yellow-800 text-xs font-medium px-2.5 py-0.5 rounded-full dark:bg-yellow-900 dark:text-yellow-300">{{ implode(', ', config('version.api.deprecated')) }}</span></p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
