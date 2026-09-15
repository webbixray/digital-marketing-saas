@extends('layouts.unified')
@section('title', 'Welcome')

@section('content')
<div class="space-y-6">
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-primary">
            <div class="card-header text-center">
                <h3 class="card-title"><i class="fas fa-rocket mr-2"></i>Welcome to {{ config('app.name') }}!</h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-4">
                    <h4>Let's get you started in 3 easy steps</h4>
                    <div class="progress" style="height: 30px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 33%;" id="onboardingProgress">Step 1 of 3</div>
                    </div>
                </div>

                <!-- Step 1: Profile -->
                <div class="onboarding-step" id="step1">
                    <h5><i class="fas fa-user mr-2"></i>Step 1: Complete Your Profile</h5>
                    <p>Tell us about yourself and your business.</p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Your Name</label>
                                <input type="text" class="form-control" value="{{ auth()->user()->name }}" disabled>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Agency Name</label>
                                <input type="text" class="form-control" value="{{ auth()->user()->agency->name ?? '' }}" disabled>
                            </div>
                        </div>
                    </div>
                    <button class="btn btn-primary btn-next" data-next="2">Next: Connect Social Accounts</button>
                </div>

                <!-- Step 2: Connect -->
                <div class="onboarding-step d-none" id="step2">
                    <h5><i class="fas fa-share-alt mr-2"></i>Step 2: Connect Your Social Accounts</h5>
                    <p>Connect your social media accounts to start posting.</p>
                    <div class="row">
                        <div class="col-6 col-md-4 text-center mb-3">
                            <a href="{{ route('social.accounts.create') }}?platform=facebook" class="btn btn-outline-primary btn-block">
                                <i class="fab fa-facebook fa-2x"></i><br>Facebook
                            </a>
                        </div>
                        <div class="col-6 col-md-4 text-center mb-3">
                            <a href="{{ route('social.accounts.create') }}?platform=instagram" class="btn btn-outline-danger btn-block">
                                <i class="fab fa-instagram fa-2x"></i><br>Instagram
                            </a>
                        </div>
                        <div class="col-6 col-md-4 text-center mb-3">
                            <a href="{{ route('social.accounts.create') }}?platform=twitter" class="btn btn-outline-info btn-block">
                                <i class="fab fa-twitter fa-2x"></i><br>Twitter
                            </a>
                        </div>
                        <div class="col-6 col-md-4 text-center mb-3">
                            <a href="{{ route('social.accounts.create') }}?platform=linkedin" class="btn btn-outline-primary btn-block">
                                <i class="fab fa-linkedin fa-2x"></i><br>LinkedIn
                            </a>
                        </div>
                        <div class="col-6 col-md-4 text-center mb-3">
                            <a href="{{ route('social.accounts.create') }}?platform=tiktok" class="btn btn-outline-dark btn-block">
                                <i class="fab fa-tiktok fa-2x"></i><br>TikTok
                            </a>
                        </div>
                        <div class="col-6 col-md-4 text-center mb-3">
                            <a href="{{ route('social.accounts.create') }}?platform=pinterest" class="btn btn-outline-danger btn-block">
                                <i class="fab fa-pinterest fa-2x"></i><br>Pinterest
                            </a>
                        </div>
                    </div>
                    <button class="btn btn-secondary btn-prev" data-prev="1">Back</button>
                    <button class="btn btn-primary btn-next" data-next="3">Next: Choose Plan</button>
                </div>

                <!-- Step 3: Plan -->
                <div class="onboarding-step d-none" id="step3">
                    <h5><i class="fas fa-crown mr-2"></i>Step 3: Choose Your Plan</h5>
                    <p>Select a plan that fits your needs.</p>
                    <div class="row">
                        @foreach(config('platform.plans') as $key => $plan)
                            <div class="col-md-3">
                                <div class="card card-outline {{ $agency->subscription_plan === $key ? 'card-primary' : '' }}">
                                    <div class="card-body text-center">
                                        <h5>{{ $plan['name'] }}</h5>
                                        <h3>${{ number_format($plan['price'], 0) }}<small>/mo</small></h3>
                                        <ul class="list-unstyled text-sm">
                                            <li>{{ $plan['posts_per_month'] == -1 ? 'Unlimited' : $plan['posts_per_month'] }} posts</li>
                                            <li>{{ $plan['social_accounts'] == -1 ? 'Unlimited' : $plan['social_accounts'] }} accounts</li>
                                            <li>{{ $plan['ai_generations_per_month'] == -1 ? 'Unlimited' : $plan['ai_generations_per_month'] }} AI gen</li>
                                        </ul>
                                        @if($agency->subscription_plan === $key)
                                            <span class="badge badge-success">Current</span>
                                        @else
                                            <form action="{{ route('agency.billing.upgrade') }}" method="POST">
                                                @csrf
                                                <input type="hidden" name="plan" value="{{ $key }}">
                                                <button class="btn btn-sm btn-primary">Choose</button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button class="btn btn-secondary btn-prev" data-prev="2">Back</button>
                    <a href="{{ route('dashboard') }}" class="btn btn-success">Go to Dashboard</a>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection


@push('scripts')
<script>
$(function() {
    $('.btn-next').click(function() {
        var next = $(this).data('next');
        $('.onboarding-step').addClass('d-none');
        $('#step' + next).removeClass('d-none');
        $('#onboardingProgress').css('width', (next * 33) + '%').text('Step ' + next + ' of 3');
    });
    $('.btn-prev').click(function() {
        var prev = $(this).data('prev');
        $('.onboarding-step').addClass('d-none');
        $('#step' + prev).removeClass('d-none');
        $('#onboardingProgress').css('width', (prev * 33) + '%').text('Step ' + prev + ' of 3');
    });
});
</script>
@endpush
