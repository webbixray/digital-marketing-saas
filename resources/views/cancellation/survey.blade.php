@extends('layouts.app')
@section('title', 'Cancel Subscription')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Cancel Subscription</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('agency.billing') }}">Billing</a></li>
                    <li class="breadcrumb-item active">Cancel</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <!-- Retention Offer -->
                @if($offer['urgency'] !== 'low')
                    <div class="alert alert-warning">
                        <h5><i class="fas fa-gift mr-2"></i>Wait! Before you go...</h5>
                        <p>{{ $offer['message'] }}</p>
                        <a href="{{ route('agency.billing') }}" class="btn btn-primary">Claim Your Discount</a>
                    </div>
                @endif

                <!-- Cancellation Survey -->
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">We're sorry to see you go</h3>
                    </div>
                    <div class="card-body">
                        <p>Help us improve by telling us why you're canceling:</p>

                        <form action="{{ route('cancellation.submit') }}" method="POST">
                            @csrf
                            <div class="form-group">
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="reason1" name="reason" value="too_expensive" class="custom-control-input" required>
                                    <label class="custom-control-label" for="reason1">Too expensive</label>
                                </div>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="reason2" name="reason" value="missing_features" class="custom-control-input">
                                    <label class="custom-control-label" for="reason2">Missing features I need</label>
                                </div>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="reason3" name="reason" value="not_using" class="custom-control-input">
                                    <label class="custom-control-label" for="reason3">Not using it enough</label>
                                </div>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="reason4" name="reason" value="switching" class="custom-control-input">
                                    <label class="custom-control-label" for="reason4">Switching to another tool</label>
                                </div>
                                <div class="custom-control custom-radio mb-2">
                                    <input type="radio" id="reason5" name="reason" value="other" class="custom-control-input">
                                    <label class="custom-control-label" for="reason5">Other</label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="feedback">Additional feedback (optional)</label>
                                <textarea class="form-control" id="feedback" name="feedback" rows="3" placeholder="Tell us what we could do better..."></textarea>
                            </div>

                            <button type="submit" class="btn btn-warning">
                                <i class="fas fa-frown mr-2"></i>Continue with Cancellation
                            </button>
                            <a href="{{ route('agency.billing') }}" class="btn btn-link">Go Back</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
