@extends('layouts.public')
@section('title', 'Pricing - DigitalMarketingSaaS')

@section('content')
<!-- Navigation -->
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
                <li class="nav-item"><a class="nav-link" href="{{ route('public.features') }}">Features</a></li>
                <li class="nav-item"><a class="nav-link active" href="{{ route('public.pricing') }}">Pricing</a></li>
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

<!-- Header -->
<section class="pricing-header">
    <h1 class="fw-bold">Simple, Transparent Pricing</h1>
    <p class="lead mb-0">Choose the plan that fits your needs. All plans include a 14-day free trial.</p>
</section>

<!-- Pricing Cards -->
<section class="py-5" style="margin-top: -80px;">
    <div class="container">
        <div class="row g-4">
            <!-- Free -->
            <div class="col-md-3">
                <div class="pricing-card">
                    <div class="plan-name">Free</div>
                    <div class="plan-price">$0<span>/mo</span></div>
                    <p class="plan-desc">Perfect for trying out the platform</p>
                    <hr>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> 1 User</li>
                        <li><i class="fas fa-check"></i> 10 Posts/month</li>
                        <li><i class="fas fa-check"></i> 5 AI Generations</li>
                        <li><i class="fas fa-check"></i> 2 Social Accounts</li>
                        <li><i class="fas fa-minus"></i> Advanced Analytics</li>
                        <li><i class="fas fa-minus"></i> Workflow Automation</li>
                    </ul>
                    <a href="{{ route('register') }}" class="btn btn-plan btn-plan-outline">Get Started</a>
                </div>
            </div>

            <!-- Starter -->
            <div class="col-md-3">
                <div class="pricing-card">
                    <div class="plan-name">Starter</div>
                    <div class="plan-price">$29<span>/mo</span></div>
                    <p class="plan-desc">Great for small businesses</p>
                    <hr>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> 3 Users</li>
                        <li><i class="fas fa-check"></i> 100 Posts/month</li>
                        <li><i class="fas fa-check"></i> 50 AI Generations</li>
                        <li><i class="fas fa-check"></i> 5 Social Accounts</li>
                        <li><i class="fas fa-check"></i> Basic Analytics</li>
                        <li><i class="fas fa-minus"></i> Workflow Automation</li>
                    </ul>
                    <a href="{{ route('register') }}" class="btn btn-plan btn-plan-outline">Get Started</a>
                </div>
            </div>

            <!-- Pro -->
            <div class="col-md-3">
                <div class="pricing-card featured">
                    <span class="badge-popular">Most Popular</span>
                    <div class="plan-name">Pro</div>
                    <div class="plan-price">$79<span>/mo</span></div>
                    <p class="plan-desc">For growing agencies</p>
                    <hr>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> 10 Users</li>
                        <li><i class="fas fa-check"></i> 500 Posts/month</li>
                        <li><i class="fas fa-check"></i> 200 AI Generations</li>
                        <li><i class="fas fa-check"></i> 15 Social Accounts</li>
                        <li><i class="fas fa-check"></i> Advanced Analytics</li>
                        <li><i class="fas fa-check"></i> Workflow Automation</li>
                    </ul>
                    <a href="{{ route('register') }}" class="btn btn-plan btn-plan-primary">Get Started</a>
                </div>
            </div>

            <!-- Enterprise -->
            <div class="col-md-3">
                <div class="pricing-card">
                    <div class="plan-name">Enterprise</div>
                    <div class="plan-price">$199<span>/mo</span></div>
                    <p class="plan-desc">For large organizations</p>
                    <hr>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Unlimited Users</li>
                        <li><i class="fas fa-check"></i> Unlimited Posts</li>
                        <li><i class="fas fa-check"></i> Unlimited AI</li>
                        <li><i class="fas fa-check"></i> Unlimited Accounts</li>
                        <li><i class="fas fa-check"></i> Custom Reports</li>
                        <li><i class="fas fa-check"></i> Custom Workflows</li>
                    </ul>
                    <a href="{{ route('public.contact') }}" class="btn btn-plan btn-plan-outline">Contact Sales</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- FAQ -->
<section class="py-5">
    <div class="container">
        <h3 class="text-center fw-bold mb-4">Frequently Asked Questions</h3>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion" id="pricingFaq">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Can I cancel anytime?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#pricingFaq">
                            <div class="accordion-body">Yes, you can cancel your subscription at any time. No long-term contracts or cancellation fees.</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Is there a free trial?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
                            <div class="accordion-body">All paid plans come with a 14-day free trial. No credit card required to start.</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                What payment methods do you accept?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
                            <div class="accordion-body">We accept all major credit cards (Visa, Mastercard, American Express) and PayPal. Enterprise plans can pay via invoice.</div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                Do you offer refunds?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#pricingFaq">
                            <div class="accordion-body">Yes! We offer a 14-day money-back guarantee on all annual plans. If you're not satisfied, contact us for a full refund.</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Trust Badges -->
<section class="py-4">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 text-center">
                <p class="text-muted mb-3">Trusted by agencies worldwide</p>
                <div class="d-flex justify-content-center gap-4 flex-wrap">
                    <span class="badge bg-light text-dark p-2"><i class="fas fa-shield-alt text-success"></i> SSL Secured</span>
                    <span class="badge bg-light text-dark p-2"><i class="fas fa-lock text-primary"></i> GDPR Compliant</span>
                    <span class="badge bg-light text-dark p-2"><i class="fas fa-server text-info"></i> 99.9% Uptime</span>
                    <span class="badge bg-light text-dark p-2"><i class="fas fa-undo text-warning"></i> 14-Day Money-Back</span>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Footer -->
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
                    <input type="email" name="email" class="form-control me-2 @error('email') is-invalid @enderror" placeholder="Enter your email" value="{{ old('email') }}" required>
                    <button type="submit" class="btn btn-primary">Subscribe</button>
                </form>
                @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
            </div>
        </div>
        <hr style="border-color: #1e293b;">
        <div class="text-center">
            <p class="mb-0">&copy; {{ date('Y') }} DigitalMarketingSaaS. All rights reserved.</p>
        </div>
    </div>
</footer>
@endsection
