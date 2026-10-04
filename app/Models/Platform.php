<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
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

    /**
     * All active platforms, keyed by name.
     *
     * Cached as plain arrays and rehydrated (Model::hydrate) because the
     * database cache store blocks class unserialization
     * (serializable_classes=false).
     *
     * @return Collection<int, self>
     */
    public static function getAllActive()
    {
        $cached = Cache::get('platforms:active');

        if (is_array($cached)) {
            return self::hydrate($cached)->keyBy('name');
        }

        $platforms = self::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        Cache::put('platforms:active', $platforms->toArray(), 3600);

        return $platforms->keyBy('name');
    }

    public static function findByName(string $name): ?self
    {
        $cached = Cache::get("platform:{$name}");

        if (is_array($cached)) {
            $model = self::newModelInstance()->newFromBuilder($cached);

            return $model->exists ? $model : null;
        }

        return Cache::remember("platform:{$name}", 3600, function () use ($name) {
            $platform = self::where('name', $name)->first();

            // Cache a plain array (or [] when absent) — never a model, which
            // cannot be unserialized under serializable_classes=false.
            return $platform ? $platform->toArray() : [];
        });
    }
}
