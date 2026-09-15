@extends('layouts.public-unified')
@section('title', 'Features - DigitalMarketingSaaS')

@section('content')
<nav class="navbar navbar-expand-lg navbar-public sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold" href="{{ route('public.landing') }}">
            <i class="fas fa-rocket me-2" style="color: var(--primary);"></i>DigitalMarketingSaaS
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#publicNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="publicNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link active" href="{{ route('public.features') }}">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('public.pricing') }}">Pricing</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('public.docs') }}">Docs</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('public.blog') }}">Blog</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('public.contact') }}">Contact</a></li>
            </ul>
            <div class="d-flex">
                <a href="{{ route('login') }}" class="btn btn-outline-secondary me-2">Log In</a>
                <a href="{{ route('register') }}" class="btn btn-cta">Get Started</a>
            </div>
        </div>
    </div>
</nav>

<section class="pricing-header" style="padding: 60px 0;">
    <h1 class="fw-bold">Powerful Features for Modern Marketers</h1>
    <p class="lead mb-0">Everything you need to manage, optimize, and scale your marketing.</p>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-robot"></i></div>
                    <h5 class="fw-semibold">AI Content Generation</h5>
                    <p class="text-muted mb-0">Generate compelling copy, blog posts, and social media captions with AI that understands your brand voice.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-share-alt"></i></div>
                    <h5 class="fw-semibold">Social Media Management</h5>
                    <p class="text-muted mb-0">Schedule, publish, and analyze posts across all major social platforms from a single dashboard.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                    <h5 class="fw-semibold">Advanced Analytics</h5>
                    <p class="text-muted mb-0">Get actionable insights with custom dashboards, ROI tracking, and AI-powered recommendations.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-envelope-open-text"></i></div>
                    <h5 class="fw-semibold">Email Campaigns</h5>
                    <p class="text-muted mb-0">Design beautiful emails, automate sequences, and track performance with real-time analytics.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-users"></i></div>
                    <h5 class="fw-semibold">Team Collaboration</h5>
                    <p class="text-muted mb-0">Assign roles, manage approvals, and collaborate with your team and clients seamlessly.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon"><i class="fas fa-magic"></i></div>
                    <h5 class="fw-semibold">Workflow Automation</h5>
                    <p class="text-muted mb-0">Build custom automation workflows that save hours of manual work every week.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="cta-section">
    <div class="container text-center">
        <h2 class="fw-bold mb-3">Ready to Get Started?</h2>
        <p class="mb-4" style="opacity: 0.85;">Join thousands of agencies already using DigitalMarketingSaaS.</p>
        <a href="{{ route('register') }}" class="btn btn-light btn-lg me-3 px-4 py-3 fw-semibold">Start Free Trial</a>
        <a href="{{ route('public.contact') }}" class="btn btn-cta-outline btn-lg px-4 py-3">Contact Sales</a>
    </div>
</section>

<footer class="footer-public">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h6 class="text-white mb-3"><i class="fas fa-rocket me-2"></i>DigitalMarketingSaaS</h6>
                <p>All-in-one marketing platform built for agencies and businesses that want to scale.</p>
            </div>
            <div class="col-md-2">
                <h6 class="text-white mb-3">Product</h6>
                <ul class="list-unstyled">
                    <li><a href="{{ route('public.features') }}">Features</a></li>
                    <li><a href="{{ route('public.pricing') }}">Pricing</a></li>
                    <li><a href="{{ route('public.docs') }}">Documentation</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <h6 class="text-white mb-3">Legal</h6>
                <ul class="list-unstyled">
                    <li><a href="{{ route('public.terms') }}">Terms of Service</a></li>
                    <li><a href="{{ route('public.privacy') }}">Privacy Policy</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <h6 class="text-white mb-3">Company</h6>
                <ul class="list-unstyled">
                    <li><a href="{{ route('public.blog') }}">Blog</a></li>
                    <li><a href="{{ route('public.contact') }}">Contact</a></li>
                    <li><a href="#">Careers</a></li>
                </ul>
            </div>
            <div class="col-md-2">
                <h6 class="text-white mb-3">Stay Updated</h6>
                <p>Get the latest marketing tips and product updates.</p>
                <form method="POST" action="{{ route('public.newsletter') }}" class="d-flex">
                    @csrf
                    <input type="email" name="email" class="form-control me-2" placeholder="Enter your email" required>
                    <button type="submit" class="btn btn-primary">Subscribe</button>
                </form>
            </div>
        </div>
        <hr style="border-color: #1e293b;">
        <div class="text-center">
            <p class="mb-0">&copy; {{ date('Y') }} DigitalMarketingSaaS. All rights reserved.</p>
        </div>
    </div>
</footer>
@endsection
