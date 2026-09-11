@extends('layouts.public')
@section('title', 'Welcome to DigitalMarketingSaaS')

@section('content')
<!-- Hero Section -->
<section class="hero-section" style="padding: 80px 0;">
    <div class="container text-center">
        <h1 class="fw-bold" style="font-size: 2.5rem;">Welcome to DigitalMarketingSaaS!</h1>
        <p class="lead mb-4">Your agency is ready. Let's get you set up in 5 easy steps.</p>
        <div class="mb-4">
            <a href="{{ route('onboarding.step1') }}" class="btn btn-light btn-lg px-4 py-3 fw-semibold">
                <i class="fas fa-rocket me-2"></i>Get Started
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-light btn-lg px-4 py-3 ms-2">
                Skip to Dashboard
            </a>
        </div>
        <div class="justify-content-center">
            <span class="badge-stat"><i class="fas fa-check me-1"></i> 5-minute setup</span>
            <span class="badge-stat"><i class="fas fa-check me-1"></i> No credit card required</span>
            <span class="badge-stat"><i class="fas fa-check me-1"></i> Cancel anytime</span>
        </div>
    </div>
</section>

<!-- What You'll Setup -->
<section class="py-5">
    <div class="container">
        <h2 class="text-center fw-bold mb-5">What We'll Set Up Together</h2>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <div class="feature-icon mx-auto"><i class="fas fa-building"></i></div>
                    <h5 class="fw-semibold">1. Agency Profile</h5>
                    <p class="text-muted mb-0">Tell us about your agency so we can personalize your experience.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <div class="feature-icon mx-auto"><i class="fas fa-share-alt"></i></div>
                    <h5 class="fw-semibold">2. Social Accounts</h5>
                    <p class="text-muted mb-0">Connect your social media accounts to start posting.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <div class="feature-icon mx-auto"><i class="fas fa-users"></i></div>
                    <h5 class="fw-semibold">3. Team Members</h5>
                    <p class="text-muted mb-0">Invite team members to collaborate with you.</p>
                </div>
            </div>
        </div>
        <div class="row g-4 mt-2">
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <div class="feature-icon mx-auto"><i class="fas fa-bullhorn"></i></div>
                    <h5 class="fw-semibold">4. First Campaign</h5>
                    <p class="text-muted mb-0">Create your first marketing campaign.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <div class="feature-icon mx-auto"><i class="fas fa-robot"></i></div>
                    <h5 class="fw-semibold">5. AI Agents</h5>
                    <p class="text-muted mb-0">Activate AI to supercharge your marketing.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card text-center">
                    <div class="feature-icon mx-auto"><i class="fas fa-chart-line"></i></div>
                    <h5 class="fw-semibold">6. See Results!</h5>
                    <p class="text-muted mb-0">Watch AI create content, schedule posts, and grow your brand.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Quick Wins Section -->
<section class="py-5" style="background: #f1f5f9;">
    <div class="container">
        <h2 class="text-center fw-bold mb-5">See Value in Minutes</h2>
        <div class="row align-items-center">
            <div class="col-md-6">
                <img src="https://via.placeholder.com/600x400/6366f1/white?text=AI+Content+Generation" alt="AI Content" class="img-fluid rounded shadow">
            </div>
            <div class="col-md-6">
                <h4 class="fw-semibold">AI Generates Your First Post</h4>
                <p class="text-muted">Tell AI about your brand, and it'll create engaging social media posts in seconds. No writer's block, no wasted time.</p>
                <ul class="list-unstyled">
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Captions, hashtags, and visuals</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Platform-optimized content</li>
                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i> Brand voice customization</li>
                </ul>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container text-center">
        <h2 class="fw-bold">Ready to Transform Your Marketing?</h2>
        <p class="lead mb-4">Join thousands of agencies using AI to grow faster.</p>
        <a href="{{ route('onboarding.step1') }}" class="btn btn-light btn-lg px-4 py-3 fw-semibold">
            <i class="fas fa-rocket me-2"></i>Start Setup Now
        </a>
    </div>
</section>

<!-- Footer -->
<footer class="footer-public">
    <div class="container text-center">
        <p class="mb-0">&copy; {{ date('Y') }} DigitalMarketingSaaS. All rights reserved.</p>
    </div>
</footer>
