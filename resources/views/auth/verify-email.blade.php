@extends('layouts.public-unified')

@section('title', 'Verify Email')

@section('content')
<x-flash-messages />
<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-12 bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-xl shadow-xl p-8 text-center">
            <div class="w-16 h-16 bg-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-envelope text-white text-2xl"></i>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-4">Verify Your Email</h1>

            @if (session('resent'))
                <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-3 mb-4">
                    A fresh verification link has been sent to your email address.
                </div>
            @endif

            <p class="text-gray-600 mb-4">Before proceeding, please check your email for a verification link.</p>
            <p class="text-gray-600 mb-4">If you did not receive the email,</p>
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf
                <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700">
                    Click here to request another
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
