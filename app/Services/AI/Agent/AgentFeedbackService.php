<?php

namespace App\Services\AI\Agent;

use App\Models\AgentLearningReport;
use App\Models\AgentPerformanceLog;
use Illuminate\Support\Facades\Log;

class AgentFeedbackService
{
    /**
     * Record a prediction vs actual outcome comparison for an agent.
     * This is the core of the real feedback loop.
     *
     * @param  string  $agentName  The agent that made the prediction
     * @param  string  $taskType  The type of task performed
     * @param  array  $prediction  The predicted values (expected outcomes)
     * @param  array  $actualOutcome  The actual measured outcomes
     * @param  int  $agencyId  The agency context for this operation
     */
    public function recordOutcome(
        string $agentName,
        string $taskType,
        array $prediction,
        array $actualOutcome,
        int $agencyId
    ): void {
        $accuracy = $this->calculateAccuracy($prediction, $actualOutcome);

        // Store the comparison in performance logs
        AgentPerformanceLog::create([
            'agent_name' => $agentName,
            'agent_category' => $this->getAgentCategory($agentName),
            'metric_type' => 'prediction_accuracy',
            'metric_value' => $accuracy,
            'parameters_used' => [
                'prediction' => $prediction,
                'actual' => $actualOutcome,
                'agency_id' => $agencyId,
            ],
            'metadata' => [
                'task_type' => $taskType,
                'delta' => $this->computeDelta($prediction, $actualOutcome),
            ],
            'recorded_at' => now(),
        ]);

        // Update learned parameters based on accuracy
        $this->updateAgentParameters($agentName, $accuracy);

        Log::info("AgentFeedbackService: recorded outcome for [{$agentName}], task [{$taskType}], accuracy={$accuracy}");
    }

    /**
     * Calculate accuracy between predicted and actual outcomes.
     * Returns a float between 0.0 (completely wrong) and 1.0 (perfect match).
     *
     * Uses normalized mean absolute error for numeric fields,
     * exact match ratio for boolean/string fields.
     *
     * @param  array  $prediction  Predicted values
     * @param  array  $actual  Actual measured values
     * @return float Accuracy score 0.0 to 1.0
     */
    public function calculateAccuracy(array $prediction, array $actual): float
    {
        if (empty($prediction) || empty($actual)) {
            return 0.0;
        }

        $scores = [];
        $commonKeys = array_intersect_key($prediction, $actual);

        if (empty($commonKeys)) {
            return 0.0;
        }

        foreach ($commonKeys as $key => $predictedValue) {
            $actualValue = $actual[$key];

            // Handle numeric comparisons
            if (is_numeric($predictedValue) && is_numeric($actualValue)) {
                $predictedNum = (float) $predictedValue;
                $actualNum = (float) $actualValue;

                // Avoid division by zero
                $maxVal = max(abs($predictedNum), abs($actualNum), 1.0);
                $error = abs($predictedNum - $actualNum) / $maxVal;
                $scores[] = max(0.0, 1.0 - $error);
            }
            // Handle boolean comparisons
            elseif (is_bool($predictedValue) || is_bool($actualValue)) {
                $scores[] = ((bool) $predictedValue === (bool) $actualValue) ? 1.0 : 0.0;
            }
            // Handle string/exact match
            else {
                $scores[] = (string) $predictedValue === (string) $actualValue ? 1.0 : 0.0;
            }
        }

        // Average all individual accuracy scores
        return count($scores) > 0 ? array_sum($scores) / count($scores) : 0.0;
    }

