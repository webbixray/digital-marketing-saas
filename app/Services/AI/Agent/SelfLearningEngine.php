<?php

namespace App\Services\AI\Agent;

use App\Models\AgentFeedback;
use App\Services\AI\Gateway\AiGateway;
use App\Services\AI\Gateway\AiRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SelfLearningEngine
{
    /**
     * Record feedback on agent output
     */
    public function recordFeedback(
        string $agentName,
        string $taskId,
        int $rating,
        ?string $feedback = null,
        ?array $expectedOutput = null,
        ?array $actualOutput = null,
        ?array $context = null
    ): AgentFeedback {
        $record = AgentFeedback::create([
            'agent_name' => $agentName,
            'task_id' => $taskId,
            'rating' => $rating,
            'feedback' => $feedback,
            'expected_output' => $expectedOutput,
            'actual_output' => $actualOutput,
            'context' => $context,
        ]);

        // Trigger learning if enough feedback accumulated
        $this->learnFromFeedback($agentName);

        return $record;
    }

    /**
     * Learn from accumulated feedback for an agent
     */
    public function learnFromFeedback(string $agentName): void
    {
        $recentFeedback = AgentFeedback::forAgent($agentName)
            ->recent(7)
            ->get();

        if ($recentFeedback->count() < 10) {
            return;
        }

        // Identify patterns
        $patterns = $this->identifyPatterns($recentFeedback);

        // Generate improvements
        $improvements = $this->generateImprovements($patterns);

        // Store improvements
        $this->storeImprovements($agentName, $improvements);

        Log::info("SelfLearningEngine: learned from {$recentFeedback->count()} feedback items for agent [{$agentName}]");
    }

    /**
     * Identify patterns in feedback
     */
    private function identifyPatterns(Collection $feedback): array
    {
        $lowRatings = $feedback->filter(fn($f) => $f->isNegative());
        $highRatings = $feedback->filter(fn($f) => $f->isPositive());

        $patterns = [];

        // Common issues in low ratings
        if ($lowRatings->isNotEmpty()) {
            $patterns['common_issues'] = $this->extractCommonIssues($lowRatings);
        }

        // Success patterns in high ratings
        if ($highRatings->isNotEmpty()) {
            $patterns['success_patterns'] = $this->extractSuccessPatterns($highRatings);
        }

        // Average rating trend
        $patterns['average_rating'] = $feedback->avg('rating');
        $patterns['total_feedback'] = $feedback->count();

        return $patterns;
    }

    /**
     * Extract common issues from low ratings
     */
    private function extractCommonIssues(Collection $lowRatings): array
    {
        $issues = [];

        foreach ($lowRatings as $feedback) {
            if ($feedback->feedback) {
                $issues[] = $feedback->feedback;
            }
        }

        // Group similar issues
        return array_count_values($issues);
    }

    /**
     * Extract success patterns from high ratings
     */
    private function extractSuccessPatterns(Collection $highRatings): array
    {
        $patterns = [];

        foreach ($highRatings as $feedback) {
            if ($feedback->actual_output) {
                $patterns[] = $feedback->actual_output;
            }
        }

        return $patterns;
    }

    /**
     * Generate improvements based on patterns
     */
    private function generateImprovements(array $patterns): array
    {
        $improvements = [];

        // Analyze common issues
        if (isset($patterns['common_issues'])) {
            foreach ($patterns['common_issues'] as $issue => $count) {
                if ($count >= 3) {
                    $improvements[] = [
                        'type' => 'fix_issue',
                        'issue' => $issue,
                        'frequency' => $count,
                        'priority' => $count >= 5 ? 'high' : 'medium',
                    ];
                }
            }
        }

        // Analyze success patterns
        if (isset($patterns['success_patterns']) && count($patterns['success_patterns']) >= 3) {
            $improvements[] = [
                'type' => 'replicate_success',
                'patterns' => array_slice($patterns['success_patterns'], 0, 5),
                'priority' => 'medium',
            ];
        }

        return $improvements;
    }

    /**
     * Store improvements for agent
     */
    private function storeImprovements(string $agentName, array $improvements): void
    {
        // Store in cache for agent to use
        cache()->put(
            "agent_improvements_{$agentName}",
            $improvements,
            now()->addDays(7)
        );
    }

    /**
     * Get improvements for an agent
     */
    public function getImprovements(string $agentName): array
    {
        return cache()->get("agent_improvements_{$agentName}", []);
    }

    /**
     * Get learning stats for an agent
     */
    public function getStats(string $agentName): array
    {
        $feedback = AgentFeedback::forAgent($agentName);

        return [
            'total_feedback' => $feedback->count(),
            'average_rating' => $feedback->avg('rating') ?? 0,
            'positive_count' => $feedback->highRating()->count(),
            'negative_count' => $feedback->lowRating()->count(),
            'recent_feedback' => $feedback->recent(7)->count(),
            'improvements_count' => count($this->getImprovements($agentName)),
        ];
    }
}
