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

            // The WhiteLabelServiceProvider composer already resolves and
            // shares the settings (cached) for view rendering; reuse that
            // shared value here instead of querying the DB a second time.
            $settings = view()->shared('whiteLabel');

            if (! $settings instanceof WhiteLabelSetting) {
                try {
                    $settings = WhiteLabelSetting::where('agency_id', $agencyId)
                        ->where('enabled', true)
                        ->first();
                } catch (\Throwable $e) {
                    // DB unavailable: skip CSS injection, keep the response.
                    return $response;
                }
            }

            if ($settings) {
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
