<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomField extends Model
{
    use HasAgency, HasFactory, SoftDeletes;

    protected $fillable = [
        'agency_id',
        'name',
        'slug',
        'type',
        'options',
        'model_type',
        'is_required',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(CustomFieldValue::class);
    }

    public const FIELD_TYPES = [
        'text' => 'Text',
        'number' => 'Number',
        'date' => 'Date',
        'select' => 'Select',
        'textarea' => 'Textarea',
        'boolean' => 'Yes/No',
    ];
}
