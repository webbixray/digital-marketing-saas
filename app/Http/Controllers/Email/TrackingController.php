<?php

namespace App\Http\Controllers\Email;

use App\Http\Controllers\Controller;
use App\Services\Email\TrackingService;
use Illuminate\Http\Request;

class TrackingController extends Controller
{
    public function __construct()
    {
        // Public endpoint — no auth required
    }

    public function open(int $recipient, Request $request, TrackingService $service)
    {
        $hash = $request->query('h', '');
        $service->trackOpen($recipient, $hash);

        // Return 1x1 transparent GIF
        $gif = base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==');

        return response($gif, 200)
            ->header('Content-Type', 'image/gif')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache');
    }

    public function click(int $recipient, Request $request, TrackingService $service)
    {
        $hash = $request->query('h', '');
        $url = $request->query('url', '');
        $redirectUrl = $service->trackClick($recipient, $hash, $url);

        if (! $redirectUrl) {
            abort(403);
        }

        return redirect()->away($redirectUrl);
    }
}
