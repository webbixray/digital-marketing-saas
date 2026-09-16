@extends("layouts.unified")
@section('title', 'Quick Start')

@section('content')
<div class="container-fluid">
    <div class="grid grid-cols-12 gap-4 justify-center>
        <div class="col-span-12 lg:col-span-8">
            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="card-header bg-primary text-white">
                    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-magic mr-2"></i>Quick Start: Sample Content</h3>
                </div>
                <div class="card-body text-center">
                    <i class="fas fa-gift fa-4x text-primary mb-4"></i>
<h4>We've created sample posts for you!</h4>
                    <p class="text-muted">Get started with pre-made content you can customize and publish right away. Perfect for learning the platform!</p>

                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <form action="{{ route('onboarding.quickStart') }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors text-lg">
                                <i class="fas fa-magic mr-2"></i>Create Sample Posts
                            </button>
                        </form>
                        <a href="{{ route('onboarding.step1') }}" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-cog mr-2"></i>Custom Setup
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
