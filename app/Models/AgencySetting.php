<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencySetting extends Model
{
    protected $fillable = [
        'agency_id',
        'key',
        'value',
    ];

    protected $casts = [
        'value' => 'array',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public static function get(Agency $agency, string $key, $default = null)
    {
        $setting = static::where('agency_id', $agency->id)
            ->where('key', $key)
            ->first();

        return $setting ? $setting->value : $default;
    }

    public static function set(Agency $agency, string $key, $value): self
    {
        return static::updateOrCreate(
            [
                'agency_id' => $agency->id,
                'key' => $key,
            ],
            [
                'value' => $value,
            ]
        );
    }

    public static function forget(Agency $agency, string $key): void
    {
        static::where('agency_id', $agency->id)
            ->where('key', $key)
            ->delete();
    }
}
