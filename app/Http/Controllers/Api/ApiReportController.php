<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\Reporting\EnterpriseReportingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ApiReportController extends Controller
{
    public function __construct(
        private readonly EnterpriseReportingService $reportingService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $agencyId = $request->user()->agency_id;

            $reports = Report::forAgency($agencyId)
                ->orderByDesc('created_at')
                ->paginate(20);

            return response()->json([
                'data' => $reports,
            ]);
        } catch (\Exception $e) {
            Log::error('API report index failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch reports'], 500);
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('API report store failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to generate report'], 500);
        }
    }

    public function show(Report $report): JsonResponse
    {
        try {
            if ((int) $report->agency_id !== (int) auth()->user()->agency_id) {
                return response()->json(['error' => 'Report not found'], 404);
            }

            return response()->json([
                'data' => $report->load(['agency', 'user']),
            ]);
        } catch (\Exception $e) {
            Log::error('API report show failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch report'], 500);
        }
    }

    public function schedule(Request $request): JsonResponse
    {
        try {
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('API report schedule failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to schedule report'], 500);
        }
    }

    public function scheduled(Request $request): JsonResponse
    {
        try {
            $agencyId = $request->user()->agency_id;

            $scheduledReports = $this->reportingService->getScheduledReports($agencyId);

            return response()->json([
                'data' => $scheduledReports,
            ]);
        } catch (\Exception $e) {
            Log::error('API report scheduled failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch scheduled reports'], 500);
        }
    }

    public function export(Request $request): JsonResponse
    {
        try {
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
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json(['error' => 'Validation failed', 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            Log::error('API report export failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to export data'], 500);
        }
    }

    public function types(): JsonResponse
    {
        try {
            return response()->json([
                'types' => $this->reportingService->getReportTypes(),
                'metrics' => $this->reportingService->getAvailableMetrics(),
            ]);
        } catch (\Exception $e) {
            Log::error('API report types failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to fetch report types'], 500);
        }
    }

    public function destroy(Report $report): JsonResponse
    {
        try {
            $report->delete();

            return response()->json([
                'message' => 'Report deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('API report destroy failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete report'], 500);
        }
    }
}
