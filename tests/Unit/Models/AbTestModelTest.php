<?php

namespace Tests\Unit\Models;

use App\Models\AbTest;
use App\Models\AbTestLog;
use App\Models\Agency;
use App\Models\SocialAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbTestModelTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // Basic Creation & Fillable
    // =========================================================================

    public function test_ab_test_can_be_created_with_factory(): void
    {
        $abTest = AbTest::factory()->create();

        $this->assertDatabaseHas('ab_tests', [
            'id' => $abTest->id,
            'name' => $abTest->name,
        ]);
    }

    public function test_ab_test_fillable_attributes(): void
    {
        $agency = Agency::factory()->create();
        $socialAccount = SocialAccount::factory()->create();

        $abTest = AbTest::create([
            'agency_id' => $agency->id,
            'social_account_id' => $socialAccount->id,
            'name' => 'Test A/B Campaign',
            'status' => 'draft',
            'type' => 'content',
            'platform' => 'facebook',
            'hypothesis' => 'Testing hypothesis',
            'variant_a_content' => 'Content A',
            'variant_b_content' => 'Content B',
            'variant_a_media' => ['image1.jpg'],
            'variant_b_media' => ['image2.jpg'],
            'variant_a_impressions' => 0,
            'variant_b_impressions' => 0,
            'variant_a_engagement' => 0,
            'variant_b_engagement' => 0,
            'variant_a_clicks' => 0,
            'variant_b_clicks' => 0,
            'winner' => null,
            'confidence' => 0,
            'sample_size' => 100,
            'started_at' => null,
            'ended_at' => null,
        ]);

        $this->assertNotNull($abTest);
        $this->assertEquals('Test A/B Campaign', $abTest->name);
        $this->assertEquals('draft', $abTest->status);
        $this->assertEquals('content', $abTest->type);
        $this->assertEquals('facebook', $abTest->platform);
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function test_ab_test_belongs_to_agency(): void
    {
        $agency = Agency::factory()->create();
        $abTest = AbTest::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $abTest->agency);
        $this->assertEquals($agency->id, $abTest->agency->id);
    }

    public function test_ab_test_belongs_to_social_account(): void
    {
        $socialAccount = SocialAccount::factory()->create();
        $abTest = AbTest::factory()->create(['social_account_id' => $socialAccount->id]);

        $this->assertInstanceOf(SocialAccount::class, $abTest->socialAccount);
        $this->assertEquals($socialAccount->id, $abTest->socialAccount->id);
    }

    public function test_ab_test_has_many_logs(): void
    {
        $abTest = AbTest::factory()->create();
        AbTestLog::factory()->count(3)->create(['ab_test_id' => $abTest->id]);

        $this->assertCount(3, $abTest->logs);
        $this->assertInstanceOf(AbTestLog::class, $abTest->logs->first());
    }

    // =========================================================================
    // Casts
    // =========================================================================

    public function test_ab_test_media_casts_to_array(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_media' => ['img1.jpg', 'img2.jpg'],
            'variant_b_media' => ['img3.jpg'],
        ]);

        $this->assertIsArray($abTest->variant_a_media);
        $this->assertIsArray($abTest->variant_b_media);
        $this->assertEquals(['img1.jpg', 'img2.jpg'], $abTest->variant_a_media);
    }

    public function test_ab_test_impressions_cast_to_integer(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_impressions' => '150',
            'variant_b_impressions' => '200',
        ]);

        $this->assertIsInt($abTest->variant_a_impressions);
        $this->assertIsInt($abTest->variant_b_impressions);
        $this->assertEquals(150, $abTest->variant_a_impressions);
    }

    public function test_ab_test_engagement_cast_to_integer(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_engagement' => '25',
            'variant_b_engagement' => '40',
        ]);

        $this->assertIsInt($abTest->variant_a_engagement);
        $this->assertIsInt($abTest->variant_b_engagement);
    }

    public function test_ab_test_clicks_cast_to_integer(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_clicks' => '10',
            'variant_b_clicks' => '15',
        ]);

        $this->assertIsInt($abTest->variant_a_clicks);
        $this->assertIsInt($abTest->variant_b_clicks);
    }

    public function test_ab_test_confidence_casts_to_decimal(): void
    {
        $abTest = AbTest::factory()->create(['confidence' => 95.50]);

        $this->assertEquals(95.50, $abTest->confidence);
    }

    public function test_ab_test_dates_cast_to_datetime(): void
    {
        $abTest = AbTest::factory()->create([
            'started_at' => '2024-01-15 10:00:00',
            'ended_at' => '2024-02-15 10:00:00',
        ]);

        $this->assertInstanceOf(\Carbon\Carbon::class, $abTest->started_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $abTest->ended_at);
    }

    // =========================================================================
    // Constants
    // =========================================================================

    public function test_ab_test_type_constants_exist(): void
    {
        $this->assertEquals('content', AbTest::TYPE_CONTENT);
        $this->assertEquals('timing', AbTest::TYPE_TIMING);
        $this->assertEquals('hashtag', AbTest::TYPE_HASHTAG);
        $this->assertEquals('media', AbTest::TYPE_MEDIA);
    }

    public function test_ab_test_types_array_has_all_types(): void
    {
        $types = AbTest::TYPES;

        $this->assertArrayHasKey('content', $types);
        $this->assertArrayHasKey('timing', $types);
        $this->assertArrayHasKey('hashtag', $types);
        $this->assertArrayHasKey('media', $types);
        $this->assertCount(4, $types);
    }

    public function test_ab_test_status_constants_exist(): void
    {
        $this->assertEquals('draft', AbTest::STATUS_DRAFT);
        $this->assertEquals('running', AbTest::STATUS_RUNNING);
        $this->assertEquals('paused', AbTest::STATUS_PAUSED);
        $this->assertEquals('completed', AbTest::STATUS_COMPLETED);
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function test_scope_by_agency_filters_correctly(): void
    {
        $agency1 = Agency::factory()->create();
        $agency2 = Agency::factory()->create();
        AbTest::factory()->create(['agency_id' => $agency1->id]);
        AbTest::factory()->create(['agency_id' => $agency2->id]);

        $results = AbTest::byAgency($agency1->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($agency1->id, $results->first()->agency_id);
    }

    public function test_scope_running_filters_correctly(): void
    {
        AbTest::factory()->create(['status' => 'running']);
        AbTest::factory()->create(['status' => 'draft']);
        AbTest::factory()->create(['status' => 'completed']);

        $results = AbTest::running()->get();

        $this->assertCount(1, $results);
        $this->assertEquals('running', $results->first()->status);
    }

    public function test_scope_for_status_filters_correctly(): void
    {
        AbTest::factory()->create(['status' => 'completed']);
        AbTest::factory()->create(['status' => 'draft']);
        AbTest::factory()->create(['status' => 'completed']);

        $results = AbTest::forStatus('completed')->get();

        $this->assertCount(2, $results);
        $results->each(function ($test) {
            $this->assertEquals('completed', $test->status);
        });
    }

    // =========================================================================
    // HasAgency Trait Scopes
    // =========================================================================

    public function test_scope_for_agency_from_trait_filters_correctly(): void
    {
        $agency1 = Agency::factory()->create();
        $agency2 = Agency::factory()->create();
        AbTest::factory()->create(['agency_id' => $agency1->id]);
        AbTest::factory()->create(['agency_id' => $agency2->id]);

        $results = AbTest::forAgency($agency1->id)->get();

        $this->assertCount(1, $results);
        $this->assertEquals($agency1->id, $results->first()->agency_id);
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    public function test_is_running_returns_true_for_running_status(): void
    {
        $abTest = AbTest::factory()->create(['status' => 'running']);
        $this->assertTrue($abTest->isRunning());
    }

    public function test_is_running_returns_false_for_draft(): void
    {
        $abTest = AbTest::factory()->create(['status' => 'draft']);
        $this->assertFalse($abTest->isRunning());
    }

    public function test_is_completed_returns_true_for_completed_status(): void
    {
        $abTest = AbTest::factory()->create(['status' => 'completed']);
        $this->assertTrue($abTest->isCompleted());
    }

    public function test_is_completed_returns_false_for_running(): void
    {
        $abTest = AbTest::factory()->create(['status' => 'running']);
        $this->assertFalse($abTest->isCompleted());
    }

    public function test_is_draft_returns_true_for_draft_status(): void
    {
        $abTest = AbTest::factory()->create(['status' => 'draft']);
        $this->assertTrue($abTest->isDraft());
    }

    public function test_is_draft_returns_false_for_completed(): void
    {
        $abTest = AbTest::factory()->create(['status' => 'completed']);
        $this->assertFalse($abTest->isDraft());
    }

    // =========================================================================
    // Calculation Methods
    // =========================================================================

    public function test_engagement_rate_calculates_correctly(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_impressions' => 1000,
            'variant_a_engagement' => 150,
            'variant_b_impressions' => 1000,
            'variant_b_engagement' => 200,
        ]);

        $this->assertEquals(15.0, $abTest->engagementRate('a'));
        $this->assertEquals(20.0, $abTest->engagementRate('b'));
    }

    public function test_engagement_rate_returns_zero_when_no_impressions(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_impressions' => 0,
            'variant_a_engagement' => 0,
        ]);

        $this->assertEquals(0, $abTest->engagementRate('a'));
    }

    public function test_click_through_rate_calculates_correctly(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_impressions' => 1000,
            'variant_a_clicks' => 50,
            'variant_b_impressions' => 1000,
            'variant_b_clicks' => 75,
        ]);

        $this->assertEquals(5.0, $abTest->clickThroughRate('a'));
        $this->assertEquals(7.5, $abTest->clickThroughRate('b'));
    }

    public function test_click_through_rate_returns_zero_when_no_impressions(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_impressions' => 0,
            'variant_a_clicks' => 0,
        ]);

        $this->assertEquals(0, $abTest->clickThroughRate('a'));
    }

    public function test_calculate_confidence_returns_zero_for_small_samples(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_impressions' => 10,
            'variant_a_engagement' => 5,
            'variant_b_impressions' => 10,
            'variant_b_engagement' => 8,
        ]);

        $this->assertEquals(0, $abTest->calculateConfidence());
    }

    public function test_calculate_confidence_returns_value_for_large_samples(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_impressions' => 1000,
            'variant_a_engagement' => 100,
            'variant_b_impressions' => 1000,
            'variant_b_engagement' => 150,
        ]);

        $confidence = $abTest->calculateConfidence();
        $this->assertGreaterThan(0, $confidence);
        $this->assertLessThanOrEqual(99.99, $confidence);
    }

    public function test_chi_squared_returns_zero_for_no_data(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_impressions' => 0,
            'variant_a_engagement' => 0,
            'variant_b_impressions' => 0,
            'variant_b_engagement' => 0,
        ]);

        $result = $abTest->chiSquared();

        $this->assertEquals(0, $result['chi_squared']);
        $this->assertEquals(1, $result['p_value']);
        $this->assertFalse($result['significant']);
        $this->assertEquals(1, $result['degrees_of_freedom']);
    }

    public function test_chi_squared_returns_result_for_valid_data(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_impressions' => 1000,
            'variant_a_engagement' => 100,
            'variant_b_impressions' => 1000,
            'variant_b_engagement' => 150,
        ]);

        $result = $abTest->chiSquared();

        $this->assertArrayHasKey('chi_squared', $result);
        $this->assertArrayHasKey('p_value', $result);
        $this->assertArrayHasKey('significant', $result);
        $this->assertArrayHasKey('degrees_of_freedom', $result);
        $this->assertGreaterThan(0, $result['chi_squared']);
    }

    public function test_determine_winner_returns_inconclusive_for_no_significance(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_impressions' => 100,
            'variant_a_engagement' => 10,
            'variant_b_impressions' => 100,
            'variant_b_engagement' => 12,
        ]);

        $this->assertEquals('inconclusive', $abTest->determineWinner());
    }

    public function test_determine_winner_returns_b_for_significant_b_winner(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_impressions' => 1000,
            'variant_a_engagement' => 50,
            'variant_b_impressions' => 1000,
            'variant_b_engagement' => 200,
        ]);

        $this->assertEquals('b', $abTest->determineWinner());
    }

    public function test_analyze_returns_full_analysis_structure(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_impressions' => 1000,
            'variant_a_engagement' => 100,
            'variant_a_clicks' => 30,
            'variant_b_impressions' => 1000,
            'variant_b_engagement' => 150,
            'variant_b_clicks' => 50,
            'sample_size' => 500,
            'started_at' => now()->subDays(3),
            'ended_at' => now(),
        ]);

        $analysis = $abTest->analyze();

        $this->assertArrayHasKey('winner', $analysis);
        $this->assertArrayHasKey('confidence', $analysis);
        $this->assertArrayHasKey('chi_squared', $analysis);
        $this->assertArrayHasKey('p_value', $analysis);
        $this->assertArrayHasKey('significant', $analysis);
        $this->assertArrayHasKey('effect_size', $analysis);
        $this->assertArrayHasKey('sample_adequate', $analysis);
        $this->assertArrayHasKey('a_engagement_rate', $analysis);
        $this->assertArrayHasKey('b_engagement_rate', $analysis);
        $this->assertArrayHasKey('a_ctr', $analysis);
        $this->assertArrayHasKey('b_ctr', $analysis);
        $this->assertArrayHasKey('duration_hours', $analysis);
        $this->assertArrayHasKey('recommendation', $analysis);
    }

    public function test_analyze_sample_adequate_is_true_when_threshold_met(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_impressions' => 1000,
            'variant_b_impressions' => 1000,
            'sample_size' => 500,
        ]);

        $analysis = $abTest->analyze();

        $this->assertTrue($analysis['sample_adequate']);
    }

    public function test_analyze_sample_adequate_is_false_when_threshold_not_met(): void
    {
        $abTest = AbTest::factory()->create([
            'variant_a_impressions' => 100,
            'variant_b_impressions' => 100,
            'sample_size' => 500,
        ]);

        $analysis = $abTest->analyze();

        $this->assertFalse($analysis['sample_adequate']);
    }
}
