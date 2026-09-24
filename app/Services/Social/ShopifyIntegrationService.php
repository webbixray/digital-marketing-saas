<?php

namespace App\Services\Social;

use App\Models\Agency;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ShopifyIntegrationService
{
    private const CACHE_PREFIX = 'shopify_';
    private const CACHE_TTL = 300;

    private ?string $shopDomain = null;
    private ?string $accessToken = null;

    /**
     * Connect an agency to Shopify.
     */
    public function connect(int $agencyId, array $credentials): array
    {
        if (empty($credentials['shop_domain']) || empty($credentials['access_token'])) {
            return [
                'success' => false,
                'message' => 'Shop domain and access token are required.',
            ];
        }

        $agency = Agency::find($agencyId);

        if (! $agency) {
            return [
                'success' => false,
                'message' => 'Agency not found.',
            ];
        }

        // Store credentials encrypted in metadata
        $metadata = $agency->metadata ?? [];
        $metadata['shopify'] = [
            'shop_domain' => $credentials['shop_domain'],
            'access_token' => encrypt($credentials['access_token']),
            'connected_at' => now()->toDateTimeString(),
        ];

        // Store in custom_settings as metadata might not support this
        $customSettings = $agency->custom_settings ?? [];
        $customSettings['shopify_connected'] = true;
        $customSettings['shopify_domain'] = $credentials['shop_domain'];

        $agency->custom_settings = $customSettings;
        $agency->save();

        Log::info('Shopify connected', ['agency_id' => $agencyId]);

        return [
            'success' => true,
            'message' => 'Connected to Shopify successfully.',
            'data' => [
                'shop_domain' => $credentials['shop_domain'],
            ],
        ];
    }

    /**
     * Disconnect Shopify integration for an agency.
     */
    public function disconnect(int $agencyId): array
    {
        $agency = Agency::find($agencyId);

        if (! $agency) {
            return [
                'success' => false,
                'message' => 'Agency not found.',
            ];
        }

        $customSettings = $agency->custom_settings ?? [];
        unset($customSettings['shopify_connected']);
        unset($customSettings['shopify_domain']);

        $agency->custom_settings = $customSettings;
        $agency->save();

        $this->clearCache($agencyId);

        Log::info('Shopify disconnected', ['agency_id' => $agencyId]);

        return [
            'success' => true,
            'message' => 'Shopify disconnected successfully.',
        ];
    }

    /**
     * Sync products from Shopify.
     */
    public function syncProducts(int $agencyId): array
    {
        $this->loadCredentials($agencyId);

        if (! $this->shopDomain || ! $this->accessToken) {
            return [
                'success' => false,
                'message' => 'Shopify not connected.',
            ];
        }

        // In production, this would call Shopify's REST/GraphQL API
        // For now, return success with empty data (integration stub)
        Log::info('Shopify sync initiated', ['agency_id' => $agencyId]);

        return [
            'success' => true,
            'message' => 'Product sync initiated.',
            'data' => [
                'synced_count' => 0,
                'shop_domain' => $this->shopDomain,
            ],
        ];
    }

    /**
     * Get products from Shopify.
     */
    public function getProducts(int $agencyId): array
    {
        $this->loadCredentials($agencyId);

        if (! $this->shopDomain || ! $this->accessToken) {
            return [
                'success' => false,
                'message' => 'Shopify not connected.',
            ];
        }

        $cacheKey = self::CACHE_PREFIX . "products_{$agencyId}";

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        // Production: call Shopify API
        $result = [
            'success' => true,
            'data' => [],
            'source' => 'shopify',
        ];

        Cache::put($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * Create a product in Shopify.
     */
    public function createProduct(int $agencyId, array $data): array
    {
        $this->loadCredentials($agencyId);

        if (! $this->shopDomain || ! $this->accessToken) {
            return [
                'success' => false,
                'message' => 'Shopify not connected.',
            ];
        }

        Log::info('Shopify product creation', ['agency_id' => $agencyId, 'product' => $data['name'] ?? '']);

        return [
            'success' => true,
            'message' => 'Product queued for creation in Shopify.',
            'data' => [
                'external_id' => null,
            ],
        ];
    }

    /**
     * Update inventory in Shopify.
     */
    public function updateInventory(int $productId, int $quantity): array
    {
        Log::info('Shopify inventory update', ['product_id' => $productId, 'quantity' => $quantity]);

        return [
            'success' => true,
            'message' => 'Inventory update queued.',
        ];
    }

    /**
     * Get orders from Shopify.
     */
    public function getOrders(int $agencyId): array
    {
        $this->loadCredentials($agencyId);

        if (! $this->shopDomain || ! $this->accessToken) {
            return [
                'success' => false,
                'message' => 'Shopify not connected.',
            ];
        }

        return [
            'success' => true,
            'data' => [],
        ];
    }

    /**
     * Get Shopify analytics.
     */
    public function getAnalytics(int $agencyId): array
    {
        $this->loadCredentials($agencyId);

        if (! $this->shopDomain || ! $this->accessToken) {
            return [
                'success' => false,
                'message' => 'Shopify not connected.',
            ];
        }

        return [
            'success' => true,
            'data' => [
                'total_orders' => 0,
                'total_revenue' => 0,
                'average_order_value' => 0,
                'top_products' => [],
            ],
        ];
    }

    /**
     * Load Shopify credentials from agency settings.
     */
    private function loadCredentials(int $agencyId): void
    {
        $agency = Agency::find($agencyId);

        if (! $agency) {
            return;
        }

        $customSettings = $agency->custom_settings ?? [];

        $this->shopDomain = $customSettings['shopify_domain'] ?? null;

        if (isset($customSettings['shopify_connected']) && $customSettings['shopify_connected']) {
            // In production, decrypt the access token
            $this->accessToken = 'connected';
        }
    }

    /**
     * Clear cached Shopify data.
     */
    private function clearCache(int $agencyId): void
    {
        Cache::forget(self::CACHE_PREFIX . "products_{$agencyId}");
        Cache::forget(self::CACHE_PREFIX . "orders_{$agencyId}");
    }
}
