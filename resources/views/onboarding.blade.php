@extends("layouts.unified")
@section('title', 'Welcome')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="text-center mb-12">
        <i class="fas fa-rocket fa-4x text-indigo-600 mb-4"></i>
        <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Welcome to {{ config('app.name') }}!</h1>
        <p class="text-lg text-gray-600 dark:text-gray-400">Let's get you set up in 5 easy steps</p>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <a href="{{ route('onboarding.step1') }}" class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow">
            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center mb-4">
                <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">1</span>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Set Up Your Agency</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Add your agency details, logo, and branding</p>
        </a>

        <a href="{{ route('onboarding.step2') }}" class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow">
            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center mb-4">
                <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">2</span>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Connect Social Accounts</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Link your Facebook, Instagram, Twitter, and more</p>
        </a>

        <a href="{{ route('onboarding.step3') }}" class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow">
            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center mb-4">
                <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">3</span>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Invite Your Team</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Add team members and assign roles</p>
        </a>

        <a href="{{ route('onboarding.step4') }}" class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow">
            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center mb-4">
                <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">4</span>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Create Your First Campaign</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Set up a campaign and start managing content</p>
        </a>

        <a href="{{ route('onboarding.step5') }}" class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700 p-6 hover:shadow-lg transition-shadow">
            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-xl flex items-center justify-center mb-4">
                <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400">5</span>
            </div>
            <h3 class="font-semibold text-gray-900 dark:text-white mb-2">Explore AI Features</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Generate content and automate workflows</p>
        </a>

        <a href="{{ route('onboarding.quickStart') }}" class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl shadow-md p-6 hover:shadow-lg transition-shadow text-white">
            <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center mb-4">
                <i class="fas fa-magic text-2xl"></i>
            </div>
            <h3 class="font-semibold mb-2">Quick Start</h3>
            <p class="text-sm opacity-80">Create sample posts to get started fast</p>
        </a>
    </div>

    <div class="text-center mt-12">
        <a href="{{ route('dashboard') }}" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300">
            Skip setup →
        </a>
    </div>
</div>
@endsection
