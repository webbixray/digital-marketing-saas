@extends('layouts.public-unified')

@section('title', 'Sign In')

@section('content')
<x-flash-messages />
<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center px-4 py-12 bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500">
    <div class="w-full max-w-md">
        <div class="text-center mb-8">
            <a href="/" class="text-white text-3xl font-bold">DigitalMarketingSaaS</a>
        </div>
        <div class="bg-white rounded-xl shadow-xl p-8">
            <div class="text-center mb-8">
                <div class="w-16 h-16 bg-indigo-600 rounded-xl flex items-center justify-center mx-auto mb-4">
                    <i class="fas fa-bolt text-white text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">Welcome Back</h1>
                <p class="text-gray-500 mt-2">Sign in to manage your social media</p>
            </div>

            @if(session('success'))
                <div class="bg-green-50 text-green-700 border border-green-200 rounded-lg p-3 mb-4">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="bg-red-50 text-red-700 border border-red-200 rounded-lg p-3 mb-4">{{ session('error') }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="email">Email Address</label>
                    <div class="relative">
                        <i class="fas fa-envelope absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="you@example.com" required autofocus
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('email') border-red-500 @enderror"
                            aria-describedby="email-error"
                            @error('email') aria-invalid="true" @enderror>
                    </div>
                    @error('email')
                        <div class="text-red-500 text-sm mt-1" id="email-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-1" for="password">Password</label>
                    <div class="relative">
                        <i class="fas fa-lock absolute left-3 top-1/2 -translate-y-1/2 text-gray-400"></i>
                        <input id="password" type="password" name="password" placeholder="••••••••" required
                            class="w-full pl-10 pr-12 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('password') border-red-500 @enderror"
                            aria-describedby="password-error"
                            @error('password') aria-invalid="true" @enderror>
                        <button type="button" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 rounded"
                            aria-label="Toggle password visibility"
                            onclick="togglePassword('password', 'passwordToggleIcon')">
                            <i id="passwordToggleIconLogin" class="fas fa-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <div class="text-red-500 text-sm mt-1" id="password-error">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-4 flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500">
                        <label for="remember" class="text-sm text-gray-600">Remember me</label>
                    </div>
                    <a href="{{ route('password.request') }}" class="text-sm text-indigo-600 hover:underline">Forgot password?</a>
                </div>

                <div class="mb-4">
                    <button type="submit" class="w-full bg-indigo-600 text-white py-2 px-4 rounded-lg hover:bg-indigo-700 flex items-center justify-center gap-2">
                        <i class="fas fa-sign-in-alt"></i> Sign In
                    </button>
                </div>
            </form>

            <div class="text-center text-gray-500">
                Don't have an account? <a href="{{ route('register') }}" class="text-indigo-600 hover:underline">Create one</a>
            </div>
        </div>
    </div>
</div>

<script nonce="{{ $cspNonce ?? '' }}">
function togglePassword(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}
</script>
@endsection
