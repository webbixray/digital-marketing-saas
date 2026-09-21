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
        'description',
        'base_url',
        'auth_url',
        'token_url',
        'api_version',
        'icon',
        'color',
        'scopes',
        'config',
        'is_active',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'scopes' => 'array',
        'config' => 'array',
        'is_active' => 'boolean',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class, 'platform', 'name');
    }

    public static function getAllActive(): array
    {
        return Cache::remember('platforms:active', 3600, function () {
            return self::where('is_active', true)
                ->orderBy('sort_order')
                ->get()
                ->keyBy('name');
        });
    }

    public static function findByName(string $name): ?self
    {
        return Cache::remember("platform:{$name}", 3600, function () use ($name) {
            return self::where('name', $name)->first();
        });
    }
}
