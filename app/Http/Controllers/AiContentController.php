<?php

namespace App\Http\Controllers;

use App\Models\AiContentLog;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentTask;
use App\Services\QuotaService;
use Illuminate\Http\Request;

class AiContentController extends Controller
{
    public function __construct(
        private readonly QuotaService $quotaService,
    ) {
        $this->middleware(['auth', 'agency']);
    }

    public function index(Request $request)
    {
        $agency = $request->user()->agency;

        // Get recent generations
        $recentGenerations = AiContentLog::where('agency_id', $agency->id)
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return view('ai.index', [
            'agency' => $agency,
            'remaining' => $this->quotaService->remainingAiGenerations($agency),
            'recentGenerations' => $recentGenerations,
        ]);
    }

    public function generate(Request $request, AgentOrchestrator $orchestrator)
    {
        $agency = $request->user()->agency;

        $validated = $request->validate([
            'prompt' => 'required|string|max:2000',
            'content_type' => 'required|in:post,caption,hashtag,headline,email,ad_copy,landing_page,blog',
            'tone' => 'nullable|string|in:professional,casual,friendly,persuasive,informative,humorous',
            'length' => 'nullable|string|in:short,medium,long',
            'context' => 'nullable|string|max:1000',
        ]);

        // Build full prompt with tone and context
        $fullPrompt = $validated['prompt'];
        if (! empty($validated['tone'])) {
            $fullPrompt .= "\n\nTone: ".$validated['tone'];
        }
        if (! empty($validated['length'])) {
            $lengthMap = ['short' => '50-100 words', 'medium' => '150-250 words', 'long' => '300-500 words'];
            $fullPrompt .= "\n\nLength: ".($lengthMap[$validated['length']] ?? 'medium');
        }
        if (! empty($validated['context'])) {
            $fullPrompt .= "\n\nAdditional context: ".$validated['context'];
        }

        try {
            $task = new AgentTask(
                id: uniqid('task_', true),
                type: 'content_generate',
                prompt: $fullPrompt,
                data: [
                    'content_type' => $validated['content_type'],
                    'tone' => $validated['tone'] ?? 'professional',
                    'platform' => 'instagram',
                ],
            );

            $context = AgentContext::fromUser($request->user());
            $result = $orchestrator->dispatch($task, $context);

            if (! $result->success) {
                throw new \Exception($result->error ?? 'Agent failed to generate content');
            }

            // Record AI usage to database
            $this->recordUsage(
                agency: $agency,
                provider: $result->metadata['provider'] ?? 'openai',
                model: $result->metadata['model'] ?? 'gpt-4o',
                action: 'generate',
                contentType: $validated['content_type'],
                prompt: $fullPrompt,
                response: $result->output,
                totalTokens: $result->tokensUsed,
                promptTokens: $result->metadata['prompt_tokens'] ?? 0,
                completionTokens: $result->metadata['completion_tokens'] ?? 0,
                costUsd: $result->costUsd,
                status: 'success',
            );

            // Return HTML view if not AJAX
            if (! $request->ajax() && ! $request->wantsJson()) {
                $recentGenerations = AiContentLog::where('agency_id', $agency->id)
                    ->orderBy('created_at', 'desc')
                    ->take(10)
                    ->get();

                return view('ai.index', [
                    'agency' => $agency,
                    'remaining' => $this->quotaService->remainingAiGenerations($agency),
                    'recentGenerations' => $recentGenerations,
                    'generatedContent' => $result->output,
                    'tokensUsed' => $result->tokensUsed,
                    'costUsd' => $result->costUsd,
                ]);
            }

            return response()->json([
                'success' => true,
                'content' => $result->output,
                'tokens' => $result->tokensUsed,
                'cost' => $result->costUsd,
                'provider' => $result->metadata['provider'] ?? $result->agentName,
                'model' => $result->metadata['model'] ?? $result->agentName,
            ]);
        } catch (\Exception $e) {
            // Record failure
            $this->recordUsage(
                agency: $agency,
                provider: 'agent_orchestrator',
                model: 'unknown',
                action: 'generate',
                contentType: $validated['content_type'],
                prompt: $fullPrompt,
                response: '',
                totalTokens: 0,
                promptTokens: 0,
                completionTokens: 0,
                costUsd: 0,
                status: 'failed',
                errorMessage: $e->getMessage(),
            );

            if (! $request->ajax() && ! $request->wantsJson()) {
                return back()->with('error', 'Generation failed: '.$e->getMessage())->withInput();
            }

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function rewrite(Request $request, AgentOrchestrator $orchestrator)
    {
        $agency = $request->user()->agency;

        $validated = $request->validate([
            'content' => 'required|string|max:5000',
            'instructions' => 'nullable|string|max:1000',
        ]);

        try {
            $task = new AgentTask(
                id: uniqid('task_', true),
                type: 'content_rewrite',
                prompt: $validated['content'],
                data: [
                    'content' => $validated['content'],
                    'instructions' => $validated['instructions'] ?? 'Make it more engaging and professional',
                    'target_tone' => 'professional',
                ],
            );

            $context = AgentContext::fromUser($request->user());
            $result = $orchestrator->dispatch($task, $context);

            if (! $result->success) {
                throw new \Exception($result->error ?? 'Agent failed to rewrite content');
            }

            return response()->json([
                'success' => true,
                'content' => $result->output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function hashtags(Request $request, AgentOrchestrator $orchestrator)
    {
        $agency = $request->user()->agency;

        $validated = $request->validate([
            'topic' => 'required|string|max:500',
            'count' => 'nullable|integer|min:1|max:30',
            'platform' => 'nullable|string|max:50',
        ]);

        try {
            $task = new AgentTask(
                id: uniqid('task_', true),
                type: 'hashtag_generate',
                prompt: $validated['topic'],
                data: [
                    'count' => (int) ($validated['count'] ?? 10),
                    'platform' => $validated['platform'] ?? 'instagram',
                ],
            );

            $context = AgentContext::fromUser($request->user());
            $result = $orchestrator->dispatch($task, $context);

            if (! $result->success) {
                throw new \Exception($result->error ?? 'Agent failed to generate hashtags');
            }

            $hashtags = json_decode($result->output, true) ?? [];

            return response()->json([
                'success' => true,
                'hashtags' => $hashtags,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function ideas(Request $request, AgentOrchestrator $orchestrator)
    {
        $agency = $request->user()->agency;

        $validated = $request->validate([
            'topic' => 'required|string|max:500',
            'count' => 'nullable|integer|min:1|max:10',
        ]);

        try {
            $count = (int) ($validated['count'] ?? 5);
            $prompt = "Generate {$count} creative content ideas about: {$validated['topic']}."
                ."\n\nFor each idea, provide:\n- Title (catchy headline)\n- Format (post, video, carousel, story, reel)\n- Brief description (2-3 sentences)\n- Target emotion/call-to-action"
                ."\n\nReturn as a JSON array of objects with keys: title, format, description, cta";

            $task = new AgentTask(
                id: uniqid('task_', true),
                type: 'content_generate',
                prompt: $prompt,
                data: [
                    'content_type' => 'post',
                    'tone' => 'creative',
                    'platform' => 'instagram',
                ],
            );

            $context = AgentContext::fromUser($request->user());
            $result = $orchestrator->dispatch($task, $context);

            if (! $result->success) {
                throw new \Exception($result->error ?? 'Agent failed to generate ideas');
            }

            // Try to parse as JSON, fallback to returning raw output
            $ideas = json_decode($result->output, true);
            if (! is_array($ideas)) {
                $ideas = [['title' => $result->output, 'format' => 'post', 'description' => '', 'cta' => '']];
            }

            return response()->json([
                'success' => true,
                'ideas' => $ideas,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Record AI usage to the database.
     */
    protected function recordUsage(
        $agency,
        string $provider,
        string $model,
        string $action,
        ?string $contentType,
        string $prompt,
        string $response,
        int $totalTokens,
        int $promptTokens,
        int $completionTokens,
        float $costUsd,
        string $status,
        ?string $errorMessage = null,
    ): void {
        try {
            AiContentLog::create([
                'agency_id' => $agency->id,
                'provider' => $provider,
                'model' => $model,
                'action' => $action,
                'content_type' => $contentType,
                'prompt' => $prompt,
                'response' => $response,
                'total_tokens' => $totalTokens,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'cost_usd' => $costUsd,
                'status' => $status,
                'error_message' => $errorMessage,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning("Failed to record AI usage: {$e->getMessage()}");
        }
    }
}
