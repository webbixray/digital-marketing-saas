<?php

namespace App\Http\Controllers;

use App\Services\ReferralService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReferralController extends Controller
{
    public function __construct(
        private readonly ReferralService $referralService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $stats = $this->referralService->getStats($user);

        return view('referral.index', compact('stats'));
    }

    public function track(Request $request, string $code)
    {
        session(['referral_code' => $code]);

        Log::info('Referral link tracked', [
            'code' => $code,
            'ip' => $request->ip(),
        ]);

        return redirect()->route('register');
    }

    public function stats(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = $this->referralService->getStats($user);

        return response()->json($stats);
    }
}
