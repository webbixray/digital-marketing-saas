@extends("layouts.unified")
@section('title', 'Quick Start')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-magic text-indigo-600 mr-2"></i>Quick Start: Sample Content</h3>
        </div>
        <div class="p-8 text-center">
            <i class="fas fa-gift fa-4x text-indigo-600 mb-4"></i>
            <h4 class="text-xl font-semibold text-gray-900 dark:text-white mb-2">We've created sample posts for you!</h4>
            <p class="text-gray-500 dark:text-gray-400 mb-6">Get started with pre-made content you can customize and publish right away. Perfect for learning the platform!</p>

            <div class="flex justify-center gap-4">
                <form action="{{ route('onboarding.quickStart') }}" method="POST">
                    @csrf
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors text-lg">
                        <i class="fas fa-magic mr-2"></i>Create Sample Posts
                    </button>
                </form>
                <a href="{{ route('onboarding.step1') }}" class="px-6 py-3 border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors text-lg">
                    <i class="fas fa-cog mr-2"></i>Custom Setup
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
