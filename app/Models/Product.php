<?php

namespace App\Models;

use App\Models\Concerns\HasAgency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasAgency, HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_DISCONTINUED = 'discontinued';

    protected $fillable = [
        'agency_id',
        'name',
        'slug',
        'description',
        'sku',
        'price',
        'sale_price',
        'currency',
        'inventory_count',
        'low_stock_threshold',
        'status',
        'images',
        'metadata',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'inventory_count' => 'integer',
        'low_stock_threshold' => 'integer',
        'images' => 'array',
        'metadata' => 'array',
    ];

    public function agency(): BelongsTo
    {
        return $this->belongsTo(Agency::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_product_tag', 'product_id', 'product_tag_id')
            ->withTimestamps();
    }

    public function scopeByAgency($query, int $agencyId)
    {
        return $query->where('agency_id', $agencyId);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeLowStock($query)
    {
        return $query->whereColumn('inventory_count', '<=', 'low_stock_threshold')
            ->where('status', '!=', self::STATUS_DISCONTINUED);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
                ->orWhere('description', 'like', "%{$term}%")
                ->orWhere('sku', 'like', "%{$term}%");
        });
    }

    public function isLowStock(): bool
    {
        return $this->inventory_count <= $this->low_stock_threshold;
    }

    public function getEffectivePrice(): float
    {
        return $this->sale_price ?? $this->price;
    }
}
