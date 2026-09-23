<?php

namespace App\Http\Controllers;

use App\Models\AbTest;
use App\Models\SocialAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class AbTestController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'agency']);
    }

    /**
     * List all A/B tests for the agency.
     */
    public function index(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $status = $request->query('status', 'all');
        $type = $request->query('type', 'all');

        $query = AbTest::where('agency_id', $agencyId)
            ->with('socialAccount')
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($type !== 'all') {
            $query->where('type', $type);
        }

        $tests = $query->paginate(15);

        // Aggregate stats
        $stats = [
            'total' => AbTest::where('agency_id', $agencyId)->count(),
            'draft' => AbTest::where('agency_id', $agencyId)->where('status', 'draft')->count(),
            'running' => AbTest::where('agency_id', $agencyId)->where('status', 'running')->count(),
            'completed' => AbTest::where('agency_id', $agencyId)->where('status', 'completed')->count(),
        ];

        return view('ab-testing.index', compact('tests', 'status', 'type', 'stats'));
    }

    /**
     * Show create form.
     */
    public function create(Request $request)
    {
        $accounts = SocialAccount::where('agency_id', $request->user()->agency_id)
            ->active()
            ->get();

        return view('ab-testing.create', compact('accounts'));
    }

    /**
     * Store new A/B test.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'social_account_id' => 'required|exists:social_accounts,id',
            'type' => 'required|in:content,timing,hashtag,media',
            'platform' => 'required|in:facebook,instagram,twitter,linkedin,tiktok,pinterest',
            'hypothesis' => 'nullable|string|max:1000',
            'variant_a_content' => 'required|string|max:5000',
            'variant_b_content' => 'required|string|max:5000',
            'sample_size' => 'required|integer|min:50|max:10000',
        ]);

        $test = AbTest::create([
            'agency_id' => $request->user()->agency_id,
            'social_account_id' => $validated['social_account_id'],
            'name' => $validated['name'],
            'type' => $validated['type'],
            'platform' => $validated['platform'],
            'hypothesis' => $validated['hypothesis'] ?? null,
            'variant_a_content' => $validated['variant_a_content'],
            'variant_b_content' => $validated['variant_b_content'],
            'sample_size' => $validated['sample_size'],
            'status' => 'draft',
        ]);

        return redirect()->route('ab-testing.show', $test)
            ->with('success', 'A/B test created successfully!');
    }

    /**
     * Show single test with results.
     */
    public function show(Request $request, AbTest $test)
    {
        $this->authorizeAgency($test);
        $test->load('socialAccount');
        
        $analysis = $test->analyze();

        return view('ab-testing.show', compact('test', 'analysis'));
    }

    /**
     * Analyze endpoint for AJAX requests.
     */
    public function analyze(Request $request, AbTest $test): JsonResponse
    {
        $this->authorizeAgency($test);
        
        $analysis = $test->analyze();
        
        return response()->json($analysis);
    }

    /**
     * Start a test.
     */
    public function start(Request $request, AbTest $test)
    {
        $this->authorizeAgency($test);
        
        if ($test->status !== 'draft') {
            return back()->with('error', 'Test can only be started from draft status.');
        }

        $test->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        Log::info('A/B test started', ['test_id' => $test->id, 'agency_id' => $request->user()->agency_id]);

        return back()->with('success', 'Test started! Results will be tracked automatically.');
    }

    /**
     * Pause a test.
     */
    public function pause(Request $request, AbTest $test)
    {
        $this->authorizeAgency($test);
        
        if ($test->status !== 'running') {
            return back()->with('error', 'Only running tests can be paused.');
        }

        $test->update(['status' => 'paused']);

        Log::info('A/B test paused', ['test_id' => $test->id, 'agency_id' => $request->user()->agency_id]);

        return back()->with('success', 'Test paused.');
    }

    /**
     * Complete a test and determine winner.
     */
    public function complete(Request $request, AbTest $test)
    {
        $this->authorizeAgency($test);
        
        if ($test->status !== 'running') {
            return back()->with('error', 'Only running tests can be completed.');
        }

        $winner = $test->determineWinner();
        $confidence = $test->calculateConfidence();

        $test->update([
            'status' => 'completed',
            'winner' => $winner,
            'confidence' => $confidence,
            'ended_at' => now(),
        ]);

        $winnerLabel = $winner === 'inconclusive' ? 'Inconclusive' : ucfirst($winner);
        return back()->with('success', "Test completed! Winner: {$winnerLabel} ({$confidence}% confidence)");
    }

    /**
     * Track an event (impression, engagement, click).
     */
    public function trackEvent(Request $request, AbTest $test, string $variant, string $event)
    {
        if (! $test->isRunning()) {
            return response()->json(['error' => 'Test not running'], 400);
        }

        if (!in_array($variant, ['a', 'b'])) {
            return response()->json(['error' => 'Invalid variant'], 400);
        }

        if (!in_array($event, ['impression', 'engagement', 'click'])) {
            return response()->json(['error' => 'Invalid event type'], 400);
        }

        // Record the event
        $test->logs()->create([
            'ab_test_id' => $test->id,
            'variant' => $variant,
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Update counters - map event to column
        $columnMap = [
            'impression' => 'variant_' . $variant . '_impressions',
            'engagement' => 'variant_' . $variant . '_engagement',
            'click' => 'variant_' . $variant . '_clicks',
        ];
        $column = $columnMap[$event];
        $test->increment($column);

        return response()->json([
            'success' => true,
            'variant' => $variant,
            'event' => $event,
            'total' => $test->fresh()->{$column},
        ]);
    }

    /**
     * Verify the user belongs to the same agency as the test.
     */
    private function authorizeAgency(AbTest $test): void
    {
        if ((int) $test->agency_id !== (int) request()->user()->agency_id) {
            abort(403);
        }
    }
}
