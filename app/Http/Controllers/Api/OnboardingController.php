<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\OnboardingEngine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OnboardingController extends Controller
{
    public function __construct(
        private OnboardingEngine $engine
    ) {}

    /**
     * Get onboarding progress for the authenticated agency
     */
    public function index(): JsonResponse
    {
        $agency = auth()->user()->agency;

        $this->engine->autoDetectProgress($agency);

        return response()->json([
            'progress' => $this->engine->getAllProgress($agency),
            'percentage' => $this->engine->getCompletionPercentage($agency),
            'next_step' => $this->engine->getNextStep($agency),
            'is_complete' => $this->engine->getNextStep($agency) === null,
        ]);
    }

    /**
     * Mark a step as completed
     */
    public function complete(Request $request, string $step): JsonResponse
    {
        $request->validate([
            'data' => 'sometimes|array',
        ]);

        $agency = auth()->user()->agency;

        if (!array_key_exists($step, OnboardingEngine::STEPS)) {
            return response()->json(['error' => 'Invalid step'], 422);
        }

        $this->engine->completeStep($agency, $step, $request->input('data', []));

        return response()->json([
            'success' => true,
            'progress' => $this->engine->getAllProgress($agency),
            'percentage' => $this->engine->getCompletionPercentage($agency),
            'next_step' => $this->engine->getNextStep($agency),
        ]);
    }

    /**
     * Auto-detect completed steps
     */
    public function autoDetect(): JsonResponse
    {
        $agency = auth()->user()->agency;
        $this->engine->autoDetectProgress($agency);

        return response()->json([
            'success' => true,
            'progress' => $this->engine->getAllProgress($agency),
            'percentage' => $this->engine->getCompletionPercentage($agency),
        ]);
    }
}
