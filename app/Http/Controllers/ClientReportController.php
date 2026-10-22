<?php

namespace App\Http\Controllers;

use App\Models\Agency;
use App\Models\Client;
use App\Models\ClientReport;
use App\Services\Analytics\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientReportController extends Controller
{
    public function __construct(
        private readonly AnalyticsService $analytics,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * Generate a new client report
     */
    public function generate(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'title' => 'required|string|max:255',
            'period' => 'required|in:monthly,quarterly,yearly',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        /** @var Agency $agency */
        $agency = $request->user()->agency;

        $client = Client::where('agency_id', $agency->id)
            ->where('id', $validated['client_id'])
            ->firstOrFail();

        $reportData = $this->analytics->generateClientReport(
            $agency,
            $client,
            $validated['start_date'],
            $validated['end_date']
        );

        $report = ClientReport::create([
            'agency_id' => $agency->id,
            'client_id' => $client->id,
            'title' => $validated['title'],
            'report_data' => $reportData,
            'period' => $validated['period'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'draft',
        ]);

        return response()->json([
            'report' => $report,
            'public_url' => $report->public_url,
        ], 201);
    }

    /**
     * Publish report (make it publicly accessible)
     */
    public function publish(Request $request, ClientReport $report): JsonResponse
    {
        $this->authorize('update', $report);

        $report->publish();

        return response()->json([
            'success' => true,
            'public_url' => $report->public_url,
        ]);
    }

    /**
     * Get report preview data
     */
    public function preview(Request $request, ClientReport $report): JsonResponse
    {
        $this->authorize('view', $report);

        return response()->json([
            'report' => $report,
            'data' => $report->report_data,
        ]);
    }

    /**
     * List all reports for agency
     */
    public function index(Request $request): JsonResponse
    {
        /** @var Agency $agency */
        $agency = $request->user()->agency;

        $reports = ClientReport::where('agency_id', $agency->id)
            ->with('client')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($reports);
    }

    /**
     * Get a single report
     */
    public function show(Request $request, ClientReport $report): JsonResponse
    {
        $this->authorize('view', $report);

        return response()->json([
            'report' => $report->load('client'),
            'data' => $report->report_data,
        ]);
    }

    /**
     * Delete a report
     */
    public function destroy(Request $request, ClientReport $report): JsonResponse
    {
        $this->authorize('delete', $report);

        $report->delete();

        return response()->json(['success' => true]);
    }
}
