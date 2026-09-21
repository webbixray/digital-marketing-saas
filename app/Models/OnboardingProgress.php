<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnboardingProgress extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'agency_id',
        'step',
        'completed_at',
        'data',
    ];

    protected $casts = [
        'completed_at' => 'datetime',
        'data' => 'array',
    ];

    public function agency()
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('completed_at');
    }

    public function scopeStep($query, string $step)
    {
        return $query->where('step', $step);
    }

    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    public function complete(array $data = []): void
    {
        $this->update([
            'completed_at' => now(),
            'data' => array_merge($this->data ?? [], $data),
        ]);
    }
}
