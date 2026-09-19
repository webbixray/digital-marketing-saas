@extends('layouts.unified')
@section('title', 'Service Unavailable')
@section('content')
<x-flash-messages />
<div class="flex items-center justify-center min-h-[60vh] px-4">
    <div class="max-w-lg mx-auto w-full">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
            <p class="text-6xl font-bold text-yellow-500 dark:text-yellow-400 mb-4">503</p>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Service Unavailable</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">We're currently undergoing maintenance. Please check back later.</p>
            <a href="{{ route('dashboard') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                <i class="fas fa-arrow-left"></i> Return to Dashboard
            </a>
        </div>
    </div>
</div>
