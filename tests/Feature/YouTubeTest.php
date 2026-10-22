<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YouTubeTest extends TestCase
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

    public function test_youtube_index_requires_auth(): void
    {
        $response = $this->get(route('youtube.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_youtube_index_loads(): void
    {
        $response = $this->actingAs($this->user)->get(route('youtube.index'));
        $response->assertStatus(200);
        $response->assertViewIs('youtube.index');
    }

    public function test_youtube_connect_redirects_to_google(): void
    {
        $response = $this->actingAs($this->user)->get(route('youtube.connect'));
        $response->assertStatus(302);
        $response->assertRedirectContains('google.com');
    }

    public function test_youtube_callback_rejects_invalid_state(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('youtube.callback', ['state' => 'invalid', 'code' => 'test']));
        $response->assertRedirect(route('youtube.index'));
        $response->assertSessionHas('error');
    }

    public function test_youtube_disconnect_requires_ownership(): void
    {
        $account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'youtube',
        ]);

        $response = $this->actingAs($this->user)->delete(route('youtube.disconnect', $account->id));
        $response->assertRedirect(route('youtube.index'));
        $this->assertSoftDeleted('social_accounts', ['id' => $account->id]);
    }

    public function test_youtube_disconnect_prevents_cross_agency(): void
    {
        $otherAgency = Agency::factory()->create();
        $account = SocialAccount::factory()->create([
            'agency_id' => $otherAgency->id,
            'platform' => 'youtube',
        ]);

        $response = $this->actingAs($this->user)->delete(route('youtube.disconnect', $account->id));
        $response->assertStatus(403);
    }

    public function test_youtube_toggle_updates_status(): void
    {
        $account = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'youtube',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post(route('youtube.toggle', $account->id));
        $response->assertRedirect(route('youtube.index'));
        $this->assertDatabaseHas('social_accounts', [
            'id' => $account->id,
            'is_active' => false,
        ]);
    }
}
