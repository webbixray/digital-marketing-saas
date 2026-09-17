@extends('layouts.public-unified')

@section('title', 'Forgot Password')

@section('content')
<x-flash-messages />
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-500 to-purple-600 px-4">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <a href="/" class="text-white text-2xl font-bold">DigitalMarketingSaaS</a>
        </div>
        <div class="bg-white rounded-xl shadow-xl p-8">
            <p class="text-gray-500 text-center mb-6">Forgot your password? Enter your email to reset it.</p>
            @if(session('success'))
                <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-3 mb-4">{{ session('success') }}</div>
            @endif
            <form action="{{ route('password.email') }}" method="POST">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input type="email" name="email" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700">Send Reset Link</button>
            </form>
            <div class="text-center mt-4">
                <a href="{{ route('login') }}" class="text-indigo-600 hover:underline">Back to login</a>
            </div>
        </div>
    </div>
</div>
@endsection
