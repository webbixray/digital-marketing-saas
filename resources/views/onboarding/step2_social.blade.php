@extends('layouts.unified')
@section('title', 'Connect Social Accounts')

@section('content')
<div class="space-y-6">
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Progress Bar -->
            <div class="card card-outline card-primary mb-4">
                <div class="card-body">
                    <h5 class="text-center mb-3">Step 2 of 5: Connect Social Accounts</h5>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar bg-primary progress-bar-striped" role="progressbar" style="width: 40%;">
                            40%
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-2 text-sm text-muted">
                        <span><i class="fas fa-check text-success"></i> Agency Info</span>
                        <span class="font-weight-bold text-primary">Social</span>
                        <span>Team</span>
                        <span>Campaign</span>
                        <span>AI</span>
                    </div>
                </div>
            </div>

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-share-alt mr-2"></i>Connect Your Social Media Accounts</h3>
                </div>

                <form action="{{ route('onboarding.step2') }}" method="POST">
                    @csrf
                    <div class="card-body">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <i class="fas fa-check mr-1"></i>{{ session('success') }}
                            </div>
                        @endif

                        <p class="text-muted">Select the social platforms you want to manage. You can always add more later.</p>

                        <div class="row">
                            @foreach($platforms as $key => $name)
                                <div class="col-md-6 col-lg-4 mb-3">
                                    <div class="card card-outline {{ isset($connectedAccounts[$key]) ? 'card-success' : 'card-secondary' }}">
                                        <div class="card-body text-center">
                                            @switch($key)
                                                @case('facebook')
                                                    <i class="fab fa-facebook fa-3x text-primary mb-2"></i>
                                                    @break
                                                @case('instagram')
                                                    <i class="fab fa-instagram fa-3x text-danger mb-2"></i>
                                                    @break
                                                @case('twitter')
                                                    <i class="fab fa-twitter fa-3x text-info mb-2"></i>
                                                    @break
                                                @case('linkedin')
                                                    <i class="fab fa-linkedin fa-3x text-primary mb-2"></i>
                                                    @break
                                                @case('tiktok')
                                                    <i class="fab fa-tiktok fa-3x text-dark mb-2"></i>
                                                    @break
                                                @case('pinterest')
                                                    <i class="fab fa-pinterest fa-3x text-danger mb-2"></i>
                                                    @break
                                            @endswitch

                                            <h5>{{ $name }}</h5>

                                            @if(isset($connectedAccounts[$key]))
                                                <span class="badge badge-success mb-2">
                                                    <i class="fas fa-check mr-1"></i>Connected
                                                </span>
                                                <input type="hidden" name="platforms[]" value="{{ $key }}">
                                            @else
                                                <div class="custom-control custom-checkbox">
                                                    <input type="checkbox" class="custom-control-input"
                                                           id="platform_{{ $key }}" name="platforms[]"
                                                           value="{{ $key }}">
                                                    <label class="custom-control-label" for="platform_{{ $key }}">
                                                        Connect this platform
                                                    </label>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('onboarding.step1') }}" class="btn btn-secondary">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </a>
                        <button type="submit" class="btn btn-primary btn-lg">
                            Next: Invite Team <i class="fas fa-arrow-right ml-1"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

