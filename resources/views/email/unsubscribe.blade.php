@extends('layouts.public')

@section('title', 'Unsubscribe')

@section('content')
<div class="max-w-md mx-auto mt-16 p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
    <div class="text-center">
        @if($status === 'success')
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100 dark:bg-green-900">
                <i class="fas fa-check text-green-600 dark:text-green-300 text-xl"></i>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Unsubscribed</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $message }}</p>
        @elseif($status === 'already')
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900">
                <i class="fas fa-info text-blue-600 dark:text-blue-300 text-xl"></i>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Already Unsubscribed</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $message }}</p>
        @elseif($status === 'pending')
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100 dark:bg-yellow-900">
                <i class="fas fa-envelope text-yellow-600 dark:text-yellow-300 text-xl"></i>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Unsubscribe from {{ $agencyName }}</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Are you sure you want to unsubscribe from all future emails from this agency?</p>
            <form method="POST" action="{{ route('email.unsubscribe.confirm', ['recipient' => $recipientId]) }}" class="mt-6">
                @csrf
                <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition">
                    Yes, Unsubscribe Me
                </button>
            </form>
        @else
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900">
                <i class="fas fa-times text-red-600 dark:text-red-300 text-xl"></i>
            </div>
            <h3 class="mt-4 text-lg font-semibold text-gray-900 dark:text-white">Invalid Request</h3>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">{{ $message }}</p>
        @endif
    </div>
</div>
@endsection
