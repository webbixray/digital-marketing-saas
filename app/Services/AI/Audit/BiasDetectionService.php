<?php

namespace App\Services\AI\Audit;

use Illuminate\Support\Str;

class BiasDetectionService
{
    private array $biasCategories = [
        'gendered_language' => [
            'label' => 'Gendered Language',
            'description' => 'Uses gender-specific terms that may exclude or stereotype',
            'weight' => 0.15,
            'patterns' => [
                '/\b(mankind|manpower|chairman|fireman|policeman|stewardess|mailman)\b/i',
                '/\b(guys|ladies\s*and\s*gentlemen|men\s*and\s*women)\b/i',
            ],
        ],
        'exclusionary_language' => [
            'label' => 'Exclusionary Language',
            'description' => 'Terms with racially charged origins or connotations',
            'weight' => 0.2,
            'patterns' => [
                '/\b(blacklist|whitelist|master\s*slave|grandfathered)\b/i',
                '/\b(peanuts|informal\s*trope|loaded\s*term)\b/i',
            ],
        ],
        'age_stereotype' => [
            'label' => 'Age Stereotype',
            'description' => 'Assumptions based on age groups',
            'weight' => 0.1,
            'patterns' => [
                '/\b(young\s*and\s*digital|digital\s*native|millennial\s*mentality)\b/i',
                '/\b(old\s*fashioned|boomer|outdated\s*generation)\b/i',
            ],
        ],
        'cultural_superiority' => [
            'label' => 'Cultural Superiority',
            'description' => 'Implies one culture is superior to another',
            'weight' => 0.25,
            'patterns' => [
                '/\b(third\s*world|developing\s*nation|western\s*standard)\b/i',
                '/\b(primitive|uncivilized|backward)\b/i',
            ],
        ],
        'socioeconomic_bias' => [
            'label' => 'Socioeconomic Bias',
            'description' => 'Assumptions based on economic status',
            'weight' => 0.15,
            'patterns' => [
                '/\b(upper\s*class|lower\s*class|ghetto|trailer\s*trash)\b/i',
                '/\b(elite|privileged|underprivileged)\b/i',
            ],
        ],
        'disability_bias' => [
            'label' => 'Disability Bias',
            'description' => 'Ableist language or stereotypes',
            'weight' => 0.2,
            'patterns' => [
                '/\b(cripp?led|lame|dumb|schizo|psycho)\b/i',
                '/\b(wheelchair.?bound|suffers?\s*from)\b/i',
            ],
        ],
        'political_bias' => [
            'label' => 'Political Bias',
            'description' => 'Partisan or politically charged language',
            'weight' => 0.15,
            'patterns' => [
                '/\b(libtard|conservatard|right\s*wing\s*nut|leftist\s*radical)\b/i',
            ],
        ],
        'confirmation_bias' => [
            'label' => 'Confirmation Bias',
            'description' => 'Language that cherry-picks evidence',
            'weight' => 0.1,
            'patterns' => [
                '/\b(everyone\s*knows|obviously|clearly|without\s*a\s*doubt)\b/i',
                '/\b(the\s*only\s*way|the\s*best\s*way)\b/i',
            ],
        ],
    ];

    /**
     * Detect bias in the given text and return detailed findings.
     */
    public function detectBias(string $text): array
    {
        $findings = [];
        $totalScore = 0;

        foreach ($this->biasCategories as $category => $config) {
            $matches = [];

            foreach ($config['patterns'] as $pattern) {
                if (preg_match_all($pattern, $text, $patternMatches)) {
                    $matches = array_merge($matches, $patternMatches[0]);
                }
            }

            if (! empty($matches)) {
                $categoryScore = min($config['weight'] * count($matches), 1.0);
                $totalScore += $categoryScore;

                $findings[] = [
                    'category' => $category,
                    'label' => $config['label'],
                    'description' => $config['description'],
                    'matches' => array_unique($matches),
                    'match_count' => count(array_unique($matches)),
                    'score' => round($categoryScore, 3),
                ];
            }
        }

        $totalScore = min($totalScore, 1.0);

        return [
            'biased' => $totalScore >= 0.3,
            'severity' => $this->getSeverity($totalScore),
            'score' => round($totalScore, 3),
            'findings' => $findings,
            'checked_at' => now()->toISOString(),
        ];
    }

    /**
     * Calculate a bias score from 0 to 1.
     */
    public function calculateBiasScore(string $text): float
    {
        $result = $this->detectBias($text);

        return $result['score'];
    }

    /**
     * Get all bias categories with metadata.
     */
    public function getBiasCategories(): array
    {
        $categories = [];

        foreach ($this->biasCategories as $key => $config) {
            $categories[$key] = [
                'key' => $key,
                'label' => $config['label'],
                'description' => $config['description'],
                'weight' => $config['weight'],
            ];
        }

        return $categories;
    }

    /**
     * Suggest mitigation strategies for a given bias type.
     */
    public function suggestMitigation(string $biasType): array
    {
        $mitigations = [
            'gendered_language' => [
                'Replace "mankind" with "humanity" or "people"',
                'Replace "manpower" with "workforce" or "staff"',
                'Replace "chairman" with "chairperson" or "chair"',
                'Use "firefighter" instead of "fireman"',
                'Use "police officer" instead of "policeman"',
                'Use "humankind" instead of "mankind"',
            ],
            'exclusionary_language' => [
                'Replace "blacklist/whitelist" with "blocklist/allowlist"',
                'Replace "master/slave" with "primary/replica" or "leader/follower"',
                'Replace "grandfathered" with "legacy-exempt" or "pre-existing"',
            ],
            'age_stereotype' => [
                'Avoid assumptions about tech skills based on age',
                'Use "experienced professional" instead of "old-fashioned"',
                'Use "early-career professional" instead of "inexperienced"',
            ],
            'cultural_superiority' => [
                'Use culturally neutral descriptors',
                'Avoid ranking cultures or nations',
                'Use "specific region" instead of loaded terms',
            ],
            'socioeconomic_bias' => [
                'Use "economically diverse" instead of class-based labels',
                'Avoid terms that stigmatize economic status',
                'Use neutral descriptors for neighborhoods or communities',
            ],
            'disability_bias' => [
                'Use person-first language ("person with a disability")',
                'Avoid using disability terms as metaphors',
                'Use "accessible" instead of "wheelchair-bound"',
                'Avoid "suffers from" — use "has" or "lives with"',
            ],
            'political_bias' => [
                'Use neutral political terminology',
                'Avoid partisan slurs or derogatory labels',
                'Present balanced viewpoints',
            ],
            'confirmation_bias' => [
                'Use evidence-based qualifiers ("research suggests")',
                'Present multiple perspectives',
                'Avoid absolute statements without evidence',
            ],
        ];

        return $mitigations[$biasType] ?? ['Review content for potential bias and use inclusive language'];
    }

    /**
     * Determine severity level from a score.
     */
    private function getSeverity(float $score): string
    {
        return match (true) {
            $score >= 0.7 => 'critical',
            $score >= 0.4 => 'high',
            $score >= 0.2 => 'moderate',
            $score > 0 => 'low',
            default => 'none',
        };
    }
}
