@extends('layouts.public-unified')

@section('title', 'Contact Us - DigitalMarketingSaaS')

@section('content')
<section class="bg-gradient-to-br from-indigo-600 to-purple-600 text-white py-20">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <h1 class="text-4xl font-bold mb-4">Contact Us</h1>
        <p class="text-xl opacity-90">We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
    </div>
</section>

<section class="py-20 bg-white">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-gray-50 rounded-xl shadow-md p-8">
            @if(session('success'))
                <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-3 mb-4">{{ session('success') }}</div>
            @endif
            <form method="POST" action="{{ route('public.contact.submit') }}">
                @csrf
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input type="text" name="first_name" placeholder="John" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input type="text" name="last_name" placeholder="Doe" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" placeholder="john@example.com" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Subject</label>
                    <select name="subject" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        <option>General Inquiry</option>
                        <option>Sales Question</option>
                        <option>Technical Support</option>
                        <option>Partnership</option>
                    </select>
                </div>
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Message</label>
                    <textarea name="message" rows="5" placeholder="How can we help?" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700">Send Message</button>
            </form>
        </div>
    </div>
</section>
@endsection
