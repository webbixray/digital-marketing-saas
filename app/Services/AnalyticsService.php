<?php

namespace App\Services;

use App\Models\AnalyticsEvent;
use App\Models\Agency;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    /**
     * Track an analytics event
     */
    public function track(Agency $agency, string $eventType, array $properties = []): AnalyticsEvent
    {
        return AnalyticsEvent::create([
            'agency_id' => $agency->id,
            'user_id' => auth()->id(),
            'event_type' => $eventType,
            'properties' => $properties,
        ]);
    }

    /**
     * Get event counts by type for a date range
     */
    public function getEventCounts(Agency $agency, string $startDate, string $endDate): array
    {
        return AnalyticsEvent::where('agency_id', $agency->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->pluck('count', 'event_type')
            ->toArray();
    }

    /**
     * Get daily event counts for charting
     */
    public function getDailyCounts(Agency $agency, string $eventType, int $days = 30): array
    {
        return AnalyticsEvent::where('agency_id', $agency->id)
            ->where('event_type', $eventType)
            ->where('created_at', '>=', now()->subDays($days))
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();
    }

    /**
     * Get top events by count
     */
    public function getTopEvents(Agency $agency, int $limit = 10): array
    {
        return AnalyticsEvent::where('agency_id', $agency->id)
            ->select('event_type', DB::raw('count(*) as count'))
            ->groupBy('event_type')
            ->orderByDesc('count')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get summary stats for dashboard
     */
    public function getDashboardStats(Agency $agency): array
    {
        $now = now();
        $thisMonth = $now->copy()->startOfMonth();
        $lastMonth = $now->copy()->subMonth()->startOfMonth();

        $eventsThisMonth = AnalyticsEvent::where('agency_id', $agency->id)
            ->where('created_at', '>=', $thisMonth)
            ->count();

        $eventsLastMonth = AnalyticsEvent::where('agency_id', $agency->id)
            ->whereBetween('created_at', [$lastMonth, $thisMonth])
            ->count();

        $growth = $eventsLastMonth > 0
            ? round((($eventsThisMonth - $eventsLastMonth) / $eventsLastMonth) * 100, 1)
            : 0;

        return [
            'events_this_month' => $eventsThisMonth,
            'events_last_month' => $eventsLastMonth,
            'growth_percent' => $growth,
            'top_events' => $this->getTopEvents($agency, 5),
            'daily_posts' => $this->getDailyCounts($agency, 'post_published', 7),
        ];
    }
}
