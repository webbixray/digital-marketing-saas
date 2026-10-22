<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->title ?? $page->name }}</title>
    <!-- Tailwind CSS -->
    <link rel="stylesheet" href="{{ asset("build/css/unified.css") }}">
    <style>
        body { background: {{ $page->background_color }}; color: {{ $page->text_color }}; min-height: 100vh; display: flex; align-items: center; justify-content: center; }
        .container { max-width: 800px; text-align: center; padding: 2rem; }
        .btn-cta { background: {{ $page->button_color }}; color: {{ $page->button_text_color }}; padding: 1rem 2rem; font-size: 1.25rem; border-radius: 50px; text-decoration: none; display: inline-block; margin-top: 1rem; }
        .btn-cta:hover { opacity: 0.9; color: {{ $page->button_text_color }}; text-decoration: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="display-4">{{ $page->headline }}</h1>
        <div class="lead mt-4">{{ $page->content }}</div>
        @if($page->cta_text && $page->cta_url)
            <a href="{{ route('public.landing-page', ['slug' => $page->slug, 'click' => 1]) }}" class="btn-cta" onclick="window.clickTracked = true;">{{ $page->cta_text }}</a>
        @endif
        <footer class="mt-5 text-muted text-sm">
            <small>&copy; {{ date('Y') }} {{ $page->agency?->name ?? config('app.name') }}</small>
        </footer>
    </div>
</body>
</html>
