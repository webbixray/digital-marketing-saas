<?php

namespace App\Services\Analytics\Predictive;

use App\Models\ClientSubscription;
use App\Models\Invoice;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RevenueForecastService
{
    /**
     * Cache TTL in seconds.
     */
    private const CACHE_TTL = 300;

    /**
     * Forecast revenue for the next N months.
     */
    public function forecastRevenue(int $agencyId, int $months = 3): array
    {
        return Cache::remember("revenue:forecast:{$agencyId}:{$months}", self::CACHE_TTL, function () use ($agencyId, $months) {
            $forecast = [];
            $currentMrr = $this->calculateCurrentMRR($agencyId);

            // Get historical growth rates
            $historicalRevenue = $this->getHistoricalMonthlyRevenue($agencyId, 6);
            $growthRate = $this->calculateGrowthRate($historicalRevenue);
            $churnRate = $this->calculateMonthlyChurnRate($agencyId);

            $projectedMrr = $currentMrr;

            for ($i = 1; $i <= $months; $i++) {
                $projectedMonth = now()->addMonths($i);
                $projectedMrr = $projectedMrr * (1 + $growthRate - $churnRate);

                $forecast[] = [
                    'month' => $projectedMonth->format('M Y'),
                    'month_number' => $i,
                    'projected_revenue' => round($projectedMrr, 2),
                    'growth_rate' => round($growthRate * 100, 2),
                    'churn_rate' => round($churnRate * 100, 2),
                    'confidence' => $this->calculateForecastConfidence($i),
                ];
            }

            return [
                'current_mrr' => $currentMrr,
                'forecast' => $forecast,
                'projected_growth' => round((($projectedMrr - $currentMrr) / max($currentMrr, 1)) * 100, 2),
            ];
        });
    }

    /**
     * Forecast Monthly Recurring Revenue.
     */
    public function forecastMRR(int $agencyId): array
    {
        return Cache::remember("revenue:mrr:{$agencyId}", self::CACHE_TTL, function () use ($agencyId) {
            $currentMrr = $this->calculateCurrentMRR($agencyId);
            $historical = $this->getHistoricalMonthlyRevenue($agencyId, 6);
            $growthRate = $this->calculateGrowthRate($historical);

            $forecastMrr = [];
            $projected = $currentMrr;

            for ($i = 1; $i <= 12; $i++) {
                $projected = $projected * (1 + $growthRate);
                $forecastMrr[] = [
                    'month' => now()->addMonths($i)->format('M Y'),
                    'mrr' => round($projected, 2),
                ];
            }

            return [
                'current_mrr' => $currentMrr,
                'next_12_months' => $forecastMrr,
                'average_growth_rate' => round($growthRate * 100, 2),
            ];
        });
    }

    /**
     * Forecast Annual Recurring Revenue.
     */
    public function forecastARR(int $agencyId): array
    {
        return Cache::remember("revenue:arr:{$agencyId}", self::CACHE_TTL, function () use ($agencyId) {
            $currentMrr = $this->calculateCurrentMRR($agencyId);
            $historical = $this->getHistoricalMonthlyRevenue($agencyId, 6);
            $growthRate = $this->calculateGrowthRate($historical);
            $churnRate = $this->calculateMonthlyChurnRate($agencyId);

            $forecastArr = [];
            $projectedMrr = $currentMrr;

            for ($i = 1; $i <= 12; $i++) {
                $projectedMrr = $projectedMrr * (1 + $growthRate - $churnRate);
                $arr = $projectedMrr * 12;

                $forecastArr[] = [
                    'month' => now()->addMonths($i)->format('M Y'),
                    'arr' => round($arr, 2),
                    'mrr' => round($projectedMrr, 2),
                ];
            }

            return [
                'current_arr' => $currentMrr * 12,
                'current_mrr' => $currentMrr,
                'next_12_months' => $forecastArr,
            ];
        });
    }

    /**
     * Get revenue trends for a given period.
     */
    public function getRevenueTrends(int $agencyId, string $period = 'quarterly'): array
    {
        $interval = match ($period) {
            'monthly' => ['days' => 30, 'group' => 'day'],
            'quarterly' => ['days' => 90, 'group' => 'week'],
            'yearly' => ['days' => 365, 'group' => 'month'],
            default => ['days' => 90, 'group' => 'week'],
        };

        $trends = Invoice::where('agency_id', $agencyId)
            ->where('status', 'paid')
            ->where('paid_date', '>=', now()->subDays($interval['days']))
            ->orderBy('paid_date')
            ->get()
            ->groupBy(function ($invoice) use ($interval) {
                return match ($interval['group']) {
                    'day' => Carbon::parse($invoice->paid_date)->format('M d'),
                    'week' => 'W' . Carbon::parse($invoice->paid_date)->format('W'),
                    'month' => Carbon::parse($invoice->paid_date)->format('M Y'),
                };
            })
            ->map(fn ($group) => [
                'period' => $group->first()->paid_date,
                'revenue' => round($group->sum('total'), 2),
                'invoice_count' => $group->count(),
            ])
            ->values()
            ->toArray();

        return [
            'period_type' => $period,
            'trends' => $trends,
            'total_revenue' => array_sum(array_column($trends, 'revenue')),
            'total_invoices' => array_sum(array_column($trends, 'invoice_count')),
        ];
    }

    /**
     * Get distribution of clients across plans.
     */
    public function getPlanDistribution(int $agencyId): array
    {
        return Cache::remember("revenue:plan_dist:{$agencyId}", self::CACHE_TTL, function () use ($agencyId) {
            $distribution = ClientSubscription::where('agency_id', $agencyId)
                ->selectRaw('plan_name, COUNT(*) as count, SUM(price) as total_revenue')
                ->groupBy('plan_name')
                ->get()
                ->map(fn ($item) => [
                    'plan_name' => $item->plan_name,
                    'client_count' => (int) $item->count,
                    'total_revenue' => (float) $item->total_revenue,
                ])
                ->toArray();

            $totalClients = array_sum(array_column($distribution, 'client_count'));
            $totalRevenue = array_sum(array_column($distribution, 'total_revenue'));

            return [
                'distribution' => $distribution,
                'total_clients' => $totalClients,
                'total_revenue' => $totalRevenue,
            ];
        });
    }

    /**
     * Calculate current Monthly Recurring Revenue from active subscriptions.
     */
    private function calculateCurrentMRR(int $agencyId): float
    {
        return (float) ClientSubscription::where('agency_id', $agencyId)
            ->where('status', 'active')
            ->sum('price');
    }

    /**
     * Get historical monthly revenue.
     */
    private function getHistoricalMonthlyRevenue(int $agencyId, int $months): Collection
    {
        $driver = DB::connection()->getDriverName();

        $dateFormat = $driver === 'sqlite'
            ? "strftime('%Y-%m', paid_date)"
            : "DATE_FORMAT(paid_date, '%Y-%m')";

        return Invoice::where('agency_id', $agencyId)
            ->where('status', 'paid')
            ->where('paid_date', '>=', now()->subMonths($months))
            ->selectRaw("{$dateFormat} as month, SUM(total) as revenue")
            ->groupBy('month')
            ->orderBy('month')
            ->pluck('revenue', 'month');
    }

    /**
     * Calculate compound growth rate from historical data.
     */
    private function calculateGrowthRate(Collection $historical): float
    {
        if ($historical->count() < 2) {
            return 0.02; // Default 2% growth
        }

        $values = $historical->values()->toArray();
        $first = (float) ($values[0] ?? 1);
        $last = (float) (end($values) ?? $first);
        $periods = count($values) - 1;

        if ($first <= 0) {
            return 0.02;
        }

        $cagr = pow($last / $first, 1 / $periods) - 1;

        return (float) max(-0.1, min(0.1, $cagr));
    }

    /**
     * Calculate monthly churn rate.
     */
    private function calculateMonthlyChurnRate(int $agencyId): float
    {
        $startOfMonth = now()->startOfMonth()->subMonth();
        $endOfMonth = now()->startOfMonth();

        $activeAtStart = ClientSubscription::where('agency_id', $agencyId)
            ->where('status', 'active')
            ->where('start_date', '<=', $startOfMonth)
            ->count();

        $cancelled = ClientSubscription::where('agency_id', $agencyId)
            ->whereBetween('cancelled_at', [$startOfMonth, $endOfMonth])
            ->count();

        return $activeAtStart > 0 ? round($cancelled / $activeAtStart, 4) : 0;
    }

    /**
     * Calculate confidence level based on forecast distance.
     */
    private function calculateForecastConfidence(int $monthsOut): float
    {
        // Confidence decreases the further out we forecast
        return max(0.5, round(1.0 - ($monthsOut * 0.04), 2));
    }
}
