<?php

namespace App\Models;
use App\Models\Concerns\HasAgency;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentSharedKnowledge extends Model
{
    use HasFactory, HasAgency;

    protected $table = 'agent_shared_knowledge';

    public $timestamps = true;

    protected $fillable = [
        'agency_id',
        'from_agent',
        'insight',
        'category',
        'confidence',
    ];

    protected $casts = [
        'confidence' => 'decimal:4',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeByAgency(Builder $query, int $agencyId): Builder
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeByAgent(Builder $query, string $agentName): Builder
    {
        return $query->where('from_agent', $agentName);
    }

    public function scopeByMinConfidence(Builder $query, float $minConfidence): Builder
    {
        return $query->where('confidence', '>=', $minConfidence);
    }

    public function scopeRecent(Builder $query, int $limit = 50): Builder
    {
        return $query->orderByDesc('created_at')->limit($limit);
    }

    public function scopeCrossAgent(Builder $query): Builder
    {
        return $query->select('category')
            ->selectRaw('COUNT(DISTINCT from_agent) as agent_count')
            ->selectRaw('COUNT(*) as insight_count')
            ->groupBy('category')
            ->havingRaw('COUNT(DISTINCT from_agent) > 1');
    }
}
