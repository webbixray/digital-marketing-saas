@extends('layouts.public')
@section('title', 'Contact Us - DigitalMarketingSaaS')

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
                <li class="nav-item"><a class="nav-link" href="{{ route('public.features') }}">Features</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('public.pricing') }}">Pricing</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('public.docs') }}">Docs</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('public.blog') }}">Blog</a></li>
                <li class="nav-item"><a class="nav-link active" href="{{ route('public.contact') }}">Contact</a></li>
            </ul>
            <div class="d-flex">
                <a href="{{ route('login') }}" class="btn btn-outline-secondary me-2">Log In</a>
                <a href="{{ route('register') }}" class="btn btn-cta">Get Started</a>
            </div>
        </div>
    </div>
</nav>

<section class="pricing-header" style="padding: 60px 0;">
    <h1 class="fw-bold">Contact Us</h1>
    <p class="lead mb-0">We'd love to hear from you. Send us a message and we'll respond as soon as possible.</p>
</section>

<section class="py-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="pricing-card">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    <form method="POST" action="{{ route('public.contact.submit') }}">
                        @csrf
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" placeholder="John" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" placeholder="Doe" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email</label>
                            <input type="email" name="email" class="form-control" placeholder="john@example.com" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Subject</label>
                            <select name="subject" class="form-select">
                                <option>General Inquiry</option>
                                <option>Sales Question</option>
                                <option>Technical Support</option>
                                <option>Partnership</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Message</label>
                            <textarea name="message" class="form-control" rows="5" placeholder="How can we help?" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary btn-lg">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
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
