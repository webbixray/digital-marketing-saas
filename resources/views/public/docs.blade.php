@extends('layouts.public-unified')

@section('title', 'Documentation - DigitalMarketingSaaS')

@section('content')
<x-flash-messages />
<section class="bg-gradient-to-br from-indigo-600 to-purple-600 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Documentation</h1>
        <p class="text-xl opacity-90">Learn how to use DigitalMarketingSaaS to grow your agency</p>
    </div>
</section>

<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach([
                ['icon' => 'rocket', 'title' => 'Getting Started', 'desc' => 'Learn the basics of setting up your account and connecting your social media accounts.'],
                ['icon' => 'robot', 'title' => 'AI Content', 'desc' => 'Discover how to generate compelling content with AI that understands your brand.'],
                ['icon' => 'share-alt', 'title' => 'Social Media', 'desc' => 'Master scheduling, publishing, and analytics across all platforms.'],
                ['icon' => 'envelope-open-text', 'title' => 'Email Campaigns', 'desc' => 'Design beautiful emails and automate your marketing sequences.'],
                ['icon' => 'chart-line', 'title' => 'Analytics', 'desc' => 'Track performance, measure ROI, and optimize your campaigns.'],
                ['icon' => 'magic', 'title' => 'Automation', 'desc' => 'Build custom workflows that save hours of manual work.'],
            ] as $doc)
            <div class="bg-gray-50 rounded-xl p-6 hover:shadow-lg transition-shadow">
                <div class="w-12 h-12 bg-indigo-100 rounded-lg flex items-center justify-center mb-4">
                    <i class="fas fa-{{ $doc['icon'] }} text-indigo-600 text-xl"></i>
                </div>
                <h3 class="font-semibold text-gray-900 mb-2">{{ $doc['title'] }}</h3>
                <p class="text-gray-600">{{ $doc['desc'] }}</p>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
