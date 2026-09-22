<?php

namespace App\Services\GDPR;

use App\Models\Agency;
use App\Models\ConsentRecord;
use App\Models\DataDeletionRequest;
use App\Models\DataExportRequest;
use App\Models\GDPRComplianceAudit;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GDPRComplianceService
{
    /**
     * Export all user data as a JSON file and return the file path.
     */
    public function exportUserData(int $agencyId, int $userId): string
    {
        $user = User::where('agency_id', $agencyId)
            ->where('id', $userId)
            ->firstOrFail();

        $agency = Agency::findOrFail($agencyId);

        $exportData = [
            'exported_at' => now()->toIso8601String(),
            'user' => $this->getUserData($user),
            'agency' => $this->getAgencyData($agency),
            'social_posts' => SocialPost::where('agency_id', $agencyId)
                ->whereHas('socialAccount', function ($query) use ($agencyId) {
                    $query->where('agency_id', $agencyId);
                })
                ->get()
                ->toArray(),
            'consent_history' => ConsentRecord::where('user_id', $userId)->get()->toArray(),
        ];

        $filename = "gdpr-exports/{$agencyId}/{$userId}/export-{$userId}-".time().'.json';
        Storage::disk('local')->put($filename, json_encode($exportData, JSON_PRETTY_PRINT));

        // Update the export request status
        DataExportRequest::where('user_id', $userId)
            ->where('status', 'pending')
            ->update([
                'status' => 'completed',
                'file_path' => $filename,
                'completed_at' => now(),
            ]);

        $this->auditLog(
            action: 'export_completed',
            category: 'gdpr',
            agencyId: $agencyId,
            userId: $userId,
            subjectType: DataExportRequest::class,
            metadata: ['file_path' => $filename]
        );

        return $filename;
    }

    /**
     * Delete all user data (right to erasure).
     */
    public function deleteUserData(int $agencyId, int $userId): void
    {
        $user = User::where('agency_id', $agencyId)
            ->where('id', $userId)
            ->firstOrFail();

        DB::beginTransaction();

        try {
            // Anonymize the user first
            $this->anonymizeUser($user);

            // Delete related data
            ConsentRecord::where('user_id', $userId)->delete();
            DataExportRequest::where('user_id', $userId)->delete();

            // Mark deletion request as completed
            DataDeletionRequest::where('user_id', $userId)
                ->where('status', 'pending')
                ->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                ]);

            // Soft delete the user
            $user->delete();

            DB::commit();

            $this->auditLog(
                action: 'deletion_completed',
                category: 'gdpr',
                agencyId: $agencyId,
                userId: $userId,
                subjectType: DataDeletionRequest::class,
                severity: 'critical'
            );

            Log::info('GDPR: User data deleted', ['user_id' => $userId, 'agency_id' => $agencyId]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('GDPR: Failed to delete user data', [
                'user_id' => $userId,
                'agency_id' => $agencyId,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Record user consent for a specific type.
     */
    public function recordConsent(int $agencyId, int $userId, string $type, ?int $expiryDays = null): ConsentRecord
    {
        User::where('agency_id', $agencyId)
            ->where('id', $userId)
            ->firstOrFail();

        $consent = ConsentRecord::create([
            'user_id' => $userId,
            'consent_type' => $type,
            'granted' => true,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'cli',
            'expires_at' => $expiryDays ? now()->addDays($expiryDays) : null,
        ]);

        $this->auditLog(
            action: 'consent_recorded',
            category: 'gdpr',
            agencyId: $agencyId,
            userId: $userId,
            subjectType: ConsentRecord::class,
            subjectId: $consent->id,
            metadata: ['consent_type' => $type, 'expires_at' => $consent->expires_at?->toIso8601String()]
        );

        return $consent;
    }

    /**
     * Withdraw user consent for a specific type.
     */
    public function withdrawConsent(int $agencyId, int $userId, string $type): void
    {
        User::where('agency_id', $agencyId)
            ->where('id', $userId)
            ->firstOrFail();

        $consent = ConsentRecord::create([
            'user_id' => $userId,
            'consent_type' => $type,
            'granted' => false,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'cli',
            'expires_at' => now(), // immediately expires
        ]);

        $this->auditLog(
            action: 'consent_withdrawn',
            category: 'gdpr',
            agencyId: $agencyId,
            userId: $userId,
            subjectType: ConsentRecord::class,
            subjectId: $consent->id,
            metadata: ['consent_type' => $type]
        );
    }

    /**
     * Get consent history for a user.
     */
    public function getConsentHistory(int $agencyId, int $userId): Collection
    {
        User::where('agency_id', $agencyId)
            ->where('id', $userId)
            ->firstOrFail();

        return ConsentRecord::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get all data requests (export + deletion) for an agency.
     */
    public function getDataRequests(int $agencyId): Collection
    {
        $userIds = User::where('agency_id', $agencyId)->pluck('id');

        $exportRequests = DataExportRequest::whereIn('user_id', $userIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($request) {
                $request->request_type = 'export';

                return $request;
            });

        $deletionRequests = DataDeletionRequest::whereIn('user_id', $userIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($request) {
                $request->request_type = 'deletion';

                return $request;
            });

        return $exportRequests->merge($deletionRequests)->sortByDesc('created_at');
    }

    /**
     * Anonymize user data (replace PII with anonymous values).
     */
    public function anonymizeUser(User $user): void
    {
        $anonymousId = 'anon_'.Str::random(16);

        $user->update([
            'name' => 'Anonymous User',
            'email' => $anonymousId.'@anonymized.local',
            'phone' => null,
            'avatar' => null,
            'title' => null,
            'notes' => null,
            'password' => bcrypt(Str::random(32)),
        ]);
    }

    /**
     * Get user data for export.
     */
    private function getUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'title' => $user->title,
            'phone' => $user->phone,
            'created_at' => $user->created_at?->toIso8601String(),
            'last_active_at' => $user->last_active_at?->toIso8601String(),
        ];
    }

    /**
     * Get agency data for export.
     */
    private function getAgencyData(Agency $agency): array
    {
        return [
            'id' => $agency->id,
            'name' => $agency->name,
            'slug' => $agency->slug,
            'website' => $agency->website,
            'timezone' => $agency->timezone,
            'currency' => $agency->currency,
        ];
    }

    // ==================== NEW v6.0 METHODS ====================

    /**
     * Log a compliance audit entry.
     */
    public function auditLog(
        string $action,
        string $category = 'gdpr',
        ?int $agencyId = null,
        ?int $userId = null,
        ?string $subjectType = null,
        ?int $subjectId = null,
        ?array $metadata = null,
        string $severity = 'info'
    ): GDPRComplianceAudit {
        return GDPRComplianceAudit::create([
            'action' => $action,
            'category' => $category,
            'agency_id' => $agencyId,
            'user_id' => $userId,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'metadata' => $metadata,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'cli',
            'severity' => $severity,
        ]);
    }

    /**
     * Get compliance overview statistics for admin dashboard.
     */
    public function getComplianceOverview(?int $agencyId = null): array
    {
        $query = GDPRComplianceAudit::query();
        $consentQuery = ConsentRecord::query();
        $exportQuery = DataExportRequest::query();
        $deletionQuery = DataDeletionRequest::query();

        if ($agencyId) {
            $userIds = User::where('agency_id', $agencyId)->pluck('id');
            $query->where('agency_id', $agencyId);
            $consentQuery->whereIn('user_id', $userIds);
            $exportQuery->whereIn('user_id', $userIds);
            $deletionQuery->whereIn('user_id', $userIds);
        }

        return [
            'total_audits' => $query->count(),
            'pending_exports' => $exportQuery->where('status', 'pending')->count(),
            'pending_deletions' => $deletionQuery->where('status', 'pending')->count(),
            'completed_exports' => $exportQuery->where('status', 'completed')->count(),
            'completed_deletions' => $deletionQuery->where('status', 'completed')->count(),
            'active_consents' => $consentQuery->where('granted', true)
                ->where(function ($q) {
                    $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
                })->count(),
            'expired_consents' => $consentQuery->where('granted', true)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<=', now())->count(),
            'withdrawn_consents' => $consentQuery->where('granted', false)->count(),
            'ccpa_opt_outs' => User::where('ccpa_opt_out', true)->count(),
            'critical_events' => $query->where('severity', 'critical')->count(),
            'recent_audits' => $query->orderBy('created_at', 'desc')->limit(50)->get(),
        ];
    }

    /**
     * Get all compliance audits, optionally filtered.
     */
    public function getAuditLogs(array $filters = [], int $perPage = 50)
    {
        $query = GDPRComplianceAudit::with(['user', 'agency']);

        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', $filters['category']);
        }

        if (! empty($filters['severity'])) {
            $query->where('severity', $filters['severity']);
        }

        if (! empty($filters['agency_id'])) {
            $query->where('agency_id', $filters['agency_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', Carbon::parse($filters['date_from']));
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', Carbon::parse($filters['date_to']));
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * Process a pending export request.
     */
    public function processExport(int $requestId): string
    {
        $export = DataExportRequest::findOrFail($requestId);

        if ($export->status !== 'pending') {
            throw new \RuntimeException("Export request {$requestId} is not pending (status: {$export->status})");
        }

        $filePath = $this->exportUserData(
            $export->user->agency_id,
            $export->user_id
        );

        $this->auditLog(
            action: 'export_processed',
            category: 'gdpr',
            agencyId: $export->user->agency_id,
            userId: $export->user_id,
            subjectType: DataExportRequest::class,
            subjectId: $export->id,
            metadata: ['admin_processed' => true]
        );

        return $filePath;
    }

    /**
     * Process a pending deletion request.
     */
    public function processDeletion(int $requestId): void
    {
        $deletion = DataDeletionRequest::findOrFail($requestId);

        if ($deletion->status !== 'pending') {
            throw new \RuntimeException("Deletion request {$requestId} is not pending (status: {$deletion->status})");
        }

        $this->deleteUserData(
            $deletion->user->agency_id,
            $deletion->user_id
        );

        $this->auditLog(
            action: 'deletion_processed',
            category: 'gdpr',
            agencyId: $deletion->user->agency_id,
            userId: $deletion->user_id,
            subjectType: DataDeletionRequest::class,
            subjectId: $deletion->id,
            severity: 'critical',
            metadata: ['admin_processed' => true]
        );
    }

    /**
     * Handle CCPA opt-out (right to opt-out of data sale).
     */
    public function ccpaOptOut(int $userId): void
    {
        $user = User::findOrFail($userId);

        $user->update([
            'ccpa_opt_out' => true,
            'ccpa_opt_out_at' => now(),
        ]);

        $this->auditLog(
            action: 'ccpa_opt_out',
            category: 'ccpa',
            agencyId: $user->agency_id,
            userId: $user->id,
            subjectType: User::class,
            subjectId: $user->id,
            metadata: ['opt_out_at' => now()->toIso8601String()]
        );
    }

    /**
     * Handle CCPA opt-in (reverse opt-out).
     */
    public function ccpaOptIn(int $userId): void
    {
        $user = User::findOrFail($userId);

        $user->update([
            'ccpa_opt_out' => false,
            'ccpa_opt_out_at' => null,
        ]);

        $this->auditLog(
            action: 'ccpa_opt_in',
            category: 'ccpa',
            agencyId: $user->agency_id,
            userId: $user->id,
            subjectType: User::class,
            subjectId: $user->id,
            metadata: ['opt_in_at' => now()->toIso8601String()]
        );
    }

    /**
     * Run automated data retention cleanup for expired data.
     */
    public function runDataRetentionCleanup(int $agencyId): array
    {
        $agency = Agency::findOrFail($agencyId);
        $retentionDays = $agency->data_retention_days ?? 365;
        $cutoffDate = now()->subDays($retentionDays);

        // Find users deleted before the cutoff date and fully purge them
        $purgedUsers = User::withTrashed()
            ->where('agency_id', $agencyId)
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '<=', $cutoffDate)
            ->delete(); // Force delete

        $this->auditLog(
            action: 'data_retention_cleaned',
            category: 'gdpr',
            agencyId: $agencyId,
            metadata: [
                'cutoff_date' => $cutoffDate->toIso8601String(),
                'retention_days' => $retentionDays,
            ]
        );

        return ['purged_users' => $purgedUsers, 'cutoff' => $cutoffDate->toIso8601String()];
    }

    /**
     * Run automated consent expiry (mark expired consents as withdrawn).
     */
    public function runConsentExpiry(): int
    {
        $expiredCount = ConsentRecord::where('granted', true)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['granted' => false]);

        if ($expiredCount > 0) {
            $this->auditLog(
                action: 'consent_expired',
                category: 'gdpr',
                metadata: ['expired_count' => $expiredCount, 'expired_at' => now()->toIso8601String()]
            );
        }

        return $expiredCount;
    }

    /**
     * Get consent statistics for dashboard charts.
     */
    public function getConsentStatistics(?int $agencyId = null): array
    {
        $query = ConsentRecord::query();

        if ($agencyId) {
            $userIds = User::where('agency_id', $agencyId)->pluck('id');
            $query->whereIn('user_id', $userIds);
        }

        $byType = $query->selectRaw('consent_type, COUNT(*) as count')
            ->groupBy('consent_type')
            ->pluck('count', 'consent_type')
            ->toArray();

        $byStatus = ConsentRecord::selectRaw('granted, COUNT(*) as count')
            ->groupBy('granted')
            ->pluck('count', 'granted')
            ->toArray();

        $byDay = ConsentRecord::selectRaw('DATE(created_at) as day, COUNT(*) as count')
            ->groupBy('day')
            ->orderBy('day', 'desc')
            ->limit(30)
            ->pluck('count', 'day')
            ->toArray();

        return [
            'by_type' => $byType,
            'by_status' => $byStatus,
            'by_day' => $byDay,
        ];
    }

    /**
     * Get pending requests queue for admin dashboard.
     */
    public function getPendingRequests(?int $agencyId = null): \Illuminate\Database\Eloquent\Collection
    {
        $exportQuery = DataExportRequest::with('user')->where('status', 'pending');
        $deletionQuery = DataDeletionRequest::with('user')->where('status', 'pending');

        if ($agencyId) {
            $userIds = User::where('agency_id', $agencyId)->pluck('id');
            $exportQuery->whereIn('user_id', $userIds);
            $deletionQuery->whereIn('user_id', $userIds);
        }

        $exports = $exportQuery->get()->map(fn ($r) => (object) [
            'id' => $r->id,
            'type' => 'export',
            'status' => $r->status,
            'created_at' => $r->created_at,
            'user_name' => $r->user?->name,
            'user_email' => $r->user?->email,
        ]);

        $deletions = $deletionQuery->get()->map(fn ($r) => (object) [
            'id' => $r->id,
            'type' => 'deletion',
            'status' => $r->status,
            'created_at' => $r->created_at,
            'user_name' => $r->user?->name,
            'user_email' => $r->user?->email,
            'scheduled_at' => $r->scheduled_at,
        ]);

        $combined = $exports->merge($deletions)->sortByDesc('created_at');
        $results = new \Illuminate\Database\Eloquent\Collection();
        foreach ($combined as $item) {
            $results->push(new DataExportRequest((array) $item));
        }
        return $results;
    }
}
