<?php

namespace App\Services\GDPR;

use App\Models\Agency;
use App\Models\ConsentRecord;
use App\Models\DataDeletionRequest;
use App\Models\DataExportRequest;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
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
    public function recordConsent(int $agencyId, int $userId, string $type): void
    {
        $user = User::where('agency_id', $agencyId)
            ->where('id', $userId)
            ->firstOrFail();

        ConsentRecord::create([
            'user_id' => $userId,
            'consent_type' => $type,
            'granted' => true,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'cli',
        ]);
    }

    /**
     * Withdraw user consent for a specific type.
     */
    public function withdrawConsent(int $agencyId, int $userId, string $type): void
    {
        $user = User::where('agency_id', $agencyId)
            ->where('id', $userId)
            ->firstOrFail();

        ConsentRecord::create([
            'user_id' => $userId,
            'consent_type' => $type,
            'granted' => false,
            'ip_address' => request()->ip() ?? '127.0.0.1',
            'user_agent' => request()->userAgent() ?? 'cli',
        ]);
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
}
