@extends('layouts.unified')

@section('title', 'Subscription Success')

@section('breadcrumb')
    <li class="hover:text-gray-700"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="hover:text-gray-700"><a href="{{ route('agency.billing') }}">Billing</a></li>
    <li class="text-gray-900 font-medium">Success</li>
@endsection

@section('content')
<div class="space-y-6">
<div class="grid grid-cols-12 gap-4 justify-center>
    <div class="col-md-8 text-center">
        <div class="card card-outline card-success">
            <div class="card-body py-5">
                <div class="success-icon mb-4">
                    <i class="fas fa-check-circle text-success" style="font-size: 5rem;"></i>
                </div>
                <h2 class="text-success">Subscription Activated!</h2>
                <p class="lead text-muted">
                    Your subscription has been successfully upgraded. You now have access to all the features of your new plan.
                </p>
                <hr>
                <div class="mt-4">
                    <a href="{{ route('dashboard') }}" class="btn btn-primary btn-lg mr-2">
                        <i class="fas fa-tachometer-alt mr-1"></i>Go to Dashboard
                    </a>
                    <a href="{{ route('agency.billing') }}" class="btn btn-outline-secondary btn-lg">
                        <i class="fas fa-credit-card mr-1"></i>Manage Billing
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection


@push('styles')
<style>
    .success-icon {
        animation: scaleIn 0.5s ease-out;
    }
    @keyframes scaleIn {
        0% { transform: scale(0); opacity: 0; }
        100% { transform: scale(1); opacity: 1; }
    }
</style>
@endpush
