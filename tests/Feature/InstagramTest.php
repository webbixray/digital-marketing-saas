<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstagramTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
        ]);
    }

    public function test_instagram_index_requires_auth(): void
    {
        $response = $this->get(route('instagram.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_instagram_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('instagram.index'));
        $response->assertStatus(200);
        $response->assertViewIs('instagram.index');
    }

    public function test_instagram_connect_redirects_to_facebook(): void
    {
        $response = $this->actingAs($this->user)->get(route('instagram.connect'));
        $response->assertStatus(302);
        $response->assertRedirectContains('facebook.com');
    }

    public function test_instagram_callback_rejects_invalid_state(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('instagram.callback', ['state' => 'invalid', 'code' => 'test']));
        $response->assertRedirect(route('instagram.index'));
        $response->assertSessionHas('error');
    }

    public function test_instagram_disconnect_requires_ownership(): void
    {
        $account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'instagram',
        ]);

        $response = $this->actingAs($this->user)->delete(route('instagram.disconnect', $account->id));
        $response->assertRedirect(route('instagram.index'));
        // Check that the account is soft deleted
        $this->assertSoftDeleted('social_accounts', ['id' => $account->id]);
    }

    public function test_instagram_disconnect_prevents_cross_agency(): void
    {
        $otherAgency = Agency::factory()->create();
        $account = SocialAccount::factory()->create([
            'agency_id' => $otherAgency->id,
            'platform' => 'instagram',
        ]);

        $response = $this->actingAs($this->user)->delete(route('instagram.disconnect', $account->id));
        $response->assertStatus(403);
    }

    public function test_instagram_toggle_updates_status(): void
    {
        $account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'instagram',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('instagram.toggle', $account->id));
        $response->assertRedirect(route('instagram.index'));
        $this->assertDatabaseHas('social_accounts', [
            'id' => $account->id,
            'is_active' => false,
        ]);
    }

    public function test_instagram_webhook_rejects_invalid_object(): void
    {
        $response = $this->postJson(route('instagram.webhook'), [
            'object' => 'facebook',
        ]);
        $response->assertStatus(400);
    }

    public function test_instagram_webhook_accepts_valid_payload(): void
    {
        $account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'instagram',
            'platform_account_id' => '123456789',
        ]);

        $response = $this->postJson(route('instagram.webhook'), [
            'object' => 'instagram',
            'entry' => [
                [
                    'id' => '123456789',
                    'changes' => [
                        [
                            'field' => 'comments',
                            'value' => [
                                'id' => 'comment_1',
                                'text' => 'Great post!',
                                'from' => ['username' => 'testuser'],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('inbox_messages', [
            'agency_id' => $this->agency->id,
            'platform' => 'instagram',
            'content' => 'Great post!',
        ]);
    }

    public function test_instagram_supported_platforms_list(): void
    {
        $this->assertArrayHasKey('instagram', SocialAccount::SUPPORTED_PLATFORMS);
    }
}
