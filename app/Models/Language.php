<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'is_rtl',
        'is_active',
        'sort_order',
        'flag_emoji',
    ];

    protected $casts = [
        'is_rtl' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Scope: only active languages.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: RTL languages.
     */
    public function scopeRtl(Builder $query): Builder
    {
        return $query->where('is_rtl', true);
    }

    /**
     * Scope: filter by language code.
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    /**
     * Get all active languages ordered by sort_order.
     */
    public static function getActiveLanguages(): Collection
    {
        return static::active()->orderBy('sort_order')->get();
    }
}
