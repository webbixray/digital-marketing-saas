@extends('layouts.unified')
@section('title', 'Page Not Found')
@section('content')
<x-flash-messages />
<div class="flex items-center justify-center min-h-[60vh] px-4">
    <div class="max-w-lg mx-auto w-full">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
            <p class="text-6xl font-bold text-yellow-500 dark:text-yellow-400 mb-4">404</p>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Page Not Found</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">The page you requested could not be found.</p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary inline-flex items-center gap-2">
                <i class="fas fa-arrow-left"></i> Return to Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
