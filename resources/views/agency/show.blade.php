@extends('layouts.modern')

@section('title', 'Agency Details')

@section('content')
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Agency Profile</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Your agency information.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card">
            <div class="card-body text-center">
                <img src="https://ui-avatars.com/api/?name={{ urlencode($agency->name) }}&background=6366f1&color=fff&size=80" class="w-20 h-20 rounded-full mx-auto mb-4" alt="">
                <h3 class="font-bold text-gray-900 dark:text-white">{{ $agency->name }}</h3>
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ $agency->website ?? 'No website' }}</p>
            </div>
        </div>

        <div class="lg:col-span-2 card">
            <div class="card-header flex items-center justify-between">
                <h3 class="font-semibold text-gray-900 dark:text-white">Details</h3>
                <a href="{{ route('agency.edit') }}" class="btn btn-sm btn-secondary"><i class="fas fa-edit"></i> Edit</a>
            </div>
            <div class="card-body">
                <dl class="space-y-3">
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Status</dt>
                        <dd><span class="badge badge-success">{{ ucfirst($agency->status ?? 'active') }}</span></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Created</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $agency->created_at->format('M d, Y') }}</dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-sm text-gray-500 dark:text-gray-400">Plan</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ ucfirst($agency->plan ?? 'free') }}</dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
@endsection
