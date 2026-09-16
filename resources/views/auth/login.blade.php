@extends('layouts.public-unified')

@section('title', 'Sign In')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="auth-logo-icon">
                <i class="fas fa-bolt"></i>
            </div>
            <h1>Welcome Back</h1>
            <p class="auth-subtitle">Sign in to manage your social media</p>
        </div>

        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <div class="input-group">
                    <i class="fas fa-envelope input-group-icon"></i>
                    <input id="email" type="email" name="email" class="form-input @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="you@example.com" required autofocus>
                </div>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Password</label>
                <div class="input-group">
                    <i class="fas fa-lock input-group-icon"></i>
                    <input id="password" type="password" name="password" class="form-input @error('password') is-invalid @enderror" placeholder="••••••••" required>
                </div>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group" style="display: flex; align-items: center; justify-content: space-between;">
                <div class="form-check">
                    <input type="checkbox" id="remember" name="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label for="remember">Remember me</label>
                </div>
                <a href="{{ route('password.request') }}" style="font-size: 14px; color: #6366f1;">Forgot password?</a>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-sign-in-alt"></i> Sign In
                </button>
            </div>
        </form>

        <div class="auth-footer">
            Don't have an account? <a href="{{ route('register') }}">Create one</a>
        </div>
    </div>
</div>
@endsection
