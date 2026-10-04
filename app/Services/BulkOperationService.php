<?php

namespace App\Services;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\Client;
use App\Models\SocialPost;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BulkOperationService
{
    /**
     * Bulk schedule posts.
     */
    public function bulkSchedule(Agency $agency, array $postIds, string $scheduledAt): int
    {
        return SocialPost::where('agency_id', $agency->id)
            ->whereIn('id', $postIds)
            ->where('status', 'draft')
            ->update([
                'status' => 'scheduled',
                'scheduled_at' => $scheduledAt,
            ]);
    }

    /**
     * Bulk delete posts.
     */
    public function bulkDelete(Agency $agency, array $postIds): int
    {
        return SocialPost::where('agency_id', $agency->id)
            ->whereIn('id', $postIds)
            ->delete();
    }

    /**
     * Bulk change campaign status.
     */
    public function bulkCampaignStatus(Agency $agency, array $campaignIds, string $status): int
    {
        return Campaign::where('agency_id', $agency->id)
            ->whereIn('id', $campaignIds)
            ->update(['status' => $status]);
    }

    /**
     * Bulk create clients from array.
     */
    public function bulkCreateClients(Agency $agency, array $clients): int
    {
        $count = 0;
        foreach ($clients as $clientData) {
            try {
                DB::beginTransaction();
                Client::create([
                    'agency_id' => $agency->id,
                    ...$clientData,
                    'status' => 'active',
                ]);
                $agency->increment('clients_count');
                $count++;
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Bulk client create error: {$e->getMessage()}");
            }
        }

        return $count;
    }

    /**
     * Get bulk operation stats.
     */
    public function getStats(Agency $agency): array
    {
        return [
            'total_posts' => SocialPost::where('agency_id', $agency->id)->count(),
            'scheduled_posts' => SocialPost::where('agency_id', $agency->id)->scheduled()->count(),
            'total_campaigns' => Campaign::where('agency_id', $agency->id)->count(),
            'total_clients' => Client::where('agency_id', $agency->id)->count(),
        ];
    }
}
