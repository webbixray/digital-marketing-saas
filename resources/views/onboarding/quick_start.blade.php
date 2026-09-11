@extends('layouts.app')
@section('title', 'Quick Start')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card card-primary">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title"><i class="fas fa-magic mr-2"></i>Quick Start: Sample Content</h3>
                </div>
                <div class="card-body text-center">
                    <i class="fas fa-gift fa-4x text-primary mb-4"></i>
<h4>We've created sample posts for you!</h4>
                    <p class="text-muted">Get started with pre-made content you can customize and publish right away. Perfect for learning the platform!</p>

                    <div class="d-flex justify-content-center gap-3 mt-4">
                        <form action="{{ route('onboarding.quickStart') }}" method="POST">
                            @csrf
                            <button type="submit" class="btn btn-primary btn-lg">
                                <i class="fas fa-magic mr-2"></i>Create Sample Posts
                            </button>
                        </form>
                        <a href="{{ route('onboarding.step1') }}" class="btn btn-outline-secondary btn-lg">
                            <i class="fas fa-cog mr-2"></i>Custom Setup
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
