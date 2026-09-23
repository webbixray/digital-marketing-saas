<?php

namespace App\Http\Controllers;

use App\Models\ConsentRecord;
use App\Models\DataDeletionRequest;
use App\Models\DataExportRequest;
use App\Services\GDPR\GDPRComplianceService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

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

        app(GDPRComplianceService::class)->auditLog(
            action: 'export_requested',
            category: 'gdpr',
            agencyId: $user->agency_id,
            userId: $user->id,
            subjectType: DataExportRequest::class,
        );

        Log::info('GDPR data export requested', [
            'user_id' => $user->id,
            'agency_id' => $user->agency_id,
            'types' => $request->export_types,
        ]);

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

        app(GDPRComplianceService::class)->auditLog(
            action: 'deletion_requested',
            category: 'gdpr',
            agencyId: $user->agency_id,
            userId: $user->id,
            subjectType: DataDeletionRequest::class,
        );

        Log::warning('GDPR data deletion requested', [
            'user_id' => $user->id,
            'agency_id' => $user->agency_id,
            'reason' => $request->reason,
        ]);

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

        Log::info('GDPR consent updated', [
            'user_id' => $user->id,
            'consent_type' => $request->consent_type,
            'granted' => $request->granted,
        ]);

        return response()->json(['success' => true]);
    }

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

        Log::info('CCPA opt-out updated', [
            'user_id' => $user->id,
            'opt_out' => $request->opt_out,
        ]);

        return response()->json(['success' => true, 'ccpa_opt_out' => (bool) $request->opt_out]);
    }
}
