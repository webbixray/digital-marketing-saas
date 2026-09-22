<?php

namespace App\Http\Controllers;

use App\Models\ConsentRecord;
use App\Models\DataDeletionRequest;
use App\Models\DataExportRequest;
use App\Services\GDPR\GDPRComplianceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class GdprController extends Controller
{
    private GDPRComplianceService $gdprService;

    public function __construct(GDPRComplianceService $gdprService)
    {
        $this->middleware(['auth', 'agency']);
        $this->gdprService = $gdprService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $consents = ConsentRecord::where('user_id', $user->id)->get();
        $exportRequests = DataExportRequest::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();
        $deletionRequests = DataDeletionRequest::where('user_id', $user->id)->orderBy('created_at', 'desc')->get();

        return view('gdpr.index', compact('user', 'consents', 'exportRequests', 'deletionRequests'));
    }

    public function requestExport(Request $request)
    {
        $request->validate([
            'export_types' => 'required|array|min:1',
            'export_types.*' => 'in:posts,campaigns,clients,invoices,activity',
        ]);

        $user = $request->user();

        DataExportRequest::create([
            'user_id' => $user->id,
            'export_types' => $request->export_types,
            'status' => 'pending',
        ]);

        // Audit log
        app(GDPRComplianceService::class)->auditLog(
            action: 'export_requested',
            category: 'gdpr',
            agencyId: $user->agency_id,
            userId: $user->id,
            subjectType: DataExportRequest::class,
        );

        return redirect()->route('gdpr.index')->with('success', 'Data export requested. You will be notified when ready.');
    }

    public function requestDeletion(Request $request)
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        DataDeletionRequest::create([
            'user_id' => $user->id,
            'reason' => $request->reason,
            'status' => 'pending',
            'scheduled_at' => now()->addDays(30),
        ]);

        // Audit log
        app(GDPRComplianceService::class)->auditLog(
            action: 'deletion_requested',
            category: 'gdpr',
            agencyId: $user->agency_id,
            userId: $user->id,
            subjectType: DataDeletionRequest::class,
        );

        return redirect()->route('gdpr.index')->with('success', 'Deletion request submitted. Your account will be deleted in 30 days.');
    }

    public function updateConsent(Request $request): JsonResponse
    {
        $request->validate([
            'consent_type' => 'required|in:marketing,analytics,third_party,data_sale',
            'granted' => 'required|boolean',
        ]);

        $user = $request->user();

        if ($request->granted) {
            $this->gdprService->recordConsent($user->agency_id, $user->id, $request->consent_type);
        } else {
            $this->gdprService->withdrawConsent($user->agency_id, $user->id, $request->consent_type);
        }

        return response()->json(['success' => true]);
    }

    /**
     * CCPA: Opt-out of data sale (right to opt-out).
     */
    public function ccpaOptOut(Request $request): JsonResponse
    {
        $request->validate([
            'opt_out' => 'required|boolean',
        ]);

        $user = $request->user();

        if ($request->opt_out) {
            $this->gdprService->ccpaOptOut($user->id);
        } else {
            $this->gdprService->ccpaOptIn($user->id);
        }

        return response()->json(['success' => true, 'ccpa_opt_out' => (bool) $request->opt_out]);
    }
}
