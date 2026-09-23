<?php

namespace App\Services\Billing;

use App\Models\Agency;
use App\Models\Reseller;
use App\Models\ResellerCommission;
use App\Models\WhiteLabelDomain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ResellerService
{
    public function createReseller(int $agencyId, array $data): Reseller
    {
        return DB::transaction(function () use ($agencyId, $data) {
            $slug = $data['slug'] ?? Reseller::generateSlug($data['name']);
            $domain = $data['domain'] ?? null;

            $reseller = Reseller::create([
                'agency_id' => $agencyId,
                'name' => $data['name'],
                'slug' => $slug,
                'domain' => $domain,
                'logo_url' => $data['logo_url'] ?? null,
                'primary_color' => $data['primary_color'] ?? null,
                'is_active' => $data['is_active'] ?? true,
                'commission_rate' => $data['commission_rate'] ?? 0,
                'commission_type' => $data['commission_type'] ?? Reseller::COMMISSION_TYPE_PERCENTAGE,
                'billing_type' => $data['billing_type'] ?? Reseller::BILLING_TYPE_REVENUE_SHARE,
                'settings' => $data['settings'] ?? [],
            ]);

            Log::info('Reseller created', [
                'reseller_id' => $reseller->id,
                'agency_id' => $agencyId,
            ]);

            return $reseller;
        });
    }

    public function updateReseller(int $id, array $data): Reseller
    {
        $reseller = Reseller::findOrFail($id);

        $updateData = array_intersect_key($data, array_flip($reseller->getFillable()));
        $reseller->update($updateData);

        Log::info('Reseller updated', ['reseller_id' => $id]);

        return $reseller->fresh();
    }

    public function deleteReseller(int $id): bool
    {
        $reseller = Reseller::findOrFail($id);

        Log::info('Reseller deleted', ['reseller_id' => $id]);

        return $reseller->delete();
    }

    public function getReseller(int $id): ?Reseller
    {
        return Reseller::with(['agency', 'domains'])->find($id);
    }

    public function getResellerAgencies(int $resellerId): \Illuminate\Database\Eloquent\Collection
    {
        $reseller = Reseller::findOrFail($resellerId);

        return Agency::whereIn(
            'id',
            ResellerCommission::where('reseller_id', $resellerId)
                ->whereNotNull('agency_id')
                ->pluck('agency_id')
                ->unique()
        )->get();
    }

    public function getResellerCommissions(int $resellerId): \Illuminate\Pagination\LengthAwarePaginator
    {
        return ResellerCommission::with(['invoice', 'agency'])
            ->where('reseller_id', $resellerId)
            ->orderByDesc('created_at')
            ->paginate(25);
    }

    public function getResellerStats(int $resellerId): array
    {
        $commissions = ResellerCommission::where('reseller_id', $resellerId);

        return [
            'total_commissions' => (clone $commissions)->count(),
            'total_earnings' => (clone $commissions)->where('status', ResellerCommission::STATUS_PAID)->sum('amount'),
            'pending_amount' => (clone $commissions)->where('status', ResellerCommission::STATUS_PENDING)->sum('amount'),
            'this_month_earnings' => (clone $commissions)->paid()->thisMonth()->sum('amount'),
            'agency_count' => (clone $commissions)->whereNotNull('agency_id')->distinct('agency_id')->count('agency_id'),
        ];
    }

    public function getCommissionSummary(int $resellerId): array
    {
        $commissions = ResellerCommission::where('reseller_id', $resellerId);

        return [
            'by_type' => [
                'signup' => (clone $commissions)->where('type', ResellerCommission::TYPE_SIGNUP)->sum('amount'),
                'renewal' => (clone $commissions)->where('type', ResellerCommission::TYPE_RENEWAL)->sum('amount'),
                'revenue' => (clone $commissions)->where('type', ResellerCommission::TYPE_REVENUE)->sum('amount'),
            ],
            'by_status' => [
                'pending' => (clone $commissions)->where('status', ResellerCommission::STATUS_PENDING)->sum('amount'),
                'paid' => (clone $commissions)->where('status', ResellerCommission::STATUS_PAID)->sum('amount'),
                'reversed' => (clone $commissions)->where('status', ResellerCommission::STATUS_REVERSED)->sum('amount'),
            ],
        ];
    }
}
