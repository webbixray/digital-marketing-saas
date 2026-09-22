<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BrandVoiceProfile extends Model
{
    use HasAgency, HasFactory;

    protected $fillable = [
        'agency_id',
        'name',
        'description',
        'tone',
        'vocabulary',
        'style_rules',
        'examples',
        'is_default',
        'platform',
    ];

    protected $casts = [
        'style_rules' => 'array',
        'examples' => 'array',
        'is_default' => 'boolean',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }
}
