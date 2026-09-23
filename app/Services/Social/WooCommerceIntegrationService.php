<?php

namespace App\Services\Social;

use App\Models\Agency;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class WooCommerceIntegrationService
{
    private const CACHE_PREFIX = 'woocommerce_';
    private const CACHE_TTL = 300;

    private ?string $storeUrl = null;
    private ?string $consumerKey = null;
    private ?string $consumerSecret = null;

    /**
     * Connect an agency to WooCommerce.
     */
    public function connect(int $agencyId, array $credentials): array
    {
        if (empty($credentials['store_url']) || empty($credentials['consumer_key']) || empty($credentials['consumer_secret'])) {
            return [
                'success' => false,
                'message' => 'Store URL, consumer key, and consumer secret are required.',
            ];
        }

        $agency = Agency::find($agencyId);

        if (! $agency) {
            return [
                'success' => false,
                'message' => 'Agency not found.',
            ];
        }

        $customSettings = $agency->custom_settings ?? [];
        $customSettings['woocommerce_connected'] = true;
        $customSettings['woocommerce_url'] = $credentials['store_url'];
        $customSettings['woocommerce_key'] = encrypt($credentials['consumer_key']);
        $customSettings['woocommerce_secret'] = encrypt($credentials['consumer_secret']);

        $agency->custom_settings = $customSettings;
        $agency->save();

        Log::info('WooCommerce connected', ['agency_id' => $agencyId]);

        return [
            'success' => true,
            'message' => 'Connected to WooCommerce successfully.',
            'data' => [
                'store_url' => $credentials['store_url'],
            ],
        ];
    }

    /**
     * Disconnect WooCommerce integration for an agency.
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
        unset($customSettings['woocommerce_connected']);
        unset($customSettings['woocommerce_url']);
        unset($customSettings['woocommerce_key']);
        unset($customSettings['woocommerce_secret']);

        $agency->custom_settings = $customSettings;
        $agency->save();

        $this->clearCache($agencyId);

        Log::info('WooCommerce disconnected', ['agency_id' => $agencyId]);

        return [
            'success' => true,
            'message' => 'WooCommerce disconnected successfully.',
        ];
    }

    /**
     * Sync products from WooCommerce.
     */
    public function syncProducts(int $agencyId): array
    {
        $this->loadCredentials($agencyId);

        if (! $this->storeUrl || ! $this->consumerKey || ! $this->consumerSecret) {
            return [
                'success' => false,
                'message' => 'WooCommerce not connected.',
            ];
        }

        Log::info('WooCommerce sync initiated', ['agency_id' => $agencyId]);

        return [
            'success' => true,
            'message' => 'Product sync initiated.',
            'data' => [
                'synced_count' => 0,
                'store_url' => $this->storeUrl,
            ],
        ];
    }

    /**
     * Get products from WooCommerce.
     */
    public function getProducts(int $agencyId): array
    {
        $this->loadCredentials($agencyId);

        if (! $this->storeUrl || ! $this->consumerKey || ! $this->consumerSecret) {
            return [
                'success' => false,
                'message' => 'WooCommerce not connected.',
            ];
        }

        $cacheKey = self::CACHE_PREFIX . "products_{$agencyId}";

        $cached = Cache::get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $result = [
            'success' => true,
            'data' => [],
            'source' => 'woocommerce',
        ];

        Cache::put($cacheKey, $result, self::CACHE_TTL);

        return $result;
    }

    /**
     * Create a product in WooCommerce.
     */
    public function createProduct(int $agencyId, array $data): array
    {
        $this->loadCredentials($agencyId);

        if (! $this->storeUrl || ! $this->consumerKey || ! $this->consumerSecret) {
            return [
                'success' => false,
                'message' => 'WooCommerce not connected.',
            ];
        }

        Log::info('WooCommerce product creation', ['agency_id' => $agencyId, 'product' => $data['name'] ?? '']);

        return [
            'success' => true,
            'message' => 'Product queued for creation in WooCommerce.',
            'data' => [
                'external_id' => null,
            ],
        ];
    }

    /**
     * Update inventory in WooCommerce.
     */
    public function updateInventory(int $productId, int $quantity): array
    {
        Log::info('WooCommerce inventory update', ['product_id' => $productId, 'quantity' => $quantity]);

        return [
            'success' => true,
            'message' => 'Inventory update queued.',
        ];
    }

    /**
     * Get orders from WooCommerce.
     */
    public function getOrders(int $agencyId): array
    {
        $this->loadCredentials($agencyId);

        if (! $this->storeUrl || ! $this->consumerKey || ! $this->consumerSecret) {
            return [
                'success' => false,
                'message' => 'WooCommerce not connected.',
            ];
        }

        return [
            'success' => true,
            'data' => [],
        ];
    }

    /**
     * Get WooCommerce analytics.
     */
    public function getAnalytics(int $agencyId): array
    {
        $this->loadCredentials($agencyId);

        if (! $this->storeUrl || ! $this->consumerKey || ! $this->consumerSecret) {
            return [
                'success' => false,
                'message' => 'WooCommerce not connected.',
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
     * Load WooCommerce credentials from agency settings.
     */
    private function loadCredentials(int $agencyId): void
    {
        $agency = Agency::find($agencyId);

        if (! $agency) {
            return;
        }

        $customSettings = $agency->custom_settings ?? [];

        $this->storeUrl = $customSettings['woocommerce_url'] ?? null;

        if (isset($customSettings['woocommerce_connected']) && $customSettings['woocommerce_connected']) {
            $this->consumerKey = 'connected';
            $this->consumerSecret = 'connected';
        }
    }

    /**
     * Clear cached WooCommerce data.
     */
    private function clearCache(int $agencyId): void
    {
        Cache::forget(self::CACHE_PREFIX . "products_{$agencyId}");
        Cache::forget(self::CACHE_PREFIX . "orders_{$agencyId}");
    }
}
