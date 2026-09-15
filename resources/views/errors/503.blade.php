@extends('layouts.unified')
@section('title', 'Service Unavailable')
@section('content')
<div class="space-y-6">
<div class="error-page">
    <h2 class="headline text-warning">503</h2>
    <div class="error-content">
        <h3><i class="fas fa-tools text-warning"></i> Service unavailable.</h3>
        <p>We're currently undergoing maintenance. Please check back later. <a href="{{ route('dashboard') }}">Return to dashboard</a></p>
    </div>
</div>
</div>
@endsection

