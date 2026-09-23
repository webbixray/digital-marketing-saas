<?php

namespace App\Services\Billing;

use App\Models\MeteredUsage;
use App\Models\UsageQuota;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MeteredBillingService
{
    /**
     * Metric pricing configuration (could be moved to config).
     */
    protected array $metricPrices = [
        'ai_tokens' => 0.0001,
        'api_calls' => 0.001,
        'storage_gb' => 0.10,
        'bandwidth_gb' => 0.05,
        'compute_minutes' => 0.02,
    ];

    public function getUnitPrice(string $metric): float
    {
        return $this->metricPrices[$metric] ?? 0.001;
    }

    public function recordUsage(int $agencyId, string $metric, float $quantity, ?Carbon $recordedAt = null): MeteredUsage
    {
        $unitPrice = $this->getUnitPrice($metric);
        $totalPrice = round($quantity * $unitPrice, 4);

        return MeteredUsage::create([
            'agency_id' => $agencyId,
            'metric' => $metric,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_price' => $totalPrice,
            'recorded_at' => $recordedAt ?? now(),
        ]);
    }

    public function getUsage(int $agencyId, string $metric, string $period = 'thisMonth'): Collection
    {
        $query = MeteredUsage::byAgency($agencyId)->byMetric($metric);

        if ($period === 'thisMonth') {
            $query->thisMonth();
        }

        return $query->get();
    }

    public function getUsageSummary(int $agencyId, ?Carbon $start = null, ?Carbon $end = null): array
    {
        $start = $start ?? now()->startOfMonth();
        $end = $end ?? now()->endOfMonth();

        $records = MeteredUsage::byAgency($agencyId)
            ->whereBetween('recorded_at', [$start, $end]);

        $byMetric = [];

        foreach ($records->get() as $record) {
            if (!isset($byMetric[$record->metric])) {
                $byMetric[$record->metric] = [
                    'total_quantity' => 0,
                    'total_price' => 0,
                    'unit_price' => $record->unit_price,
                    'record_count' => 0,
                ];
            }

            $byMetric[$record->metric]['total_quantity'] += $record->quantity;
            $byMetric[$record->metric]['total_price'] += $record->total_price;
            $byMetric[$record->metric]['record_count']++;
        }

        return [
            'period_start' => $start->toDateTimeString(),
            'period_end' => $end->toDateTimeString(),
            'total_price' => $records->sum('total_price'),
            'total_records' => $records->count(),
            'by_metric' => $byMetric,
        ];
    }

    public function calculateBill(int $agencyId, string $period = 'thisMonth'): float
    {
        $query = MeteredUsage::byAgency($agencyId);

        if ($period === 'thisMonth') {
            $query->thisMonth();
        }

        return round($query->sum('total_price'), 4);
    }

    public function getBillItems(int $agencyId, ?Carbon $start = null, ?Carbon $end = null): array
    {
        $start = $start ?? now()->startOfMonth();
        $end = $end ?? now()->endOfMonth();

        $items = MeteredUsage::byAgency($agencyId)
            ->whereBetween('recorded_at', [$start, $end])
            ->groupBy('metric')
            ->select('metric', DB::raw('SUM(quantity) as total_quantity'), DB::raw('SUM(total_price) as total_price'), DB::raw('AVG(unit_price) as unit_price'), DB::raw('COUNT(*) as record_count'))
            ->get();

        $billItems = [];
        foreach ($items as $item) {
            $billItems[] = [
                'metric' => $item->metric,
                'quantity' => $item->total_quantity,
                'unit_price' => $item->unit_price,
                'total_price' => round($item->total_price, 4),
                'record_count' => $item->record_count,
            ];
        }

        return $billItems;
    }

    public function resetMonthlyUsage(int $agencyId): void
    {
        // Quotas with period=monthly should reset their 'used' counter
        UsageQuota::byAgency($agencyId)
            ->where('period', 'monthly')
            ->update([
                'used' => 0,
                'reset_at' => now(),
            ]);

        Log::info('Monthly usage quotas reset', [
            'agency_id' => $agencyId,
            'reset_at' => now()->toDateTimeString(),
        ]);
    }

    public function getQuotaStatus(int $agencyId): array
    {
        $quotas = UsageQuota::byAgency($agencyId)->get();

        $status = [];
        foreach ($quotas as $quota) {
            $usagePercent = $quota->limit > 0 ? round(($quota->used / $quota->limit) * 100, 2) : 0;

            $status[] = [
                'metric' => $quota->metric,
                'limit' => $quota->limit,
                'used' => $quota->used,
                'remaining' => max(0, $quota->limit - $quota->used),
                'usage_percent' => $usagePercent,
                'exceeded' => $quota->used > $quota->limit,
                'period' => $quota->period,
                'reset_at' => $quota->reset_at?->toDateTimeString(),
            ];
        }

        return [
            'agency_id' => $agencyId,
            'quotas' => $status,
            'has_exceeded' => $quotas->whereColumn('used', '>', 'limit')->count() > 0,
            'exceeded_count' => $quotas->whereColumn('used', '>', 'limit')->count(),
        ];
    }
}
