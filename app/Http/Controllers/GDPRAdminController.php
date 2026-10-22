<?php

namespace App\Http\Controllers;

use App\Models\GDPRComplianceAudit;
use App\Services\GDPR\GDPRComplianceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GDPRAdminController extends Controller
{
    private GDPRComplianceService $gdprService;

    public function __construct(GDPRComplianceService $gdprService)
    {
        $this->middleware(['auth', 'agency', 'role:owner|admin']);
        $this->gdprService = $gdprService;
    }

    /**
     * Show the GDPR compliance admin dashboard.
     */
    public function dashboard(Request $request): View
    {
        $agencyId = $request->attributes->get('agency_id') ?? $request->user()?->agency_id;

        $overview = $this->gdprService->getComplianceOverview($agencyId);
        $pendingRequests = $this->gdprService->getPendingRequests($agencyId);
        $recentAudits = GDPRComplianceAudit::with(['user', 'agency'])
            ->when($agencyId, fn ($q) => $q->where('agency_id', $agencyId))
            ->orderBy('created_at', 'desc')
            ->limit(25)
            ->get();
        $consentStats = $this->gdprService->getConsentStatistics($agencyId);

        return view('gdpr.admin.dashboard', compact('overview', 'pendingRequests', 'recentAudits', 'consentStats'));
    }

    /**
     * Process a pending data export request.
     */
    public function processExport(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            $filePath = $this->gdprService->processExport($id);

            return redirect()
                ->route('admin.gdpr.dashboard')
                ->with('success', "Export processed successfully. File: {$filePath}");
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.gdpr.dashboard')
                ->with('error', 'Failed to process export: '.$e->getMessage());
        }
    }

    /**
     * Process a pending data deletion request.
     */
    public function processDeletion(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'notes' => 'nullable|string|max|500',
        ]);

        try {
            $this->gdprService->processDeletion($id);

            return redirect()
                ->route('admin.gdpr.dashboard')
                ->with('success', 'Deletion processed successfully.');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.gdpr.dashboard')
                ->with('error', 'Failed to process deletion: '.$e->getMessage());
        }
    }

    /**
     * Show the filterable audit log.
     */
    public function auditLog(Request $request): View
    {
        $agencyId = $request->attributes->get('agency_id') ?? $request->user()?->agency_id;

        $filters = $request->only([
            'action', 'category', 'severity', 'date_from', 'date_to', 'agency_id',
        ]);

        if ($agencyId) {
            $filters['agency_id'] = $agencyId;
        }

        $audits = $this->gdprService->getAuditLogs($filters);

        // For filter dropdowns
        $actions = GDPRComplianceAudit::selectRaw('action, COUNT(*) as count')
            ->groupBy('action')
            ->pluck('count', 'action')
            ->toArray();

        $categories = GDPRComplianceAudit::selectRaw('category, COUNT(*) as count')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();

        $severities = GDPRComplianceAudit::selectRaw('severity, COUNT(*) as count')
            ->groupBy('severity')
            ->pluck('count', 'severity')
            ->toArray();

        return view('gdpr.admin.audit-log', compact('audits', 'actions', 'categories', 'severities', 'filters'));
    }

    /**
     * Run data retention cleanup.
     */
    public function runRetentionCleanup(Request $request): RedirectResponse
    {
        $agencyId = $request->attributes->get('agency_id') ?? $request->user()?->agency_id;

        if (! $agencyId) {
            return redirect()->route('admin.gdpr.dashboard')->with('error', 'Agency required.');
        }

        $result = $this->gdprService->runDataRetentionCleanup($agencyId);

        return redirect()
            ->route('admin.gdpr.dashboard')
            ->with('success', "Retention cleanup complete. {$result['purged_users']} users purged (cutoff: {$result['cutoff']}).");
    }

    /**
     * Run consent expiry sweep.
     */
    public function runConsentExpiry(Request $request): RedirectResponse
    {
        $expiredCount = $this->gdprService->runConsentExpiry();

        return redirect()
            ->route('admin.gdpr.dashboard')
            ->with('success', "Consent expiry sweep complete. {$expiredCount} consents marked expired.");
    }
}
