@extends('layouts.unified')
@section('title', 'Bad Request')
@section('content')
<x-flash-messages />
<div class="space-y-6">
<div class="error-page" style="text-align: center; padding: 40px;">
    <h2 class="headline text-red-500 dark:text-red-400" style="font-size: 120px; font-weight: 700; margin: 0; line-height: 1;">400</h2>
    <div class="error-content">
        <h3><i class="fas fa-exclamation-circle text-red-500 dark:text-red-400"></i> Bad Request</h3>
        <p class="text-gray-600 dark:text-gray-400">The request could not be processed. Please check your input and try again.</p>
        @if(is_object($errors) && method_exists($errors, 'any') && $errors->any())
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl p-4" style="text-align: left; max-width: 400px; margin: 20px auto;">
                <strong>Validation errors:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <a href="{{ url()->previous() ?? route('dashboard') }}" class="border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 px-4 py-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors">
            <i class="fas fa-arrow-left"></i> Go Back
        </a>
    </div>
</div>
</div>
@endsection
