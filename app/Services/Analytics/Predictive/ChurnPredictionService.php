<?php

namespace App\Services\Analytics\Predictive;

use App\Models\Client;
use App\Models\ClientSubscription;
use App\Models\Invoice;
use App\Models\SocialPost;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ChurnPredictionService
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    /**
     * Predict churn probability for a specific client.
     */
    public function predictChurn(int $clientId, int $agencyId): array
    {
        $client = Client::where('agency_id', $agencyId)->findOrFail($clientId);

        $riskScore = $this->getChurnRiskScore($clientId);
        $riskFactors = $this->getChurnRiskFactors($clientId);
        $riskLevel = $this->classifyRiskLevel($riskScore);

        // Generate action recommendation
        $recommendation = $this->getRecommendation($riskScore, $riskFactors);

        return [
            'client_id' => $clientId,
            'client_name' => $client->name,
            'risk_score' => $riskScore,
            'risk_level' => $riskLevel,
            'risk_factors' => $riskFactors,
            'recommendation' => $recommendation,
            'predicted_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Get the specific risk factors contributing to churn.
     */
    public function getChurnRiskFactors(int $clientId): array
    {
        $client = Client::findOrFail($clientId);
        $factors = [];

        // Factor 1: Low engagement (few posts in last 60 days)
        $recentPosts = SocialPost::where('client_id', $clientId)
            ->where('status', 'published')
            ->where('published_at', '>=', now()->subDays(60))
            ->count();

        if ($recentPosts < 5) {
            $factors[] = [
                'factor' => 'low_engagement',
                'description' => 'Low social media engagement in the last 60 days',
                'severity' => $recentPosts === 0 ? 'high' : 'medium',
                'value' => $recentPosts,
                'threshold' => 5,
            ];
        }

        // Factor 2: Overdue invoices
        $overdueCount = Invoice::where('client_id', $clientId)
            ->where('status', 'overdue')
            ->where('due_date', '<', now())
            ->count();

        if ($overdueCount > 0) {
            $factors[] = [
                'factor' => 'payment_overdue',
                'description' => 'Has overdue invoices',
                'severity' => $overdueCount >= 2 ? 'high' : 'medium',
                'value' => $overdueCount,
                'threshold' => 0,
            ];
        }

        // Factor 3: Subscription status
        $activeSubscription = ClientSubscription::where('client_id', $clientId)
            ->where('status', 'active')
            ->exists();

        if (! $activeSubscription) {
            $factors[] = [
                'factor' => 'no_active_subscription',
                'description' => 'No active subscription',
                'severity' => 'critical',
                'value' => 0,
                'threshold' => 1,
            ];
        }

        // Factor 4: Declining engagement trend
        $recentEngagement = $this->getEngagementTrend($clientId, 30);
        $previousEngagement = $this->getEngagementTrend($clientId, 60, 30);

        if ($previousEngagement > 0 && $recentEngagement < $previousEngagement * 0.5) {
            $factors[] = [
                'factor' => 'declining_engagement',
                'description' => 'Engagement has declined significantly in the last 30 days',
                'severity' => 'high',
                'value' => $recentEngagement,
                'threshold' => $previousEngagement,
            ];
        }

        // Factor 5: No contact in 90+ days
        if ($client->last_contact_at && Carbon::parse($client->last_contact_at)->diffInDays(now()) > 90) {
            $factors[] = [
                'factor' => 'no_recent_contact',
                'description' => 'No contact in the last 90 days',
                'severity' => 'medium',
                'value' => Carbon::parse($client->last_contact_at)->diffInDays(now()),
                'threshold' => 90,
            ];
        }

        return $factors;
    }

    /**
     * Get a numeric churn risk score (0.0 to 1.0).
     */
    public function getChurnRiskScore(int $clientId): float
    {
        return Cache::remember("churn:score:{$clientId}", self::CACHE_TTL, function () use ($clientId) {
            $factors = $this->getChurnRiskFactors($clientId);
            $score = 0.0;

            // Weight each factor
            $weights = [
                'critical' => 0.35,
                'high' => 0.25,
                'medium' => 0.15,
                'low' => 0.05,
            ];

            foreach ($factors as $factor) {
                $severity = $factor['severity'] ?? 'low';
                $score += $weights[$severity] ?? 0;
            }

            return min(1.0, round($score, 4));
        });
    }

    /**
     * Get clients above a churn risk threshold.
     */
    public function getHighRiskClients(int $agencyId, float $threshold = 0.7): Collection
    {
        $clients = Client::where('agency_id', $agencyId)
            ->where('status', 'active')
            ->get();

        return $clients->map(function ($client) {
                $client->churn_risk_score = $this->getChurnRiskScore($client->id);
                $client->risk_level = $this->classifyRiskLevel($client->churn_risk_score);

                return $client;
            })
            ->filter(fn ($client) => $client->churn_risk_score >= $threshold)
            ->sortByDesc('churn_risk_score')
            ->values();
    }

    /**
     * Get churn trend over time.
     */
    public function getChurnTrends(int $agencyId, int $months = 6): array
    {
        $trends = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $startOfMonth = $month->copy()->startOfMonth();
            $endOfMonth = $month->copy()->endOfMonth();

            // Count cancelled subscriptions in this month
            $cancelledCount = ClientSubscription::where('agency_id', $agencyId)
                ->whereBetween('cancelled_at', [$startOfMonth, $endOfMonth])
                ->count();

            // Count active clients at start of month
            $activeClients = Client::where('agency_id', $agencyId)
                ->where('status', 'active')
                ->where('created_at', '<=', $endOfMonth)
                ->count();

            $churnRate = $activeClients > 0 ? round(($cancelledCount / $activeClients) * 100, 2) : 0;

            $trends[] = [
                'month' => $month->format('M Y'),
                'cancelled_count' => $cancelledCount,
                'active_clients' => $activeClients,
                'churn_rate' => $churnRate,
            ];
        }

        return $trends;
    }

    /**
     * Classify risk level based on score.
     */
    private function classifyRiskLevel(float $score): string
    {
        if ($score >= 0.8) {
            return 'critical';
        }
        if ($score >= 0.6) {
            return 'high';
        }
        if ($score >= 0.4) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Get a recommendation based on risk score and factors.
     */
    private function getRecommendation(float $score, array $factors): string
    {
        if ($score >= 0.8) {
            return 'Urgent: Schedule an immediate call and review the account health. Consider a retention offer.';
        }
        if ($score >= 0.6) {
            return 'High priority: Reach out within 48 hours with a check-in email and performance recap.';
        }
        if ($score >= 0.4) {
            return 'Moderate: Include in next week\'s touchpoint email with new feature highlights.';
        }

        return 'Low risk: Continue regular communication cadence.';
    }

    /**
     * Get engagement trend for a client over a period.
     */
    private function getEngagementTrend(int $clientId, int $days, int $offsetDays = 0): float
    {
        $start = now()->subDays($offsetDays + $days);
        $end = now()->subDays($offsetDays);

        return (float) SocialPost::where('client_id', $clientId)
            ->where('status', 'published')
            ->whereBetween('published_at', [$start, $end])
            ->avg('engagement_rate') ?? 0;
    }
}
