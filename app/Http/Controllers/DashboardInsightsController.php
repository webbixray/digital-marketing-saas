<?php

namespace App\Http\Controllers;

use App\Services\DashboardInsightsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardInsightsController extends Controller
{
    public function __construct(
        private readonly DashboardInsightsService $insightsService,
    ) {}

    /**
     * Get AI-powered dashboard insights for the current agency
     */
    public function index(Request $request): JsonResponse
    {
        $agency = $request->user()->agency;

        $insights = $this->insightsService->generateInsights($agency);

        return response()->json([
            'insights' => $insights,
            'count' => count($insights),
            'generated_at' => now()->toDateTimeString(),
        ]);
    }
}
