<?php

namespace App\Http\Controllers;

use App\Services\ChurnPreventionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CancellationController extends Controller
{
    public function __construct(
        private readonly ChurnPreventionService $churnService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    public function survey(Request $request)
    {
        $agency = $request->user()->agency;
        $offer = $this->churnService->getRetentionOffer($agency);

        return view('cancellation.survey', compact('offer'));
    }

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

        Log::info('Cancellation survey submitted', [
            'agency_id' => $agency->id,
            'reason' => $validated['reason'],
        ]);

        return redirect()->route('cancellation.confirm')
            ->with('reason', $validated['reason']);
    }

    public function confirm()
    {
        return view('cancellation.confirm');
    }
}
