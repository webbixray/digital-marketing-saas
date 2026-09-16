@extends('layouts.unified')
@section('title', 'Connect Social Accounts')

@section('content')
<div class="space-y-6">
<div class="container-fluid">
    <div class="grid grid-cols-12 gap-4 justify-center>
        <div class="col-span-12 lg:col-span-8">
            <!-- Progress Bar -->
            <div class="card card-outline card-primary mb-4">
                <div class="p-6">
                    <h5 class="text-center mb-3">Step 2 of 5: Connect Social Accounts</h5>
                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700" style="height: 25px;">
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

            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-share-alt mr-2"></i>Connect Your Social Media Accounts</h3>
                </div>

                <form action="{{ route('onboarding.step2') }}" method="POST">
                    @csrf
                    <div class="p-6">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <i class="fas fa-check mr-1"></i>{{ session('success') }}
                            </div>
                        @endif

                        <p class="text-muted">Select the social platforms you want to manage. You can always add more later.</p>

                        <div class="grid grid-cols-12 gap-4>
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
                        <a href="{{ route('onboarding.step1') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </a>
                        <button type="submit" class="bg-indigo-600 text-white px-6 py-3 rounded-lg hover:bg-indigo-700 inline-flex items-center gap-2 font-medium transition-colors text-lg">
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

