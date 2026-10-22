@extends('layouts.public-unified')

@section('title', 'DigitalMarketingSaaS - Grow Your Business')

@section('content')
<!-- Hero Section -->
<x-flash-messages />
<section class="bg-gradient-to-br from-indigo-600 via-purple-600 to-pink-500 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl md:text-5xl font-extrabold mb-6">All the Marketing Tools Your Agency Needs</h1>
        <p class="text-xl mb-8 max-w-3xl mx-auto opacity-90">Automate campaigns, create AI-powered content, manage social media, and track analytics — all from one powerful platform.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}" class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100">
                <i class="fas fa-play mr-2"></i>Start Free Trial
            </a>
            <a href="{{ route('public.features') }}" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-indigo-600">
                <i class="fas fa-eye mr-2"></i>See Features
            </a>
        </div>
        <div class="flex flex-wrap gap-4 justify-center mt-8">
            <span class="bg-white/20 px-4 py-2 rounded-full text-sm"><i class="fas fa-check mr-1"></i> No credit card required</span>
            <span class="bg-white/20 px-4 py-2 rounded-full text-sm"><i class="fas fa-check mr-1"></i> 14-day free trial</span>
            <span class="bg-white/20 px-4 py-2 rounded-full text-sm"><i class="fas fa-check mr-1"></i> Cancel anytime</span>
        </div>
    </div>
</section>

<!-- Features Grid -->
<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Everything You Need to Scale</h2>
            <p class="text-gray-600 max-w-2xl mx-auto">Powerful tools that help agencies and businesses grow faster</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            @foreach([
                ['icon' => 'robot', 'title' => 'AI Content Generation', 'desc' => 'Generate compelling copy, blog posts, and social media captions with AI that understands your brand voice.'],
                ['icon' => 'share-alt', 'title' => 'Social Media Management', 'desc' => 'Schedule, publish, and analyze posts across all major social platforms from a single dashboard.'],
                ['icon' => 'envelope-open-text', 'title' => 'Email Campaigns', 'desc' => 'Design beautiful emails, automate sequences, and track performance with real-time analytics.'],
                ['icon' => 'chart-line', 'title' => 'Advanced Analytics', 'desc' => 'Get actionable insights with custom dashboards, ROI tracking, and AI-powered recommendations.'],
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

<!-- Testimonials -->
<section class="py-20 bg-gray-50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-12">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">Loved by Marketers Worldwide</h2>
            <p class="text-gray-600">Join thousands of agencies and businesses growing with our platform</p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach([
                ['quote' => 'This platform transformed how we manage our clients. The AI content tools alone save us 20+ hours per week.', 'name' => 'Sarah Johnson', 'role' => 'CEO, GrowthHub Agency'],
                ['quote' => 'We switched from 5 different tools to this single platform. Our team is more productive and our clients are happier.', 'name' => 'Michael Chen', 'role' => 'Director, Nexus Digital'],
                ['quote' => 'The analytics and reporting features are incredible. We can finally show clients real ROI from their campaigns.', 'name' => 'Emily Rodriguez', 'role' => 'Founder, Spark Media'],
            ] as $testimonial)
            <div class="bg-white rounded-xl shadow-md p-6">
                <div class="flex gap-1 text-yellow-400 mb-4">
                    @for($i = 0; $i < 5; $i++)<i class="fas fa-star"></i>@endfor
                </div>
                <p class="text-gray-700 mb-4">"{{ $testimonial['quote'] }}"</p>
                <p class="font-semibold text-gray-900">{{ $testimonial['name'] }}</p>
                <p class="text-sm text-gray-500">{{ $testimonial['role'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h2 class="text-3xl font-bold mb-4">Ready to Transform Your Marketing?</h2>
        <p class="text-lg mb-8 opacity-90">Join 2,500+ agencies already using DigitalMarketingSaaS to grow their business.</p>
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('register') }}" class="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:bg-gray-100">
                <i class="fas fa-rocket mr-2"></i>Start Your Free Trial
            </a>
            <a href="{{ route('public.contact') }}" class="border-2 border-white text-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-indigo-600">
                <i class="fas fa-envelope mr-2"></i>Contact Sales
            </a>
        </div>
    </div>
</section>
@endsection