    /**
     * Update learned parameters for an agent based on recent accuracy trends.
     * Stores persistent tuning recommendations in agent_learning_reports.
     *
     * @param  string  $agentName  The agent name
     * @param  float  $accuracy  Current accuracy score
     */
    public function updateAgentParameters(string $agentName, float $accuracy): void
    {
        // Get recent accuracy trend
        $recentAccuracies = AgentPerformanceLog::forAgent($agentName)
            ->byMetric('prediction_accuracy')
            ->recent(14)
            ->orderBy('recorded_at', 'desc')
            ->limit(20)
            ->pluck('metric_value')
            ->toArray();

        if (count($recentAccuracies) < 3) {
            // Not enough data to update parameters
            Log::debug("AgentFeedbackService: insufficient data for [{$agentName}] parameter update.");

            return;
        }

        $avgAccuracy = array_sum($recentAccuracies) / count($recentAccuracies);
        $trend = $this->calculateAccuracyTrend($recentAccuracies);

        // Determine parameter adjustments based on performance
        $adjustments = $this->deriveParameterAdjustments($avgAccuracy, $trend);

        if (! empty($adjustments)) {
            AgentLearningReport::create([
                'agent_name' => $agentName,
                'improvement_type' => 'parameter_update',
                'description' => sprintf(
                    'Auto-parameter update: avg_accuracy=%.2f, trend=%s. Adjustments recommended.',
                    $avgAccuracy,
                    $trend
                ),
                'changes' => $adjustments,
                'status' => 'pending',
            ]);
        }

        Log::info("AgentFeedbackService: updated parameters for [{$agentName}], avg_accuracy={$avgAccuracy}, trend={$trend}");
    }

    /**
     * Compute delta between prediction and actual for diagnostic logging.
     */
    private function computeDelta(array $prediction, array $actual): array
    {
        $delta = [];
        foreach (array_intersect_key($prediction, $actual) as $key => $predVal) {
            if (is_numeric($predVal) && is_numeric($actual[$key])) {
                $delta[$key] = round((float) $actual[$key] - (float) $predVal, 4);
            } else {
                $delta[$key] = (string) $predVal !== (string) $actual[$key] ? 'mismatch' : 'match';
            }
        }

        return $delta;
    }

    /**
     * Get the agent category for an agent by name.
     */
    private function getAgentCategory(string $agentName): string
    {
        $categories = [
            'content' => 'content_generation',
            'social' => 'social_media',
            'campaign' => 'campaign_management',
            'security' => 'security',
            'analytics' => 'analytics',
            'report' => 'reporting',
            'support' => 'customer_support',
            'team' => 'team_management',
            'ab_testing' => 'experimentation',
        ];

        foreach ($categories as $prefix => $category) {
            if (str_starts_with($agentName, $prefix)) {
                return $category;
            }
        }

        return 'general';
    }

    /**
     * Calculate accuracy trend direction from recent values.
     */
    private function calculateAccuracyTrend(array $values): string
    {
        $values = array_filter($values, fn ($v) => is_numeric($v));
        if (count($values) < 3) {
            return 'insufficient_data';
        }

        $firstHalf = array_slice($values, 0, (int) ceil(count($values) / 2));
        $secondHalf = array_slice($values, (int) ceil(count($values) / 2));

        $firstAvg = array_sum($firstHalf) / count($firstHalf);
        $secondAvg = array_sum($secondHalf) / count($secondHalf);

        if ($firstAvg == 0) {
            return $secondAvg > 0 ? 'improving' : 'stable';
        }

        $change = (($secondAvg - $firstAvg) / $firstAvg) * 100;

        if ($change > 5) {
            return 'improving';
        }
        if ($change < -5) {
            return 'declining';
        }

        return 'stable';
    }

    /**
     * Derive parameter adjustment recommendations based on performance.
     */
    private function deriveParameterAdjustments(float $avgAccuracy, string $trend): array
    {
        $adjustments = [];

        if ($avgAccuracy < 0.5) {
            $adjustments['prompt_complexity'] = 'reduce';
            $adjustments['model_tier'] = 'upgrade';
            $adjustments['temperature'] = 0.3;
            $adjustments['priority'] = 'critical';
        } elseif ($avgAccuracy < 0.7) {
            $adjustments['prompt_complexity'] = 'refine';
            $adjustments['few_shot_examples'] = true;
            $adjustments['temperature'] = 0.5;
            $adjustments['priority'] = 'high';
        } elseif ($avgAccuracy < 0.85 && $trend === 'declining') {
            $adjustments['prompt_complexity'] = 'review';
            $adjustments['temperature'] = 0.6;
            $adjustments['priority'] = 'medium';
        }

        if ($trend === 'declining') {
            $adjustments['alert'] = 'Performance declining. Consider reviewing training data and prompt strategy.';
        }

        return $adjustments;
    }
}
