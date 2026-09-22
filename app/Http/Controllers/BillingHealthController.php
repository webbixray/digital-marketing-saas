<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Agency;
use App\Models\ClientSubscription;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BillingHealthController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Display the billing health dashboard.
     */
    public function index(Request $request)
    {
        $agency = $request->user()->agency;
        $agencyId = $agency->id;

        $metrics = $this->calculateMetrics($agencyId);
        $forecast = $this->generateForecast($agencyId);
        $overdueInvoices = $this->getOverdueInvoices($agencyId);
        $paymentTimeline = $this->getPaymentTimeline($agencyId);
        $planDistribution = $this->getPlanDistribution($agencyId);

        return view('billing.health.index', compact(
            'agency',
            'metrics',
            'forecast',
            'overdueInvoices',
            'paymentTimeline',
            'planDistribution'
        ));
    }

    /**
     * Return metrics as JSON for AJAX refresh.
     */
    public function metrics(Request $request)
    {
        $agency = $request->user()->agency;
        $metrics = $this->calculateMetrics($agency->id);

        return response()->json([
            'success' => true,
            'data' => $metrics,
        ]);
    }

    /**
     * Return forecast data as JSON.
     */
    public function forecast(Request $request)
    {
        $agency = $request->user()->agency;
        $months = $request->input('months', 6);

        $forecast = $this->generateForecast($agency->id, $months);

        return response()->json([
            'success' => true,
            'data' => $forecast,
        ]);
    }

    /**
     * Calculate all billing health metrics.
     */
    private function calculateMetrics(int $agencyId): array
    {
        $now = Carbon::now();
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();
        $lastMonthStart = $now->copy()->subMonth()->startOfMonth();
        $lastMonthEnd = $now->copy()->subMonth()->endOfMonth();

        // MRR: Monthly Recurring Revenue from paid invoices this month + active subscriptions
        $currentMRR = Invoice::where('agency_id', $agencyId)
            ->where('status', 'paid')
            ->whereBetween('paid_date', [$startOfMonth, $endOfMonth])
            ->sum('total') ?? 0;

        $lastMonthMRR = Invoice::where('agency_id', $agencyId)
            ->where('status', 'paid')
            ->whereBetween('paid_date', [$lastMonthStart, $lastMonthEnd])
            ->sum('total') ?? 0;

        // Also include recurring subscriptions
        $subscriptionMRR = ClientSubscription::where('agency_id', $agencyId)
            ->where('status', 'active')
            ->sum('price') ?? 0;

        $totalMRR = $currentMRR + $subscriptionMRR;

        // MRR Growth
        $previousTotalMRR = $lastMonthMRR + $subscriptionMRR;
        $mrrGrowth = $previousTotalMRR > 0
            ? round((($totalMRR - $previousTotalMRR) / $previousTotalMRR) * 100, 1)
            : 0;

        // Churn Rate: cancelled subscriptions / total active subscriptions at start of month
        $activeAtMonthStart = ClientSubscription::where('agency_id', $agencyId)
            ->where('status', 'active')
            ->where('start_date', '<', $startOfMonth)
            ->count();

        $cancelledThisMonth = ClientSubscription::where('agency_id', $agencyId)
            ->where('status', 'cancelled')
            ->whereBetween('cancelled_at', [$startOfMonth, $endOfMonth])
            ->count();

        $totalSubscriptions = $activeAtMonthStart + $cancelledThisMonth;
        $churnRate = $totalSubscriptions > 0
            ? round(($cancelledThisMonth / $totalSubscriptions) * 100, 2)
            : 0;

        // Overdue Invoices
        $overdueCount = Invoice::where('agency_id', $agencyId)
            ->where('status', 'overdue')
            ->where('due_date', '<', $now)
            ->count();

        $overdueAmount = Invoice::where('agency_id', $agencyId)
            ->where('status', 'overdue')
            ->where('due_date', '<', $now)
            ->sum('total') ?? 0;

        // Total paid this month
        $collectedThisMonth = Invoice::where('agency_id', $agencyId)
            ->where('status', 'paid')
            ->whereBetween('paid_date', [$startOfMonth, $endOfMonth])
            ->sum('total') ?? 0;

        // Outstanding (unpaid + overdue)
        $outstandingAmount = Invoice::where('agency_id', $agencyId)
            ->whereIn('status', ['pending', 'overdue'])
            ->sum('total') ?? 0;

        // Total revenue (all time)
        $totalRevenue = Invoice::where('agency_id', $agencyId)
            ->where('status', 'paid')
            ->sum('total') ?? 0;

        // Average invoice value
        $avgInvoiceValue = Invoice::where('agency_id', $agencyId)
            ->where('status', 'paid')
            ->avg('total') ?? 0;

        // Pending invoices
        $pendingCount = Invoice::where('agency_id', $agencyId)
            ->where('status', 'pending')
            ->count();

        $pendingAmount = Invoice::where('agency_id', $agencyId)
            ->where('status', 'pending')
            ->sum('total') ?? 0;

        return [
            'mrr' => round($totalMRR, 2),
            'mrr_formatted' => '$' . number_format($totalMRR, 2),
            'mrr_growth' => $mrrGrowth,
            'mrr_growth_direction' => $mrrGrowth >= 0 ? 'up' : 'down',
            'churn_rate' => $churnRate,
            'churn_status' => $this->getChurnStatus($churnRate),
            'cancelled_count' => $cancelledThisMonth,
            'overdue_count' => $overdueCount,
            'overdue_amount' => round($overdueAmount, 2),
            'overdue_amount_formatted' => '$' . number_format($overdueAmount, 2),
            'collected_this_month' => round($collectedThisMonth, 2),
            'collected_formatted' => '$' . number_format($collectedThisMonth, 2),
            'outstanding_amount' => round($outstandingAmount, 2),
            'outstanding_formatted' => '$' . number_format($outstandingAmount, 2),
            'total_revenue' => round($totalRevenue, 2),
            'total_revenue_formatted' => '$' . number_format($totalRevenue, 2),
            'avg_invoice_value' => round($avgInvoiceValue, 2),
            'avg_invoice_formatted' => '$' . number_format($avgInvoiceValue, 2),
            'pending_count' => $pendingCount,
            'pending_amount' => round($pendingAmount, 2),
            'pending_formatted' => '$' . number_format($pendingAmount, 2),
        ];
    }

    /**
     * Generate revenue forecast based on historical data.
     */
    private function generateForecast(int $agencyId, int $months = 6): array
    {
        $now = Carbon::now();
        $labels = [];
        $historical = [];
        $projected = [];

        // Get historical revenue for the past 6 months
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = $now->copy()->subMonths($i)->startOfMonth();
            $monthEnd = $now->copy()->subMonths($i)->endOfMonth();
            $labels[] = $monthStart->format('M Y');

            $revenue = Invoice::where('agency_id', $agencyId)
                ->where('status', 'paid')
                ->whereBetween('paid_date', [$monthStart, $monthEnd])
                ->sum('total') ?? 0;

            $historical[] = round($revenue, 2);
            $projected[] = null; // No projection for past months
        }

        // Calculate growth trend for forecast
        $avgGrowth = $this->calculateGrowthRate($historical);
        $lastValue = end($historical) ?: 0;

        // Project future months
        for ($i = 1; $i <= $months; $i++) {
            $labels[] = $now->copy()->addMonths($i)->format('M Y');
            $historical[] = null; // No historical for future

            $projectedValue = max(0, $lastValue * (1 + $avgGrowth));
            $projected[] = round($projectedValue, 2);
            $lastValue = $projectedValue;
        }

        // Calculate ARR projection (Annual Recurring Revenue)
        $recentAvg = $this->calculateRecentAverage($historical);
        $arrProjection = $recentAvg * 12;

        return [
            'labels' => $labels,
            'historical' => $historical,
            'projected' => $projected,
            'growth_rate' => round($avgGrowth * 100, 2),
            'arr_projection' => round($arrProjection, 2),
            'arr_formatted' => '$' . number_format($arrProjection, 2),
        ];
    }

    /**
     * Get overdue invoices for alerts section.
     */
    private function getOverdueInvoices(int $agencyId, int $limit = 10): array
    {
        return Invoice::where('agency_id', $agencyId)
            ->where('status', 'overdue')
            ->where('due_date', '<', Carbon::now())
            ->with('client')
            ->orderBy('due_date', 'asc')
            ->limit($limit)
            ->get()
            ->map(function ($invoice) {
                $daysOverdue = Carbon::parse($invoice->due_date)->diffInDays(Carbon::now());
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'client_name' => $invoice->client?->name ?? 'Unknown',
                    'amount' => $invoice->total,
                    'amount_formatted' => '$' . number_format($invoice->total, 2),
                    'due_date' => Carbon::parse($invoice->due_date)->format('M d, Y'),
                    'days_overdue' => $daysOverdue,
                    'severity' => $daysOverdue > 30 ? 'critical' : ($daysOverdue > 14 ? 'warning' : 'info'),
                ];
            })
            ->toArray();
    }

    /**
     * Get payment timeline (recent payments).
     */
    private function getPaymentTimeline(int $agencyId, int $limit = 15): array
    {
        return Invoice::where('agency_id', $agencyId)
            ->where('status', 'paid')
            ->with('client')
            ->orderBy('paid_date', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($invoice) {
                return [
                    'id' => $invoice->id,
                    'invoice_number' => $invoice->invoice_number,
                    'client_name' => $invoice->client?->name ?? 'Unknown',
                    'amount' => $invoice->total,
                    'amount_formatted' => '$' . number_format($invoice->total, 2),
                    'paid_date' => $invoice->paid_date
                        ? Carbon::parse($invoice->paid_date)->format('M d, Y')
                        : '—',
                    'payment_method' => ucfirst($invoice->payment_method ?? '—'),
                    'days_to_pay' => $invoice->paid_date && $invoice->issue_date
                        ? Carbon::parse($invoice->issue_date)->diffInDays(Carbon::parse($invoice->paid_date))
                        : null,
                ];
            })
            ->toArray();
    }

    /**
     * Get plan distribution for agencies with subscription data.
     */
    private function getPlanDistribution(int $agencyId): array
    {
        $subscriptions = ClientSubscription::where('agency_id', $agencyId)
            ->select('plan_name', DB::raw('count(*) as count'), DB::raw('sum(price) as revenue'))
            ->groupBy('plan_name')
            ->get();

        return $subscriptions->map(function ($sub) {
            return [
                'plan' => $sub->plan_name ?? 'Unknown',
                'count' => $sub->count,
                'revenue' => round($sub->revenue, 2),
                'revenue_formatted' => '$' . number_format($sub->revenue, 2),
            ];
        })->toArray();
    }

    /**
     * Determine churn status color indicator.
     */
    private function getChurnStatus(float $rate): string
    {
        if ($rate <= 2) {
            return 'excellent';
        }
        if ($rate <= 5) {
            return 'healthy';
        }
        if ($rate <= 10) {
            return 'warning';
        }
        return 'critical';
    }

    /**
     * Calculate average growth rate from historical data.
     */
    private function calculateGrowthRate(array $values): float
    {
        $nonNull = array_filter($values, fn($v) => $v !== null && $v > 0);

        if (count($nonNull) < 2) {
            return 0.02; // Default 2% monthly growth if insufficient data
        }

        $values = array_values($nonNull);
        $growthRates = [];

        for ($i = 1; $i < count($values); $i++) {
            if ($values[$i - 1] > 0) {
                $growthRates[] = ($values[$i] - $values[$i - 1]) / $values[$i - 1];
            }
        }

        if (empty($growthRates)) {
            return 0.02;
        }

        // Cap growth rate to reasonable bounds
        $avgGrowth = array_sum($growthRates) / count($growthRates);
        return max(-0.1, min(0.15, $avgGrowth));
    }

    /**
     * Calculate recent average from historical data.
     */
    private function calculateRecentAverage(array $values): float
    {
        $recent = array_slice($values, -3);
        $nonNull = array_filter($recent, fn($v) => $v !== null);

        if (empty($nonNull)) {
            return 0;
        }

        return array_sum($nonNull) / count($nonNull);
    }
}
