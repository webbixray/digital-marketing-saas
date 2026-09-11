<?php

namespace App\Http\Controllers;

use App\Services\ChurnPreventionService;
use Illuminate\Http\Request;

class CancellationController extends Controller
{
    public function __construct(
        private readonly ChurnPreventionService $churnService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Show cancellation survey.
     */
    public function survey(Request $request)
    {
        $agency = $request->user()->agency;
        $offer = $this->churnService->getRetentionOffer($agency);

        return view('cancellation.survey', compact('offer'));
    }

    /**
     * Process cancellation survey.
     */
    public function submitSurvey(Request $request)
    {
        $validated = $request->validate([
            'reason' => 'required|in:too_expensive,missing_features,not_using,switching,other',
            'feedback' => 'nullable|string|max:1000',
        ]);

        $agency = $request->user()->agency;
        $this->churnService->recordSurveyResponse(
            $agency->id,
            $validated['reason'],
            $validated['feedback'] ?? null
        );

        return redirect()->route('cancellation.confirm')
            ->with('reason', $validated['reason']);
    }

    /**
     * Show cancellation confirmation.
     */
    public function confirm()
    {
        return view('cancellation.confirm');
    }
}
