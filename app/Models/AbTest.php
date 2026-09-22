<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbTest extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'agency_id',
        'social_account_id',
        'name',
        'status',
        'type',
        'platform',
        'hypothesis',
        'variant_a_content',
        'variant_b_content',
        'variant_a_media',
        'variant_b_media',
        'variant_a_impressions',
        'variant_b_impressions',
        'variant_a_engagement',
        'variant_b_engagement',
        'variant_a_clicks',
        'variant_b_clicks',
        'winner',
        'confidence',
        'sample_size',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'variant_a_media' => 'array',
        'variant_b_media' => 'array',
        'variant_a_impressions' => 'integer',
        'variant_b_impressions' => 'integer',
        'variant_a_engagement' => 'integer',
        'variant_b_engagement' => 'integer',
        'variant_a_clicks' => 'integer',
        'variant_b_clicks' => 'integer',
        'confidence' => 'decimal:2',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    // Test types
    public const TYPE_CONTENT = 'content';
    public const TYPE_TIMING = 'timing';
    public const TYPE_HASHTAG = 'hashtag';
    public const TYPE_MEDIA = 'media';

    public const TYPES = [
        self::TYPE_CONTENT => 'Content Variants',
        self::TYPE_TIMING => 'Posting Times',
        self::TYPE_HASHTAG => 'Hashtag Sets',
        self::TYPE_MEDIA => 'Media Types',
    ];

    // Statuses
    public const STATUS_DRAFT = 'draft';
    public const STATUS_RUNNING = 'running';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_COMPLETED = 'completed';

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(AbTestLog::class);
    }

    public function isRunning(): bool
    {
        return $this->status === self::STATUS_RUNNING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    public function scopeForStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Calculate engagement rate for a variant.
     */
    public function engagementRate(string $variant): float
    {
        $impressions = $this->{"variant_{$variant}_impressions"};
        $engagement = $this->{"variant_{$variant}_engagement"};
        
        return $impressions > 0 ? round(($engagement / $impressions) * 100, 2) : 0;
    }

    /**
     * Calculate click-through rate for a variant.
     */
    public function clickThroughRate(string $variant): float
    {
        $impressions = $this->{"variant_{$variant}_impressions"};
        $clicks = $this->{"variant_{$variant}_clicks"};
        
        return $impressions > 0 ? round(($clicks / $impressions) * 100, 2) : 0;
    }

    /**
     * Calculate statistical confidence using Z-test for proportions.
     */
    public function calculateConfidence(): float
    {
        $aEngagement = $this->variant_a_engagement;
        $bEngagement = $this->variant_b_engagement;
        $aImpressions = $this->variant_a_impressions;
        $bImpressions = $this->variant_b_impressions;

        if ($aImpressions < 30 || $bImpressions < 30) {
            return 0;
        }

        $pA = $aEngagement / $aImpressions;
        $pB = $bEngagement / $bImpressions;
        $pPooled = ($aEngagement + $bEngagement) / ($aImpressions + $bImpressions);

        $se = sqrt($pPooled * (1 - $pPooled) * (1 / $aImpressions + 1 / $bImpressions));

        if ($se == 0) {
            return 0;
        }

        $z = ($pB - $pA) / $se;

        $confidence = 2 * (1 - $this->normalCDF(abs($z))) * 100;

        return round(min(99.99, max(0, $confidence)), 2);
    }

    /**
     * Calculate chi-squared statistic for the A/B test.
     * Tests whether engagement differs significantly between variants.
     */
    public function chiSquared(): array
    {
        $aEng = $this->variant_a_engagement;
        $bEng = $this->variant_b_engagement;
        $aImp = $this->variant_a_impressions;
        $bImp = $this->variant_b_impressions;

        $aNonEng = $aImp - $aEng;
        $bNonEng = $bImp - $bEng;

        $totalEng = $aEng + $bEng;
        $totalNonEng = $aNonEng + $bNonEng;
        $totalA = $aImp;
        $totalB = $bImp;
        $grandTotal = $aImp + $bImp;

        if ($grandTotal === 0 || $totalEng === 0 || $totalNonEng === 0) {
            return [
                'chi_squared' => 0,
                'p_value' => 1,
                'significant' => false,
                'degrees_of_freedom' => 1,
            ];
        }

        // Expected values
        $expAE = ($totalA * $totalEng) / $grandTotal;
        $expBE = ($totalB * $totalEng) / $grandTotal;
        $expAN = ($totalA * $totalNonEng) / $grandTotal;
        $expBN = ($totalB * $totalNonEng) / $grandTotal;

        // Chi-squared components (with Yates' correction for continuity)
        $chiSq = 0;
        $chiSq += $this->chiComponent($aEng, $expAE);
        $chiSq += $this->chiComponent($bEng, $expBE);
        $chiSq += $this->chiComponent($aNonEng, $expAN);
        $chiSq += $this->chiComponent($bNonEng, $expBN);

        // For df=1, p-value from chi-squared
        $pValue = $this->chiSquaredPValue($chiSq, 1);

        return [
            'chi_squared' => round($chiSq, 4),
            'p_value' => round($pValue, 4),
            'significant' => $pValue < 0.05,
            'degrees_of_freedom' => 1,
        ];
    }

    /**
     * Determine winner based on engagement rate with significance.
     */
    public function determineWinner(): string
    {
        $chiSq = $this->chiSquared();

        if (!$chiSq['significant']) {
            return 'inconclusive';
        }

        $aRate = $this->engagementRate('a');
        $bRate = $this->engagementRate('b');

        if ($bRate > $aRate) {
            return 'b';
        } elseif ($aRate > $bRate) {
            return 'a';
        }

        return 'inconclusive';
    }

    /**
     * Get full analysis with significance, effect size, and recommendations.
     */
    public function analyze(): array
    {
        $chiSq = $this->chiSquared();
        $confidence = $this->calculateConfidence();
        $winner = $this->determineWinner();

        $aRate = $this->engagementRate('a');
        $bRate = $this->engagementRate('b');

        // Effect size (relative improvement)
        $effectSize = $aRate > 0 ? round((($bRate - $aRate) / $aRate) * 100, 2) : 0;

        // Power analysis - check if sample size is adequate
        $sampleAdequate = $this->variant_a_impressions >= $this->sample_size
            && $this->variant_b_impressions >= $this->sample_size;

        // Duration
        $duration = null;
        if ($this->started_at && $this->ended_at) {
            $duration = $this->started_at->diffInHours($this->ended_at);
        } elseif ($this->started_at) {
            $duration = $this->started_at->diffInHours(now());
        }

        return [
            'winner' => $winner,
            'confidence' => $confidence,
            'chi_squared' => $chiSq['chi_squared'],
            'p_value' => $chiSq['p_value'],
            'significant' => $chiSq['significant'],
            'effect_size' => $effectSize,
            'sample_adequate' => $sampleAdequate,
            'a_engagement_rate' => $aRate,
            'b_engagement_rate' => $bRate,
            'a_ctr' => $this->clickThroughRate('a'),
            'b_ctr' => $this->clickThroughRate('b'),
            'duration_hours' => $duration,
            'recommendation' => $this->generateRecommendation($winner, $chiSq['significant'], $sampleAdequate),
        ];
    }

    /**
     * Generate human-readable recommendation.
     */
    private function generateRecommendation(string $winner, bool $significant, bool $sampleAdequate): string
    {
        if (!$sampleAdequate) {
            return 'Continue running the test. Sample size is not yet adequate for reliable conclusions.';
        }

        if (!$significant) {
            return 'No statistically significant difference detected. Either the variants perform similarly or more data is needed.';
        }

        if ($winner === 'inconclusive') {
            return 'Results are inconclusive despite collecting enough data. Consider testing more distinct variants.';
        }

        $winnerLabel = $winner === 'a' ? 'Control (A)' : 'Treatment (B)';
        return "{$winnerLabel} is the winner with statistical significance. Consider implementing this variant for future campaigns.";
    }

    private function chiComponent(float $observed, float $expected): float
    {
        if ($expected <= 0) {
            return 0;
        }
        return pow($observed - $expected, 2) / $expected;
    }

    /**
     * Approximate chi-squared p-value for df=1.
     */
    private function chiSquaredPValue(float $chiSq, int $df): float
    {
        if ($chiSq <= 0) {
            return 1;
        }

        // For df=1: p-value = 2 * (1 - normalCDF(sqrt(chiSq)))
        $z = sqrt($chiSq);
        return 2 * (1 - $this->normalCDF($z));
    }

    private function normalCDF(float $x): float
    {
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
}
