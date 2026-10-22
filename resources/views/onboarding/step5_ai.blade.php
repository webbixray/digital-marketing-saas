@extends('layouts.unified')
@section('title', 'Enable AI Agents')

@section('content')
<x-flash-messages />
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-robot text-indigo-600 mr-2"></i>Activate AI Agents</h3>
        </div>
        <div class="p-6">
            <div class="text-center mb-6">
                <i class="fas fa-robot fa-4x text-indigo-600 mb-3"></i>
                <h4 class="text-lg font-semibold text-gray-900 dark:text-white">Let AI Supercharge Your Marketing</h4>
                <p class="text-gray-500 dark:text-gray-400">Enable AI agents to automate and optimize your marketing workflows.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                <label class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-xl p-4 cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-pen-fancy fa-xl text-blue-600"></i>
                        <div>
                            <h5 class="font-medium text-gray-900 dark:text-white">Content Generation</h5>
                            <p class="text-xs text-gray-500 dark:text-gray-400">AI writes captions, posts, and ad copy</p>
                        </div>
                    </div>
                    <input type="checkbox" name="ai_content_generation" value="1" checked class="w-5 h-5 text-indigo-600 rounded">
                </label>

                <label class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-xl p-4 cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-chart-line fa-xl text-amber-500"></i>
                        <div>
                            <h5 class="font-medium text-gray-900 dark:text-white">Post Optimization</h5>
                            <p class="text-xs text-gray-500 dark:text-gray-400">AI optimizes timing and hashtags</p>
                        </div>
                    </div>
                    <input type="checkbox" name="ai_post_optimization" value="1" checked class="w-5 h-5 text-indigo-600 rounded">
                </label>

                <label class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-xl p-4 cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-chart-pie fa-xl text-green-600"></i>
                        <div>
                            <h5 class="font-medium text-gray-900 dark:text-white">AI Analytics</h5>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Get AI-powered insights</p>
                        </div>
                    </div>
                    <input type="checkbox" name="ai_analytics" value="1" checked class="w-5 h-5 text-indigo-600 rounded">
                </label>

                <label class="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded-xl p-4 cursor-pointer hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors">
                    <div class="flex items-center gap-3">
                        <i class="fas fa-clock fa-xl text-red-500"></i>
                        <div>
                            <h5 class="font-medium text-gray-900 dark:text-white">Smart Scheduling</h5>
                            <p class="text-xs text-gray-500 dark:text-gray-400">AI determines best times to post</p>
                        </div>
                    </div>
                    <input type="checkbox" name="ai_scheduling" value="1" checked class="w-5 h-5 text-indigo-600 rounded">
                </label>
            </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex justify-between">
            <a href="{{ route('onboarding.step4') }}" class="text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200">
                <i class="fas fa-arrow-left mr-1"></i> Back
            </a>
            <form action="{{ route('onboarding.step5') }}" method="POST">
                @csrf
                <input type="hidden" name="complete" value="1">
                <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 inline-flex items-center gap-2 font-medium transition-colors">
                    <i class="fas fa-rocket mr-1"></i> Complete Setup
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
