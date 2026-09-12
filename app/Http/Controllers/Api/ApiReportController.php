<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\Reporting\EnterpriseReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiReportController extends Controller
{
    public function __construct(
        private readonly EnterpriseReportingService $reportingService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $reports = Report::forAgency($agencyId)
            ->orderByDesc('created_at')
            ->paginate(20);

        return response()->json([
            'data' => $reports,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:social,email,campaign,analytics,custom,performance,engagement,conversion',
            'format' => 'sometimes|string|in:pdf,csv,xlsx',
            'filters' => 'sometimes|array',
            'columns' => 'sometimes|array',
        ]);

        $agencyId = $request->user()->agency_id;

        $report = $this->reportingService->generateCustomReport($agencyId, $validated);

        return response()->json([
            'message' => 'Report generation started',
            'report' => $report,
        ], 202);
    }

    public function show(Report $report): JsonResponse
    {
        return response()->json([
            'data' => $report->load(['agency', 'user']),
        ]);
    }

    public function schedule(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:social,email,campaign,analytics,custom',
            'frequency' => 'required|string|in:daily,weekly,monthly',
            'format' => 'sometimes|string|in:pdf,csv,xlsx',
            'filters' => 'sometimes|array',
            'columns' => 'sometimes|array',
        ]);

        $agencyId = $request->user()->agency_id;

        $scheduledReport = $this->reportingService->scheduleReport(
            $agencyId,
            $validated,
            $validated['frequency'],
        );

        return response()->json([
            'message' => 'Report scheduled successfully',
            'scheduled_report' => $scheduledReport,
        ], 201);
    }

    public function scheduled(Request $request): JsonResponse
    {
        $agencyId = $request->user()->agency_id;

        $scheduledReports = $this->reportingService->getScheduledReports($agencyId);

        return response()->json([
            'data' => $scheduledReports,
        ]);
    }

    public function export(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|string|in:csv,xlsx',
            'filters' => 'sometimes|array',
        ]);

        $agencyId = $request->user()->agency_id;

        $exportUrl = $this->reportingService->exportData(
            $agencyId,
            $validated['type'],
            $validated['filters'] ?? [],
        );

        return response()->json([
            'message' => 'Export ready',
            'url' => $exportUrl,
        ]);
    }

    public function types(): JsonResponse
    {
        return response()->json([
            'types' => $this->reportingService->getReportTypes(),
            'metrics' => $this->reportingService->getAvailableMetrics(),
        ]);
    }

    public function destroy(Report $report): JsonResponse
    {
        $report->delete();

        return response()->json([
            'message' => 'Report deleted successfully',
        ]);
    }
}
