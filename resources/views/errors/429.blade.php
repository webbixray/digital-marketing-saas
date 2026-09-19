@extends('layouts.unified')
@section('title', 'Too Many Requests')
@section('content')
<x-flash-messages />
<div class="flex items-center justify-center min-h-[60vh] px-4">
    <div class="max-w-lg mx-auto w-full">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
            <p class="text-6xl font-bold text-yellow-500 dark:text-yellow-400 mb-4">429</p>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Too Many Requests</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">You've exceeded your quota. Please upgrade your plan or wait.</p>
            <a href="{{ route('agency.billing') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                <i class="fas fa-arrow-up"></i> Upgrade Plan
            </a>
        </div>
    </div>
</div>
