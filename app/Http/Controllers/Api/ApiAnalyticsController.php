<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\JsonResponse;

class ApiAnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics)
    {
        $this->middleware(['auth', 'agency']);
    }

    public function crossPlatform(): JsonResponse
    {
        $stats = $this->analytics->getCrossPlatformStats(request()->user()->agency);

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function platform(string $platform): JsonResponse
    {
        $stats = $this->analytics->getPlatformStats(request()->user()->agency, $platform);

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function growth(): JsonResponse
    {
        $days = (int) request()->query('days', 30);
        $stats = $this->analytics->getSocialGrowth(request()->user()->agency, $days);

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function optimalTimes(): JsonResponse
    {
        $stats = $this->analytics->getOptimalPostingTimes(request()->user()->agency);

        return response()->json(['success' => true, 'data' => $stats]);
    }

    public function bestPlatform(): JsonResponse
    {
        $stats = $this->analytics->getBestPerformingPlatform(request()->user()->agency);

        return response()->json(['success' => true, 'data' => $stats]);
    }
}
