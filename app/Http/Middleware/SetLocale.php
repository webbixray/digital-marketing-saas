<?php

namespace App\Http\Middleware;

use App\Services\Localization\LocaleService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * Sets the application locale based on:
     * 1. Authenticated user's locale
     * 2. Agency locale
     * 3. Session locale
     * 4. Default 'en'
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var LocaleService $localeService */
        $localeService = app(LocaleService::class);
        $locale = $localeService->getLocale();

        app()->setLocale($locale);
        $direction = $localeService->getDirection();

        // Share direction with all views
        view()->share('direction', $direction);
        view()->share('currentLocale', $locale);

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }
}
