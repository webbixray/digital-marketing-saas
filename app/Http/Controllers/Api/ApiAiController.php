<?php

namespace App\Http\Controllers\Api;

use App\Concerns\StructuredLogger;
use App\Http\Controllers\Controller;
use App\Models\Agency;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentOrchestrator;
use App\Services\AI\Agent\AgentTask;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApiAiController extends Controller
{
    use StructuredLogger;

    public function __construct(private AgentOrchestrator $orchestrator)
    {
        $this->middleware(['auth', 'agency']);
    }

    public function generate(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'action' => 'required|in:generate,rewrite,summarize,translate,hashtags,ideas',
                'prompt' => 'required|string|max:10000',
                'content_type' => 'nullable|in:post,email,article,caption,hashtags,ideas',
                'tone' => 'nullable|string|in:professional,casual,friendly,formal,enthusiastic',
                'length' => 'nullable|in:short,medium,long',
            ]);

            $agency = Agency::find($request->user()->agency_id);
            $action = $request->get('action', 'generate');

            // Map action to task type
            $taskType = match ($action) {
                'generate' => 'content_generate',
                'rewrite' => 'content_rewrite',
                'hashtags' => 'hashtag_generate',
                'ideas' => 'content_generate',
                default => 'content_generate',
            };

            // Build data array based on action
            $data = [
                'content_type' => $request->get('content_type', 'post'),
                'tone' => $request->get('tone', 'professional'),
                'platform' => 'instagram',
            ];

            if ($action === 'rewrite') {
                $data['content'] = $request->prompt;
                $data['instructions'] = 'Improve and rewrite this content';
                $data['target_tone'] = $request->get('tone', 'professional');
            }

            if ($action === 'hashtags') {
                $data['count'] = 10;
                $data['platform'] = 'instagram';
            }

            if ($action === 'ideas') {
                $prompt = "Generate 5 creative content ideas about: {$request->prompt}."
                    ."\n\nFor each idea, provide:\n- Title (catchy headline)\n- Format (post, video, carousel, story, reel)\n- Brief description (2-3 sentences)\n- Target emotion/call-to-action"
                    ."\n\nReturn as a JSON array of objects with keys: title, format, description, cta";
            }

            $task = new AgentTask(
                id: uniqid('task_', true),
                type: $taskType,
                prompt: $prompt ?? $request->prompt,
                data: $data,
            );

            $context = AgentContext::fromUser($request->user());
            $result = $this->orchestrator->dispatch($task, $context);

            if (! $result->success) {
                $this->logAgentError('ai_generate_failed', [
                    'agency_id' => $request->user()->agency_id,
                    'action' => $action,
                    'error' => $result->error,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $result->error ?? 'Agent failed to process request',
                ], 500);
            }

            $this->logAgentExecution('ai_generate_completed', [
                'agency_id' => $request->user()->agency_id,
                'action' => $action,
                'task_type' => $taskType,
                'cost_usd' => $result->costUsd,
                'tokens_used' => $result->tokensUsed,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'output' => $result->output,
                    'agent' => $result->agentName,
                    'cost_usd' => $result->costUsd,
                    'tokens_used' => $result->tokensUsed,
                    'execution_time_ms' => $result->executionTimeMs,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            $this->logAgentError('ai_generate_exception', [
                'agency_id' => $request->user()->agency_id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate content. Please try again.',
            ], 500);
        }
    }
}
