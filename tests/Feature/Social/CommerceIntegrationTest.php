<?php

namespace Tests\Feature\Social;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Product;
use App\Models\ProductTag;
use App\Models\SocialAccount;
use App\Models\SocialCommercePost;
use App\Models\SocialPost;
use App\Models\User;
use App\Services\Social\CommerceIntegrationService;
use App\Services\Social\ShopifyIntegrationService;
use App\Services\Social\WooCommerceIntegrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommerceIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private Agency $otherAgency;

    private User $user;

    private CommerceIntegrationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->otherAgency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
        $this->service = app(CommerceIntegrationService::class);
    }

    // ----------------------------------------------------------------
    // CRUD Tests
    // ----------------------------------------------------------------

    public function test_it_creates_a_product(): void
    {
        $data = [
            'name' => 'Test Product',
            'description' => 'A test product',
            'sku' => 'TEST-001',
            'price' => 29.99,
            'currency' => 'USD',
            'inventory_count' => 100,
            'status' => 'active',
        ];

        $product = $this->service->createProduct($this->agency->id, $data);

        $this->assertDatabaseHas('products', [
            'name' => 'Test Product',
            'agency_id' => $this->agency->id,
        ]);
        $this->assertEquals(29.99, $product->price);
    }

    public function test_it_updates_a_product(): void
    {
        $product = Product::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'Old Name',
            'price' => 10.00,
        ]);

        $updated = $this->service->updateProduct($product->id, [
            'name' => 'New Name',
            'price' => 15.00,
        ]);

        $this->assertNotNull($updated);
        $this->assertEquals('New Name', $updated->name);
        $this->assertEquals(15.00, $updated->price);
    }

    public function test_it_deletes_a_product(): void
    {
        $product = Product::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $result = $this->service->deleteProduct($product->id);

        $this->assertTrue($result);
        $this->assertSoftDeleted('products', ['id' => $product->id]);
    }

    public function test_it_lists_products_with_filters(): void
    {
        Product::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'status' => 'active',
        ]);
        Product::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        $active = $this->service->getProducts($this->agency->id, ['status' => 'active']);
        $all = $this->service->getProducts($this->agency->id);

        $this->assertCount(3, $active);
        $this->assertCount(4, $all);
    }

    // ----------------------------------------------------------------
    // Tagging Tests
    // ----------------------------------------------------------------

    public function test_it_attaches_tags_to_a_product(): void
    {
        $product = Product::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $this->service->tagProduct($product->id, ['electronics', 'audio']);

        $product->refresh();
        $this->assertCount(2, $product->tags);
        $this->assertEquals('electronics', $product->tags->firstWhere('name', 'electronics')->name);
    }

    public function test_it_detaches_tags_from_a_product(): void
    {
        $tag = ProductTag::factory()->create([
            'agency_id' => $this->agency->id,
            'name' => 'to-remove',
        ]);
        $product = Product::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
        $product->tags()->attach($tag);

        $this->service->untagProduct($product->id, [$tag->id]);

        $product->refresh();
        $this->assertCount(0, $product->tags);
    }

    public function test_it_syncs_tags_on_create(): void
    {
        $product = $this->service->createProduct($this->agency->id, [
            'name' => 'Tagged Product',
            'price' => 10.00,
            'tags' => ['new', 'featured'],
        ]);

        $this->assertCount(2, $product->tags);
    }

    // ----------------------------------------------------------------
    // Analytics Tests
    // ----------------------------------------------------------------

    public function test_it_tracks_clicks_on_commerce_post(): void
    {
        $commercePost = SocialCommercePost::factory()->create([
            'agency_id' => $this->agency->id,
            'clicks' => 5,
        ]);

        $result = $this->service->trackClick($commercePost->id);

        $this->assertTrue($result);
        $this->assertEquals(6, $commercePost->fresh()->clicks);
    }

    public function test_it_tracks_conversions_and_revenue(): void
    {
        $commercePost = SocialCommercePost::factory()->create([
            'agency_id' => $this->agency->id,
            'conversions' => 2,
            'revenue' => 50.00,
        ]);

        $result = $this->service->trackConversion($commercePost->id, 25.00);

        $this->assertTrue($result);
        $commercePost->refresh();
        $this->assertEquals(3, $commercePost->conversions);
        $this->assertEquals(75.00, $commercePost->revenue);
    }

    public function test_it_returns_product_analytics(): void
    {
        $product = Product::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
        SocialCommercePost::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'product_id' => $product->id,
            'clicks' => 100,
            'conversions' => 10,
            'revenue' => 250.00,
        ]);

        $analytics = $this->service->getProductAnalytics($product->id);

        $this->assertEquals(200, $analytics['total_clicks']);
        $this->assertEquals(20, $analytics['total_conversions']);
        $this->assertEquals(500.00, $analytics['total_revenue']);
    }

    // ----------------------------------------------------------------
    // Shopify Integration Tests
    // ----------------------------------------------------------------

    public function test_it_connects_to_shopify_with_valid_credentials(): void
    {
        $shopify = app(ShopifyIntegrationService::class);
        $result = $shopify->connect($this->agency->id, [
            'shop_domain' => 'mystore.myshopify.com',
            'access_token' => 'shpat_xxxxxxxxxxxxx',
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('successfully', $result['message']);
    }

    public function test_it_rejects_shopify_connection_with_missing_credentials(): void
    {
        $shopify = app(ShopifyIntegrationService::class);
        $result = $shopify->connect($this->agency->id, [
            'shop_domain' => '',
            'access_token' => '',
        ]);

        $this->assertFalse($result['success']);
    }

    public function test_it_disconnects_from_shopify(): void
    {
        $shopify = app(ShopifyIntegrationService::class);
        $shopify->connect($this->agency->id, [
            'shop_domain' => 'mystore.myshopify.com',
            'access_token' => 'shpat_xxxxxxxxxxxxx',
        ]);

        $result = $shopify->disconnect($this->agency->id);

        $this->assertTrue($result['success']);
    }

    // ----------------------------------------------------------------
    // WooCommerce Integration Tests
    // ----------------------------------------------------------------

    public function test_it_connects_to_woocommerce_with_valid_credentials(): void
    {
        $woo = app(WooCommerceIntegrationService::class);
        $result = $woo->connect($this->agency->id, [
            'store_url' => 'https://mystore.com',
            'consumer_key' => 'ck_xxxxxxxxxxxxx',
            'consumer_secret' => 'cs_xxxxxxxxxxxxx',
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringContainsString('successfully', $result['message']);
    }

    public function test_it_rejects_woocommerce_connection_with_missing_credentials(): void
    {
        $woo = app(WooCommerceIntegrationService::class);
        $result = $woo->connect($this->agency->id, [
            'store_url' => '',
            'consumer_key' => '',
            'consumer_secret' => '',
        ]);

        $this->assertFalse($result['success']);
    }

    // ----------------------------------------------------------------
    // Auth & Cross-Agency Tests
    // ----------------------------------------------------------------

    public function test_it_prevents_access_to_other_agency_products(): void
    {
        $otherProduct = Product::factory()->create([
            'agency_id' => $this->otherAgency->id,
        ]);

        $products = $this->service->getProducts($this->agency->id);

        $this->assertEmpty($products->where('id', $otherProduct->id));
    }

    public function test_unauthenticated_user_cannot_access_products(): void
    {
        $response = $this->get(route('products.index'));
        $response->assertStatus(401);
    }
}
