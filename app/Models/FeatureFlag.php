<?php

namespace App\Models;
use App\Models\Concerns\HasAgency;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeatureFlag extends Model
{
    use HasFactory, HasAgency;

    protected $fillable = [
        'agency_id',
        'feature_key',
        'feature_name',
        'description',
        'enabled',
        'required_plan',
        'minimum_version',
        'allowed_roles',
        'settings',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'allowed_roles' => 'array',
        'settings' => 'array',
        'required_plan' => 'integer',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function scopeEnabled($query)
    {
        return $query->where('enabled', true);
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }
}
