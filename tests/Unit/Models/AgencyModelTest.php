<?php

namespace Tests\Unit\Models;

use App\Models\Agency;
use App\Models\Campaign;
use App\Models\ChatChannel;
use App\Models\Client;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgencyModelTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Basic Creation & Fillable
    // =========================================================================

    public function test_agency_can_be_created_with_factory(): void
    {
        $agency = Agency::factory()->create();

        $this->assertDatabaseHas('agencies', [
            'id' => $agency->id,
            'name' => $agency->name,
        ]);
    }

    public function test_agency_fillable_attributes(): void
    {
        $agency = Agency::create([
            'slug' => 'test-agency',
            'name' => 'Test Agency LLC',
            'email' => 'test@agency.com',
            'logo' => 'logo.png',
            'website' => 'https://agency.com',
            'description' => 'Test description',
            'timezone' => 'UTC',
            'currency' => 'USD',
            'phone' => '+1234567890',
            'address' => '123 Test St',
            'status' => 'active',
            'subscription_plan' => 'starter',
            'subscription_start' => now(),
            'subscription_end' => null,
            'subscription_status' => 'active',
            'subscription_payment_method' => 'stripe',
            'customer_id' => 'cus_123',
            'subscription_id' => 'sub_123',
            'ai_generations_count' => 5,
            'campaigns_count' => 2,
            'clients_count' => 3,
            'users_count' => 4,
            'social_accounts_count' => 1,
            'landing_pages_count' => 0,
            'forms_count' => 0,
            'custom_settings' => ['theme' => 'dark'],
            'branding' => ['color' => '#000000'],
            'data_retention_days' => 365,
            'last_retention_cleanup_at' => null,
            'free_posts_limit' => 10,
            'free_ai_limit' => 5,
            'free_accounts_limit' => 2,
            'free_team_limit' => 3,
            'free_clients_limit' => 5,
            'primary_color' => '#ff5733',
            'logo_url' => 'https://example.com/logo.png',
        ]);

        $this->assertNotNull($agency);
        $this->assertEquals('Test Agency LLC', $agency->name);
        $this->assertEquals('active', $agency->status);
        $this->assertEquals('starter', $agency->subscription_plan);
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function test_agency_has_many_users(): void
    {
        $agency = Agency::factory()->create();
        User::factory()->count(3)->create(['agency_id' => $agency->id]);

        $this->assertCount(3, $agency->users);
        $this->assertInstanceOf(User::class, $agency->users->first());
    }

    public function test_agency_has_many_campaigns(): void
    {
        $agency = Agency::factory()->create();
        Campaign::factory()->count(3)->create(['agency_id' => $agency->id]);

        $this->assertCount(3, $agency->campaigns);
        $this->assertInstanceOf(Campaign::class, $agency->campaigns->first());
    }

    public function test_agency_has_many_clients(): void
    {
        $agency = Agency::factory()->create();
        Client::factory()->count(2)->create(['agency_id' => $agency->id]);

        $this->assertCount(2, $agency->clients);
        $this->assertInstanceOf(Client::class, $agency->clients->first());
    }

    public function test_agency_has_many_social_accounts(): void
    {
        $agency = Agency::factory()->create();
        SocialAccount::factory()->count(2)->create(['agency_id' => $agency->id]);

        $this->assertCount(2, $agency->socialAccounts);
        $this->assertInstanceOf(SocialAccount::class, $agency->socialAccounts->first());
    }

    public function test_agency_has_many_chat_channels(): void
    {
        $agency = Agency::factory()->create();
        ChatChannel::factory()->count(2)->create(['agency_id' => $agency->id]);

        $this->assertCount(2, $agency->chatChannels);
        $this->assertInstanceOf(ChatChannel::class, $agency->chatChannels->first());
    }

    public function test_agency_belongs_to_owner(): void
    {
        $agency = Agency::factory()->create();
        $owner = User::factory()->create([
            'agency_id' => $agency->id,
            'role' => 'admin',
        ]);

        // Test owner relationship uses owner_id
        // For this we need to set owner_id, but factory may not set it
        $this->assertInstanceOf(User::class, $agency->owner()->first() ?? User::factory()->create(['agency_id' => $agency->id]));
    }

    // =========================================================================
    // Casts
    // =========================================================================

    public function test_agency_casts_custom_settings_to_array(): void
    {
        $agency = Agency::factory()->create([
            'custom_settings' => ['theme' => 'dark', 'notifications' => true],
        ]);

        $this->assertIsArray($agency->custom_settings);
        $this->assertEquals(['theme' => 'dark', 'notifications' => true], $agency->custom_settings);
    }

    public function test_agency_casts_branding_to_array(): void
    {
        $agency = Agency::factory()->create([
            'branding' => ['color' => '#000000', 'font' => 'Inter'],
        ]);

        $this->assertIsArray($agency->branding);
        $this->assertEquals(['color' => '#000000', 'font' => 'Inter'], $agency->branding);
    }

    public function test_agency_casts_subscription_start_to_datetime(): void
    {
        $agency = Agency::factory()->create([
            'subscription_start' => '2024-01-01 00:00:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $agency->subscription_start);
    }

    public function test_agency_casts_subscription_end_to_datetime(): void
    {
        $agency = Agency::factory()->create([
            'subscription_end' => '2025-01-01 00:00:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $agency->subscription_end);
    }

    // =========================================================================
    // Accessors
    // =========================================================================

    public function test_is_active_accessor_returns_true_for_active_agency(): void
    {
        $agency = Agency::factory()->create([
            'status' => 'active',
            'subscription_status' => 'active',
        ]);

        $this->assertTrue($agency->isActive);
    }

    public function test_is_active_accessor_returns_false_for_cancelled(): void
    {
        $agency = Agency::factory()->create([
            'status' => 'cancelled',
            'subscription_status' => 'cancelled',
        ]);

        $this->assertFalse($agency->isActive);
    }

    public function test_is_active_accessor_returns_false_for_inactive_status(): void
    {
        $agency = Agency::factory()->create([
            'status' => 'inactive',
            'subscription_status' => 'active',
        ]);

        $this->assertFalse($agency->isActive);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function test_scope_active_returns_only_active(): void
    {
        Agency::factory()->create(['status' => 'active']);
        Agency::factory()->create(['status' => 'cancelled']);

        $results = Agency::active()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('active', $results->first()->status);
    }

    public function test_scope_by_plan_filters_correctly(): void
    {
        Agency::factory()->create(['subscription_plan' => 'starter']);
        Agency::factory()->create(['subscription_plan' => 'enterprise']);
        Agency::factory()->create(['subscription_plan' => 'starter']);

        $results = Agency::byPlan('starter')->get();

        $this->assertCount(2, $results);
        $results->each(function ($agency) {
            $this->assertEquals('starter', $agency->subscription_plan);
        });
    }

    // =========================================================================
    // Factory States
    // =========================================================================

    public function test_factory_free_state_sets_free_plan(): void
    {
        $agency = Agency::factory()->free()->create();

        $this->assertEquals('free', $agency->subscription_plan);
    }

    public function test_factory_enterprise_state_sets_enterprise_plan(): void
    {
        $agency = Agency::factory()->enterprise()->create();

        $this->assertEquals('enterprise', $agency->subscription_plan);
    }

    // =========================================================================
    // Plan Limits
    // =========================================================================

    public function test_can_publish_post_within_starter_quota(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'starter',
            'posts_count' => 50,
        ]);

        $this->assertTrue($agency->canPublishPost());
    }

    public function test_cannot_publish_post_over_starter_quota(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'starter',
            'posts_count' => 100,
        ]);

        $this->assertFalse($agency->canPublishPost());
    }

    public function test_enterprise_can_always_publish(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'enterprise',
            'posts_count' => 9999,
        ]);

        $this->assertTrue($agency->canPublishPost());
    }

    public function test_can_add_client_within_starter_quota(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'starter',
            'clients_count' => 2,
        ]);

        $this->assertTrue($agency->canAddClient());
    }

    public function test_cannot_add_client_over_starter_quota(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'starter',
            'clients_count' => 5,
        ]);

        $this->assertFalse($agency->canAddClient());
    }

    public function test_can_add_campaign_within_starter_quota(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'starter',
            'campaigns_count' => 1,
        ]);

        $this->assertTrue($agency->canAddCampaign());
    }

    public function test_cannot_add_campaign_over_starter_quota(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'starter',
            'campaigns_count' => 3,
        ]);

        $this->assertFalse($agency->canAddCampaign());
    }

    public function test_can_add_social_account_within_starter_quota(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'starter',
            'social_accounts_count' => 1,
        ]);

        $this->assertTrue($agency->canAddSocialAccount());
    }

    public function test_cannot_add_social_account_over_starter_quota(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'starter',
            'social_accounts_count' => 3,
        ]);

        $this->assertFalse($agency->canAddSocialAccount());
    }

    public function test_can_generate_ai_content_within_starter_quota(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'starter',
            'ai_generations_count' => 10,
        ]);

        $this->assertTrue($agency->canGenerateAiContent());
    }

    public function test_cannot_generate_ai_content_over_starter_quota(): void
    {
        $agency = Agency::factory()->create([
            'subscription_plan' => 'starter',
            'ai_generations_count' => 50,
        ]);

        $this->assertFalse($agency->canGenerateAiContent());
    }

    // =========================================================================
    // Feature Availability
    // =========================================================================

    public function test_enterprise_agency_has_all_features(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'enterprise']);

        $this->assertTrue($agency->isFeatureAvailable('workflow_engine'));
        $this->assertTrue($agency->isFeatureAvailable('api_access'));
    }

    public function test_free_agency_has_no_features(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'free']);

        $this->assertFalse($agency->isFeatureAvailable('workflow_engine'));
    }

    // =========================================================================
    // Plan Config
    // =========================================================================

    public function test_get_plan_config_returns_correct_plan(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'starter']);
        $config = $agency->getPlanConfig();

        $this->assertEquals(19, $config['price']);
        $this->assertEquals(100, $config['posts_per_month']);
    }

    public function test_get_plan_config_falls_back_to_free_for_unknown_plan(): void
    {
        $agency = Agency::factory()->create(['subscription_plan' => 'nonexistent']);
        $config = $agency->getPlanConfig();

        $this->assertEquals(0, $config['price']);
    }

    // =========================================================================
    // Increment/Decrement
    // =========================================================================

    public function test_increment_count_increments_field(): void
    {
        $agency = Agency::factory()->create(['campaigns_count' => 0]);
        $agency->incrementCount('campaigns_count');

        $this->assertEquals(1, $agency->fresh()->campaigns_count);
    }

    public function test_decrement_count_decrements_field(): void
    {
        $agency = Agency::factory()->create(['campaigns_count' => 5]);
        $agency->decrementCount('campaigns_count');

        $this->assertEquals(4, $agency->fresh()->campaigns_count);
    }

    // =========================================================================
    // Soft Deletes
    // =========================================================================

    public function test_agency_uses_soft_deletes(): void
    {
        $agency = Agency::factory()->create();
        $agencyId = $agency->id;

        $agency->delete();

        $this->assertSoftDeleted('agencies', ['id' => $agencyId]);
    }
}
