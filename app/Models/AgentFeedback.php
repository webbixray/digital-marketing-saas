<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AgentFeedback extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'agent_name',
        'task_id',
        'rating',
        'feedback',
        'expected_output',
        'actual_output',
        'context',
    ];

    protected $casts = [
        'rating' => 'integer',
        'expected_output' => 'array',
        'actual_output' => 'array',
        'context' => 'array',
    ];

    public function scopeForAgent($query, string $agentName)
    {
        return $query->where('agent_name', $agentName);
    }

    public function scopeHighRating($query)
    {
        return $query->where('rating', '>=', 4);
    }

    public function scopeLowRating($query)
    {
        return $query->where('rating', '<=', 2);
    }

    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    public function isPositive(): bool
    {
        return $this->rating >= 4;
    }

    public function isNegative(): bool
    {
        return $this->rating <= 2;
    }
}
