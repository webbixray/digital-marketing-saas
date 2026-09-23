<?php

namespace Tests\Feature\Webhook;

use App\Models\Agency;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
    }

    public function test_list_webhooks(): void
    {
        Webhook::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->get(route('webhooks.index'));
        $response->assertOk();
        $response->assertViewIs('webhooks.index');
        $response->assertViewHas('webhooks');
    }

    public function test_create_webhook(): void
    {
        $response = $this->actingAs($this->user)->post(route('webhooks.store'), [
            'name' => 'Test Webhook',
            'url' => 'https://example.com/webhook',
            'events' => ['post.published'],
            'is_active' => true,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('webhooks', [
            'name' => 'Test Webhook',
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_update_webhook(): void
    {
        $webhook = Webhook::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->put(route('webhooks.update', $webhook), [
            'name' => 'Updated Webhook',
            'url' => 'https://example.com/updated',
            'events' => ['post.published'],
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('webhooks', [
            'id' => $webhook->id,
            'name' => 'Updated Webhook',
        ]);
    }

    public function test_delete_webhook(): void
    {
        $webhook = Webhook::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->actingAs($this->user)->delete(route('webhooks.destroy', $webhook));
        $response->assertRedirect(route('webhooks.index'));
        $this->assertSoftDeleted('webhooks', ['id' => $webhook->id]);
    }

    public function test_webhook_toggles_active(): void
    {
        $webhook = Webhook::factory()->create(['agency_id' => $this->agency->id, 'is_active' => true]);
        $response = $this->actingAs($this->user)->put(route('webhooks.update', $webhook), [
            'name' => $webhook->name,
            'url' => $webhook->url,
            'events' => ['post.published'],
            'is_active' => false,
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('webhooks', [
            'id' => $webhook->id,
            'is_active' => false,
        ]);
    }

    public function test_webhook_has_signature_validation(): void
    {
        $webhook = Webhook::factory()->create(['agency_id' => $this->agency->id]);
        $this->assertNotNull($webhook->secret);
        $this->assertNotEmpty($webhook->secret);
    }

    public function test_requires_auth(): void
    {
        $response = $this->get(route('webhooks.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_cross_agency_authorization(): void
    {
        $otherAgency = Agency::factory()->create();
        $webhook = Webhook::factory()->create(['agency_id' => $otherAgency->id]);
        $response = $this->actingAs($this->user)->get(route('webhooks.show', $webhook));
        $response->assertForbidden();
    }
}
