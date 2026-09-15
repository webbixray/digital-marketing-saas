@extends('layouts.unified')
@section('title', 'Page Not Found')
@section('content')
<div class="space-y-6">
<div class="error-page">
    <h2 class="headline text-warning">404</h2>
    <div class="error-content">
        <h3><i class="fas fa-exclamation-triangle text-warning"></i> Page not found.</h3>
        <p>The page you requested could not be found. <a href="{{ route('dashboard') }}">Return to dashboard</a></p>
    </div>
</div>
</div>
@endsection

