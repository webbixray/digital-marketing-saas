<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DigitalMarketingSaaS - AI-Powered Social Media Management</title>
    <meta name="description" content="Automate campaigns, create AI-powered content, manage social media, and track analytics — all from one powerful platform.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; color: #1a1a1a; background: #fff; }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }

        /* Navigation */
        nav { position: fixed; top: 0; left: 0; right: 0; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px); z-index: 100; border-bottom: 1px solid #eee; }
        nav .container { display: flex; align-items: center; justify-content: space-between; height: 72px; }
        .logo { font-size: 20px; font-weight: 700; color: #4f46e5; text-decoration: none; }
        .nav-links { display: flex; gap: 32px; align-items: center; }
        .nav-links a { color: #4b5563; text-decoration: none; font-size: 15px; font-weight: 500; }
        .nav-links a:hover { color: #1a1a1a; }
        .btn { padding: 10px 20px; border-radius: 8px; font-size: 15px; font-weight: 600; text-decoration: none; display: inline-block; transition: all 0.2s; }
        .btn-outline { border: 1px solid #d1d5db; color: #1a1a1a; }
        .btn-outline:hover { background: #f9fafb; }
        .btn-primary { background: #4f46e5; color: #fff; }
        .btn-primary:hover { background: #4338ca; transform: translateY(-1px); }

        /* Hero */
        .hero { padding: 160px 0 100px; text-align: center; background: linear-gradient(135deg, #f8faff 0%, #eef2ff 100%); }
        .hero h1 { font-size: 64px; font-weight: 800; line-height: 1.1; letter-spacing: -0.02em; margin-bottom: 24px; }
        .hero h1 span { color: #4f46e5; }
        .hero p { font-size: 20px; color: #6b7280; max-width: 600px; margin: 0 auto 40px; line-height: 1.6; }
        .hero-buttons { display: flex; gap: 16px; justify-content: center; }
        .hero-buttons .btn { padding: 14px 28px; font-size: 16px; }
        .badges { display: flex; gap: 24px; justify-content: center; margin-top: 32px; }
        .badge { background: #fff; padding: 8px 16px; border-radius: 999px; font-size: 13px; color: #6b7280; border: 1px solid #e5e7eb; }

        /* Features */
        .features { padding: 100px 0; }
        .features h2 { font-size: 40px; font-weight: 700; text-align: center; margin-bottom: 16px; }
        .features .subtitle { text-align: center; color: #6b7280; margin-bottom: 60px; }
        .feature-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 32px; }
        .feature-card { background: #f9fafb; padding: 32px; border-radius: 16px; border: 1px solid #eee; }
        .feature-card h3 { font-size: 18px; font-weight: 600; margin-bottom: 8px; }
        .feature-card p { color: #6b7280; line-height: 1.6; font-size: 14px; }
        .feature-icon { width: 48px; height: 48px; background: #eef2ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 16px; }
        .feature-icon svg { width: 24px; height: 24px; color: #4f46e5; }

        /* Platforms */
        .platforms { padding: 80px 0; background: #f9fafb; }
        .platforms h2 { font-size: 40px; font-weight: 700; text-align: center; margin-bottom: 40px; }
        .platform-grid { display: flex; justify-content: center; gap: 24px; flex-wrap: wrap; }
        .platform-badge { background: #fff; padding: 16px 24px; border-radius: 12px; font-weight: 600; border: 1px solid #e5e7eb; display: flex; align-items: center; gap: 8px; }

        /* Pricing */
        .pricing { padding: 100px 0; }
        .pricing h2 { font-size: 40px; font-weight: 700; text-align: center; margin-bottom: 16px; }
        .pricing .subtitle { text-align: center; color: #6b7280; margin-bottom: 60px; }
        .pricing-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; }
        .pricing-card { background: #fff; padding: 32px; border-radius: 16px; border: 1px solid #e5e7eb; text-align: center; }
        .pricing-card.featured { border-color: #4f46e5; box-shadow: 0 8px 30px rgba(79,70,229,0.1); position: relative; }
        .pricing-card .badge-popular { position: absolute; top: -12px; left: 50%; transform: translateX(-50%); background: #4f46e5; color: #fff; padding: 4px 12px; border-radius: 999px; font-size: 12px; font-weight: 600; }
        .plan-name { font-size: 16px; font-weight: 600; color: #6b7280; }
        .plan-price { font-size: 48px; font-weight: 800; margin: 16px 0; }
        .plan-price span { font-size: 16px; font-weight: 500; color: #6b7280; }
        .plan-desc { color: #6b7280; margin-bottom: 24px; }
        .feature-list { list-style: none; margin-bottom: 32px; }
        .feature-list li { padding: 8px 0; color: #4b5563; font-size: 14px; }
        .feature-list li i { color: #10b981; margin-right: 8px; }
        .feature-list li.disabled { color: #9ca3af; }
        .feature-list li.disabled i { color: #d1d5db; }

        /* CTA */
        .cta { padding: 100px 0; background: #4f46e5; text-align: center; color: #fff; }
        .cta h2 { font-size: 40px; font-weight: 700; margin-bottom: 16px; }
        .cta p { font-size: 18px; opacity: 0.8; margin-bottom: 40px; }
        .cta .btn { background: #fff; color: #4f46e5; padding: 14px 28px; font-size: 16px; }
        .cta .btn:hover { transform: translateY(-2px); }

        /* Footer */
        footer { padding: 40px 0; background: #f9fafb; border-top: 1px solid #eee; }
        footer .container { display: flex; justify-content: space-between; align-items: center; }
        footer p { color: #9ca3af; font-size: 14px; }

        @media (max-width: 768px) {
            .hero h1 { font-size: 36px; }
            .feature-grid { grid-template-columns: 1fr; }
            .pricing-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav>
        <div class="container">
            <a href="/" class="logo">DigitalMarketingSaaS</a>
            <div class="nav-links">
                <a href="/features">Features</a>
                <a href="/pricing">Pricing</a>
                <a href="/docs">Docs</a>
                <a href="/contact">Contact</a>
                <a href="/login" class="btn btn-outline">Log In</a>
                <a href="/register" class="btn btn-primary">Get Started</a>
            </div>
        </div>
    </nav>

    <!-- Hero -->
    <section class="hero">
        <div class="container">
            <h1>AI-Powered Social Media Management for <span>Agencies</span></h1>
            <p>Automate campaigns, create AI-powered content, manage 7+ social platforms, and track analytics — all from one powerful dashboard.</p>
            <div class="hero-buttons">
                <a href="/register" class="btn btn-primary">Start Free Trial</a>
                <a href="/features" class="btn btn-outline">See Features</a>
            </div>
            <div class="badges">
                <span class="badge">No credit card required</span>
                <span class="badge">14-day free trial</span>
                <span class="badge">Cancel anytime</span>
            </div>
        </div>
    </section>

    <!-- Features -->
    <section class="features">
        <div class="container">
            <h2>Everything You Need to Scale</h2>
            <p class="subtitle">Powerful tools that help agencies and businesses grow faster</p>
            <div class="feature-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3>AI Content Generation</h3>
                    <p>Generate compelling copy, blog posts, and social media captions with AI that understands your brand voice.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2m0 2a2 2 0 100 4m0-4a2 2 0 110 4m0 0v10m0-10h10m-10 0a2 2 0 100 4m0-4a2 2 0 110 4m0 0V6"/></svg>
                    </div>
                    <h3>Multi-Platform Publishing</h3>
                    <p>Schedule, publish, and analyze posts across Twitter, Instagram, Facebook, LinkedIn, TikTok, Pinterest, and YouTube.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <h3>Email Campaigns</h3>
                    <p>Design beautiful emails, automate sequences, and track performance with real-time analytics.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    </div>
                    <h3>Advanced Analytics</h3>
                    <p>Get actionable insights with custom dashboards, ROI tracking, and AI-powered recommendations.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </div>
                    <h3>Team Collaboration</h3>
                    <p>Assign roles, manage approvals, and collaborate with your team and clients seamlessly.</p>
                </div>
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </div>
                    <h3>Workflow Automation</h3>
                    <p>Build custom automation workflows that save hours of manual work every week.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Platforms -->
    <section class="platforms">
        <div class="container">
            <h2>One Platform, Unlimited Reach</h2>
            <div class="platform-grid">
                <div class="platform-badge">Twitter / X</div>
                <div class="platform-badge">Instagram</div>
                <div class="platform-badge">Facebook</div>
                <div class="platform-badge">LinkedIn</div>
                <div class="platform-badge">TikTok</div>
                <div class="platform-badge">Pinterest</div>
                <div class="platform-badge">YouTube</div>
            </div>
        </div>
    </section>

    <!-- Pricing -->
    <section class="pricing">
        <div class="container">
            <h2>Simple, Transparent Pricing</h2>
            <p class="subtitle">Choose the plan that fits your needs. All plans include a 14-day free trial.</p>
            <div class="pricing-grid">
                <div class="pricing-card">
                    <div class="plan-name">Free</div>
                    <div class="plan-price">$0<span>/mo</span></div>
                    <p class="plan-desc">Perfect for trying out the platform</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> 1 User</li>
                        <li><i class="fas fa-check"></i> 10 Posts/month</li>
                        <li><i class="fas fa-check"></i> 5 AI Generations</li>
                        <li><i class="fas fa-check"></i> 2 Social Accounts</li>
                        <li class="disabled"><i class="fas fa-minus"></i> Advanced Analytics</li>
                        <li class="disabled"><i class="fas fa-minus"></i> Workflow Automation</li>
                    </ul>
                    <a href="/register" class="btn btn-outline" style="width:100%">Get Started</a>
                </div>
                <div class="pricing-card">
                    <div class="plan-name">Starter</div>
                    <div class="plan-price">$29<span>/mo</span></div>
                    <p class="plan-desc">Great for small businesses</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> 3 Users</li>
                        <li><i class="fas fa-check"></i> 100 Posts/month</li>
                        <li><i class="fas fa-check"></i> 50 AI Generations</li>
                        <li><i class="fas fa-check"></i> 5 Social Accounts</li>
                        <li><i class="fas fa-check"></i> Basic Analytics</li>
                        <li class="disabled"><i class="fas fa-minus"></i> Workflow Automation</li>
                    </ul>
                    <a href="/register" class="btn btn-outline" style="width:100%">Get Started</a>
                </div>
                <div class="pricing-card featured">
                    <span class="badge-popular">Most Popular</span>
                    <div class="plan-name">Pro</div>
                    <div class="plan-price">$79<span>/mo</span></div>
                    <p class="plan-desc">For growing agencies</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> 10 Users</li>
                        <li><i class="fas fa-check"></i> 500 Posts/month</li>
                        <li><i class="fas fa-check"></i> 200 AI Generations</li>
                        <li><i class="fas fa-check"></i> 15 Social Accounts</li>
                        <li><i class="fas fa-check"></i> Advanced Analytics</li>
                        <li><i class="fas fa-check"></i> Workflow Automation</li>
                    </ul>
                    <a href="/register" class="btn btn-primary" style="width:100%">Get Started</a>
                </div>
                <div class="pricing-card">
                    <div class="plan-name">Enterprise</div>
                    <div class="plan-price">$199<span>/mo</span></div>
                    <p class="plan-desc">For large organizations</p>
                    <ul class="feature-list">
                        <li><i class="fas fa-check"></i> Unlimited Users</li>
                        <li><i class="fas fa-check"></i> Unlimited Posts</li>
                        <li><i class="fas fa-check"></i> Unlimited AI</li>
                        <li><i class="fas fa-check"></i> Unlimited Accounts</li>
                        <li><i class="fas fa-check"></i> Custom Reports</li>
                        <li><i class="fas fa-check"></i> Custom Workflows</li>
                    </ul>
                    <a href="/contact" class="btn btn-outline" style="width:100%">Contact Sales</a>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA -->
    <section class="cta">
        <div class="container">
            <h2>Ready to Transform Your Agency?</h2>
            <p>Join thousands of agencies already using AI to grow faster.</p>
            <a href="/register" class="btn">Start Your Free Trial</a>
        </div>
    </section>

    <!-- Footer -->
    <footer>
        <div class="container">
            <p>&copy; {{ date('Y') }} DigitalMarketingSaaS. All rights reserved.</p>
            <p><a href="/privacy" style="color:#9ca3af;text-decoration:none">Privacy</a> · <a href="/terms" style="color:#9ca3af;text-decoration:none">Terms</a></p>
        </div>
    </footer>
</body>
</html>
