@extends('layouts.public-unified')

@section('title', 'Blog - DigitalMarketingSaaS')

@section('content')
<section class="bg-gradient-to-br from-indigo-600 to-purple-600 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Blog</h1>
        <p class="text-xl opacity-90">Marketing insights, tips, and platform updates</p>
    </div>
</section>

<section class="py-20 bg-white">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            @foreach([
                ['title' => '10 Ways AI is Transforming Social Media Marketing', 'date' => 'Sep 10, 2026', 'excerpt' => 'Discover how AI tools are revolutionizing content creation, scheduling, and analytics for agencies.'],
                ['title' => 'The Complete Guide to Multi-Platform Campaign Management', 'date' => 'Sep 5, 2026', 'excerpt' => 'Learn how to manage campaigns across Facebook, Instagram, LinkedIn, and Twitter from a single dashboard.'],
                ['title' => 'How to Increase Client Retention by 40%', 'date' => 'Aug 28, 2026', 'excerpt' => 'Proven strategies for keeping clients happy and growing your agency revenue.'],
            ] as $post)
            <div class="bg-gray-50 rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
                <div class="h-48 bg-gradient-to-br from-indigo-400 to-purple-500"></div>
                <div class="p-6">
                    <p class="text-sm text-gray-500 mb-2">{{ $post['date'] }}</p>
                    <h3 class="font-semibold text-gray-900 mb-2">{{ $post['title'] }}</h3>
                    <p class="text-gray-600">{{ $post['excerpt'] }}</p>
                </div>
            </div>
            @endforeach
        </div>
    </div>
</section>
@endsection
