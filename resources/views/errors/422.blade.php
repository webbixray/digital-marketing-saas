@extends('layouts.unified')
@section('title', 'Validation Error')
@section('content')
<div class="space-y-6">
<div class="error-page" style="text-align: center; padding: 40px;">
    <h2 class="headline text-warning" style="font-size: 120px; font-weight: 700; margin: 0; line-height: 1;">422</h2>
    <div class="error-content">
        <h3><i class="fas fa-exclamation-triangle text-warning"></i> Validation Error</h3>
        <p>Your request contains invalid data. Please correct the errors below.</p>
        @if(is_object($errors) && method_exists($errors, 'any') && $errors->any())
            <div class="alert alert-warning" style="text-align: left; max-width: 500px; margin: 20px auto;">
                <strong>The following errors occurred:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <a href="{{ url()->previous() ?? route('dashboard') }}" class="btn btn-default">
            <i class="fas fa-arrow-left"></i> Go Back
        </a>
    </div>
</div>
</div>
@endsection

