<?php

namespace App\Http\Controllers;

use App\Services\Analytics\Predictive\ChurnPredictionService;
use App\Services\Analytics\Predictive\OptimalTimeService;
use App\Services\Analytics\Predictive\RevenueForecastService;
use App\Services\Analytics\Predictive\TrendDetectionService;
use Illuminate\Http\Request;

class PredictiveAnalyticsController extends Controller
{
    public function __construct(
        private readonly ChurnPredictionService $churnService,
        private readonly RevenueForecastService $revenueService,
        private readonly OptimalTimeService $optimalTimeService,
        private readonly TrendDetectionService $trendService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Main predictive dashboard.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $agency = $user->agency;

        // Churn risk summary
        $highRiskClients = $this->churnService->getHighRiskClients($agency->id, 0.7);
        $churnTrends = $this->churnService->getChurnTrends($agency->id, 6);

        // Revenue forecast
        $revenueForecast = $this->revenueService->forecastRevenue($agency->id, 3);
        $planDistribution = $this->revenueService->getPlanDistribution($agency->id);

        // Optimal times
        $platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok'];
        $optimalTimes = $this->optimalTimeService->getBestPostingTimes($agency->id);

        // Trends
        $trends = $this->trendService->detectTrends($agency->id, 30);
        $emergingTopics = $this->trendService->getEmergingTopics($agency->id, 10);

        return view('predictive-analytics.dashboard', [
            'agency' => $agency,
            'churn' => [
                'high_risk_count' => $highRiskClients->count(),
                'high_risk_clients' => $highRiskClients,
                'trends' => $churnTrends,
            ],
            'revenue' => [
                'forecast' => $revenueForecast,
                'plan_distribution' => $planDistribution,
            ],
            'optimal_times' => $optimalTimes,
            'trends' => [
                'summary' => $trends,
                'topics' => $emergingTopics,
            ],
        ]);
    }

    /**
     * Churn predictions page.
     */
    public function churn(Request $request)
    {
        $user = $request->user();
        $agency = $user->agency;

        $threshold = (float) $request->get('threshold', 0.5);
        $highRiskClients = $this->churnService->getHighRiskClients($agency->id, $threshold);

        // Get detailed predictions for high-risk clients
        $predictions = $highRiskClients->map(function ($client) {
            return $this->churnService->predictChurn($client->id, $client->agency_id);
        });

        $trends = $this->churnService->getChurnTrends($agency->id);

        return view('predictive-analytics.churn', [
            'agency' => $agency,
            'predictions' => $predictions,
            'trends' => $trends,
            'threshold' => $threshold,
        ]);
    }

    /**
     * Revenue forecast page.
     */
    public function revenue(Request $request)
    {
        $user = $request->user();
        $agency = $user->agency;

        $months = (int) $request->get('months', 3);
        $period = $request->get('period', 'quarterly');

        return view('predictive-analytics.revenue', [
            'agency' => $agency,
            'forecast' => $this->revenueService->forecastRevenue($agency->id, $months),
            'mrr' => $this->revenueService->forecastMRR($agency->id),
            'arr' => $this->revenueService->forecastARR($agency->id),
            'trends' => $this->revenueService->getRevenueTrends($agency->id, $period),
            'plan_distribution' => $this->revenueService->getPlanDistribution($agency->id),
        ]);
    }

    /**
     * Optimal posting times page.
     */
    public function optimalTimes(Request $request)
    {
        $user = $request->user();
        $agency = $user->agency;

        $platform = $request->get('platform', 'facebook');
        $platforms = ['facebook', 'instagram', 'twitter', 'linkedin', 'tiktok', 'pinterest', 'youtube'];

        return view('predictive-analytics.optimal-times', [
            'agency' => $agency,
            'platforms' => $platforms,
            'selected_platform' => $platform,
            'best_times' => $this->optimalTimeService->getBestPostingTimes($agency->id, $platform),
            'heatmap' => $this->optimalTimeService->getEngagementHeatmap($agency->id, $platform),
            'audience_activity' => $this->optimalTimeService->getAudienceActivity($agency->id, $platform),
        ]);
    }

    /**
     * Emerging trends page.
     */
    public function trends(Request $request)
    {
        $user = $request->user();
        $agency = $user->agency;

        $days = (int) $request->get('days', 30);

        return view('predictive-analytics.trends', [
            'agency' => $agency,
            'days' => $days,
            'detected' => $this->trendService->detectTrends($agency->id, $days),
            'topics' => $this->trendService->getEmergingTopics($agency->id, 20),
            'hashtags' => $this->trendService->getHashtagTrends($agency->id),
            'competitor' => $this->trendService->getCompetitorTrends($agency->id),
            'industry' => $this->trendService->getIndustryTrends($agency->id),
        ]);
    }

    /**
     * Run a prediction via API.
     */
    public function predict(Request $request)
    {
        $request->validate([
            'type' => 'required|in:churn,revenue,engagement,optimal_time,trends',
            'target_id' => 'nullable|integer',
            'platform' => 'nullable|string|max:50',
        ]);

        $agencyId = $request->user()->agency_id;
        $type = $request->input('type');

        $result = match ($type) {
            'churn' => $this->churnService->predictChurn(
                (int) $request->input('target_id'),
                $agencyId
            ),
            'revenue' => $this->revenueService->forecastRevenue($agencyId),
            'trends' => $this->trendService->detectTrends($agencyId),
            default => abort(400, 'Unknown prediction type'),
        };

        return response()->json([
            'success' => true,
            'type' => $type,
            'data' => $result,
            'generated_at' => now()->toDateTimeString(),
        ]);
    }
}
