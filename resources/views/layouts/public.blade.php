<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'DigitalMarketingSaaS')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root { --primary: #6366f1; --primary-dark: #4f46e5; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .navbar-public { background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .navbar-public .nav-link { color: #1e293b; font-weight: 500; }
        .navbar-public .nav-link:hover { color: var(--primary); }
        .footer-public { background: #0f172a; color: #94a3b8; padding: 2rem 0; }
        .footer-public a { color: #94a3b8; text-decoration: none; }
        .footer-public a:hover { color: white; }
        .pricing-header { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); color: white; padding: 80px 0 120px; text-align: center; }
        .pricing-card { border: 1px solid #e2e8f0; border-radius: 16px; background: white; padding: 2rem; height: 100%; transition: transform 0.2s, box-shadow 0.2s; position: relative; }
        .pricing-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
        .pricing-card.featured { border: 2px solid var(--primary); transform: scale(1.02); }
        .badge-popular { position: absolute; top: -12px; right: 1.5rem; background: var(--primary); color: white; padding: 0.25rem 1rem; border-radius: 20px; font-size: 0.75rem; font-weight: 600; }
        .plan-name { font-weight: 700; font-size: 1.25rem; color: #1e293b; }
        .plan-price { font-size: 3rem; font-weight: 800; color: #1e293b; }
        .plan-price span { font-size: 1rem; font-weight: 500; color: #64748b; }
        .plan-desc { color: #64748b; margin-bottom: 1.5rem; }
        .feature-list { list-style: none; padding: 0; margin: 1.5rem 0; }
        .feature-list li { padding: 0.5rem 0; display: flex; align-items: center; color: #334155; }
        .feature-list li i { margin-right: 0.75rem; }
        .feature-list li .fa-check { color: #10b981; }
        .feature-list li .fa-minus { color: #cbd5e1; }
        .btn-plan { width: 100%; padding: 0.875rem; border-radius: 8px; font-weight: 600; border: none; }
        .btn-plan-primary { background: var(--primary); color: white; }
        .btn-plan-primary:hover { background: var(--primary-dark); color: white; }
        .btn-plan-outline { background: transparent; border: 2px solid var(--primary); color: var(--primary); }
        .btn-plan-outline:hover { background: var(--primary); color: white; }
        .hero-section { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 50%, #ec4899 100%); color: white; padding: 120px 0; }
        .hero-section h1 { font-size: 3.5rem; font-weight: 700; margin-bottom: 1.5rem; }
        .hero-section p.lead { font-size: 1.25rem; opacity: 0.95; }
        .feature-card { border: none; border-radius: 12px; padding: 2rem; height: 100%; transition: transform 0.2s, box-shadow 0.2s; background: #f8fafc; }
        .feature-card:hover { transform: translateY(-4px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
        .feature-icon { width: 56px; height: 56px; border-radius: 12px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; margin-bottom: 1rem; }
        .cta-section { background: linear-gradient(135deg, #1e293b 0%, #334155 100%); color: white; padding: 80px 0; }
        .btn-cta { background: var(--primary); color: white; padding: 0.875rem 2rem; border-radius: 8px; font-weight: 600; border: none; }
        .btn-cta:hover { background: var(--primary-dark); color: white; }
        .btn-cta-outline { background: transparent; color: white; padding: 0.875rem 2rem; border-radius: 8px; font-weight: 600; border: 2px solid rgba(255,255,255,0.3); }
        .btn-cta-outline:hover { border-color: white; color: white; }
        .badge-stat { display: inline-block; background: rgba(255,255,255,0.15); padding: 0.5rem 1rem; border-radius: 8px; margin: 0.25rem; }
    </style>
    @stack('styles')
</head>
<body>
    @yield('content')

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    @stack('scripts')
</body>
</html>
