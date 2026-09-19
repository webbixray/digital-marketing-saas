<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class Platform extends Model
{
    protected $fillable = [
        'name',
        'display_name',
        'base_url',
        'scopes',
        'auth_url',
        'token_url',
        'api_version',
        'is_active',
    ];

    protected $casts = [
        'scopes' => 'array',
        'is_active' => 'boolean',
    ];

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class, 'platform');
    }

    public static function getAllActive(): array
    {
        return Cache::remember('platforms:active', 3600, function () {
            return self::where('is_active', true)
                ->orderBy('display_name')
                ->get()
                ->keyBy('name');
        });
    }
}
