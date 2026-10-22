<?php

namespace Tests\Unit\Models;

use App\Models\Agency;
use App\Models\AnalyticsEvent;
use App\Models\SocialPost;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsEventModelTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Basic Creation & Fillable
    // =========================================================================

    public function test_analytics_event_can_be_created_with_factory(): void
    {
        $event = AnalyticsEvent::factory()->create();

        $this->assertDatabaseHas('analytics_events', [
            'id' => $event->id,
            'event_type' => $event->event_type,
        ]);
    }

    public function test_analytics_event_fillable_attributes(): void
    {
        $agency = Agency::factory()->create();
        $user = User::factory()->create();
        $socialPost = SocialPost::factory()->create();

        $event = AnalyticsEvent::create([
            'agency_id' => $agency->id,
            'user_id' => $user->id,
            'event_type' => 'page_view',
            'event_data' => ['source' => 'web', 'page' => 'dashboard'],
            'platform' => 'facebook',
            'social_post_id' => $socialPost->id,
            'metadata' => ['browser' => 'chrome', 'ip' => '127.0.0.1'],
        ]);

        $this->assertNotNull($event);
        $this->assertEquals('page_view', $event->event_type);
        $this->assertEquals('facebook', $event->platform);
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function test_analytics_event_belongs_to_agency(): void
    {
        $agency = Agency::factory()->create();
        $event = AnalyticsEvent::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $event->agency);
        $this->assertEquals($agency->id, $event->agency->id);
    }

    public function test_analytics_event_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $event = AnalyticsEvent::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $event->user);
        $this->assertEquals($user->id, $event->user->id);
    }

    public function test_analytics_event_belongs_to_social_post(): void
    {
        $socialPost = SocialPost::factory()->create();
        $event = AnalyticsEvent::factory()->create(['social_post_id' => $socialPost->id]);

        $this->assertInstanceOf(SocialPost::class, $event->socialPost);
        $this->assertEquals($socialPost->id, $event->socialPost->id);
    }

    public function test_analytics_event_user_can_be_null(): void
    {
        $event = AnalyticsEvent::factory()->create(['user_id' => null]);

        $this->assertNull($event->user_id);
        $this->assertNull($event->user);
    }

    public function test_analytics_event_social_post_can_be_null(): void
    {
        $event = AnalyticsEvent::factory()->create(['social_post_id' => null]);

        $this->assertNull($event->social_post_id);
        $this->assertNull($event->socialPost);
    }

    // =========================================================================
    // Casts
    // =========================================================================

    public function test_event_data_casts_to_array(): void
    {
        $event = AnalyticsEvent::factory()->create([
            'event_data' => ['source' => 'web', 'value' => 42],
        ]);

        $this->assertIsArray($event->event_data);
        $this->assertEquals(['source' => 'web', 'value' => 42], $event->event_data);
    }

    public function test_metadata_casts_to_array(): void
    {
        $event = AnalyticsEvent::factory()->create([
            'metadata' => ['browser' => 'firefox', 'ip' => '192.168.1.1'],
        ]);

        $this->assertIsArray($event->metadata);
        $this->assertEquals(['browser' => 'firefox', 'ip' => '192.168.1.1'], $event->metadata);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function test_scope_for_agency_from_trait_filters_correctly(): void
    {
        $agency1 = Agency::factory()->create();
        $agency2 = Agency::factory()->create();
        AnalyticsEvent::factory()->create(['agency_id' => $agency1->id]);
        AnalyticsEvent::factory()->create(['agency_id' => $agency2->id]);

        $results = AnalyticsEvent::forAgency($agency1->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($agency1->id, $results->first()->agency_id);
    }

    // =========================================================================
    // HasAgency Trait
    // =========================================================================

    public function test_analytics_event_uses_has_agency_trait(): void
    {
        $agency = Agency::factory()->create();
        $event = AnalyticsEvent::factory()->create(['agency_id' => $agency->id]);

        $this->assertEquals($agency->id, $event->agency_id);
        $this->assertDatabaseHas('analytics_events', [
            'id' => $event->id,
            'agency_id' => $agency->id,
        ]);
    }

    // =========================================================================
    // Event Types
    // =========================================================================

    public function test_analytics_event_supports_multiple_event_types(): void
    {
        $types = ['page_view', 'click', 'impression', 'engagement', 'conversion'];

        foreach ($types as $type) {
            $event = AnalyticsEvent::factory()->create(['event_type' => $type]);
            $this->assertEquals($type, $event->event_type);
        }

        $this->assertDatabaseCount('analytics_events', 5);
    }

    // =========================================================================
    // Factory
    // =========================================================================

    public function test_factory_creates_valid_event(): void
    {
        $event = AnalyticsEvent::factory()->create();

        $this->assertNotNull($event->agency_id);
        $this->assertNotNull($event->event_type);
        $this->assertNotNull($event->platform);
        $this->assertIsArray($event->event_data);
        $this->assertIsArray($event->metadata);
    }
}
