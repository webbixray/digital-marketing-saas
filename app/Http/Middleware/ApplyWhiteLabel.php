<?php

namespace App\Http\Middleware;

use App\Models\WhiteLabelSetting;
use App\Services\WhiteLabel\WhiteLabelService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyWhiteLabel
{
    public function __construct(private WhiteLabelService $whiteLabelService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Check if white-label is enabled for this agency
        if (auth()->check() && auth()->user()->agency_id) {
            $agencyId = auth()->user()->agency_id;
            $settings = WhiteLabelSetting::where('agency_id', $agencyId)
                ->where('enabled', true)
                ->first();

            if ($settings) {
                // Share white-label data with all views
                view()->share('whiteLabel', $settings);

                // Inject custom CSS into HTML responses
                if ($settings->custom_css && $response->headers->get('Content-Type') && str_contains($response->headers->get('Content-Type'), 'text/html')) {
                    $content = $response->getContent();
                    if ($content && str_contains($content, '</head>')) {
                        $customStyle = "<style>{$settings->custom_css}</style></head>";
                        $content = str_replace('</head>', $customStyle, $content);
                        $response->setContent($content);
                    }
                }
            }
        }

        return $response;
    }
}
