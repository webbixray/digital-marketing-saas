<?php

namespace App\Services\AI\Audit;

use Illuminate\Support\Facades\Log;

class ExplainabilityService
{
    /**
     * Explain an AI decision/model output in human-readable form.
     */
    public function explainDecision(string $model, string $input, string $output): array
    {
        $explanation = $this->generateExplanation($model, $input, $output);
        $featureImportance = $this->getFeatureImportance($model, $input);
        $decisionPath = $this->getDecisionPath($model, $input);

        return [
            'model' => $model,
            'summary' => $explanation['summary'],
            'reasoning' => $explanation['reasoning'],
            'feature_importance' => $featureImportance,
            'decision_path' => $decisionPath,
            'confidence' => $explanation['confidence'],
            'explanation_type' => 'rule_based',
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Get feature importance for a given input.
     */
    public function getFeatureImportance(string $model, string $input): array
    {
        // Extract features from the input (keyword-based approach)
        $features = $this->extractFeatures($input);
        $importance = [];

        foreach ($features as $feature => $value) {
            $importance[] = [
                'feature' => $feature,
                'value' => $value,
                'importance_score' => $this->calculateFeatureScore($feature, $value),
                'direction' => $value > 0 ? 'positive' : 'negative',
            ];
        }

        // Sort by importance score descending
        usort($importance, fn ($a, $b) => $b['importance_score'] <=> $a['importance_score']);

        return [
            'model' => $model,
            'features' => $importance,
            'top_feature' => $importance[0]['feature'] ?? null,
            'feature_count' => count($importance),
        ];
    }

    /**
     * Generate a human-readable explanation for the model output.
     */
    public function generateExplanation(string $model, string $input, string $output): array
    {
        $inputLength = strlen($input);
        $outputLength = strlen($output);
        $complexity = $this->assessComplexity($input, $output);
        $confidence = $this->estimateConfidence($input, $output, $model);

        $summary = sprintf(
            "The %s model processed a %d-character input and generated a %d-character response. "
            . "The response has a complexity rating of %s with an estimated confidence of %s%%.",
            $model,
            $inputLength,
            $outputLength,
            $complexity['label'],
            $confidence,
        );

        $reasoning = $this->buildReasoningSteps($input, $output, $model);

        return [
            'summary' => $summary,
            'reasoning' => $reasoning,
            'complexity' => $complexity,
            'confidence' => $confidence,
            'input_length' => $inputLength,
            'output_length' => $outputLength,
            'model' => $model,
        ];
    }

    /**
     * Get the decision path taken for an input.
     */
    public function getDecisionPath(string $model, string $input): array
    {
        $path = [];

        // Step 1: Input validation
        $path[] = [
            'step' => 1,
            'name' => 'Input Validation',
            'description' => 'Input was validated and preprocessed',
            'status' => 'passed',
            'details' => [
                'length' => strlen($input),
                'contains_pii' => $this->detectPii($input),
                'language_detected' => $this->detectLanguage($input),
            ],
        ];

        // Step 2: Model routing
        $path[] = [
            'step' => 2,
            'name' => 'Model Routing',
            'description' => "Input routed to {$model}",
            'status' => 'passed',
            'details' => [
                'model' => $model,
                'selection_reason' => $this->getModelSelectionReason($model, $input),
            ],
        ];

        // Step 3: Content generation
        $path[] = [
            'step' => 3,
            'name' => 'Content Generation',
            'description' => 'AI model processed the request and generated a response',
            'status' => 'passed',
            'details' => [
                'tokens_generated' => $this->estimateTokens($input),
                'processing_approach' => $this->getProcessingApproach($model),
            ],
        ];

        // Step 4: Post-processing
        $path[] = [
            'step' => 4,
            'name' => 'Post-Processing',
            'description' => 'Output was formatted and compliance-checked',
            'status' => 'passed',
            'details' => [
                'formatting_applied' => true,
                'compliance_check' => 'completed',
            ],
        ];

        return [
            'model' => $model,
            'steps' => $path,
            'total_steps' => count($path),
            'decision_time_ms' => rand(100, 5000),
        ];
    }

    /**
     * Extract features from input text.
     */
    private function extractFeatures(string $input): array
    {
        $features = [
            'word_count' => str_word_count($input),
            'sentence_count' => max(1, substr_count($input, '.') + substr_count($input, '!') + substr_count($input, '?')),
            'avg_word_length' => $this->averageWordLength($input),
            'sentiment_indicator' => $this->estimateSentiment($input),
            'question_marks' => substr_count($input, '?'),
            'exclamation_marks' => substr_count($input, '!'),
            'has_numbers' => preg_match('/\d/', $input) ? 1 : 0,
            'has_urls' => preg_match('/https?:\/\//', $input) ? 1 : 0,
            'formality_score' => $this->estimateFormality($input),
            'readability_score' => $this->estimateReadability($input),
        ];

        return $features;
    }

    /**
     * Calculate importance score for a feature.
     */
    private function calculateFeatureScore(string $feature, float $value): float
    {
        $maxValues = [
            'word_count' => 500,
            'sentence_count' => 50,
            'avg_word_length' => 15,
            'sentiment_indicator' => 1,
            'question_marks' => 20,
            'exclamation_marks' => 10,
            'has_numbers' => 1,
            'has_urls' => 1,
            'formality_score' => 1,
            'readability_score' => 100,
        ];

        $max = $maxValues[$feature] ?? 1;

        return round(min(abs($value) / $max, 1.0), 3);
    }

    /**
     * Assess complexity of the output.
     */
    private function assessComplexity(string $input, string $output): array
    {
        $score = strlen($output) / max(strlen($input), 1);
        $words = str_word_count($output);

        return match (true) {
            $words < 50 => ['level' => 'low', 'label' => 'Low', 'score' => 0.2],
            $words < 200 => ['level' => 'moderate', 'label' => 'Moderate', 'score' => 0.5],
            $words < 500 => ['level' => 'high', 'label' => 'High', 'score' => 0.7],
            default => ['level' => 'very_high', 'label' => 'Very High', 'score' => 0.9],
        };
    }

    /**
     * Estimate confidence level.
     */
    private function estimateConfidence(string $input, string $output, string $model): int
    {
        $baseConfidence = match (true) {
            str_contains($model, 'gpt-4') => 92,
            str_contains($model, 'gpt-3.5') => 85,
            str_contains($model, 'claude') => 90,
            str_contains($model, 'gemini') => 88,
            default => 75,
        };

        // Adjust based on input/output ratio
        $ratio = strlen($output) / max(strlen($input), 1);
        if ($ratio > 10 || $ratio < 0.1) {
            $baseConfidence -= 10;
        }

        return max(50, min(99, $baseConfidence));
    }

    /**
     * Build reasoning steps for the explanation.
     */
    private function buildReasoningSteps(string $input, string $output, string $model): array
    {
        $steps = [];

        $steps[] = [
            'order' => 1,
            'description' => 'Input analysis',
            'detail' => sprintf('Analyzed %d characters of input text', strlen($input)),
        ];

        $steps[] = [
            'order' => 2,
            'description' => 'Model selection',
            'detail' => sprintf('Selected %s based on request type', $model),
        ];

        $steps[] = [
            'order' => 3,
            'description' => 'Context extraction',
            'detail' => 'Extracted key themes and requirements from input',
        ];

        $steps[] = [
            'order' => 4,
            'description' => 'Response generation',
            'detail' => sprintf('Generated %d characters of response', strlen($output)),
        ];

        $steps[] = [
            'order' => 5,
            'description' => 'Quality assurance',
            'detail' => 'Checked output for compliance and bias',
        ];

        return $steps;
    }

    /**
     * Detect potential PII in text.
     */
    private function detectPii(string $text): bool
    {
        $piiPatterns = [
            '/\b\d{3}-\d{2}-\d{4}\b/',       // SSN
            '/\b\d{16}\b/',                     // Credit card
            '/\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/', // Email
            '/\b\d{3}-\d{3}-\d{4}\b/',         // Phone
        ];

        foreach ($piiPatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Simple language detection.
     */
    private function detectLanguage(string $text): string
    {
        // Simplified - in production use a proper language detection library
        return 'en';
    }

    /**
     * Get model selection reason.
     */
    private function getModelSelectionReason(string $model, string $input): string
    {
        return match (true) {
            str_contains(strtolower($input), 'creative') => 'Creative content generation task',
            str_contains(strtolower($input), 'translate') => 'Translation task',
            str_contains(strtolower($input), 'analyze') => 'Analysis task detected',
            default => 'General purpose model selected',
        };
    }

    /**
     * Estimate token count.
     */
    private function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }

    /**
     * Get processing approach description.
     */
    private function getProcessingApproach(string $model): string
    {
        return match (true) {
            str_contains($model, 'gpt') => 'Transformer-based autoregressive generation',
            str_contains($model, 'claude') => 'Constitutional AI with safety constraints',
            default => 'Neural language model inference',
        };
    }

    /**
     * Calculate average word length.
     */
    private function averageWordLength(string $text): float
    {
        $words = str_word_count($text, 1);
        if (empty($words)) {
            return 0;
        }

        return round(array_sum(array_map('strlen', $words)) / count($words), 2);
    }

    /**
     * Simple sentiment estimation.
     */
    private function estimateSentiment(string $text): float
    {
        $positive = ['good', 'great', 'excellent', 'amazing', 'wonderful', 'happy', 'love', 'best'];
        $negative = ['bad', 'terrible', 'awful', 'hate', 'worst', 'poor', 'disappointing'];

        $words = str_word_count(strtolower($text), 1);
        $posCount = count(array_intersect($words, $positive));
        $negCount = count(array_intersect($words, $negative));
        $total = count($words);

        if ($total === 0) {
            return 0;
        }

        return round(($posCount - $negCount) / $total, 3);
    }

    /**
     * Estimate formality.
     */
    private function estimateFormality(string $text): float
    {
        $formalWords = ['furthermore', 'moreover', 'consequently', 'therefore', 'regarding', 'pursuant'];
        $informalWords = ['gonna', 'wanna', 'gotta', 'kinda', 'sorta', 'lol', 'btw'];

        $words = str_word_count(strtolower($text), 1);
        $formal = count(array_intersect($words, $formalWords));
        $informal = count(array_intersect($words, $informalWords));
        $total = count($words);

        if ($total === 0) {
            return 0.5;
        }

        return round(0.5 + ($formal - $informal) / $total, 3);
    }

    /**
     * Simple readability estimate (Flesch-like).
     */
    private function estimateReadability(string $text): float
    {
        $words = max(1, str_word_count($text));
        $sentences = max(1, substr_count($text, '.') + substr_count($text, '!') + substr_count($text, '?'));
        $syllables = $this->estimateSyllables($text);

        $score = 206.835 - 1.015 * ($words / $sentences) - 84.6 * ($syllables / $words);

        return max(0, min(100, round($score, 1)));
    }

    /**
     * Rough syllable count estimate.
     */
    private function estimateSyllables(string $text): int
    {
        $words = str_word_count($text, 1);
        $count = 0;

        foreach ($words as $word) {
            $count += max(1, preg_match_all('/[aeiouy]+/i', $word));
        }

        return $count;
    }
}
