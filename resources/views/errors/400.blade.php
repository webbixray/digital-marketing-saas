@extends('layouts.unified')
@section('title', 'Bad Request')
@section('content')
<div class="space-y-6">
<div class="error-page" style="text-align: center; padding: 40px;">
    <h2 class="headline text-danger" style="font-size: 120px; font-weight: 700; margin: 0; line-height: 1;">400</h2>
    <div class="error-content">
        <h3><i class="fas fa-exclamation-circle text-danger"></i> Bad Request</h3>
        <p>The request could not be processed. Please check your input and try again.</p>
        @if(is_object($errors) && method_exists($errors, 'any') && $errors->any())
            <div class="alert alert-danger" style="text-align: left; max-width: 400px; margin: 20px auto;">
                <strong>Validation errors:</strong>
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

