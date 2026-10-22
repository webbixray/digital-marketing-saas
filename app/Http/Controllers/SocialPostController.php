<?php

namespace App\Http\Controllers;

use App\Enums\PostStatus;
use App\Models\Campaign;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentTask;
use App\Services\ContentQualityScorer;
use App\Services\FeatureFlagService;
use App\Services\Social\SocialPostService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SocialPostController extends Controller
{
    public function __construct(
        private readonly SocialPostService $postService,
        private readonly ContentQualityScorer $scorer,
        private readonly FeatureFlagService $featureFlag,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $agencyId = $request->user()->agency_id;

        $query = SocialPost::where('agency_id', $agencyId);

        // Filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('platform')) {
            $query->where('platform', $request->platform);
        }
        if ($request->filled('search')) {
            $query->where('content', 'like', '%'.$request->search.'%');
        }

        $posts = $query->with('socialAccount')->orderBy('created_at', 'desc')->paginate(15);

        $platforms = SocialAccount::SUPPORTED_PLATFORMS;
        $statuses = [
            'draft' => 'Draft',
            'scheduled' => 'Scheduled',
            'published' => 'Published',
            'failed' => 'Failed',
        ];

        return view('social.posts.index', compact('agencyId', 'posts', 'platforms', 'statuses'));
    }

    public function create(Request $request)
    {
        $agencyId = $request->user()->agency_id;
        $accounts = SocialAccount::where('agency_id', $agencyId)->active()->get();
        $campaigns = Campaign::where('agency_id', $agencyId)->active()->get();

        return view('social.posts.create', compact('agencyId', 'accounts', 'campaigns'));
    }

    public function store(Request $request)
    {
        $agencyId = $request->user()->agency_id;

        // Check if AI content generation is enabled
        $agency = $request->user()->agency;
        if (! $this->featureFlag->isEnabled($agency, 'ai_content_generation')) {
            return back()->with('error', 'AI content generation is not available on your plan.');
        }

        $validated = $request->validate([
            'social_account_id' => 'required|exists:social_accounts,id',
            'content' => 'required|string|max:5000',
            'media' => 'nullable|array',
            'hashtags' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
            'campaign_id' => 'nullable|exists:campaigns,id',
        ]);

        $account = SocialAccount::findOrFail($validated['social_account_id']);

        if ($account->agency_id !== $agencyId) {
            abort(403);
        }

        // Quality scoring
        $qualityScore = $this->scorer->score(new SocialPost([
            'content' => $validated['content'],
            'platform' => $account->platform,
            'media' => $validated['media'] ?? [],
            'hashtags' => $validated['hashtags'] ?? [],
        ]));

        $post = $this->postService->createPost($agencyId, [
            'social_account_id' => $validated['social_account_id'],
            'platform' => $account->platform,
            'content' => $validated['content'],
            'media' => $validated['media'] ?? null,
            'hashtags' => $validated['hashtags'] ?? null,
            'status' => isset($validated['scheduled_at']) ? PostStatus::SCHEDULED->value : PostStatus::DRAFT->value,
            'scheduled_at' => $validated['scheduled_at'] ?? null,
            'quality_score' => $qualityScore,
        ]);

        // Attach to campaign if specified
        if (! empty($validated['campaign_id'])) {
            $post->campaigns()->attach($validated['campaign_id']);
        }

        DB::table('agencies')->where('id', $agencyId)->increment('posts_count');

        return redirect()->route('social.posts.index')
            ->with('success', 'Post created successfully.');
    }

    public function show(Request $request, $postId)
    {
        $agencyId = $request->user()->agency_id;

        $post = SocialPost::findOrFail($postId);

        if ($post->agency_id !== $agencyId) {
            abort(403);
        }

        $post->load('campaigns', 'socialAccount');

        return view('social.posts.show', compact('agencyId', 'post'));
    }

    public function edit(Request $request, $postId)
    {
        $agencyId = $request->user()->agency_id;

        $post = SocialPost::findOrFail($postId);

        if ($post->agency_id !== $agencyId) {
            abort(403);
        }

        $accounts = SocialAccount::where('agency_id', $agencyId)->active()->get();
        $campaigns = Campaign::where('agency_id', $agencyId)->active()->get();

        return view('social.posts.edit', compact('agencyId', 'post', 'accounts', 'campaigns'));
    }

    public function update(Request $request, $postId)
    {
        $agencyId = $request->user()->agency_id;

        $post = SocialPost::findOrFail($postId);

        if ($post->agency_id !== $agencyId) {
            abort(403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'media' => 'nullable|array',
            'hashtags' => 'nullable|array',
            'scheduled_at' => 'nullable|date|after:now',
        ]);

        $post->update([
            'content' => $validated['content'],
            'media' => $validated['media'] ?? $post->media,
            'hashtags' => $validated['hashtags'] ?? $post->hashtags,
            'scheduled_at' => $validated['scheduled_at'] ?? $post->scheduled_at,
        ]);

        return redirect()->route('social.posts.index')
            ->with('success', 'Post updated successfully.');
    }

    public function destroy(Request $request, $postId)
    {
        $agencyId = $request->user()->agency_id;

        $post = SocialPost::findOrFail($postId);

        if ($post->agency_id !== $agencyId) {
            abort(403);
        }

        $post->delete();
        DB::table('agencies')->where('id', $agencyId)->decrement('posts_count');

        return redirect()->route('social.posts.index')
            ->with('success', 'Post deleted.');
    }

    public function publish(Request $request, $postId)
    {
        $agencyId = $request->user()->agency_id;

        $post = SocialPost::findOrFail($postId);

        if ($post->agency_id !== $agencyId) {
            abort(403);
        }

        $result = $this->postService->publishPost($post);

        if ($result['success']) {
            return redirect()->route('social.posts.index')->with('success', 'Post published successfully!');
        }

        return back()->with('error', 'Failed to publish: '.$result['message']);
    }

    public function retry(Request $request, $postId)
    {
        $agencyId = $request->user()->agency_id;

        $post = SocialPost::findOrFail($postId);

        if ($post->agency_id !== $agencyId) {
            abort(403);
        }

        $result = $this->postService->retryPost($post);

        if ($result['success']) {
            return redirect()->route('social.posts.index')->with('success', 'Post retried successfully!');
        }

        return back()->with('error', $result['message']);
    }

    public function score(Request $request, $postId)
    {
        $agencyId = $request->user()->agency_id;

        $post = SocialPost::findOrFail($postId);

        if ($post->agency_id !== $agencyId) {
            abort(403);
        }

        $score = $this->scorer->score($post);
        $label = $this->scorer->getLabel($score);

        $post->update(['quality_score' => $score]);

        return response()->json([
            'score' => $score,
            'label' => $label,
        ]);
    }

    /**
     * Schedule a post using the SocialMediaAgent for optimal timing.
     */
    public function scheduleWithAgent(Request $request, $postId, AgentOrchestrator $orchestrator)
    {
        $agencyId = $request->user()->agency_id;

        $post = SocialPost::findOrFail($postId);

        if ($post->agency_id !== $agencyId) {
            abort(403);
        }

        $validated = $request->validate([
            'preferred_date' => 'nullable|date_format:Y-m-d',
        ]);

        $user = $request->user();
        $context = AgentContext::fromUser($user);

        $task = new AgentTask(
            id: 'post_schedule_'.uniqid(),
            type: 'post_schedule',
            prompt: 'Determine optimal posting time',
            data: [
                'platform' => $post->platform,
                'content' => $post->content,
                'preferred_date' => $validated['preferred_date'] ?? null,
            ],
        );

        $result = $orchestrator->dispatch($task, $context);

        if ($result->success) {
            // Parse recommended time from agent output
            $recommendedTime = $result->metadata['recommended_time'] ?? null;

            if ($recommendedTime) {
                $post->update([
                    'scheduled_at' => $recommendedTime,
                    'status' => PostStatus::SCHEDULED->value,
                ]);
            }

            return response()->json([
                'success' => true,
                'schedule_recommendation' => $result->output,
                'recommended_time' => $recommendedTime,
                'metadata' => $result->metadata,
                'cost_usd' => $result->costUsd,
                'tokens_used' => $result->tokensUsed,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result->error ?? 'Agent failed to schedule post',
        ], 500);
    }

    /**
     * Analyze post performance using the AnalyticsAgent.
     */
    public function analyzeWithAgent(Request $request, $postId, AgentOrchestrator $orchestrator)
    {
        $agencyId = $request->user()->agency_id;

        $post = SocialPost::findOrFail($postId);

        if ($post->agency_id !== $agencyId) {
            abort(403);
        }

        $user = $request->user();
        $context = AgentContext::fromUser($user);

        $task = new AgentTask(
            id: 'post_analyze_'.uniqid(),
            type: 'performance_analysis',
            prompt: 'Analyze post performance and provide insights',
            data: [
                'platform' => $post->platform,
                'post_id' => $post->id,
                'date_range' => '30 days',
            ],
        );

        $result = $orchestrator->dispatch($task, $context);

        if ($result->success) {
            return response()->json([
                'success' => true,
                'analysis' => $result->output,
                'metadata' => $result->metadata,
                'cost_usd' => $result->costUsd,
                'tokens_used' => $result->tokensUsed,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result->error ?? 'Agent failed to analyze post',
        ], 500);
    }

    /**
     * Get reply suggestions using the SupportAgent.
     */
    public function replySuggestionsWithAgent(Request $request, $postId, AgentOrchestrator $orchestrator)
    {
        $agencyId = $request->user()->agency_id;

        $post = SocialPost::findOrFail($postId);

        if ($post->agency_id !== $agencyId) {
            abort(403);
        }

        $validated = $request->validate([
            'message' => 'required|string|max:2000',
            'tone' => 'nullable|in:friendly,professional,casual,empathetic',
        ]);

        $user = $request->user();
        $context = AgentContext::fromUser($user);

        $task = new AgentTask(
            id: 'reply_suggest_'.uniqid(),
            type: 'response_suggest',
            prompt: 'Suggest a reply to the message',
            data: [
                'message' => $validated['message'],
                'subject' => 'Social media reply',
                'category' => 'social_media',
                'tone' => $validated['tone'] ?? 'friendly',
                'platform' => $post->platform,
            ],
        );

        $result = $orchestrator->dispatch($task, $context);

        if ($result->success) {
            return response()->json([
                'success' => true,
                'suggestions' => $result->output,
                'metadata' => $result->metadata,
                'cost_usd' => $result->costUsd,
                'tokens_used' => $result->tokensUsed,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => $result->error ?? 'Agent failed to suggest replies',
        ], 500);
    }
}
