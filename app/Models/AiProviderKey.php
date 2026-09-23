<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiProviderKey extends Model
{
    use HasFactory;

    protected $table = 'ai_provider_keys';

    protected $fillable = [
        'agency_id',
        'provider_name',
        'api_key',
        'api_base_url',
        'is_active',
        'priority',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'priority' => 'integer',
    ];

    public function getMaskedKeyAttribute(): string
    {
        $key = $this->api_key;
        if (strlen($key) <= 8) return '****';
        return substr($key, 0, 4) . '...' . substr($key, -4);
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForProvider($query, string $providerName)
    {
        return $query->where('provider_name', $providerName);
    }

    public function scopePriority($query)
    {
        return $query->orderBy('priority', 'asc');
    }

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
