<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class ApiAnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analytics)
    {
        $this->middleware(['auth', 'agency']);
    }

    public function crossPlatform(): JsonResponse
    {
        try {
            $stats = $this->analytics->getCrossPlatformStats(request()->user()->agency);
            return response()->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            Log::error('API analytics crossPlatform failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to fetch cross-platform analytics'], 500);
        }
    }

    public function platform(string $platform): JsonResponse
    {
        try {
            $stats = $this->analytics->getPlatformStats(request()->user()->agency, $platform);
            return response()->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            Log::error('API analytics platform failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to fetch platform analytics'], 500);
        }
    }

    public function growth(): JsonResponse
    {
        try {
            $days = (int) request()->query('days', 30);
            $stats = $this->analytics->getSocialGrowth(request()->user()->agency, $days);
            return response()->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            Log::error('API analytics growth failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to fetch growth analytics'], 500);
        }
    }

    public function optimalTimes(): JsonResponse
    {
        try {
            $stats = $this->analytics->getOptimalPostingTimes(request()->user()->agency);
            return response()->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            Log::error('API analytics optimalTimes failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to fetch optimal posting times'], 500);
        }
    }

    public function bestPlatform(): JsonResponse
    {
        try {
            $stats = $this->analytics->getBestPlatform(request()->user()->agency);
            return response()->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            Log::error('API analytics bestPlatform failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to fetch best platform analytics'], 500);
        }
    }

    /**
     * Get dashboard analytics stats
     */
    public function dashboard(): JsonResponse
    {
        try {
            $stats = $this->analytics->getDashboardStats(request()->user()->agency);
            return response()->json(['success' => true, 'data' => $stats]);
        } catch (\Exception $e) {
            Log::error('API analytics dashboard failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to fetch dashboard analytics'], 500);
        }
    }

    /**
     * Get daily event counts for charting
     */
    public function daily(): JsonResponse
    {
        try {
            $eventType = request('event_type', 'post_published');
            $days = request('days', 30);
            $data = $this->analytics->getDailyCounts(request()->user()->agency, $eventType, $days);
            return response()->json(['success' => true, 'data' => $data]);
        } catch (\Exception $e) {
            Log::error('API analytics daily failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to fetch daily analytics'], 500);
        }
    }

    /**
     * Get top events by count
     */
    public function topEvents(): JsonResponse
    {
        try {
            $limit = request('limit', 10);
            $events = $this->analytics->getTopEvents(request()->user()->agency, $limit);
            return response()->json(['success' => true, 'data' => $events]);
        } catch (\Exception $e) {
            Log::error('API analytics topEvents failed', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'error' => 'Failed to fetch top events'], 500);
        }
    }
}
