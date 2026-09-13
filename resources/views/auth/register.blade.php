@extends('layouts.auth')

@section('title', 'Create Account')

@section('content')
<div class="auth-container">
    <div class="auth-card">
        <div class="auth-logo">
            <div class="auth-logo-icon">
                <i class="fas fa-bolt"></i>
            </div>
            <h1>Get Started</h1>
            <p class="auth-subtitle">Create your agency account</p>
        </div>

        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <form method="POST" action="{{ route('register') }}">
            @csrf
            <div class="form-group">
                <label class="form-label" for="agency_name">Agency Name</label>
                <div class="input-group">
                    <i class="fas fa-building input-group-icon"></i>
                    <input id="agency_name" type="text" name="agency_name" class="form-input @error('agency_name') is-invalid @enderror" value="{{ old('agency_name') }}" placeholder="Your Agency" required>
                </div>
                @error('agency_name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="name">Your Name</label>
                <div class="input-group">
                    <i class="fas fa-user input-group-icon"></i>
                    <input id="name" type="text" name="name" class="form-input @error('name') is-invalid @enderror" value="{{ old('name') }}" placeholder="John Doe" required>
                </div>
                @error('name')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="form-group">
                <label class="form-label" for="email">Email Address</label>
                <div class="input-group">
                    <i class="fas fa-envelope input-group-icon"></i>
                    <input id="email" type="email" name="email" class="form-input @error('email') is-invalid @enderror" value="{{ old('email') }}" placeholder="you@example.com" required>
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

            <div class="form-group">
                <label class="form-label" for="password_confirmation">Confirm Password</label>
                <div class="input-group">
                    <i class="fas fa-lock input-group-icon"></i>
                    <input id="password_confirmation" type="password" name="password_confirmation" class="form-input" placeholder="••••••••" required>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" class="btn-primary">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </div>
        </form>

        <div class="auth-footer">
            Already have an account? <a href="{{ route('login') }}">Sign in</a>
        </div>
    </div>
</div>
@endsection
