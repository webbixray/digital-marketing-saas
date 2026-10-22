@extends('layouts.unified')

@section('title', 'Subscription Success')

@section('breadcrumb')
    <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="hover:text-gray-700"><a href="{{ route('agency.billing') }}">Billing</a></li>
    <li class="text-gray-900 font-medium">Success</li>
@endsection

@section('content')
<x-flash-messages />
<div class="space-y-6">
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Subscription Activated!</h2>
        <p class="text-gray-500 dark:text-gray-400 mt-1">Your subscription has been successfully upgraded.</p>
    </div>

    <div class="flex justify-center">
        <div class="md:w-2/3 text-center">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="p-8">
                    <div class="mb-4">
                        <i class="fas fa-check-circle text-green-600 dark:text-green-400" style="font-size: 5rem;"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-green-600 dark:text-green-400">Subscription Activated!</h2>
                    <p class="text-gray-500 dark:text-gray-400 mt-2">
                        Your subscription has been successfully upgraded. You now have access to all the features of your new plan.
                    </p>
                    <hr class="border-t border-gray-200 dark:border-gray-700 my-6">
                    <div class="flex justify-center gap-4">
                        <a href="{{ route('dashboard') }}" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors text-lg">
                            <i class="fas fa-tachometer-alt mr-1"></i>Go to Dashboard
                        </a>
                        <a href="{{ route('agency.billing') }}" class="px-6 py-3 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors text-lg">
                            <i class="fas fa-credit-card mr-1"></i>Manage Billing
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
