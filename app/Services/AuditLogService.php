<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Agency;
use App\Models\User;
use Illuminate\Support\Collection;

class AuditLogService
{
    /**
     * Log an action to the audit trail.
     */
    public function log(
        Agency $agency,
        string $action,
        string $description,
        ?User $user = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        array $metadata = []
    ): ActivityLog {
        return ActivityLog::create([
            'agency_id' => $agency->id,
            'user_id' => $user?->id,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'metadata' => $metadata,
            'created_at' => now(),
        ]);
    }

    /**
     * Get audit trail for an agency.
     */
    public function getAuditTrail(int $agencyId, int $limit = 50, int $offset = 0): Collection
    {
        return ActivityLog::where('agency_id', $agencyId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();
    }

    /**
     * Get audit trail for a specific user.
     */
    public function getUserActivity(int $userId, int $limit = 50): Collection
    {
        return ActivityLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get();
    }

    /**
     * Get audit trail for a specific subject.
     */
    public function getSubjectHistory(string $subjectType, int $subjectId): Collection
    {
        return ActivityLog::where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get action statistics for an agency.
     */
    public function getActionStats(int $agencyId, int $days = 30): array
    {
        $startDate = now()->subDays($days);

        $stats = ActivityLog::where('agency_id', $agencyId)
            ->where('created_at', '>=', $startDate)
            ->selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->orderByDesc('count')
            ->get();

        return [
            'total_actions' => $stats->sum('count'),
            'by_action' => $stats->pluck('count', 'action')->toArray(),
            'period_days' => $days,
        ];
    }
}
