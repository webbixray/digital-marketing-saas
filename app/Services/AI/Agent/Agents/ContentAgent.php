<?php

namespace App\Services\AI\Agent\Agents;

use App\Models\Agency;
use App\Services\AI\Agent\AbstractAgent;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentResult;
use App\Services\AI\Agent\AgentTask;
use App\Services\AI\Gateway\AiRequest;
use Illuminate\Support\Facades\Log;

class ContentAgent extends AbstractAgent
{
    protected string $name = 'content_agent';

    /**
     * @var array<string>
     */
    protected array $supportedTaskTypes = [
        'content_generate',
        'content_optimize',
        'content_rewrite',
        'hashtag_generate',
    ];

    /**
     * Engagement thresholds for success scoring.
     */
    private const ENGAGEMENT_THRESHOLD_HIGH = 0.7;

    private const ENGAGEMENT_THRESHOLD_MEDIUM = 0.4;

    /**
     * {@inheritdoc}
     */
    public function execute(AgentTask $task, AgentContext $context): AgentResult
    {
        $startTime = microtime(true);

        if (! $this->canHandle($task->type)) {
            return AgentResult::failure(
                taskId: $task->id,
                agentName: $this->name,
                error: "Unsupported task type: {$task->type}"
            );
        }

        $agency = $context->agency;

        if (! $agency) {
            return AgentResult::failure(
                taskId: $task->id,
                agentName: $this->name,
                error: 'No agency context provided'
            );
        }

        try {
            $result = match ($task->type) {
                'content_generate' => $this->handleContentGenerate($task, $agency, $context),
                'content_optimize' => $this->handleContentOptimize($task, $agency, $context),
                'content_rewrite' => $this->handleContentRewrite($task, $agency, $context),
                'hashtag_generate' => $this->handleHashtagGenerate($task, $agency, $context),
                default => null,
            };

            if ($result === null) {
                return AgentResult::failure(
                    taskId: $task->id,
                    agentName: $this->name,
                    error: "Failed to execute task: {$task->type}"
                );
            }

            $executionTime = (microtime(true) - $startTime) * 1000;
            $result = new AgentResult(
                taskId: $result->taskId,
                agentName: $result->agentName,
                success: $result->success,
                output: $result->output,
                costUsd: $result->costUsd,
                tokensUsed: $result->tokensUsed,
                executionTimeMs: $executionTime,
                error: $result->error,
                metadata: $result->metadata,
                timestamp: $result->timestamp
            );

            $this->recordExecution($task->type, $result);
            $this->persistAgencyResults($context, $task->type, $result);

            return $result;
        } catch (\Exception $e) {
            Log::error("ContentAgent execution failed: {$e->getMessage()}", [
                'task_id' => $task->id,
                'task_type' => $task->type,
            ]);

            $executionTime = (microtime(true) - $startTime) * 1000;
            $result = AgentResult::failure(
                taskId: $task->id,
                agentName: $this->name,
                error: $e->getMessage(),
                metadata: ['task_type' => $task->type]
            );

            $this->recordExecution($task->type, $result);

            return $result;
        }
    }

    /**
     * Get the success rate based on engagement scores of generated content.
     * This calculates success as the percentage of content that achieved
     * above-medium engagement thresholds.
     */
    public function getEngagementBasedSuccessRate(): float
    {
        $scores = $this->executionStats['engagement_scores'] ?? [];

        if (empty($scores)) {
            return 0.0;
        }

        $highEngagement = count(array_filter($scores, fn ($s) => $s >= self::ENGAGEMENT_THRESHOLD_HIGH));
        $mediumEngagement = count(array_filter($scores, fn ($s) => $s >= self::ENGAGEMENT_THRESHOLD_MEDIUM));

        // Weight: high engagement counts double, medium counts once
        $weightedSuccesses = ($highEngagement * 2) + $mediumEngagement;

        return min($weightedSuccesses / (count($scores) * 2), 1.0);
    }

    /**
     * Get prompt patterns that produce the best engagement.
     *
     * @return array<string, float>
     */
    public function getTopPerformingPrompts(int $limit = 5): array
    {
        $patterns = $this->executionStats['prompt_patterns'] ?? [];

        if (empty($patterns)) {
            return [];
        }

        // Sort by average engagement descending
        uasort($patterns, fn ($a, $b) => ($b['avg_engagement'] ?? 0) <=> ($a['avg_engagement'] ?? 0));

        return array_slice($patterns, 0, $limit, true);
    }

