<?php

namespace App\Http\Controllers;

use App\Models\AbTest;
use App\Models\SocialAccount;
use Illuminate\Http\Request;

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

        $query = AbTest::where('agency_id', $agencyId)
            ->with('socialAccount')
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $tests = $query->paginate(15);

        return view('ab-testing.index', compact('tests', 'status'));
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
     * Show single test.
     */
    public function show(Request $request, AbTest $test)
    {
        if ((int) $test->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }
        $test->load('socialAccount');
        return view('ab-testing.show', compact('test'));
    }

    /**
     * Start a test.
     */
    public function start(Request $request, AbTest $test)
    {
        if ((int) $test->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }
        if ($test->status !== 'draft') {
            return back()->with('error', 'Test can only be started from draft status.');
        }

        $test->update([
            'status' => 'running',
            'started_at' => now(),
        ]);

        return back()->with('success', 'Test started! Results will be tracked automatically.');
    }

    /**
     * Pause a test.
     */
    public function pause(Request $request, AbTest $test)
    {
        if ((int) $test->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }
        if ($test->status !== 'running') {
            return back()->with('error', 'Only running tests can be paused.');
        }

        $test->update(['status' => 'paused']);

        return back()->with('success', 'Test paused.');
    }

    /**
     * Complete a test and determine winner.
     */
    public function complete(Request $request, AbTest $test)
    {
        if ((int) $test->agency_id !== (int) $request->user()->agency_id) {
            abort(403);
        }
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

        return back()->with('success', "Test completed! Winner: " . ucfirst($winner) . " ({$confidence}% confidence)");
    }

    /**
     * Track an event (impression, engagement, click).
     */
    public function trackEvent(Request $request, AbTest $test, string $variant, string $event)
    {
        if (!$test->isRunning()) {
            return response()->json(['error' => 'Test not running'], 400);
        }

        // Record the event
        $test->logs()->create([
            'ab_test_id' => $test->id,
            'variant' => $variant,
            'event' => $event,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // Update counters
        $column = "variant_{$variant}_{$event}s";
        $test->increment($column);

        return response()->json(['success' => true]);
    }
}
