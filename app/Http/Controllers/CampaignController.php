<?php

namespace App\Http\Controllers;

use App\Enums\CampaignStatus;
use App\Models\Campaign;
use App\Models\Client;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CampaignController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $agencyId = $request->user()->agency_id;

        $agency = DB::table('agencies')->where('id', $agencyId)->first();

        $query = Campaign::where('agency_id', $agencyId);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        $campaigns = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('campaigns.index', compact('agency', 'campaigns'));
    }

    public function create(Request $request)
    {
        $agencyId = $request->user()->agency_id;

        $agency = DB::table('agencies')->where('id', $agencyId)->first();
        $clients = Client::where('agency_id', $agencyId)->active()->get();
        $types = Campaign::CAMPAIGN_TYPES;

        return view('campaigns.create', compact('agency', 'clients', 'types'));
    }

    public function store(Request $request)
    {
        $agencyId = $request->user()->agency_id;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:'.implode(',', array_keys(Campaign::CAMPAIGN_TYPES)),
            'description' => 'nullable|string',
            'objective' => 'nullable|string|max:255',
            'target_audience' => 'nullable|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $campaign = Campaign::create([
            'agency_id' => $agencyId,
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.uniqid(),
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'objective' => $validated['objective'] ?? null,
            'target_audience' => $validated['target_audience'] ?? null,
            'client_id' => $validated['client_id'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'status' => CampaignStatus::DRAFT->value,
        ]);

        DB::table('agencies')->where('id', $agencyId)->increment('campaigns_count');

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign created successfully.');
    }

    public function show(Request $request, $campaignId)
    {
        $agencyId = $request->user()->agency_id;

        $agency = DB::table('agencies')->where('id', $agencyId)->first();

        $campaign = Campaign::with('client')->findOrFail($campaignId);

        if ($campaign->agency_id !== $agencyId) {
            abort(403);
        }

        $posts = $campaign->posts()->with('socialAccount')->orderBy('created_at', 'desc')->paginate(10);

        return view('campaigns.show', compact('agency', 'campaign', 'posts'));
    }

    public function edit(Request $request, $campaignId)
    {
        $agencyId = $request->user()->agency_id;

        $agency = DB::table('agencies')->where('id', $agencyId)->first();

        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->agency_id !== $agencyId) {
            abort(403);
        }

        $clients = Client::where('agency_id', $agencyId)->active()->get();
        $types = Campaign::CAMPAIGN_TYPES;

        return view('campaigns.edit', compact('agency', 'campaign', 'clients', 'types'));
    }

    public function update(Request $request, $campaignId)
    {
        $agencyId = $request->user()->agency_id;

        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->agency_id !== $agencyId) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:'.implode(',', array_keys(Campaign::CAMPAIGN_TYPES)),
            'description' => 'nullable|string',
            'objective' => 'nullable|string|max:255',
            'target_audience' => 'nullable|string|max:255',
            'client_id' => 'nullable|exists:clients,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $campaign->update($validated);

        return redirect()->route('campaigns.show', $campaign)
            ->with('success', 'Campaign updated successfully.');
    }

    public function destroy(Request $request, $campaignId)
    {
        $agencyId = $request->user()->agency_id;

        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->agency_id !== $agencyId) {
            abort(403);
        }

        $campaign->delete();

        DB::table('agencies')->where('id', $agencyId)->decrement('campaigns_count');

        return redirect()->route('campaigns.index')
            ->with('success', 'Campaign deleted.');
    }

    public function changeStatus(Request $request, $campaignId)
    {
        $agencyId = $request->user()->agency_id;

        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->agency_id !== $agencyId) {
            abort(403);
        }

        $validated = $request->validate([
            'status' => 'required|in:draft,active,paused,completed,cancelled',
        ]);

        $campaign->update(['status' => $validated['status']]);

        return back()->with('success', 'Campaign status updated.');
    }

    /**
     * Optimize a campaign using the CampaignAgent.
     */
    public function optimizeWithAgent(Request $request, $campaignId, AgentOrchestrator $orchestrator)
    {
        $agencyId = $request->user()->agency_id;

        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->agency_id !== $agencyId) {
            abort(403);
        }

        $validated = $request->validate([
            'goals' => 'nullable|array',
            'goals.*' => 'string|in:engagement,reach,conversion,awareness,retention',
        ]);

        $user = $request->user();
        $context = AgentContext::fromUser($user);

        $task = new AgentTask(
            id: 'campaign_opt_'.uniqid(),
            type: 'campaign_optimize',
            prompt: 'Optimize campaign performance',
            data: [
                'campaign_id' => $campaign->id,
                'goals' => $validated['goals'] ?? ['engagement', 'reach'],
            ],
        );

        $result = $orchestrator->dispatch($task, $context);

        if ($result->success) {
            return response()->json([
                'success' => true,
                'optimization' => $result->output,
                'metadata' => $result->metadata,
                'cost_usd' => $result->costUsd,
                'tokens_used' => $result->tokensUsed,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result->error ?? 'Agent failed to optimize campaign',
        ], 500);
    }

    /**
     * Create an A/B test for a campaign using the AbTestingAgent.
     */
    public function abTestWithAgent(Request $request, $campaignId, AgentOrchestrator $orchestrator)
    {
        $agencyId = $request->user()->agency_id;

        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->agency_id !== $agencyId) {
            abort(403);
        }

        $validated = $request->validate([
            'test_type' => 'required|in:creative,audience,copy,cta,timing,budget',
            'hypothesis' => 'nullable|string|max:500',
            'platform' => 'nullable|string|in:instagram,facebook,twitter,linkedin,tiktok',
        ]);

        $user = $request->user();
        $context = AgentContext::fromUser($user);

        $task = new AgentTask(
            id: 'ab_test_'.uniqid(),
            type: 'ab_test_design',
            prompt: 'Design an A/B test for campaign',
            data: [
                'test_type' => $validated['test_type'],
                'campaign_id' => $campaign->id,
                'hypothesis' => $validated['hypothesis'] ?? '',
                'platform' => $validated['platform'] ?? 'instagram',
            ],
        );

        $result = $orchestrator->dispatch($task, $context);

        if ($result->success) {
            return response()->json([
                'success' => true,
                'test_design' => $result->output,
                'metadata' => $result->metadata,
                'cost_usd' => $result->costUsd,
                'tokens_used' => $result->tokensUsed,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result->error ?? 'Agent failed to design A/B test',
        ], 500);
    }

    /**
     * Get AI insights for campaign performance.
     */
    public function getAgentInsights(Request $request, $campaignId, AgentOrchestrator $orchestrator)
    {
        $agencyId = $request->user()->agency_id;

        $campaign = Campaign::findOrFail($campaignId);

        if ($campaign->agency_id !== $agencyId) {
            abort(403);
        }

        $user = $request->user();
        $context = AgentContext::fromUser($user);

        // Use analytics agent for performance insights
        $task = new AgentTask(
            id: 'campaign_insights_'.uniqid(),
            type: 'performance_analysis',
            prompt: 'Analyze campaign performance and provide insights',
            data: [
                'campaign_id' => $campaign->id,
                'platform' => null,
                'date_range' => '30 days',
            ],
        );

        $result = $orchestrator->dispatch($task, $context);

        if ($result->success) {
            return response()->json([
                'success' => true,
                'insights' => $result->output,
                'metadata' => $result->metadata,
                'cost_usd' => $result->costUsd,
                'tokens_used' => $result->tokensUsed,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result->error ?? 'Agent failed to provide insights',
        ], 500);
    }
}
