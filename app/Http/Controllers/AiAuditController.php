<?php

namespace App\Http\Controllers;

use App\Models\AiAuditLog;
use App\Services\AI\Audit\AiAuditService;
use App\Services\AI\Audit\ExplainabilityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AiAuditController extends Controller
{
    public function __construct(
        private readonly AiAuditService $auditService,
        private readonly ExplainabilityService $explainabilityService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): View
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
        $actions = AiAuditLog::byAgency($agencyId)->distinct()->pluck('action', 'action');

        return view('ai-audit.index', compact('logs', 'actions', 'filters'));
    }

    public function show(Request $request, int $id): View
    {
        $agencyId = $request->user()->agency_id;

        $log = AiAuditLog::byAgency($agencyId)
            ->with(['user', 'agency'])
            ->findOrFail($id);

        return view('ai-audit.show', compact('log'));
    }

    public function flagged(Request $request): View
    {
        $agencyId = $request->user()->agency_id;

        $flaggedContent = $this->auditService->getFlaggedContent($agencyId, 100);

        return view('ai-audit.flagged', compact('flaggedContent'));
    }

    public function report(Request $request): View
    {
        $agencyId = $request->user()->agency_id;
        $period = $request->input('period', '30 days');

        $report = $this->auditService->getComplianceReport($agencyId, $period);

        return view('ai-audit.report', compact('report', 'period'));
    }

    public function export(Request $request): BinaryFileResponse
    {
        $agencyId = $request->user()->agency_id;
        $format = $request->input('format', 'csv');

        if (! in_array($format, ['csv', 'json'])) {
            $format = 'csv';
        }

        $filePath = $this->auditService->exportAuditLog($agencyId, $format);

        $fullPath = Storage::disk('local')->path($filePath);
        $downloadName = "ai-audit-{$agencyId}-" . now()->format('Y-m-d') . ".{$format}";

        return response()->download($fullPath, $downloadName);
    }

    public function explain(Request $request, int $id): View
    {
        $agencyId = $request->user()->agency_id;

        $log = AiAuditLog::byAgency($agencyId)->findOrFail($id);

        $explanation = $this->explainabilityService->explainDecision(
            $log->model_used ?? 'unknown',
            'input-hash:' . $log->input_hash,
            'output-hash:' . $log->output_hash,
        );

        return view('ai-audit.explain', compact('log', 'explanation'));
    }
}
