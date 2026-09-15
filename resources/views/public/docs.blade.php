<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Documentation for DigitalMarketingSaaS">
    <title>Documentation - DigitalMarketingSaaS</title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="{{ asset("build/css/unified.css") }}">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary: #6366f1; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f8fafc; }
        .docs-header {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            color: white;
            padding: 60px 0;
            text-align: center;
        }
        .docs-sidebar {
            position: sticky;
            top: 80px;
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
        }
        .docs-sidebar a {
            display: block;
            padding: 0.5rem 0.75rem;
            color: #334155;
            text-decoration: none;
            border-radius: 6px;
            margin-bottom: 0.25rem;
        }
        .docs-sidebar a:hover, .docs-sidebar a.active {
            background: #eef2ff;
            color: var(--primary);
        }
        .docs-content {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            border: 1px solid #e2e8f0;
        }
        .docs-content h2 { margin-top: 2rem; padding-bottom: 0.5rem; border-bottom: 1px solid #e2e8f0; }
        .docs-content h2:first-child { margin-top: 0; }
        .docs-content code {
            background: #f1f5f9;
            padding: 0.2rem 0.4rem;
            border-radius: 4px;
            font-size: 0.875rem;
        }
        .docs-content pre {
            background: #1e293b;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 8px;
            overflow-x: auto;
        }
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
                    <li class="nav-item"><a class="nav-link active" href="{{ route('public.docs') }}">Docs</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('public.blog') }}">Blog</a></li>
                    <li class="nav-item"><a class="nav-link" href="{{ route('public.contact') }}">Contact</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <section class="docs-header">
        <h1 class="fw-bold">Documentation</h1>
        <p class="lead mb-0">Everything you need to get started and make the most of our platform.</p>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-md-3">
                    <div class="docs-sidebar">
                        <h6 class="fw-bold mb-3">Getting Started</h6>
                        <a href="#quickstart">Quick Start</a>
                        <a href="#installation">Installation</a>
                        <a href="#configuration">Configuration</a>
                        <h6 class="fw-bold mb-3 mt-4">Core Features</h6>
                        <a href="#campaigns">Campaigns</a>
                        <a href="#social">Social Media</a>
                        <a href="#email">Email</a>
                        <a href="#ai">AI Tools</a>
                        <h6 class="fw-bold mb-3 mt-4">API</h6>
                        <a href="#api-auth">Authentication</a>
                        <a href="#api-endpoints">Endpoints</a>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="docs-content">
                        <h2 id="quickstart">Quick Start</h2>
                        <p>Get up and running with DigitalMarketingSaaS in minutes. Follow these steps to start managing your marketing campaigns.</p>
                        <ol>
                            <li>Create your account at <a href="{{ route('register') }}">{{ route('register') }}</a></li>
                            <li>Set up your agency profile and team members</li>
                            <li>Connect your social media accounts</li>
                            <li>Create your first campaign</li>
                        </ol>

                        <h2 id="installation">Installation</h2>
                        <p>If you're self-hosting DigitalMarketingSaaS, follow these installation steps:</p>
                        <pre><code>git clone https://github.com/your-org/digital-marketing-saas.git
cd digital-marketing-saas
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
php artisan db:seed</code></pre>

                        <h2 id="configuration">Configuration</h2>
                        <p>Configure your environment variables in the <code>.env</code> file:</p>
                        <pre><code>APP_NAME="DigitalMarketingSaaS"
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password</code></pre>

                        <h2 id="campaigns">Campaigns</h2>
                        <p>Create and manage marketing campaigns across multiple channels. Each campaign can include social posts, email sequences, and landing pages.</p>

                        <h2 id="social">Social Media</h2>
                        <p>Connect your social accounts and start scheduling posts. Supported platforms include Facebook, Instagram, Twitter, LinkedIn, and TikTok.</p>

                        <h2 id="email">Email</h2>
                        <p>Build email campaigns with our drag-and-drop editor. Set up automation sequences and track performance in real-time.</p>

                        <h2 id="ai">AI Tools</h2>
                        <p>Leverage AI to generate content, optimize campaigns, and get actionable insights. The AI tools learn from your brand voice and audience.</p>

                        <h2 id="api-auth">API Authentication</h2>
                        <p>All API requests require an API key. Include it in the Authorization header:</p>
                        <pre><code>Authorization: Bearer YOUR_API_KEY</code></pre>

                        <h2 id="api-endpoints">API Endpoints</h2>
                        <p>Our RESTful API provides full access to your account data. Base URL:</p>
                        <pre><code>https://api.digitalmarketsaas.com/v1</code></pre>
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
