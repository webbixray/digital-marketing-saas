<?php

namespace App\Services\Workflow;

use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Services\AI\AiContentService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WorkflowEngine
{
    public function __construct(private AiContentService $aiContent) {}

    public function execute(Workflow $workflow, array $triggerData = []): WorkflowExecution
    {
        $execution = WorkflowExecution::create([
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'trigger_data' => $triggerData,
            'started_at' => now(),
        ]);

        $actionResults = [];
        $startTime = microtime(true);

        try {
            if (! $this->evaluateConditions($workflow->conditions, $triggerData)) {
                $execution->update([
                    'status' => 'success',
                    'action_results' => ['skipped' => 'Conditions not met'],
                    'duration_ms' => $this->calcDuration($startTime),
                    'completed_at' => now(),
                ]);

                return $execution;
            }

            foreach ($workflow->actions ?? [] as $action) {
                $result = $this->executeAction($action, $triggerData);
                $actionResults[] = $result;
            }

            $execution->update([
                'status' => 'success',
                'action_results' => $actionResults,
                'output_data' => $actionResults,
                'duration_ms' => $this->calcDuration($startTime),
                'completed_at' => now(),
            ]);

            $workflow->increment('execution_count');
            $workflow->update(['last_executed_at' => now()]);

        } catch (\Exception $e) {
            $execution->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
                'action_results' => $actionResults,
                'duration_ms' => $this->calcDuration($startTime),
                'completed_at' => now(),
            ]);
            $workflow->update(['error_message' => $e->getMessage()]);
            Log::error("Workflow #{$workflow->id} execution failed: {$e->getMessage()}");
        }

        return $execution;
    }

    protected function evaluateConditions(?array $conditions, array $triggerData): bool
    {
        if (empty($conditions)) {
            return true;
        }
        foreach ($conditions as $key => $expected) {
            if (data_get($triggerData, $key) !== $expected) {
                return false;
            }
        }

        return true;
    }

    protected function executeAction(array $action, array $triggerData): array
    {
        $type = $action['type'] ?? 'unknown';
        $config = $action['config'] ?? [];

        return match ($type) {
            'send_notification' => $this->actionSendNotification($config, $triggerData),
            'create_post' => $this->actionCreatePost($config, $triggerData),
            'schedule_post' => $this->actionSchedulePost($config, $triggerData),
            'ai_generate' => $this->actionAiGenerate($config, $triggerData),
            'webhook' => $this->actionWebhook($config, $triggerData),
            'sleep' => $this->actionSleep($config),
            'loop' => $this->actionLoop($config, $triggerData),
            default => ['status' => 'skipped', 'reason' => "Unknown action type: {$type}"],
        };
    }

    protected function actionSendNotification(array $config, array $triggerData): array
    {
        $channel = $config['channel'] ?? 'log';
        $message = $config['message'] ?? 'Workflow notification';
        $recipient = $config['recipient'] ?? 'admin';

        try {
            switch ($channel) {
                case 'log':
                    Log::info("[Workflow Notification] To: {$recipient}, Message: {$message}");
                    break;
                case 'telegram':
                case 'email':
                default:
                    Log::info("[Workflow Notification] {$message}");
            }

            return ['status' => 'success', 'action' => 'send_notification', 'channel' => $channel];
        } catch (\Exception $e) {
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    protected function actionCreatePost(array $config, array $triggerData): array
    {
        $accountId = $config['social_account_id'] ?? null;
        $content = $config['content'] ?? $triggerData['content'] ?? null;

        if (! $accountId || ! $content) {
            return ['status' => 'failed', 'reason' => 'Missing account ID or content'];
        }

        $account = SocialAccount::find($accountId);
        if (! $account) {
            return ['status' => 'failed', 'reason' => 'Social account not found'];
        }

        $post = SocialPost::create([
            'agency_id' => $account->agency_id,
            'social_account_id' => $account->id,
            'content' => $content,
            'hashtags' => $config['hashtags'] ?? [],
            'status' => 'draft',
            'scheduled_at' => null,
        ]);

        return ['status' => 'success', 'action' => 'create_post', 'post_id' => $post->id];
    }

    protected function actionSchedulePost(array $config, array $triggerData): array
    {
        $accountId = $config['social_account_id'] ?? null;
        $content = $config['content'] ?? $triggerData['content'] ?? null;
        $scheduledAt = $config['scheduled_at'] ?? null;

        if (! $accountId || ! $content || ! $scheduledAt) {
            return ['status' => 'failed', 'reason' => 'Missing required fields'];
        }

        $account = SocialAccount::find($accountId);
        if (! $account) {
            return ['status' => 'failed', 'reason' => 'Social account not found'];
        }

        $post = SocialPost::create([
            'agency_id' => $account->agency_id,
            'social_account_id' => $account->id,
            'content' => $content,
            'hashtags' => $config['hashtags'] ?? [],
            'status' => 'scheduled',
            'scheduled_at' => $scheduledAt,
        ]);

        return ['status' => 'success', 'action' => 'schedule_post', 'post_id' => $post->id, 'scheduled_at' => $scheduledAt];
    }

    protected function actionAiGenerate(array $config, array $triggerData): array
    {
        $prompt = $config['prompt'] ?? $triggerData['prompt'] ?? null;
        $type = $config['generation_type'] ?? 'social_post';

        if (! $prompt) {
            return ['status' => 'failed', 'reason' => 'Missing prompt'];
        }

        try {
            $agency = $triggerData['agency'] ?? (auth()->check() ? auth()->user()->agency : null);
            $result = $this->aiContent->generate(
                $agency,
                $prompt,
                $type
            );

            return [
                'status' => 'success',
                'action' => 'ai_generate',
                'type' => $type,
                'content' => $result->content ?? null,
                'cost' => $result->costUsd ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Workflow AI generate failed', ['error' => $e->getMessage()]);
            return ['status' => 'failed', 'reason' => $e->getMessage()];
        }
    }

    protected function actionWebhook(array $config, array $triggerData): array
    {
        $url = $config['url'] ?? null;
        $method = $config['method'] ?? 'POST';
        $payload = $config['payload'] ?? $triggerData;

        if (! $url) {
            return ['status' => 'failed', 'reason' => 'Missing webhook URL'];
        }

        try {
            $response = Http::timeout(30)->{$method}($url, $payload);

            return [
                'status' => $response->successful() ? 'success' : 'failed',
                'action' => 'webhook',
                'http_status' => $response->status(),
            ];
        } catch (\Exception $e) {
            return ['status' => 'failed', 'error' => $e->getMessage()];
        }
    }

    protected function actionSleep(array $config): array
    {
        $seconds = $config['seconds'] ?? 1;

        return ['status' => 'success', 'action' => 'sleep', 'seconds' => $seconds, 'message' => "Sleep action recorded ({$seconds}s). Use a delayed job for actual waiting."];
    }

    /**
     * Execute a loop action - repeats nested actions N times.
     */
    protected function actionLoop(array $config, array $triggerData): array
    {
        $iterations = $config['iterations'] ?? 1;
        $actions = $config['actions'] ?? [];
        $results = [];

        for ($i = 0; $i < $iterations; $i++) {
            foreach ($actions as $action) {
                $result = $this->executeAction($action, $triggerData);
                $results[] = $result;
            }
        }

        return ['status' => 'success', 'action' => 'loop', 'iterations' => $iterations, 'results' => $results];
    }

    protected function calcDuration(float $startTime): int
    {
        return (int) round((microtime(true) - $startTime) * 1000);
    }
}
