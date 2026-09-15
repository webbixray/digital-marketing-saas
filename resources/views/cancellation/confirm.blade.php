@extends("layouts.unified")
@section('title', 'Subscription Cancelled')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Subscription Cancelled</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Cancelled</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card card-outline card-danger">
                    <div class="card-header">
                        <h3 class="card-title">Your subscription has been cancelled</h3>
                    </div>
                    <div class="card-body text-center">
                        <i class="fas fa-frown fa-4x text-muted mb-4"></i>
                        <h4>We're sorry to see you go!</h4>
                        <p class="text-muted">Your account has been downgraded to the Free plan. You can still use the platform with limited features.</p>
                        <p class="text-muted">Changed your mind? You can reactivate your subscription at any time.</p>

                        <div class="mt-4">
                            <a href="{{ route('agency.billing') }}" class="btn btn-primary">
                                <i class="fas fa-undo mr-2"></i>Reactivate Subscription
                            </a>
                            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-home mr-2"></i>Go to Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
