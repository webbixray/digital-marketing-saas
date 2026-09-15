<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Blog - DigitalMarketingSaaS">
    <title>Blog - DigitalMarketingSaaS</title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="{{ asset("build/css/unified.css") }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary: #6366f1; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; }
        .blog-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        .blog-card {
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
            background: white;
            height: 100%;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .blog-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
        .blog-card-img {
            background: #e2e8f0;
            height: 180px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
        }
        .blog-card-body { padding: 1.5rem; }
        .blog-card-body .category {
            color: var(--primary);
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .blog-card-body h5 { margin: 0.5rem 0; }
        .blog-card-body .meta { color: #64748b; font-size: 0.875rem; }
        .navbar-public { background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .footer-public { background: #0f172a; color: #94a3b8; padding: 2rem 0; }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-public sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="{{ route('public.landing') }}">
                <i class="fas fa-rocket me-2" style="color: var(--primary);"></i>DigitalMarketingSaaS
            </a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="{{ route('public.features') }}">Features</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('public.pricing') }}">Pricing</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('public.docs') }}">Docs</a></li>
                    <li class="nav-item"><a class="nav-link active" href="{{ route('public.blog') }}">Blog</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('public.contact') }}">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="blog-header">
        <h1 class="fw-bold">Blog</h1>
        <p class="lead mb-0">Marketing insights, tips, and product updates.</p>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="blog-card">
                        <div class="blog-card-img"><i class="fas fa-image fa-2x"></i></div>
                        <div class="blog-card-body">
                            <span class="category">Marketing</span>
                            <h5>10 Ways AI is Transforming Digital Marketing in 2025</h5>
                            <p class="text-muted">Discover how artificial intelligence is reshaping the marketing landscape and what it means for your agency.</p>
                            <span class="meta"><i class="far fa-calendar me-1"></i> Sep 5, 2025 &middot; 5 min read</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="blog-card">
                        <div class="blog-card-img"><i class="fas fa-image fa-2x"></i></div>
                        <div class="blog-card-body">
                            <span class="category">Product Update</span>
                            <h5>Introducing Workflow Automation 2.0</h5>
                            <p class="text-muted">Our biggest automation update yet brings visual workflow builder, conditional logic, and 50+ new integrations.</p>
                            <span class="meta"><i class="far fa-calendar me-1"></i> Sep 1, 2025 &middot; 3 min read</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="blog-card">
                        <div class="blog-card-img"><i class="fas fa-image fa-2x"></i></div>
                        <div class="blog-card-body">
                            <span class="category">Tips</span>
                            <h5>How to Build a High-Converting Email Sequence</h5>
                            <p class="text-muted">Learn the proven framework for email sequences that nurture leads and drive conversions.</p>
                            <span class="meta"><i class="far fa-calendar me-1"></i> Aug 28, 2025 &middot; 7 min read</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="blog-card">
                        <div class="blog-card-img"><i class="fas fa-image fa-2x"></i></div>
                        <div class="blog-card-body">
                            <span class="category">Case Study</span>
                            <h5>How GrowthHub Agency Scaled to 200 Clients</h5>
                            <p class="text-muted">A deep dive into how one agency used our platform to 10x their client base in 12 months.</p>
                            <span class="meta"><i class="far fa-calendar me-1"></i> Aug 20, 2025 &middot; 8 min read</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="blog-card">
                        <div class="blog-card-img"><i class="fas fa-image fa-2x"></i></div>
                        <div class="blog-card-body">
                            <span class="category">Marketing</span>
                            <h5>Social Media Trends to Watch in 2025</h5>
                            <p class="text-muted">From short-form video to social commerce, here are the trends shaping social media marketing.</p>
                            <span class="meta"><i class="far fa-calendar me-1"></i> Aug 15, 2025 &middot; 6 min read</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="blog-card">
                        <div class="blog-card-img"><i class="fas fa-image fa-2x"></i></div>
                        <div class="blog-card-body">
                            <span class="category">Guide</span>
                            <h5>The Complete Guide to Marketing Analytics</h5>
                            <p class="text-muted">Everything you need to know about tracking, measuring, and optimizing your marketing performance.</p>
                            <span class="meta"><i class="far fa-calendar me-1"></i> Aug 10, 2025 &middot; 10 min read</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer-public">
        <div class="container text-center">
            <p class="mb-0">&copy; {{ date('Y') }} DigitalMarketingSaaS. All rights reserved.</p>
        </div>
    </footer>

    
</body>
</html>
