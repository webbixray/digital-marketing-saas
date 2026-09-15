<?php

namespace App\Models;
use App\Models\Concerns\HasAgency;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AbTest extends Model
{
    use HasFactory, HasAgency;

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
        return $this->status === 'running';
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
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

        // Approximate confidence from Z-score using error function
        $confidence = 2 * (1 - $this->normalCDF(abs($z))) * 100;

        return round(min(99.99, max(0, $confidence)), 2);
    }

    /**
     * Determine winner based on engagement rate.
     */
    public function determineWinner(): string
    {
        $confidence = $this->calculateConfidence();

        if ($confidence < 95) {
            return 'inconclusive';
        }

        $aRate = $this->variant_a_impressions > 0 ? $this->variant_a_engagement / $this->variant_a_impressions : 0;
        $bRate = $this->variant_b_impressions > 0 ? $this->variant_b_engagement / $this->variant_b_impressions : 0;

        if ($bRate > $aRate) {
            return 'b';
        } elseif ($aRate > $bRate) {
            return 'a';
        }

        return 'inconclusive';
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
