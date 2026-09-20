<?php

namespace Tests\Feature\Integrations;

use App\Models\Agency;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Models\User;
use App\Models\ZapierSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZapierTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'admin',
        ]);
    }

    public function test_triggers_list_returns_200(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('api.integrations.zapier.triggers'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'count',
            ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(50, count($data));
    }

    public function test_actions_list_returns_200(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson(route('api.integrations.zapier.actions'));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'count',
            ]);

        $data = $response->json('data');
        $this->assertGreaterThanOrEqual(30, count($data));
    }

    public function test_execute_action_creates_post(): void
    {
        $socialAccount = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('api.integrations.zapier.actions.execute'), [
                'action' => 'create_post',
                'data' => [
                    'platform' => 'facebook',
                    'content' => 'Test post from Zapier',
                    'social_account_id' => $socialAccount->id,
                    'status' => 'draft',
                ],
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'status'],
            ]);

        $this->assertDatabaseHas('social_posts', [
            'content' => 'Test post from Zapier',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_execute_action_sends_campaign(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('api.integrations.zapier.actions.execute'), [
                'action' => 'send_campaign',
                'data' => [
                    'name' => 'Zapier Test Campaign',
                    'type' => 'general',
                    'description' => 'Test campaign created via Zapier',
                ],
            ]);

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data' => ['id', 'name', 'status'],
            ]);

        $this->assertDatabaseHas('campaigns', [
            'name' => 'Zapier Test Campaign',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_subscribe_creates_webhook_subscription(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('api.integrations.zapier.subscribe'), [
                'webhook_url' => 'https://hooks.zapier.com/hooks/catch/123456/abcdef/',
                'trigger_type' => 'new_post_published',
            ]);

        $response->assertCreated()
            ->assertJsonStructure([
                'success',
                'message',
                'data' => ['id', 'agency_id', 'webhook_url', 'trigger_type', 'is_active'],
            ]);

        $this->assertDatabaseHas('zapier_subscriptions', [
            'agency_id' => $this->agency->id,
            'webhook_url' => 'https://hooks.zapier.com/hooks/catch/123456/abcdef/',
            'trigger_type' => 'new_post_published',
            'is_active' => true,
        ]);
    }

    public function test_unsubscribe_removes_webhook_subscription(): void
    {
        ZapierSubscription::create([
            'agency_id' => $this->agency->id,
            'webhook_url' => 'https://hooks.zapier.com/hooks/catch/123456/abcdef/',
            'trigger_type' => 'new_post_published',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('api.integrations.zapier.unsubscribe'), [
                'webhook_url' => 'https://hooks.zapier.com/hooks/catch/123456/abcdef/',
                'trigger_type' => 'new_post_published',
            ]);

        $response->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('zapier_subscriptions', [
            'agency_id' => $this->agency->id,
            'webhook_url' => 'https://hooks.zapier.com/hooks/catch/123456/abcdef/',
            'trigger_type' => 'new_post_published',
        ]);
    }

    public function test_trigger_data_returns_recent_posts(): void
    {
        $socialAccount = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
        ]);

        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $socialAccount->id,
            'status' => 'published',
            'published_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('api.integrations.zapier.triggers.data', ['trigger' => 'new_post_published']));

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'trigger',
                'data',
                'count',
            ]);

        $this->assertEquals(3, $response->json('count'));
    }

    public function test_requires_authentication(): void
    {
        $response = $this->getJson(route('api.integrations.zapier.triggers'));
        $response->assertUnauthorized();

        $response = $this->getJson(route('api.integrations.zapier.actions'));
        $response->assertUnauthorized();

        $response = $this->postJson(route('api.integrations.zapier.actions.execute'), [
            'action' => 'create_post',
            'data' => [],
        ]);
        $response->assertUnauthorized();
    }

    public function test_requires_agency(): void
    {
        $userWithoutAgency = User::factory()->create(['agency_id' => null]);

        $response = $this->actingAs($userWithoutAgency)
            ->getJson(route('api.integrations.zapier.triggers'));

        // Should be forbidden or fail agency middleware check
        $this->assertTrue(
            in_array($response->status(), [403, 302, 401, 419, 422]),
            "Expected 403/302/401/419/422 but got {$response->status()}"
        );
    }
}
