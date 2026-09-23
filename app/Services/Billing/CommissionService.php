<?php

namespace App\Services\Billing;

use App\Models\Invoice;
use App\Models\Reseller;
use App\Models\ResellerCommission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommissionService
{
    public function calculateCommission(int $invoiceId, int $resellerId): ?ResellerCommission
    {
        return DB::transaction(function () use ($invoiceId, $resellerId) {
            $invoice = Invoice::findOrFail($invoiceId);
            $reseller = Reseller::findOrFail($resellerId);

            if (! $reseller->is_active) {
                Log::info('Skipping commission for inactive reseller', [
                    'reseller_id' => $resellerId,
                ]);

                return null;
            }

            $amount = $this->computeAmount($invoice->total, $reseller);

            $commission = ResellerCommission::create([
                'reseller_id' => $resellerId,
                'agency_id' => $invoice->agency_id,
                'invoice_id' => $invoiceId,
                'amount' => $amount,
                'type' => $this->determineType($invoice),
                'status' => ResellerCommission::STATUS_PENDING,
            ]);

            Log::info('Commission calculated', [
                'commission_id' => $commission->id,
                'reseller_id' => $resellerId,
                'invoice_id' => $invoiceId,
                'amount' => $amount,
            ]);

            return $commission;
        });
    }

    public function processCommission(int $commissionId): bool
    {
        $commission = ResellerCommission::findOrFail($commissionId);

        if ($commission->status !== ResellerCommission::STATUS_PENDING) {
            Log::warning('Commission is not pending', [
                'commission_id' => $commissionId,
                'status' => $commission->status,
            ]);

            return false;
        }

        $commission->update([
            'status' => ResellerCommission::STATUS_PAID,
            'paid_at' => now(),
        ]);

        Log::info('Commission processed', [
            'commission_id' => $commissionId,
            'amount' => $commission->amount,
        ]);

        return true;
    }

    public function getPendingCommissions(int $resellerId): \Illuminate\Database\Eloquent\Collection
    {
        return ResellerCommission::with(['invoice', 'agency'])
            ->where('reseller_id', $resellerId)
            ->pending()
            ->orderByDesc('created_at')
            ->get();
    }

    public function payCommission(int $commissionId): bool
    {
        return $this->processCommission($commissionId);
    }

    public function reverseCommission(int $commissionId): bool
    {
        $commission = ResellerCommission::findOrFail($commissionId);

        if ($commission->status === ResellerCommission::STATUS_REVERSED) {
            return false;
        }

        $commission->update([
            'status' => ResellerCommission::STATUS_REVERSED,
            'paid_at' => null,
        ]);

        Log::info('Commission reversed', [
            'commission_id' => $commissionId,
            'amount' => $commission->amount,
        ]);

        return true;
    }

    public function getCommissionHistory(int $resellerId): \Illuminate\Pagination\LengthAwarePaginator
    {
        return ResellerCommission::with(['invoice', 'agency'])
            ->where('reseller_id', $resellerId)
            ->orderByDesc('created_at')
            ->paginate(25);
    }

    public function getMonthlyCommissions(int $resellerId, string $month): \Illuminate\Database\Eloquent\Collection
    {
        $start = \Carbon\Carbon::parse($month)->startOfMonth();
        $end = \Carbon\Carbon::parse($month)->endOfMonth();

        return ResellerCommission::with(['invoice', 'agency'])
            ->where('reseller_id', $resellerId)
            ->whereBetween('created_at', [$start, $end])
            ->orderByDesc('created_at')
            ->get();
    }

    private function computeAmount(float $invoiceTotal, Reseller $reseller): float
    {
        if ($reseller->commission_type === Reseller::COMMISSION_TYPE_FIXED) {
            return $reseller->commission_rate;
        }

        return round($invoiceTotal * ($reseller->commission_rate / 100), 2);
    }

    private function determineType(Invoice $invoice): string
    {
        $status = $invoice->status ?? '';

        if (in_array($status, ['draft', 'sent'])) {
            return ResellerCommission::TYPE_SIGNUP;
        }

        if ($invoice->paid_date && $invoice->paid_date->gt(now()->subDays(30))) {
            return ResellerCommission::TYPE_RENEWAL;
        }

        return ResellerCommission::TYPE_REVENUE;
    }
}
