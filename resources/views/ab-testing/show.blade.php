@extends('layouts.app')
@section('title', $test->name)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">{{ $test->name }}</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('ab-testing.index') }}">A/B Testing</a></li>
                    <li class="breadcrumb-item active">{{ $test->name }}</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Test Results</h3>
                        <div class="card-tools">
                            <span class="badge badge-{{ $test->status === 'running' ? 'success' : ($test->status === 'completed' ? 'primary' : 'secondary') }}">{{ ucfirst($test->status) }}</span>
                        </div>
                    </div>
                    <div class="card-body">
                        @if($test->hypothesis)
                            <div class="alert alert-info">
                                <strong>Hypothesis:</strong> {{ $test->hypothesis }}
                            </div>
                        @endif

                        <div class="row">
                            <div class="col-md-6">
                                <div class="card card-outline card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">Variant A (Control)</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>{{ $test->variant_a_content }}</p>
                                        <hr>
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <h4>{{ $test->variant_a_impressions }}</h4>
                                                <small class="text-muted">Impressions</small>
                                            </div>
                                            <div class="col-4">
                                                <h4>{{ $test->variant_a_engagement }}</h4>
                                                <small class="text-muted">Engagement</small>
                                            </div>
                                            <div class="col-4">
                                                <h4>{{ $test->variant_a_clicks }}</h4>
                                                <small class="text-muted">Clicks</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-outline card-warning">
                                    <div class="card-header">
                                        <h3 class="card-title">Variant B (Treatment)</h3>
                                    </div>
                                    <div class="card-body">
                                        <p>{{ $test->variant_b_content }}</p>
                                        <hr>
                                        <div class="row text-center">
                                            <div class="col-4">
                                                <h4>{{ $test->variant_b_impressions }}</h4>
                                                <small class="text-muted">Impressions</small>
                                            </div>
                                            <div class="col-4">
                                                <h4>{{ $test->variant_b_engagement }}</h4>
                                                <small class="text-muted">Engagement</small>
                                            </div>
                                            <div class="col-4">
                                                <h4>{{ $test->variant_b_clicks }}</h4>
                                                <small class="text-muted">Clicks</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($test->winner)
                            <div class="alert alert-{{ $test->winner === 'inconclusive' ? 'warning' : 'success' }} mt-3">
                                <strong>Winner:</strong> {{ ucfirst($test->winner) }} ({{ $test->confidence }}% confidence)
                            </div>
                        @endif
                    </div>
                    <div class="card-footer">
                        @if($test->status === 'draft')
                            <form action="{{ route('ab-testing.start', $test) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-success"><i class="fas fa-play mr-2"></i>Start Test</button>
                            </form>
                        @endif
                        @if($test->status === 'running')
                            <form action="{{ route('ab-testing.pause', $test) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-warning"><i class="fas fa-pause mr-2"></i>Pause</button>
                            </form>
                        @endif
                        @if(in_array($test->status, ['running', 'paused']))
                            <form action="{{ route('ab-testing.complete', $test) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary"><i class="fas fa-check mr-2"></i>Complete</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Details</h3>
                    </div>
                    <div class="card-body">
                        <p><strong>Platform:</strong> {{ ucfirst($test->platform) }}</p>
                        <p><strong>Type:</strong> {{ ucfirst($test->type) }}</p>
                        <p><strong>Sample Size:</strong> {{ $test->sample_size }} per variant</p>
                        <p><strong>Account:</strong> {{ $test->socialAccount?->platform_username }}</p>
                        <p><strong>Created:</strong> {{ $test->created_at->toDateString() }}</p>
                        @if($test->started_at)
                            <p><strong>Started:</strong> {{ $test->started_at->toDateString() }}</p>
                        @endif
                        @if($test->ended_at)
                            <p><strong>Ended:</strong> {{ $test->ended_at->toDateString() }}</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
