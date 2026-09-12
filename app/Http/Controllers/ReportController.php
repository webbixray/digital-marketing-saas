<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentTask;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $request->validate(['type' => 'nullable|in:social,email,campaign,analytics,custom']);
        $agency = $request->user()->agency;
        $type = $request->input('type');
        $query = Report::where('agency_id', $agency->id)->with('user');
        if ($type) {
            $query->where('type', $type);
        }
        $reports = $query->orderBy('created_at', 'desc')->paginate(20);
        $types = Report::where('agency_id', $agency->id)->select('type')->distinct()->pluck('type');

        return view('reports.index', compact('agency', 'reports', 'types'));
    }

    public function create(Request $request)
    {
        return view('reports.create', ['agency' => $request->user()->agency]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:social,email,campaign,analytics,custom',
            'format' => 'required|in:pdf,csv,xlsx',
            'schedule' => 'required|in:once,daily,weekly,monthly',
        ]);
        $agency = $request->user()->agency;
        Report::create([
            'agency_id' => $agency->id,
            'user_id' => $request->user()->id,
            'name' => $request->name,
            'type' => $request->type,
            'format' => $request->format,
            'schedule' => $request->schedule,
            'filters' => $request->filters,
            'columns' => $request->columns,
            'status' => 'pending',
        ]);

        return redirect()->route('reports.index')->with('success', 'Report created. It will be generated shortly.');
    }

    public function show(Request $request, Report $report)
    {
        if ($report->agency_id !== $request->user()->agency->id) {
            abort(403);
        }

        return view('reports.show', ['agency' => $request->user()->agency, 'report' => $report]);
    }

    public function download(Request $request, Report $report)
    {
        if ($report->agency_id !== $request->user()->agency->id) {
            abort(403);
        }
        if (! $report->file_path) {
            abort(404, 'Report file not found.');
        }

        return response()->download(storage_path('app/public/'.$report->file_path));
    }

    public function destroy(Request $request, Report $report)
    {
        if ($report->agency_id !== $request->user()->agency->id) {
            abort(403);
        }
        $report->delete();

        return redirect()->route('reports.index')->with('success', 'Report deleted.');
    }

    public function generate(Request $request, Report $report)
    {
        if ($report->agency_id !== $request->user()->agency->id) {
            abort(403);
        }
        $report->update(['status' => 'processing']);

        return back()->with('success', 'Report generation started.');
    }

    /**
     * Generate a report using the ReportAgent.
     */
    public function generateWithAgent(Request $request, AgentOrchestrator $orchestrator)
    {
        $validated = $request->validate([
            'report_type' => 'required|in:social,email,campaign,analytics,custom',
            'date_range' => 'nullable|string|max:50',
            'format' => 'nullable|in:pdf,csv,xlsx',
        ]);

        $user = $request->user();
        $agency = $user->agency;

        $context = AgentContext::fromUser($user);

        $task = new AgentTask(
            id: 'report_gen_'.uniqid(),
            type: 'report_generate',
            prompt: 'Generate a '.$validated['report_type'].' report',
            data: [
                'report_type' => $validated['report_type'],
                'date_range' => $validated['date_range'] ?? '30 days',
                'format' => $validated['format'] ?? 'pdf',
            ],
        );

        $result = $orchestrator->dispatch($task, $context);

        if ($result->success) {
            $report = Report::create([
                'agency_id' => $agency->id,
                'user_id' => $user->id,
                'name' => 'AI Generated '.ucfirst($validated['report_type']).' Report',
                'type' => $validated['report_type'],
                'format' => $validated['format'] ?? 'pdf',
                'schedule' => 'once',
                'status' => 'completed',
                'ai_generated' => true,
                'ai_output' => $result->output,
                'ai_cost_usd' => $result->costUsd,
            ]);

            return response()->json([
                'success' => true,
                'report' => $report,
                'output' => $result->output,
                'cost_usd' => $result->costUsd,
                'tokens_used' => $result->tokensUsed,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result->error ?? 'Agent failed to generate report',
        ], 500);
    }

    /**
     * Get AI recommendations for report improvements.
     */
    public function getAgentRecommendations(Request $request, Report $report, AgentOrchestrator $orchestrator)
    {
        if ($report->agency_id !== $request->user()->agency->id) {
            abort(403);
        }

        $validated = $request->validate([
            'goals' => 'nullable|array',
            'goals.*' => 'string|in:engagement,reach,conversion,awareness,retention',
        ]);

        $user = $request->user();
        $context = AgentContext::fromUser($user);

        $task = new AgentTask(
            id: 'report_rec_'.uniqid(),
            type: 'report_recommend',
            prompt: 'Provide recommendations for report improvement',
            data: [
                'report_id' => $report->id,
                'report_type' => $report->type,
                'current_metrics' => $report->metrics ?? [],
                'goals' => $validated['goals'] ?? ['engagement', 'reach', 'conversion'],
            ],
        );

        $result = $orchestrator->dispatch($task, $context);

        if ($result->success) {
            return response()->json([
                'success' => true,
                'recommendations' => $result->output,
                'cost_usd' => $result->costUsd,
                'tokens_used' => $result->tokensUsed,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result->error ?? 'Agent failed to provide recommendations',
        ], 500);
    }

    /**
     * Schedule a recurring agent-generated report.
     */
    public function scheduleAgentReport(Request $request, AgentOrchestrator $orchestrator)
    {
        $validated = $request->validate([
            'report_type' => 'required|in:social,email,campaign,analytics,custom',
            'frequency' => 'required|in:daily,weekly,monthly',
            'recipients' => 'nullable|array',
            'recipients.*' => 'email',
            'format' => 'nullable|in:pdf,csv,xlsx',
        ]);

        $user = $request->user();
        $agency = $user->agency;
        $context = AgentContext::fromUser($user);

        $task = new AgentTask(
            id: 'report_sched_'.uniqid(),
            type: 'report_schedule',
            prompt: 'Create optimal report scheduling plan',
            data: [
                'frequency' => $validated['frequency'],
                'report_type' => $validated['report_type'],
                'recipients' => $validated['recipients'] ?? [],
                'format' => $validated['format'] ?? 'pdf',
            ],
        );

        $result = $orchestrator->dispatch($task, $context);

        if ($result->success) {
            $report = Report::create([
                'agency_id' => $agency->id,
                'user_id' => $user->id,
                'name' => 'Scheduled '.ucfirst($validated['report_type']).' Report ('.$validated['frequency'].')',
                'type' => $validated['report_type'],
                'format' => $validated['format'] ?? 'pdf',
                'schedule' => $validated['frequency'],
                'status' => 'scheduled',
                'ai_generated' => true,
                'ai_schedule_config' => $result->output,
                'ai_cost_usd' => $result->costUsd,
            ]);

            return response()->json([
                'success' => true,
                'report' => $report,
                'schedule_config' => $result->output,
                'cost_usd' => $result->costUsd,
                'tokens_used' => $result->tokensUsed,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result->error ?? 'Agent failed to schedule report',
        ], 500);
    }
}
