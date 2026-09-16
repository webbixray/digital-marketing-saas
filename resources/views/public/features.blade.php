@extends('layouts.public-unified')

@section('title', 'Features - DigitalMarketingSaaS')

@section('content')
<section class="bg-gradient-to-br from-indigo-600 to-purple-600 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Powerful Features for Modern Marketers</h1>
        <p class="text-xl opacity-90">Everything you need to manage, optimize, and scale your marketing.</p>
    </div>
</section>

<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach([
                ['icon' => 'robot', 'title' => 'AI Content Generation', 'desc' => 'Generate compelling copy, blog posts, and social media captions with AI that understands your brand voice.'],
                ['icon' => 'share-alt', 'title' => 'Social Media Management', 'desc' => 'Schedule, publish, and analyze posts across all major social platforms from a single dashboard.'],
                ['icon' => 'chart-line', 'title' => 'Advanced Analytics', 'desc' => 'Get actionable insights with custom dashboards, ROI tracking, and AI-powered recommendations.'],
                ['icon' => 'envelope-open-text', 'title' => 'Email Campaigns', 'desc' => 'Design beautiful emails, automate sequences, and track performance with real-time analytics.'],
                ['icon' => 'users', 'title' => 'Team Collaboration', 'desc' => 'Assign roles, manage approvals, and collaborate with your team and clients seamlessly.'],
                ['icon' => 'magic', 'title' => 'Workflow Automation', 'desc' => 'Build custom automation workflows that save hours of manual work every week.'],
            ] as $feature)
            <div class="bg-gray-50 rounded-xl p-6 hover:shadow-lg transition-shadow">
                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-{{ $feature['icon'] }} text-indigo-600 text-xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">{{ $feature['title'] }}</h3>
                <p class="text-gray-600">{{ $feature['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<section class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold mb-4">Ready to Get Started?</h2>
        <p class="text-lg mb-8 opacity-90">Join thousands of agencies already using DigitalMarketingSaaS.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}" class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100">Start Free Trial</a>
            <a href="{{ route('public.contact') }}" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-indigo-600">Contact Sales</a>
        </div>
    </div>
</section>
@endsection
