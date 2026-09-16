@extends('layouts.unified')
@section('title', 'Enable AI Agents')

@section('content')
<div class="space-y-6">
<div class="container-fluid">
    <div class="grid grid-cols-12 gap-4 justify-center>
        <div class="col-span-12 lg:col-span-8">
            <!-- Progress Bar -->
            <div class="card card-outline card-primary mb-4">
                <div class="p-6">
                    <h5 class="text-center mb-3">Step 5 of 5: Enable AI Agents</h5>
                    <div class="w-full bg-gray-200 rounded-full h-2 dark:bg-gray-700" style="height: 25px;">
                        <div class="progress-bar bg-success progress-bar-striped" role="progressbar" style="width: 100%;">
                            100%
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mt-2 text-sm text-muted">
                        <span><i class="fas fa-check text-success"></i> Agency Info</span>
                        <span><i class="fas fa-check text-success"></i> Social</span>
                        <span><i class="fas fa-check text-success"></i> Team</span>
                        <span><i class="fas fa-check text-success"></i> Campaign</span>
                        <span class="font-weight-bold text-primary">AI</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-md border border-gray-200 dark:bg-gray-800 dark:border-gray-700">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="font-semibold text-gray-900 dark:text-white"><i class="fas fa-robot mr-2"></i>Activate AI Agents</h3>
                </div>

                <form action="{{ route('onboarding.step5') }}" method="POST">
                    @csrf
                    <div class="p-6">
                        @if(session('success'))
                            <div class="alert alert-success alert-dismissible">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <i class="fas fa-check mr-1"></i>{{ session('success') }}
                            </div>
                        @endif

                        <div class="text-center mb-4">
                            <i class="fas fa-robot fa-4x text-primary mb-3"></i>
                            <h4>Let AI Supercharge Your Marketing</h4>
                            <p class="text-muted">Enable AI agents to automate and optimize your marketing workflows.</p>
                        </div>

                        <div class="grid grid-cols-12 gap-4>
                            <div class="col-md-6 mb-3">
                                <div class="card card-outline card-info h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-pen-fancy fa-2x text-info mb-2"></i>
                                        <h5>Content Generation</h5>
                                        <p class="text-sm text-muted">AI writes captions, posts, and ad copy for you.</p>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input"
                                                   id="ai_content_generation" name="ai_content_generation"
                                                   value="1" {{ ($aiSettings['content_generation'] ?? true) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="ai_content_generation">Enable</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="card card-outline card-warning h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-chart-line fa-2x text-warning mb-2"></i>
                                        <h5>Post Optimization</h5>
                                        <p class="text-sm text-muted">AI optimizes timing, hashtags, and content.</p>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input"
                                                   id="ai_post_optimization" name="ai_post_optimization"
                                                   value="1" {{ ($aiSettings['post_optimization'] ?? true) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="ai_post_optimization">Enable</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="card card-outline card-success h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-chart-pie fa-2x text-success mb-2"></i>
                                        <h5>AI Analytics</h5>
                                        <p class="text-sm text-muted">Get AI-powered insights and recommendations.</p>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input"
                                                   id="ai_analytics" name="ai_analytics"
                                                   value="1" {{ ($aiSettings['analytics'] ?? true) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="ai_analytics">Enable</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mb-3">
                                <div class="card card-outline card-danger h-100">
                                    <div class="card-body text-center">
                                        <i class="fas fa-clock fa-2x text-danger mb-2"></i>
                                        <h5>Smart Scheduling</h5>
                                        <p class="text-sm text-muted">AI determines the best times to post.</p>
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input"
                                                   id="ai_scheduling" name="ai_scheduling"
                                                   value="1" {{ ($aiSettings['scheduling'] ?? true) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="ai_scheduling">Enable</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex justify-content-between">
                        <a href="{{ route('onboarding.step4') }}" class="bg-gray-600 text-white px-4 py-2 rounded-lg hover:bg-gray-700 inline-flex items-center gap-2 font-medium transition-colors">
                            <i class="fas fa-arrow-left mr-1"></i> Back
                        </a>
                        <button type="submit" class="btn btn-success btn-lg">
                            <i class="fas fa-rocket mr-1"></i> Complete Setup
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

