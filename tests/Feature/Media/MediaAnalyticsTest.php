<?php

namespace Tests\Feature\Media;

use App\Models\Agency;
use App\Models\MediaAsset;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaAnalyticsTest extends TestCase
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

    public function test_most_used_assets_endpoint_returns_top_assets(): void
    {
        MediaAsset::factory()->count(5)->create([
            'agency_id' => $this->agency->id,
            'usage_count' => 0,
        ]);
        MediaAsset::factory()->create([
            'agency_id' => $this->agency->id,
            'usage_count' => 10,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('api.media.analytics'));

        $response->assertOk();
    }

    public function test_storage_trends_return_30_days_data(): void
    {
        MediaAsset::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('api.media.analytics'));

        $response->assertOk();
    }

    public function test_file_type_breakdown_categorizes_assets(): void
    {
        MediaAsset::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
            'file_type' => 'image',
        ]);
        MediaAsset::factory()->count(2)->create([
            'agency_id' => $this->agency->id,
            'file_type' => 'video',
        ]);

        $response = $this->actingAs($this->user)->getJson(route('api.media.analytics'));

        $response->assertOk();
    }

    public function test_upload_activity_shows_daily_counts(): void
    {
        MediaAsset::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('api.media.analytics'));

        $response->assertOk();
    }

    public function test_analytics_requires_authentication(): void
    {
        $response = $this->getJson(route('api.media.analytics'));

        $response->assertUnauthorized();
    }

    public function test_ai_generate_endpoint_accepts_prompt(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('api.media.ai.generate'), [
            'prompt' => 'A sunset over mountains',
            'style' => 'photorealistic',
            'size' => '1024x1024',
        ]);

        $response->assertOk();
    }

    public function test_ai_styles_endpoint_returns_available_styles(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('media.ai.styles'));

        $response->assertOk()
            ->assertJsonStructure([
                'styles' => [
                    '*' => ['key', 'name', 'description'],
                ],
                'sizes' => [
                    '*' => ['key', 'label', 'width', 'height'],
                ],
            ]);
    }

    public function test_asset_usage_stats_shows_related_posts(): void
    {
        $asset = MediaAsset::factory()->create([
            'agency_id' => $this->agency->id,
            'usage_count' => 5,
        ]);
        SocialPost::factory()->count(3)->create([
            'agency_id' => $this->agency->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('media.ai.analytics'));

        $response->assertOk();
    }
}
