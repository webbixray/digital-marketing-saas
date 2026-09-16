@extends('layouts.public-unified')

@section('title', 'Welcome to DigitalMarketingSaaS')

@section('content')
<section class="bg-gradient-to-br from-indigo-600 to-purple-600 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Welcome to DigitalMarketingSaaS!</h1>
        <p class="text-xl opacity-90 mb-8">Your agency is ready. Let's get you set up in 5 easy steps.</p>
        <a href="{{ route('dashboard') }}" class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100">Go to Dashboard</a>
    </div>
</section>

<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Quick Setup Guide</h2>
            <p class="text-gray-600">Get started in just a few minutes</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            @foreach([
                ['icon' => 'building', 'title' => 'Complete Your Profile', 'step' => '1'],
                ['icon' => 'share-alt', 'title' => 'Connect Social Accounts', 'step' => '2'],
                ['icon' => 'users', 'title' => 'Invite Team Members', 'step' => '3'],
                ['icon' => 'rocket', 'title' => 'Launch Your First Campaign', 'step' => '4'],
            ] as $step)
            <div class="text-center">
                <div class="w-16 h-16 bg-indigo-100 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-{{ $step['icon'] }} text-indigo-600 text-xl"></i>
                </div>
                <span class="text-xs font-bold text-indigo-600">STEP {{ $step['step'] }}</span>
                <h3 class="font-semibold text-gray-900 mt-2">{{ $step['title'] }}</h3>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
