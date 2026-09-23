<?php

namespace App\Services\Social;

use App\Models\Product;
use App\Models\ProductTag;
use App\Models\SocialCommercePost;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommerceIntegrationService
{
    /**
     * Create a new product for an agency.
     */
    public function createProduct(int $agencyId, array $data): Product
    {
        return DB::transaction(function () use ($agencyId, $data) {
            $product = Product::create([
                'agency_id' => $agencyId,
                'name' => $data['name'],
                'slug' => $data['slug'] ?? Str::slug($data['name']) . '-' . $agencyId,
                'description' => $data['description'] ?? null,
                'sku' => $data['sku'] ?? null,
                'price' => $data['price'] ?? 0,
                'sale_price' => $data['sale_price'] ?? null,
                'currency' => $data['currency'] ?? 'USD',
                'inventory_count' => $data['inventory_count'] ?? 0,
                'low_stock_threshold' => $data['low_stock_threshold'] ?? 5,
                'status' => $data['status'] ?? Product::STATUS_ACTIVE,
                'images' => $data['images'] ?? [],
                'metadata' => $data['metadata'] ?? [],
            ]);

            if (! empty($data['tags'])) {
                $this->syncTags($product, $data['tags']);
            }

            return $product;
        });
    }

    /**
     * Update an existing product.
     */
    public function updateProduct(int $id, array $data): ?Product
    {
        $product = Product::find($id);

        if (! $product) {
            return null;
        }

        return DB::transaction(function () use ($product, $data) {
            $updateData = array_intersect_key($data, array_flip([
                'name', 'slug', 'description', 'sku', 'price', 'sale_price',
                'currency', 'inventory_count', 'low_stock_threshold', 'status', 'images', 'metadata',
            ]));

            if (empty($updateData['slug']) && ! empty($data['name'])) {
                $updateData['slug'] = Str::slug($data['name']) . '-' . $product->agency_id;
            }

            $product->update($updateData);

            if (isset($data['tags'])) {
                $this->syncTags($product, $data['tags']);
            }

            return $product->fresh();
        });
    }

    /**
     * Delete a product.
     */
    public function deleteProduct(int $id): bool
    {
        $product = Product::find($id);

        if (! $product) {
            return false;
        }

        $product->tags()->detach();

        return $product->delete();
    }

    /**
     * Get products for an agency with optional filters.
     */
    public function getProducts(int $agencyId, array $filters = []): Collection
    {
        $query = Product::byAgency($agencyId);

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['low_stock'])) {
            $query->lowStock();
        }

        if (! empty($filters['tag_id'])) {
            $query->whereHas('tags', fn ($q) => $q->where('product_tags.id', $filters['tag_id']));
        }

        $sortField = $filters['sort'] ?? 'created_at';
        $sortDir = $filters['direction'] ?? 'desc';

        return $query->orderBy($sortField, $sortDir)->get();
    }

    /**
     * Tag a product with one or more tags.
     */
    public function tagProduct(int $productId, array $tags): void
    {
        $product = Product::find($productId);

        if (! $product) {
            return;
        }

        $this->syncTags($product, $tags, false);
    }

    /**
     * Remove tags from a product.
     */
    public function untagProduct(int $productId, array $tagIds): void
    {
        $product = Product::find($productId);

        if (! $product) {
            return;
        }

        $product->tags()->detach($tagIds);
    }

    /**
     * Get analytics for a product.
     */
    public function getProductAnalytics(int $productId): array
    {
        $product = Product::with('tags')->find($productId);

        if (! $product) {
            return [];
        }

        $commercePosts = SocialCommercePost::byProduct($productId)->get();

        $totalClicks = $commercePosts->sum('clicks');
        $totalConversions = $commercePosts->sum('conversions');
        $totalRevenue = $commercePosts->sum('revenue');

        $ctr = $totalClicks > 0 ? round(($totalConversions / $totalClicks) * 100, 2) : 0;
        $aov = $totalConversions > 0 ? round($totalRevenue / $totalConversions, 2) : 0;

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'total_posts' => $commercePosts->count(),
            'total_clicks' => $totalClicks,
            'total_conversions' => $totalConversions,
            'total_revenue' => $totalRevenue,
            'ctr' => $ctr,
            'average_order_value' => $aov,
            'inventory' => [
                'count' => $product->inventory_count,
                'is_low' => $product->isLowStock(),
                'threshold' => $product->low_stock_threshold,
            ],
            'periods' => [
                'this_month' => [
                    'clicks' => SocialCommercePost::byProduct($productId)->thisMonth()->sum('clicks'),
                    'conversions' => SocialCommercePost::byProduct($productId)->thisMonth()->sum('conversions'),
                    'revenue' => SocialCommercePost::byProduct($productId)->thisMonth()->sum('revenue'),
                ],
            ],
        ];
    }

    /**
     * Track a click on a commerce post.
     */
    public function trackClick(int $commercePostId): bool
    {
        $post = SocialCommercePost::find($commercePostId);

        if (! $post) {
            return false;
        }

        $post->increment('clicks');

        return true;
    }

    /**
     * Track a conversion on a commerce post.
     */
    public function trackConversion(int $commercePostId, float $amount = 0): bool
    {
        $post = SocialCommercePost::find($commercePostId);

        if (! $post) {
            return false;
        }

        $post->increment('conversions');
        $post->increment('revenue', $amount);

        return true;
    }

    /**
     * Get top products by revenue for an agency.
     */
    public function getTopProducts(int $agencyId, int $limit = 10): Collection
    {
        return SocialCommercePost::byAgency($agencyId)
            ->select('product_id')
            ->selectRaw('SUM(clicks) as total_clicks')
            ->selectRaw('SUM(conversions) as total_conversions')
            ->selectRaw('SUM(revenue) as total_revenue')
            ->groupBy('product_id')
            ->orderByDesc('total_revenue')
            ->with('product')
            ->limit($limit)
            ->get();
    }

    /**
     * Sync tags for a product.
     */
    private function syncTags(Product $product, array $tags, bool $detach = false): void
    {
        $tagIds = [];

        foreach ($tags as $tag) {
            if (is_numeric($tag)) {
                $tagIds[] = (int) $tag;
            } else {
                $existingTag = ProductTag::firstOrCreate(
                    ['agency_id' => $product->agency_id, 'name' => $tag],
                    ['slug' => Str::slug($tag)]
                );
                $tagIds[] = $existingTag->id;
            }
        }

        if ($detach) {
            $product->tags()->sync($tagIds);
        } else {
            $product->tags()->syncWithoutDetaching($tagIds);
        }
    }
}
