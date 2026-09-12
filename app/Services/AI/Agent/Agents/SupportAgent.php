<?php

namespace App\Services\AI\Agent\Agents;

use App\Models\Agency;
use App\Models\InboxMessage;
use App\Services\AI\Agent\AbstractAgent;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentResult;
use App\Services\AI\Agent\AgentTask;
use App\Services\AI\Gateway\AiRequest;
use Illuminate\Support\Facades\Log;

class SupportAgent extends AbstractAgent
{
    protected string $name = 'support_agent';

    /**
     * @var array<string>
     */
    protected array $supportedTaskTypes = [
        'ticket_classify',
        'response_suggest',
        'sentiment_analysis',
        'escalation_detect',
    ];

    /**
     * Resolution rate thresholds for success scoring.
     */
    private const RESOLUTION_THRESHOLD_HIGH = 0.8;

    private const RESOLUTION_THRESHOLD_MEDIUM = 0.5;

    /**
     * Sentiment categories.
     */
    private const SENTIMENT_POSITIVE = 'positive';

    private const SENTIMENT_NEUTRAL = 'neutral';

    private const SENTIMENT_NEGATIVE = 'negative';

    private const SENTIMENT_URGENT = 'urgent';

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
                'ticket_classify' => $this->handleTicketClassify($task, $agency, $context),
                'response_suggest' => $this->handleResponseSuggest($task, $agency, $context),
                'sentiment_analysis' => $this->handleSentimentAnalysis($task, $agency, $context),
                'escalation_detect' => $this->handleEscalationDetect($task, $agency, $context),
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
            Log::error("SupportAgent execution failed: {$e->getMessage()}", [
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
     * Get success rate based on resolution rate.
     * Measures how often the agent's suggestions lead to successful resolutions.
     */
    public function getSuccessRate(): float
    {
        $resolutionScores = $this->executionStats['resolution_scores'] ?? [];

        if (empty($resolutionScores)) {
            return 0.5;
        }

        $highResolution = count(array_filter($resolutionScores, fn ($r) => $r >= self::RESOLUTION_THRESHOLD_HIGH));
        $mediumResolution = count(array_filter($resolutionScores, fn ($r) => $r >= self::RESOLUTION_THRESHOLD_MEDIUM));

        // Weight: high resolution counts double, medium counts once
        $weightedSuccesses = ($highResolution * 2) + $mediumResolution;

        return min($weightedSuccesses / (count($resolutionScores) * 2), 1.0);
    }

    /**
     * Get resolution rate statistics.
     *
     * @return array<string, float>
     */
    public function getResolutionStats(): array
    {
        return $this->executionStats['resolution_stats'] ?? [];
    }

    /**
     * Get common ticket categories and their resolution rates.
     *
     * @return array<string, array>
     */
    public function getCategoryStats(): array
    {
        return $this->executionStats['category_stats'] ?? [];
    }

    /**
     * Handle ticket classification.
     */
    private function handleTicketClassify(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $message = $task->data['message'] ?? '';
        $subject = $task->data['subject'] ?? '';
        $customerId = $task->data['customer_id'] ?? null;

        // Get past classification patterns
        $categoryStats = $this->getCategoryStats();

        $prompt = "Classify the following support ticket:\n\n";
        $prompt .= "Subject: {$subject}\n";
        $prompt .= "Message: {$message}\n";

        if (! empty($categoryStats)) {
            $prompt .= "\nBased on past tickets, common categories are:\n";
            foreach (array_slice($categoryStats, 0, 5, true) as $category => $stats) {
                $prompt .= "- {$category} ({$stats['count']} tickets, ".round($stats['avg_resolution'] * 100)."% resolution rate)\n";
            }
        }

        $prompt .= "\nProvide:\n1. Primary category\n2. Subcategory\n3. Priority level (low/medium/high/critical)\n4. Suggested tags\n5. Confidence score";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a support ticket classification expert. You accurately categorize tickets to route them to the right team and prioritize effectively.'
        );

        $response = $this->callAi($request, $agency);

        // Parse classification
        $classification = $this->parseClassification($response->content);

        // Learn from classification
        $this->learnFromClassification($classification);

        $resolutionScore = $classification['confidence'] ?? 0.5;
        $meta = [
            'category' => $classification['category'] ?? 'unknown',
            'subcategory' => $classification['subcategory'] ?? '',
            'priority' => $classification['priority'] ?? 'medium',
            'confidence' => $resolutionScore,
            'customer_id' => $customerId,
        ];

        $this->recordResolutionScore('ticket_classify', $resolutionScore, $meta);

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
     * Handle response suggestion.
     */
    private function handleResponseSuggest(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $message = $task->data['message'] ?? '';
        $subject = $task->data['subject'] ?? '';
        $category = $task->data['category'] ?? 'general';
        $tone = $task->data['tone'] ?? 'professional';
        $history = $task->data['history'] ?? [];

        // Get past successful responses
        $pastResponses = $this->getLearnedPatterns($context, 'response_suggest');
        $successfulResponses = array_filter($pastResponses, fn ($r) => ($r['metadata']['resolution_score'] ?? 0) >= self::RESOLUTION_THRESHOLD_MEDIUM);

        $prompt = "Suggest a response to the following support ticket:\n\n";
        $prompt .= "Subject: {$subject}\n";
        $prompt .= "Message: {$message}\n";
        $prompt .= "Category: {$category}\n";
        $prompt .= "Tone: {$tone}\n";

        if (! empty($history)) {
            $prompt .= "\nConversation history:\n";
            foreach (array_slice($history, -5) as $entry) {
                $prompt .= "- {$entry['role']}: {$entry['message']}\n";
            }
        }

        if (! empty($successfulResponses)) {
            $prompt .= "\nBased on past successful resolutions, these approaches work well:\n";
            $topResponses = array_slice($successfulResponses, 0, 3);
            foreach ($topResponses as $response) {
                $prompt .= '- Category: '.($response['metadata']['category'] ?? 'general')."\n";
            }
        }

        $prompt .= "\nProvide:\n1. Suggested response\n2. Alternative response options\n3. Internal notes for the support agent\n4. Follow-up actions if needed";

        $request = AiRequest::creative(
            prompt: $prompt,
            systemPrompt: 'You are a customer support expert. You craft helpful, empathetic responses that resolve customer issues efficiently.'
        );

        $response = $this->callAi($request, $agency);

        $resolutionScore = 0.6;
        $meta = [
            'category' => $category,
            'tone' => $tone,
            'resolution_score' => $resolutionScore,
            'has_history' => ! empty($history),
        ];

        $this->recordResolutionScore('response_suggest', $resolutionScore, $meta);

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
     * Handle sentiment analysis.
     */
    private function handleSentimentAnalysis(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $message = $task->data['message'] ?? '';
        $subject = $task->data['subject'] ?? '';
        $customerId = $task->data['customer_id'] ?? null;

        // Get customer history if available
        $customerHistory = $customerId ? $this->getCustomerHistory($agency, $customerId) : [];

        $prompt = "Analyze the sentiment of the following customer message:\n\n";
        $prompt .= "Subject: {$subject}\n";
        $prompt .= "Message: {$message}\n";

        if (! empty($customerHistory)) {
            $prompt .= "\nCustomer history summary:\n";
            $prompt .= '- Total tickets: '.($customerHistory['total_tickets'] ?? 0)."\n";
            $prompt .= '- Average sentiment: '.($customerHistory['avg_sentiment'] ?? 'neutral')."\n";
            $prompt .= '- Satisfaction trend: '.($customerHistory['satisfaction_trend'] ?? 'stable')."\n";
        }

        $prompt .= "\nProvide:\n1. Sentiment classification (positive/neutral/negative/urgent)\n2. Sentiment score (0-1, where 0 is very negative, 1 is very positive)\n3. Key emotional indicators\n4. Urgency level\n5. Customer satisfaction risk (low/medium/high)\n6. Recommended approach";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a sentiment analysis expert. You accurately assess customer emotions and identify potential issues before they escalate.'
        );

        $response = $this->callAi($request, $agency);

        // Parse sentiment
        $sentiment = $this->parseSentiment($response->content);

        // Learn from sentiment analysis
        $this->learnFromSentiment($sentiment, $customerId);

        $resolutionScore = $sentiment['score'] ?? 0.5;
        $meta = [
            'sentiment' => $sentiment['classification'] ?? 'neutral',
            'score' => $resolutionScore,
            'urgency' => $sentiment['urgency'] ?? 'low',
            'satisfaction_risk' => $sentiment['risk'] ?? 'low',
            'customer_id' => $customerId,
        ];

        $this->recordResolutionScore('sentiment_analysis', $resolutionScore, $meta);

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
     * Handle escalation detection.
     */
    private function handleEscalationDetect(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $message = $task->data['message'] ?? '';
        $subject = $task->data['subject'] ?? '';
        $category = $task->data['category'] ?? 'general';
        $customerId = $task->data['customer_id'] ?? null;
        $ticketAge = $task->data['ticket_age_hours'] ?? 0;

        // Get escalation patterns
        $escalationPatterns = $this->executionStats['escalation_patterns'] ?? [];

        $prompt = "Determine if the following support ticket needs escalation:\n\n";
        $prompt .= "Subject: {$subject}\n";
        $prompt .= "Message: {$message}\n";
        $prompt .= "Category: {$category}\n";
        $prompt .= "Ticket age: {$ticketAge} hours\n";

        if (! empty($escalationPatterns)) {
            $prompt .= "\nBased on past escalations, these patterns typically require escalation:\n";
            foreach (array_slice($escalationPatterns, 0, 5) as $pattern) {
                $prompt .= "- {$pattern['indicator']}\n";
            }
        }

        $prompt .= "\nProvide:\n1. Escalation needed (yes/no)\n2. Escalation urgency (low/medium/high/critical)\n3. Reason for escalation\n4. Recommended escalation path\n5. Suggested priority level\n6. Key risk indicators";

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a support escalation specialist. You identify tickets that require immediate attention and escalation to senior staff.'
        );

        $response = $this->callAi($request, $agency);

        // Parse escalation decision
        $escalation = $this->parseEscalation($response->content);

        // Learn from escalation patterns
        $this->learnFromEscalation($escalation, $message, $category);

        $resolutionScore = $escalation['needs_escalation'] ? 0.7 : 0.5;
        $meta = [
            'needs_escalation' => $escalation['needs_escalation'],
            'urgency' => $escalation['urgency'] ?? 'low',
            'reason' => $escalation['reason'] ?? '',
            'escalation_path' => $escalation['path'] ?? '',
            'resolution_score' => $resolutionScore,
            'customer_id' => $customerId,
        ];

        $this->recordResolutionScore('escalation_detect', $resolutionScore, $meta);

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
     * Get customer support history.
     */
    private function getCustomerHistory(Agency $agency, int $customerId): array
    {
        $messages = InboxMessage::where('agency_id', $agency->id)
            ->where('customer_id', $customerId)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        if ($messages->isEmpty()) {
            return [];
        }

        $totalTickets = $messages->count();
        $resolvedTickets = $messages->where('status', 'resolved')->count();

        return [
            'total_tickets' => $totalTickets,
            'resolved_tickets' => $resolvedTickets,
            'resolution_rate' => $totalTickets > 0 ? $resolvedTickets / $totalTickets : 0,
            'avg_sentiment' => $messages->avg('sentiment_score') ?? 0.5,
            'satisfaction_trend' => $this->calculateSatisfactionTrend($messages),
        ];
    }

    /**
     * Calculate satisfaction trend from messages.
     */
    private function calculateSatisfactionTrend($messages): string
    {
        if ($messages->count() < 5) {
            return 'insufficient_data';
        }

        $firstHalf = $messages->take((int) ($messages->count() / 2));
        $secondHalf = $messages->skip((int) ($messages->count() / 2));

        $firstAvg = $firstHalf->avg('sentiment_score') ?? 0.5;
        $secondAvg = $secondHalf->avg('sentiment_score') ?? 0.5;

        if ($secondAvg > $firstAvg * 1.1) {
            return 'improving';
        }
        if ($secondAvg < $firstAvg * 0.9) {
            return 'declining';
        }

        return 'stable';
    }

    /**
     * Parse classification from AI response.
     */
    private function parseClassification(string $content): array
    {
        $classification = [
            'category' => 'general',
            'subcategory' => '',
            'priority' => 'medium',
            'confidence' => 0.5,
        ];

        // Extract category
        if (preg_match('/category[:\s]+([^\n]+)/i', $content, $matches)) {
            $classification['category'] = strtolower(trim($matches[1]));
        }

        // Extract priority
        if (preg_match('/priority[:\s]+([^\n]+)/i', $content, $matches)) {
            $classification['priority'] = strtolower(trim($matches[1]));
        }

        // Extract confidence
        if (preg_match('/confidence[:\s]+([\d.]+)/i', $content, $matches)) {
            $classification['confidence'] = (float) $matches[1];
        }

        return $classification;
    }

    /**
     * Parse sentiment from AI response.
     */
    private function parseSentiment(string $content): array
    {
        $sentiment = [
            'classification' => 'neutral',
            'score' => 0.5,
            'urgency' => 'low',
            'risk' => 'low',
        ];

        // Extract sentiment classification
        if (preg_match('/sentiment[:\s]+([^\n]+)/i', $content, $matches)) {
            $sentiment['classification'] = strtolower(trim($matches[1]));
        }

        // Extract score
        if (preg_match('/score[:\s]+([\d.]+)/i', $content, $matches)) {
            $sentiment['score'] = (float) $matches[1];
        }

        // Extract urgency
        if (preg_match('/urgency[:\s]+([^\n]+)/i', $content, $matches)) {
            $sentiment['urgency'] = strtolower(trim($matches[1]));
        }

        // Extract risk
        if (preg_match('/risk[:\s]+([^\n]+)/i', $content, $matches)) {
            $sentiment['risk'] = strtolower(trim($matches[1]));
        }

        return $sentiment;
    }

    /**
     * Parse escalation decision from AI response.
     */
    private function parseEscalation(string $content): array
    {
        $escalation = [
            'needs_escalation' => false,
            'urgency' => 'low',
            'reason' => '',
            'path' => '',
        ];

        // Check for escalation yes/no
        if (preg_match('/escalation\s+needed[:\s]+(yes|no)/i', $content, $matches)) {
            $escalation['needs_escalation'] = strtolower($matches[1]) === 'yes';
        }

        // Extract urgency
        if (preg_match('/urgency[:\s]+([^\n]+)/i', $content, $matches)) {
            $escalation['urgency'] = strtolower(trim($matches[1]));
        }

        // Extract reason
        if (preg_match('/reason[:\s]+([^\n]+)/i', $content, $matches)) {
            $escalation['reason'] = trim($matches[1]);
        }

        return $escalation;
    }

    /**
     * Learn from ticket classification.
     */
    private function learnFromClassification(array $classification): void
    {
        $category = $classification['category'] ?? 'unknown';
        $categoryStats = $this->executionStats['category_stats'] ?? [];

        if (! isset($categoryStats[$category])) {
            $categoryStats[$category] = [
                'count' => 0,
                'avg_resolution' => 0.5,
                'resolutions' => [],
            ];
        }

        $categoryStats[$category]['count']++;
        $categoryStats[$category]['resolutions'][] = $classification['confidence'] ?? 0.5;
        $categoryStats[$category]['avg_resolution'] = array_sum($categoryStats[$category]['resolutions']) / count($categoryStats[$category]['resolutions']);

        $this->executionStats['category_stats'] = $categoryStats;
        $this->persistMemory();
    }

    /**
     * Learn from sentiment analysis.
     */
    private function learnFromSentiment(array $sentiment, ?int $customerId): void
    {
        $sentimentHistory = $this->executionStats['sentiment_history'] ?? [];

        $sentimentHistory[] = [
            'classification' => $sentiment['classification'] ?? 'neutral',
            'score' => $sentiment['score'] ?? 0.5,
            'urgency' => $sentiment['urgency'] ?? 'low',
            'customer_id' => $customerId,
            'timestamp' => now()->toIso8601String(),
        ];

        // Keep last 100 sentiment records
        if (count($sentimentHistory) > 100) {
            $sentimentHistory = array_slice($sentimentHistory, -100);
        }

        $this->executionStats['sentiment_history'] = $sentimentHistory;
        $this->persistMemory();
    }

    /**
     * Learn from escalation patterns.
     */
    private function learnFromEscalation(array $escalation, string $message, string $category): void
    {
        if (! $escalation['needs_escalation']) {
            return;
        }

        $patterns = $this->executionStats['escalation_patterns'] ?? [];

        // Extract key indicators from the message
        $indicators = $this->extractEscalationIndicators($message);

        foreach ($indicators as $indicator) {
            $found = false;
            foreach ($patterns as &$pattern) {
                if ($pattern['indicator'] === $indicator) {
                    $pattern['count'] = ($pattern['count'] ?? 0) + 1;
                    $found = true;
                    break;
                }
            }

            if (! $found) {
                $patterns[] = [
                    'indicator' => $indicator,
                    'count' => 1,
                    'category' => $category,
                ];
            }
        }

        // Keep top 20 patterns
        usort($patterns, fn ($a, $b) => ($b['count'] ?? 0) <=> ($a['count'] ?? 0));
        $this->executionStats['escalation_patterns'] = array_slice($patterns, 0, 20);
        $this->persistMemory();
    }

    /**
     * Extract escalation indicators from message.
     */
    private function extractEscalationIndicators(string $message): array
    {
        $indicators = [];
        $messageLower = strtolower($message);

        $urgentKeywords = [
            'urgent', 'asap', 'immediately', 'critical', 'emergency',
            'frustrated', 'angry', 'unacceptable', 'terrible', 'worst',
            'refund', 'cancel', 'lawyer', 'complaint', 'manager',
        ];

        foreach ($urgentKeywords as $keyword) {
            if (str_contains($messageLower, $keyword)) {
                $indicators[] = $keyword;
            }
        }

        return $indicators;
    }

    /**
     * Record resolution score and learn from it.
     */
    private function recordResolutionScore(string $taskType, float $resolutionScore, array $meta = []): void
    {
        $this->executionStats['resolution_scores'][] = $resolutionScore;

        // Track task-type specific resolution
        $taskResolution = $this->executionStats['task_resolution'] ?? [];
        $taskResolution[$taskType][] = $resolutionScore;
        $this->executionStats['task_resolution'] = $taskResolution;

        // Update resolution stats
        $stats = $this->executionStats['resolution_stats'] ?? [];
        $stats[$taskType] = [
            'avg' => $taskResolution[$taskType] ? array_sum($taskResolution[$taskType]) / count($taskResolution[$taskType]) : 0,
            'count' => count($taskResolution[$taskType] ?? []),
        ];
        $this->executionStats['resolution_stats'] = $stats;

        $this->persistMemory();
    }
}