    /**
     * Build an enhanced prompt that incorporates learned patterns.
     */
    protected function buildEnhancedPrompt(string $basePrompt, AgentContext $context, string $taskType): string
    {
        $patterns = $this->getLearnedPatterns($context, $taskType);

        if (empty($patterns)) {
            return $basePrompt;
        }

        // Find top performing patterns
        $highPerformers = array_filter($patterns, fn ($p) => ($p['metadata']['engagement_score'] ?? 0) >= self::ENGAGEMENT_THRESHOLD_MEDIUM);
        $topPerformers = array_slice($highPerformers, 0, 3);

        if (empty($topPerformers)) {
            return $basePrompt;
        }

        $enhancement = "\n\nBased on past successful content, consider these patterns:\n";
        foreach ($topPerformers as $pattern) {
            $meta = $pattern['metadata'] ?? [];
            if (isset($meta['content_type'])) {
                $enhancement .= "- Content type: {$meta['content_type']}\n";
            }
            if (isset($meta['tone'])) {
                $enhancement .= "- Tone: {$meta['tone']}\n";
            }
            if (isset($meta['structure'])) {
                $enhancement .= "- Structure: {$meta['structure']}\n";
            }
        }

        return $basePrompt.$enhancement;
    }

    /**
     * Record engagement score and learn from it.
     */
    protected function recordEngagementScore(string $taskType, float $engagementScore, array $meta = []): void
    {
        $this->executionStats['engagement_scores'][] = $engagementScore;

        // Track prompt pattern performance
        $patternKey = $meta['pattern_key'] ?? md5($meta['prompt'] ?? 'default');
        $patterns = $this->executionStats['prompt_patterns'] ?? [];

        if (! isset($patterns[$patternKey])) {
            $patterns[$patternKey] = [
                'count' => 0,
                'total_engagement' => 0,
                'avg_engagement' => 0,
                'metadata' => $meta,
            ];
        }

        $patterns[$patternKey]['count']++;
        $patterns[$patternKey]['total_engagement'] += $engagementScore;
        $patterns[$patternKey]['avg_engagement'] = $patterns[$patternKey]['total_engagement'] / $patterns[$patternKey]['count'];

        $this->executionStats['prompt_patterns'] = $patterns;

        // Trim to top 50 patterns
        if (count($patterns) > 50) {
            uasort($patterns, fn ($a, $b) => ($b['avg_engagement'] ?? 0) <=> ($a['avg_engagement'] ?? 0));
            $this->executionStats['prompt_patterns'] = array_slice($patterns, 0, 50, true);
        }

        $this->persistMemory();
    }

    /**
     * Handle content generation tasks.
     */
    private function handleContentGenerate(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $prompt = $task->prompt;
        $contentType = $task->data['content_type'] ?? 'post';
        $platform = $task->data['platform'] ?? 'instagram';
        $tone = $task->data['tone'] ?? 'professional';

        // Build enhanced prompt using learned patterns
        $enhancedPrompt = $this->buildEnhancedPrompt($prompt, $context, 'content_generate');

        $systemPrompt = $this->getContentSystemPrompt($platform, $tone, $contentType);

        $request = AiRequest::creative(
            prompt: $enhancedPrompt,
            systemPrompt: $systemPrompt,
        );

        $response = $this->callAi($request, $agency);

        // Calculate engagement prediction based on content features
        $engagementScore = $this->predictEngagement($response->content, $platform);
        $meta = [
            'content_type' => $contentType,
            'platform' => $platform,
            'tone' => $tone,
            'engagement_score' => $engagementScore,
            'pattern_key' => md5($enhancedPrompt),
            'provider' => $response->provider ?? 'openai',
            'model' => $response->model ?? 'gpt-4o',
            'prompt_tokens' => $response->promptTokens ?? 0,
            'completion_tokens' => $response->completionTokens ?? 0,
        ];

        $this->recordEngagementScore('content_generate', $engagementScore, $meta);

        return AgentResult::success(
            taskId: $task->id,
            agentName: $this->name,
            output: $response->content,
            costUsd: $response->costUsd ?? 0,
            tokensUsed: $response->totalTokens,
            executionTimeMs: 0,
            metadata: $meta,
        );
    }

