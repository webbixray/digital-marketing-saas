<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiAuditLog;
use App\Services\AI\Audit\AiAuditService;
use App\Services\AI\Audit\ExplainabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiAiAuditController extends Controller
{
    public function __construct(
        private readonly AiAuditService $auditService,
        private readonly ExplainabilityService $explainabilityService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $filters = [
            'action' => $request->input('action'),
            'compliance_status' => $request->input('compliance_status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'flagged' => $request->boolean('flagged'),
            'per_page' => $request->input('per_page', 25),
        ];

        $logs = $this->auditService->getAuditLogs($agencyId, $filters);

        return response()->json([
            'success' => true,
            'data' => $logs->items(),
            'meta' => [
                'current_page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $log = AiAuditLog::byAgency($agencyId)
            ->with(['user', 'agency'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $log,
        ]);
    }

    public function flagged(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $limit = $request->input('limit', 50);

        $flaggedContent = $this->auditService->getFlaggedContent($agencyId, $limit);

        return response()->json([
            'success' => true,
            'data' => $flaggedContent,
            'count' => $flaggedContent->count(),
        ]);
    }

    public function report(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;
        $period = $request->input('period', '30 days');

        $report = $this->auditService->getComplianceReport($agencyId, $period);

        return response()->json([
            'success' => true,
            'data' => $report,
        ]);
    }

    public function explain(Request $request, int $id): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $log = AiAuditLog::byAgency($agencyId)->findOrFail($id);

        $explanation = $this->explainabilityService->explainDecision(
            $log->model_used ?? 'unknown',
            'input-hash:' . $log->input_hash,
            'output-hash:' . $log->output_hash,
        );

        return response()->json([
            'success' => true,
            'data' => [
                'log' => $log,
                'explanation' => $explanation,
            ],
        ]);
    }
}
