@extends('layouts.unified')
@section('title', 'Bad Request')
@section('content')
<x-flash-messages />
<div class="flex items-center justify-center min-h-[60vh] px-4">
    <div class="max-w-lg mx-auto w-full">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-8 text-center">
            <p class="text-6xl font-bold text-red-500 dark:text-red-400 mb-4">400</p>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">Bad Request</h1>
            <p class="text-gray-600 dark:text-gray-400 mb-6">The request could not be processed. Please check your input and try again.</p>
            @if(is_object($errors) && method_exists($errors, 'any') && $errors->any())
                <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4 text-left max-w-md mx-auto mb-6">
                    <strong>Validation errors:</strong>
                    <ul class="mt-2 list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <a href="{{ url()->previous() ?? route('dashboard') }}" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors">
                <i class="fas fa-arrow-left"></i> Go Back
            </a>
        </div>
    </div>
</div>
