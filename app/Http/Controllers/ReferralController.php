<?php

namespace App\Http\Controllers;

use App\Services\ReferralService;
use Illuminate\Http\Request;

class ReferralController extends Controller
{
    public function __construct(
        private readonly ReferralService $referralService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Display referral dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $stats = $this->referralService->getStats($user);

        return view('referral.index', compact('stats'));
    }

    /**
     * Track referral link visit.
     */
    public function track(Request $request, string $code)
    {
        session(['referral_code' => $code]);

        return redirect()->route('register');
    }
}