    /**
     * Handle content optimization tasks.
     */
    private function handleContentOptimize(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $content = $task->data['content'] ?? '';
        $platform = $task->data['platform'] ?? 'instagram';
        $goals = $task->data['goals'] ?? ['engagement'];

        $prompt = "Optimize the following content for {$platform} to maximize ".implode(', ', $goals).":\n\n{$content}";
        $prompt .= "\n\nProvide the optimized version and briefly explain what changes you made.";

        $request = AiRequest::creative(
            prompt: $prompt,
            systemPrompt: 'You are an expert content optimizer for social media. You understand platform-specific best practices and what drives engagement.'
        );

        $response = $this->callAi($request, $agency);

        // Estimate improvement in engagement
        $originalEngagement = $this->predictEngagement($content, $platform);
        $optimizedEngagement = $this->predictEngagement($response->content, $platform);
        $engagementScore = max($optimizedEngagement, $originalEngagement);

        $meta = [
            'platform' => $platform,
            'original_engagement' => $originalEngagement,
            'optimized_engagement' => $optimizedEngagement,
            'engagement_score' => $engagementScore,
            'goals' => $goals,
        ];

        $this->recordEngagementScore('content_optimize', $engagementScore, $meta);

        return AgentResult::success(
            taskId: $task->id,
            agentName: $this->name,
            output: $response->content,
            costUsd: $response->costUsd ?? 0,
            tokensUsed: $response->totalTokens,
            executionTimeMs: 0,
            metadata: $meta,
        );
    }

    /**
     * Handle content rewrite tasks.
     */
    private function handleContentRewrite(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $content = $task->data['content'] ?? '';
        $instructions = $task->data['instructions'] ?? 'Make it more engaging and professional';
        $targetTone = $task->data['target_tone'] ?? 'professional';

        $prompt = "Rewrite the following content with these instructions:\n{$instructions}\n\nTarget tone: {$targetTone}\n\nContent:\n{$content}";
        $prompt .= "\n\nReturn ONLY the rewritten content, no explanations.";

        $request = AiRequest::creative(
            prompt: $prompt,
            systemPrompt: 'You are an expert copywriter who can adapt content to any tone or style while preserving the core message.'
        );

        $response = $this->callAi($request, $agency);

        $meta = [
            'target_tone' => $targetTone,
            'engagement_score' => 0.5, // Default for rewrites
            'content_length_change' => strlen($response->content) - strlen($content),
        ];

        return AgentResult::success(
            taskId: $task->id,
            agentName: $this->name,
            output: $response->content,
            costUsd: $response->costUsd ?? 0,
            tokensUsed: $response->totalTokens,
            executionTimeMs: 0,
            metadata: $meta,
        );
    }

    /**
     * Handle hashtag generation tasks.
     */
    private function handleHashtagGenerate(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $topic = $task->prompt;
        $platform = $task->data['platform'] ?? 'instagram';
        $count = $task->data['count'] ?? 10;

        // Check memory for top performing hashtags
        $pastResults = $this->getLearnedPatterns($context, 'hashtag_generate');
        $topHashtags = $this->extractTopHashtagsFromMemory($pastResults);

        $prompt = "Generate {$count} highly relevant, high-performing hashtags for: {$topic}\nPlatform: {$platform}";
        if (! empty($topHashtags)) {
            $prompt .= "\n\nBased on past successful content, these hashtags performed well: ".implode(', ', $topHashtags);
        }
        $prompt .= "\n\nReturn ONLY a comma-separated list of hashtags (with # prefix). No extra text.";

        $request = AiRequest::text(
            prompt: $prompt,
            systemPrompt: 'You are a social media hashtag strategist who knows what drives discoverability and engagement.'
        );

        $response = $this->callAi($request, $agency);

        // Parse hashtags
        $hashtags = $this->parseHashtags($response->content);

        $meta = [
            'platform' => $platform,
            'hashtag_count' => count($hashtags),
            'hashtags' => $hashtags,
            'engagement_score' => $this->estimateHashtagEngagement($hashtags, $platform),
        ];

        $this->recordEngagementScore('hashtag_generate', $meta['engagement_score'], $meta);

        return AgentResult::success(
            taskId: $task->id,
            agentName: $this->name,
            output: json_encode($hashtags),
            costUsd: $response->costUsd ?? 0,
            tokensUsed: $response->totalTokens,
            executionTimeMs: 0,
            metadata: $meta,
        );
    }

