<?php

namespace App\Services\AI\Agent\Agents;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\SocialPost;
use App\Services\AI\Agent\AbstractAgent;
use App\Services\AI\Agent\AgentContext;
use App\Services\AI\Agent\AgentResult;
use App\Services\AI\Agent\AgentTask;
use App\Services\AI\Gateway\AiRequest;
use Illuminate\Support\Facades\Log;

class AbTestingAgent extends AbstractAgent
{
    protected string $name = 'ab_testing_agent';

    /**
     * @var array<string>
     */
    protected array $supportedTaskTypes = [
        'test_design',
        'result_analysis',
        'winner_select',
        'variant_generate',
    ];

    /**
     * Test outcome accuracy thresholds.
     */
    private const ACCURACY_THRESHOLD_HIGH = 0.85;

    private const ACCURACY_THRESHOLD_MEDIUM = 0.6;

    /**
     * Minimum sample size for statistical significance.
     */
    private const MIN_SAMPLE_SIZE = 100;

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
                'test_design' => $this->handleTestDesign($task, $agency, $context),
                'result_analysis' => $this->handleResultAnalysis($task, $agency, $context),
                'winner_select' => $this->handleWinnerSelect($task, $agency, $context),
                'variant_generate' => $this->handleVariantGenerate($task, $agency, $context),
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
            Log::error("AbTestingAgent execution failed: {$e->getMessage()}", [
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
     * {@inheritdoc}
     *
     * Success rate based on test outcome accuracy.
     */
    public function getSuccessRate(): float
    {
        $accuracyScores = $this->executionStats['test_accuracies'] ?? [];

        if (empty($accuracyScores)) {
            return 0.5;
        }

        $highAccuracy = count(array_filter($accuracyScores, fn ($s) => $s >= self::ACCURACY_THRESHOLD_HIGH));
        $mediumAccuracy = count(array_filter($accuracyScores, fn ($s) => $s >= self::ACCURACY_THRESHOLD_MEDIUM));

        $weightedSuccesses = ($highAccuracy * 2) + $mediumAccuracy;

        return min($weightedSuccesses / (count($accuracyScores) * 2), 1.0);
    }

    /**
     * Get test variation performance insights.
     *
     * @return array<string, array>
     */
    public function getVariationPerformance(): array
    {
        return $this->executionStats['variation_performance'] ?? [];
    }

    /**
     * Get test design patterns that produce best results.
     *
     * @return array<string, array>
     */
    public function getTestPatterns(): array
    {
        return $this->executionStats['test_patterns'] ?? [];
    }

    /**
     * Handle A/B test design.
     */
    private function handleTestDesign(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $testType = $task->data['test_type'] ?? 'creative';
        $element = $task->data['element'] ?? 'headline';
        $campaignId = $task->data['campaign_id'] ?? null;
        $hypothesis = $task->data['hypothesis'] ?? '';
        $primaryMetric = $task->data['primary_metric'] ?? 'click_rate';
        $confidenceLevel = $task->data['confidence_level'] ?? 0.95;

        // Get past successful test patterns
        $patterns = $this->getTestPatterns();
        $successfulPatterns = array_filter($patterns, fn ($p) => ($p['success_rate'] ?? 0) >= 0.6);

        // Get campaign data for context
        $campaignData = $campaignId ? $this->getCampaignTestData($agency, $campaignId) : null;

        $prompt = "Design a rigorous A/B test for the following scenario:\n\n";
        $prompt .= "Test type: {$testType}\n";
        $prompt .= "Element to test: {$element}\n";
        $prompt .= "Primary metric: {$primaryMetric}\n";
        $prompt .= 'Confidence level: '.($confidenceLevel * 100)."%\n";

        if ($hypothesis) {
            $prompt .= "Hypothesis: {$hypothesis}\n";
        }

        if ($campaignData) {
            $prompt .= "\nCampaign context:\n";
            $prompt .= "- Campaign: {$campaignData['name']}\n";
            $prompt .= "- Current engagement rate: {$campaignData['engagement_rate']}\n";
            $prompt .= "- Total posts: {$campaignData['posts_count']}\n";
        }

        if (! empty($successfulPatterns)) {
            $prompt .= "\nBased on past successful tests:\n";
            foreach (array_slice($successfulPatterns, 0, 3) as $pattern) {
                $prompt .= "- {$pattern['description']}: {$pattern['success_rate']}% success\n";
            }
        }

        $prompt .= "\nProvide:\n";
        $prompt .= "1. Clear hypothesis statement\n";
        $prompt .= "2. Control variant (A) definition\n";
        $prompt .= "3. Treatment variant (B) definition\n";
        $prompt .= '4. Sample size calculation (minimum '.self::MIN_SAMPLE_SIZE." per variant)\n";
        $prompt .= "5. Test duration recommendation\n";
        $prompt .= "6. Success metrics and how to measure them\n";
        $prompt .= "7. Statistical test to use (chi-square, t-test, etc.)\n";
        $prompt .= "8. Potential confounding variables to control\n";
        $prompt .= "9. Implementation plan\n";
        $prompt .= '10. Risk mitigation strategies';

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a conversion rate optimization expert. You design rigorous A/B tests that produce statistically significant, actionable results. Always prioritize statistical validity.'
        );

        $response = $this->callAi($request, $agency);

        $accuracyScore = $this->calculateTestDesignScore($response->content);

        $meta = [
            'task_type' => 'test_design',
            'test_type' => $testType,
            'element' => $element,
            'campaign_id' => $campaignId,
            'primary_metric' => $primaryMetric,
            'confidence_level' => $confidenceLevel,
            'accuracy_score' => $accuracyScore,
        ];

        $this->recordTestAccuracy('test_design', $accuracyScore, $meta);
        $this->learnTestPattern($testType, $element, $accuracyScore);

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
     * Handle A/B test result analysis.
     */
    private function handleResultAnalysis(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $testId = $task->data['test_id'] ?? null;
        $variantAData = $task->data['variant_a'] ?? [];
        $variantBData = $task->data['variant_b'] ?? [];
        $metric = $task->data['metric'] ?? 'conversion_rate';

        if (empty($variantAData) || empty($variantBData)) {
            return AgentResult::failure(
                taskId: $task->id,
                agentName: $this->name,
                error: 'Both variant A and variant B data are required for analysis'
            );
        }

        // Calculate basic statistics
        $statsA = $this->calculateVariantStats($variantAData);
        $statsB = $this->calculateVariantStats($variantBData);

        // Perform statistical significance test
        $significance = $this->calculateStatisticalSignificance($statsA, $statsB);

        $prompt = "Analyze the following A/B test results:\n\n";
        $prompt .= "Metric: {$metric}\n\n";

        $prompt .= "Variant A (Control):\n";
        $prompt .= "- Sample size: {$statsA['sample_size']}\n";
        $prompt .= "- Conversions: {$statsA['conversions']}\n";
        $prompt .= '- Conversion rate: '.round($statsA['rate'] * 100, 2)."%\n";
        $prompt .= '- Standard deviation: '.round($statsA['std_dev'], 4)."\n\n";

        $prompt .= "Variant B (Treatment):\n";
        $prompt .= "- Sample size: {$statsB['sample_size']}\n";
        $prompt .= "- Conversions: {$statsB['conversions']}\n";
        $prompt .= '- Conversion rate: '.round($statsB['rate'] * 100, 2)."%\n";
        $prompt .= '- Standard deviation: '.round($statsB['std_dev'], 4)."\n\n";

        $prompt .= "Statistical Analysis:\n";
        $prompt .= '- Z-score: '.round($significance['z_score'], 4)."\n";
        $prompt .= '- P-value: '.round($significance['p_value'], 4)."\n";
        $prompt .= '- Confidence interval: ['.round($significance['ci_lower'] * 100, 2).'%, '.round($significance['ci_upper'] * 100, 2)."%]\n";
        $prompt .= '- Statistically significant: '.($significance['is_significant'] ? 'Yes' : 'No')."\n";
        $prompt .= '- Relative lift: '.round($significance['relative_lift'] * 100, 2)."%\n\n";

        $prompt .= "Provide:\n";
        $prompt .= "1. Summary of findings\n";
        $prompt .= "2. Statistical significance assessment\n";
        $prompt .= "3. Practical significance evaluation\n";
        $prompt .= "4. Confidence level interpretation\n";
        $prompt .= "5. Potential sources of bias\n";
        $prompt .= "6. Recommendation (implement B, keep A, or continue testing)\n";
        $prompt .= "7. Expected business impact\n";
        $prompt .= '8. Follow-up test suggestions';

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a statistical analysis expert specializing in A/B testing. You interpret test results with rigorous statistical methodology and provide clear, actionable recommendations.'
        );

        $response = $this->callAi($request, $agency);

        $accuracyScore = $significance['is_significant'] ? 0.8 : 0.5;

        $meta = [
            'task_type' => 'result_analysis',
            'test_id' => $testId,
            'metric' => $metric,
            'variant_a_rate' => $statsA['rate'],
            'variant_b_rate' => $statsB['rate'],
            'p_value' => $significance['p_value'],
            'is_significant' => $significance['is_significant'],
            'relative_lift' => $significance['relative_lift'],
            'accuracy_score' => $accuracyScore,
        ];

        $this->recordTestAccuracy('result_analysis', $accuracyScore, $meta);
        $this->recordVariationPerformance($metric, $statsA, $statsB, $significance);

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
     * Handle winner selection from test results.
     */
    private function handleWinnerSelect(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $testResults = $task->data['test_results'] ?? [];
        $selectionCriteria = $task->data['criteria'] ?? ['statistical_significance', 'practical_significance', 'business_impact'];

        if (empty($testResults)) {
            return AgentResult::failure(
                taskId: $task->id,
                agentName: $this->name,
                error: 'Test results are required for winner selection'
            );
        }

        $prompt = "Select the winning variant from the following A/B test results:\n\n";
        $prompt .= 'Selection criteria: '.implode(', ', $selectionCriteria)."\n\n";

        foreach ($testResults as $result) {
            $prompt .= "Variant: {$result['variant']}\n";
            $prompt .= "- Sample size: {$result['sample_size']}\n";
            $prompt .= '- Conversion rate: '.round($result['conversion_rate'] * 100, 2)."%\n";
            $prompt .= "- P-value: {$result['p_value']}\n";
            $prompt .= '- Revenue impact: $'.number_format($result['revenue_impact'] ?? 0, 2)."\n";
            $prompt .= '- Engagement score: '.round($result['engagement_score'] ?? 0, 2)."\n\n";
        }

        $prompt .= "Provide:\n";
        $prompt .= "1. Clear winner declaration\n";
        $prompt .= "2. Confidence level in the decision\n";
        $prompt .= "3. Expected impact of implementing the winner\n";
        $prompt .= "4. Risk assessment\n";
        $prompt .= "5. Implementation timeline\n";
        $prompt .= "6. Monitoring plan post-implementation\n";
        $prompt .= '7. Conditions that would invalidate the result';

        $request = AiRequest::analysis(
            prompt: $prompt,
            systemPrompt: 'You are a decision science expert. You evaluate A/B test results against multiple criteria and make clear, defensible winner selections.'
        );

        $response = $this->callAi($request, $agency);

        $accuracyScore = 0.7;

        $meta = [
            'task_type' => 'winner_select',
            'variants_evaluated' => count($testResults),
            'criteria' => $selectionCriteria,
            'accuracy_score' => $accuracyScore,
        ];

        $this->recordTestAccuracy('winner_select', $accuracyScore, $meta);

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
     * Handle variant generation for A/B tests.
     */
    private function handleVariantGenerate(AgentTask $task, Agency $agency, AgentContext $context): AgentResult
    {
        $baseContent = $task->data['base_content'] ?? '';
        $element = $task->data['element'] ?? 'headline';
        $variantCount = $task->data['variant_count'] ?? 2;
        $testGoal = $task->data['goal'] ?? 'increase_engagement';
        $platform = $task->data['platform'] ?? 'instagram';

        // Get learned variation performance
        $variationPerf = $this->getVariationPerformance();
        $elementPerf = $variationPerf[$element] ?? [];

        $prompt = "Generate {$variantCount} testable variants for A/B testing:\n\n";
        $prompt .= "Element: {$element}\n";
        $prompt .= "Platform: {$platform}\n";
        $prompt .= "Goal: {$testGoal}\n";
        $prompt .= "Base content: {$baseContent}\n\n";

        if (! empty($elementPerf)) {
            $prompt .= "Based on past test results for this element:\n";
            $prompt .= "- Best performing approach: {$elementPerf['best_approach']}\n";
            $prompt .= '- Average lift: '.round($elementPerf['avg_lift'] * 100, 1)."%\n\n";
        }

        $prompt .= "Generate variants that:\n";
        $prompt .= "1. Test a single variable (isolated change)\n";
        $prompt .= "2. Are meaningfully different from each other\n";
        $prompt .= "3. Are realistic and implementable\n";
        $prompt .= "4. Have clear hypotheses for why they might perform better\n\n";

        $prompt .= "For each variant provide:\n";
        $prompt .= "- Variant label (A, B, C, etc.)\n";
        $prompt .= "- The specific content/copy\n";
        $prompt .= "- Hypothesis for why it might perform better\n";
        $prompt .= "- Expected direction of change\n";
        $prompt .= '- Any risks or considerations';

        $request = AiRequest::creative(
            prompt: $prompt,
            systemPrompt: 'You are a creative testing specialist. You generate compelling A/B test variants that isolate variables and test clear hypotheses while maintaining brand consistency.'
        );

        $response = $this->callAi($request, $agency);

        $accuracyScore = 0.65;

        $meta = [
            'task_type' => 'variant_generate',
            'element' => $element,
            'platform' => $platform,
            'variant_count' => $variantCount,
            'goal' => $testGoal,
            'accuracy_score' => $accuracyScore,
        ];

        $this->recordTestAccuracy('variant_generate', $accuracyScore, $meta);

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
     * Get campaign test data.
     */
    private function getCampaignTestData(Agency $agency, int $campaignId): ?array
    {
        $campaign = Campaign::where('agency_id', $agency->id)->find($campaignId);

        if (! $campaign) {
            return null;
        }

        $posts = SocialPost::where('agency_id', $agency->id)
            ->whereHas('campaigns', fn ($q) => $q->where('campaign_id', $campaign->id))
            ->get();

        return [
            'name' => $campaign->name,
            'engagement_rate' => $campaign->engagement_rate,
            'posts_count' => $posts->count(),
            'total_views' => $posts->sum('views_count'),
            'total_clicks' => $posts->sum('clicks_count'),
        ];
    }

    /**
     * Calculate variant statistics.
     */
    private function calculateVariantStats(array $data): array
    {
        $sampleSize = $data['sample_size'] ?? count($data['observations'] ?? []);
        $conversions = $data['conversions'] ?? 0;
        $rate = $sampleSize > 0 ? $conversions / $sampleSize : 0;

        // Calculate standard deviation for binomial distribution
        $stdDev = sqrt($rate * (1 - $rate) / max($sampleSize, 1));

        return [
            'sample_size' => $sampleSize,
            'conversions' => $conversions,
            'rate' => $rate,
            'std_dev' => $stdDev,
        ];
    }

    /**
     * Calculate statistical significance using two-proportion z-test.
     */
    private function calculateStatisticalSignificance(array $statsA, array $statsB): array
    {
        $p1 = $statsA['rate'];
        $p2 = $statsB['rate'];
        $n1 = max($statsA['sample_size'], 1);
        $n2 = max($statsB['sample_size'], 1);

        // Pooled proportion
        $pPool = ($statsA['conversions'] + $statsB['conversions']) / ($n1 + $n2);

        // Standard error
        $se = sqrt($pPool * (1 - $pPool) * ((1 / $n1) + (1 / $n2)));

        // Z-score
        $zScore = $se > 0 ? ($p2 - $p1) / $se : 0;

        // P-value (two-tailed)
        $pValue = 2 * (1 - $this->normalCdf(abs($zScore)));

        // Confidence interval for difference
        $diff = $p2 - $p1;
        $marginOfError = 1.96 * $se; // 95% CI

        // Relative lift
        $relativeLift = $p1 > 0 ? ($p2 - $p1) / $p1 : 0;

        return [
            'z_score' => $zScore,
            'p_value' => $pValue,
            'is_significant' => $pValue < 0.05,
            'ci_lower' => $diff - $marginOfError,
            'ci_upper' => $diff + $marginOfError,
            'relative_lift' => $relativeLift,
        ];
    }

    /**
     * Normal cumulative distribution function approximation.
     */
    private function normalCdf(float $x): float
    {
        // Abramowitz and Stegun approximation
        $a1 = 0.254829592;
        $a2 = -0.284496736;
        $a3 = 1.421413741;
        $a4 = -1.453152027;
        $a5 = 1.061405429;
        $p = 0.3275911;

        $sign = $x < 0 ? -1 : 1;
        $x = abs($x) / sqrt(2);

        $t = 1.0 / (1.0 + $p * $x);
        $y = 1.0 - ((((($a5 * $t + $a4) * $t) + $a3) * $t + $a2) * $t + $a1) * $t * exp(-$x * $x);

        return 0.5 * (1.0 + $sign * $y);
    }

    /**
     * Calculate test design quality score.
     */
    private function calculateTestDesignScore(string $content): float
    {
        $score = 0.5;

        // Designs with sample size calculations score higher
        if (preg_match('/sample size|n\s*=\s*\d+|minimum.*\d+/i', $content)) {
            $score += 0.12;
        }

        // Designs with clear hypotheses score higher
        if (preg_match('/hypothesis|we expect|we predict|we believe/i', $content)) {
            $score += 0.1;
        }

        // Designs mentioning statistical tests score higher
        if (preg_match('/chi-square|t-test|z-test|significance|confidence interval/i', $content)) {
            $score += 0.1;
        }

        // Designs with duration recommendations score higher
        if (preg_match('/day|week|duration|run.*test/i', $content)) {
            $score += 0.08;
        }

        // Designs with risk assessment score higher
        if (preg_match('/risk|confound|bias|limitation/i', $content)) {
            $score += 0.05;
        }

        return min($score, 1.0);
    }

    /**
     * Record test accuracy for learning.
     */
    private function recordTestAccuracy(string $taskType, float $score, array $meta = []): void
    {
        $this->executionStats['test_accuracies'][] = $score;

        $taskAccuracies = $this->executionStats['task_accuracies'] ?? [];
        $taskAccuracies[$taskType][] = $score;
        $this->executionStats['task_accuracies'] = $taskAccuracies;

        $this->persistMemory();
    }

    /**
     * Learn test patterns from successful designs.
     */
    private function learnTestPattern(string $testType, string $element, float $score): void
    {
        $patterns = $this->executionStats['test_patterns'] ?? [];

        $key = "{$testType}_{$element}";
        $existing = $patterns[$key] ?? ['success_sum' => 0, 'count' => 0, 'success_rate' => 0];

        $existing['success_sum'] += $score;
        $existing['count']++;
        $existing['success_rate'] = $existing['success_sum'] / $existing['count'];
        $existing['description'] = "{$testType} test on {$element}";
        $existing['last_updated'] = now()->toIso8601String();

        $patterns[$key] = $existing;

        $this->executionStats['test_patterns'] = $patterns;
        $this->persistMemory();
    }

    /**
     * Record variation performance for learning.
     */
    private function recordVariationPerformance(string $metric, array $statsA, array $statsB, array $significance): void
    {
        $perf = $this->executionStats['variation_performance'] ?? [];

        $elementPerf = $perf[$metric] ?? ['lifts' => [], 'avg_lift' => 0, 'best_approach' => 'unknown'];

        $elementPerf['lifts'][] = $significance['relative_lift'];
        $elementPerf['avg_lift'] = array_sum($elementPerf['lifts']) / count($elementPerf['lifts']);

        if ($significance['is_significant'] && $significance['relative_lift'] > 0) {
            $elementPerf['best_approach'] = 'variant_b_wins';
        } elseif ($significance['is_significant'] && $significance['relative_lift'] < 0) {
            $elementPerf['best_approach'] = 'control_a_wins';
        }

        $perf[$metric] = $elementPerf;
        $this->executionStats['variation_performance'] = $perf;
        $this->persistMemory();
    }
}