    /**
     * Get system prompt for content generation.
     */
    private function getContentSystemPrompt(string $platform, string $tone, string $contentType): string
    {
        $platformTips = match ($platform) {
            'instagram' => 'Use vivid imagery language, include relevant hashtags, write engaging captions that encourage interaction.',
            'facebook' => 'Focus on storytelling, ask questions to encourage comments, keep paragraphs short.',
            'twitter' => 'Be concise and punchy, use strong hooks, leverage trending topics.',
            'linkedin' => 'Maintain professional tone, share insights, use industry terminology appropriately.',
            'tiktok' => 'Be authentic and trendy, use conversational language, reference popular culture.',
            'pinterest' => 'Use descriptive keywords, focus on visual appeal, write actionable titles.',
            default => 'Follow general best practices for social media content.',
        };

        return "You are an expert social media content creator. Create {$contentType} content for {$platform} with a {$tone} tone.\n\nPlatform guidance: {$platformTips}\n\nFocus on driving engagement through compelling copy.";
    }

    /**
     * Predict engagement score for content.
     */
    private function predictEngagement(string $content, string $platform): float
    {
        $score = 0.5; // Base score
        $length = strlen($content);

        // Length scoring based on platform
        $optimalLengths = [
            'instagram' => [100, 500],
            'facebook' => [80, 250],
            'twitter' => [70, 280],
            'linkedin' => [150, 800],
            'tiktok' => [50, 150],
        ];

        $optimal = $optimalLengths[$platform] ?? [100, 300];
        if ($length >= $optimal[0] && $length <= $optimal[1]) {
            $score += 0.15;
        } elseif ($length < $optimal[0]) {
            $score += 0.05;
        }

        // Hashtag usage
        $hashtagCount = substr_count($content, '#');
        if ($hashtagCount >= 3 && $hashtagCount <= 15) {
            $score += 0.1;
        }

        // Questions drive engagement
        if (str_contains($content, '?')) {
            $score += 0.08;
        }

        // Emoji usage
        if (preg_match('/[\x{1F600}-\x{1F64F}\x{1F300}-\x{1F5FF}\x{1F680}-\x{1F6FF}]/u', $content)) {
            $score += 0.07;
        }

        // Call-to-action phrases
        $ctaPatterns = ['click', 'link', 'learn more', 'comment', 'share', 'follow', 'discover', 'get'];
        foreach ($ctaPatterns as $cta) {
            if (stripos($content, $cta) !== false) {
                $score += 0.05;
                break;
            }
        }

        return min($score, 1.0);
    }

    /**
     * Estimate hashtag engagement potential.
     */
    private function estimateHashtagEngagement(array $hashtags, string $platform): float
    {
        $count = count($hashtags);

        // Instagram favors more hashtags, Twitter fewer
        $optimal = match ($platform) {
            'instagram' => 10,
            'twitter' => 2,
            'facebook' => 3,
            'linkedin' => 5,
            default => 5,
        };

        $diff = abs($count - $optimal);
        if ($diff <= 2) {
            return 0.8;
        }
        if ($diff <= 5) {
            return 0.6;
        }

        return 0.4;
    }

    /**
     * Parse hashtags from AI response.
     */
    private function parseHashtags(string $content): array
    {
        $hashtags = [];

        if (preg_match_all('/#[\w]+/', $content, $matches)) {
            $hashtags = $matches[0];
        } else {
            // Fallback: split by comma
            $parts = explode(',', $content);
            foreach ($parts as $part) {
                $tag = trim($part);
                if (! str_starts_with($tag, '#')) {
                    $tag = '#'.$tag;
                }
                if (strlen($tag) > 1) {
                    $hashtags[] = $tag;
                }
            }
        }

        return array_values(array_unique(array_map('strtolower', $hashtags)));
    }

    /**
     * Extract top performing hashtags from past results.
     */
    private function extractTopHashtagsFromMemory(array $pastResults): array
    {
        $hashtagPerformance = [];

        foreach ($pastResults as $result) {
            $meta = $result['metadata'] ?? [];
            $hashtags = $meta['hashtags'] ?? [];
            $engagement = $meta['engagement_score'] ?? 0;

            foreach ($hashtags as $tag) {
                if (! isset($hashtagPerformance[$tag])) {
                    $hashtagPerformance[$tag] = ['total' => 0, 'count' => 0];
                }
                $hashtagPerformance[$tag]['total'] += $engagement;
                $hashtagPerformance[$tag]['count']++;
            }
        }

        // Calculate average engagement per hashtag
        $averages = [];
        foreach ($hashtagPerformance as $tag => $data) {
            $averages[$tag] = $data['total'] / max($data['count'], 1);
        }

        arsort($averages);

        return array_slice(array_keys($averages), 0, 5);
    }
}
