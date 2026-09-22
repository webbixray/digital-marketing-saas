<?php

namespace Tests\Feature;

use App\Models\AbTest;
use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbTestControllerTest extends TestCase
{
    use RefreshDatabase;

    private $agency;
    private $user;
    private $account;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->account = SocialAccount::factory()->create(['agency_id' => $this->agency->id]);
        $this->actingAs($this->user);
    }

    public function test_it_lists_ab_tests_for_agency(): void
    {
        AbTest::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/ab-testing');
        $response->assertStatus(200);
        $response->assertViewHas('tests');
    }

    public function test_it_filters_tests_by_status(): void
    {
        AbTest::factory()->create(['agency_id' => $this->agency->id, 'status' => 'draft']);
        AbTest::factory()->create(['agency_id' => $this->agency->id, 'status' => 'running']);
        $response = $this->get('/ab-testing?status=draft');
        $response->assertStatus(200);
        $tests = $response->viewData('tests');
        $this->assertCount(1, $tests);
        $this->assertEquals('draft', $tests->first()->status);
    }

    public function test_it_filters_tests_by_type(): void
    {
        AbTest::factory()->create(['agency_id' => $this->agency->id, 'type' => 'content']);
        AbTest::factory()->create(['agency_id' => $this->agency->id, 'type' => 'timing']);
        $response = $this->get('/ab-testing?type=content');
        $response->assertStatus(200);
        $tests = $response->viewData('tests');
        $this->assertCount(1, $tests);
        $this->assertEquals('content', $tests->first()->type);
    }

    public function test_it_shows_create_form_with_accounts(): void
    {
        SocialAccount::factory()->count(2)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/ab-testing/create');
        $response->assertStatus(200);
        $response->assertViewHas('accounts');
    }

    public function test_it_creates_ab_test_with_valid_data(): void
    {
        $data = [
            'name' => 'Test A/B',
            'social_account_id' => $this->account->id,
            'type' => 'content',
            'platform' => 'facebook',
            'hypothesis' => 'Shorter captions get more engagement',
            'variant_a_content' => 'Short caption',
            'variant_b_content' => 'Long caption with more details',
            'sample_size' => 100,
        ];
        $response = $this->post('/ab-testing', $data);
        $response->assertStatus(302);
        $this->assertDatabaseHas('ab_tests', [
            'name' => 'Test A/B',
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);
    }

    public function test_it_validates_required_fields_on_store(): void
    {
        $response = $this->post('/ab-testing', []);
        $response->assertSessionHasErrors(['name', 'social_account_id', 'type', 'platform', 'variant_a_content', 'variant_b_content', 'sample_size']);
    }

    public function test_it_validates_sample_size_minimum(): void
    {
        $data = [
            'name' => 'Test',
            'social_account_id' => $this->account->id,
            'type' => 'content',
            'platform' => 'facebook',
            'variant_a_content' => 'A',
            'variant_b_content' => 'B',
            'sample_size' => 10,
        ];
        $response = $this->post('/ab-testing', $data);
        $response->assertSessionHasErrors(['sample_size']);
    }

    public function test_it_shows_single_test_with_analysis(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'variant_a_impressions' => 100,
            'variant_b_impressions' => 100,
            'variant_a_engagement' => 20,
            'variant_b_engagement' => 30,
        ]);
        $response = $this->get("/ab-testing/{$test->id}");
        $response->assertStatus(200);
        $response->assertViewHas(['test', 'analysis']);
    }

    public function test_it_returns_analysis_as_json(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'variant_a_impressions' => 100,
            'variant_b_impressions' => 100,
            'variant_a_engagement' => 20,
            'variant_b_engagement' => 30,
        ]);
        $response = $this->getJson("/ab-testing/{$test->id}/analyze");
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'winner',
            'confidence',
            'chi_squared',
            'p_value',
            'significant',
            'effect_size',
            'sample_adequate',
            'a_engagement_rate',
            'b_engagement_rate',
            'recommendation',
        ]);
    }

    public function test_it_starts_draft_test(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);
        $response = $this->post("/ab-testing/{$test->id}/start");
        $response->assertStatus(302);
        $this->assertDatabaseHas('ab_tests', [
            'id' => $test->id,
            'status' => 'running',
        ]);
        $this->assertNotNull($test->fresh()->started_at);
    }

    public function test_it_cannot_start_non_draft_test(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'running',
        ]);
        $response = $this->post("/ab-testing/{$test->id}/start");
        $response->assertSessionHas('error');
    }

    public function test_it_pauses_running_test(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'running',
        ]);
        $response = $this->post("/ab-testing/{$test->id}/pause");
        $response->assertStatus(302);
        $this->assertDatabaseHas('ab_tests', [
            'id' => $test->id,
            'status' => 'paused',
        ]);
    }

    public function test_it_cannot_pause_non_running_test(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);
        $response = $this->post("/ab-testing/{$test->id}/pause");
        $response->assertSessionHas('error');
    }

    public function test_it_completes_running_test(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'running',
            'variant_a_impressions' => 100,
            'variant_b_impressions' => 100,
            'variant_a_engagement' => 20,
            'variant_b_engagement' => 30,
        ]);
        $response = $this->post("/ab-testing/{$test->id}/complete");
        $response->assertStatus(302);
        $this->assertDatabaseHas('ab_tests', [
            'id' => $test->id,
            'status' => 'completed',
        ]);
        $this->assertNotNull($test->fresh()->ended_at);
    }

    public function test_it_cannot_complete_non_running_test(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'paused',
        ]);
        $response = $this->post("/ab-testing/{$test->id}/complete");
        $response->assertSessionHas('error');
    }

    public function test_it_tracks_events_for_running_test(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'running',
        ]);
        $response = $this->postJson("/ab-testing/{$test->id}/track/a/impression");
        $response->assertStatus(200);
        $this->assertDatabaseHas('ab_test_logs', [
            'ab_test_id' => $test->id,
            'variant' => 'a',
            'event' => 'impression',
        ]);
        $this->assertEquals(1, $test->fresh()->variant_a_impressions);
    }

    public function test_it_rejects_tracking_for_non_running_test(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);
        $response = $this->postJson("/ab-testing/{$test->id}/track/a/impression");
        $response->assertStatus(400);
    }

    public function test_it_rejects_invalid_variant(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'running',
        ]);
        $response = $this->postJson("/ab-testing/{$test->id}/track/c/impression");
        $response->assertStatus(400);
    }

    public function test_it_rejects_invalid_event_type(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'running',
        ]);
        $response = $this->postJson("/ab-testing/{$test->id}/track/a/invalid");
        $response->assertStatus(400);
    }

    public function test_it_prevents_accessing_other_agency_tests(): void
    {
        $otherAgency = Agency::factory()->create();
        $test = AbTest::factory()->create(['agency_id' => $otherAgency->id]);
        $response = $this->get("/ab-testing/{$test->id}");
        $response->assertStatus(403);
    }

    public function test_it_prevents_starting_other_agency_tests(): void
    {
        $otherAgency = Agency::factory()->create();
        $test = AbTest::factory()->create([
            'agency_id' => $otherAgency->id,
            'status' => 'draft',
        ]);
        $response = $this->post("/ab-testing/{$test->id}/start");
        $response->assertStatus(403);
    }

    public function test_it_requires_authentication(): void
    {
        auth()->logout();
        $response = $this->get('/ab-testing');
        $response->assertRedirect('/login');
    }

    public function test_it_calculates_chi_squared_significance(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'variant_a_impressions' => 1000,
            'variant_b_impressions' => 1000,
            'variant_a_engagement' => 100,
            'variant_b_engagement' => 150,
        ]);
        $chiSq = $test->chiSquared();
        $this->assertArrayHasKey('chi_squared', $chiSq);
        $this->assertArrayHasKey('p_value', $chiSq);
        $this->assertArrayHasKey('significant', $chiSq);
        $this->assertTrue($chiSq['significant']);
    }

    public function test_it_detects_no_significance_for_similar_variants(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'variant_a_impressions' => 100,
            'variant_b_impressions' => 100,
            'variant_a_engagement' => 20,
            'variant_b_engagement' => 21,
        ]);
        $chiSq = $test->chiSquared();
        $this->assertFalse($chiSq['significant']);
    }

    public function test_it_calculates_engagement_rate(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'variant_a_impressions' => 200,
            'variant_a_engagement' => 50,
            'variant_b_impressions' => 200,
            'variant_b_engagement' => 75,
        ]);
        $this->assertEquals(25.0, $test->engagementRate('a'));
        $this->assertEquals(37.5, $test->engagementRate('b'));
    }

    public function test_it_calculates_click_through_rate(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'variant_a_impressions' => 500,
            'variant_a_clicks' => 25,
            'variant_b_impressions' => 500,
            'variant_b_clicks' => 40,
        ]);
        $this->assertEquals(5.0, $test->clickThroughRate('a'));
        $this->assertEquals(8.0, $test->clickThroughRate('b'));
    }

    public function test_it_determines_winner_correctly(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'variant_a_impressions' => 1000,
            'variant_b_impressions' => 1000,
            'variant_a_engagement' => 100,
            'variant_b_engagement' => 150,
        ]);
        $this->assertEquals('b', $test->determineWinner());
    }

    public function test_it_returns_inconclusive_for_insufficient_data(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'variant_a_impressions' => 10,
            'variant_b_impressions' => 10,
            'variant_a_engagement' => 2,
            'variant_b_engagement' => 3,
        ]);
        $this->assertEquals('inconclusive', $test->determineWinner());
    }

    public function test_it_generates_full_analysis(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'variant_a_impressions' => 1000,
            'variant_b_impressions' => 1000,
            'variant_a_engagement' => 100,
            'variant_b_engagement' => 150,
            'sample_size' => 500,
        ]);
        $analysis = $test->analyze();
        $this->assertEquals('b', $analysis['winner']);
        $this->assertTrue($analysis['significant']);
        $this->assertTrue($analysis['sample_adequate']);
        $this->assertNotEmpty($analysis['recommendation']);
    }

    public function test_it_creates_timing_test_type(): void
    {
        $data = [
            'name' => 'Morning vs Evening',
            'social_account_id' => $this->account->id,
            'type' => 'timing',
            'platform' => 'instagram',
            'variant_a_content' => 'Post at 8am',
            'variant_b_content' => 'Post at 6pm',
            'sample_size' => 200,
        ];
        $response = $this->post('/ab-testing', $data);
        $response->assertStatus(302);
        $this->assertDatabaseHas('ab_tests', [
            'name' => 'Morning vs Evening',
            'type' => 'timing',
        ]);
    }

    public function test_it_creates_hashtag_test_type(): void
    {
        $data = [
            'name' => 'Hashtag Strategy',
            'social_account_id' => $this->account->id,
            'type' => 'hashtag',
            'platform' => 'instagram',
            'variant_a_content' => '#marketing #social',
            'variant_b_content' => '#digitalmarketing #contentcreator',
            'sample_size' => 150,
        ];
        $response = $this->post('/ab-testing', $data);
        $response->assertStatus(302);
        $this->assertDatabaseHas('ab_tests', [
            'type' => 'hashtag',
        ]);
    }

    public function test_it_creates_media_test_type(): void
    {
        $data = [
            'name' => 'Image vs Video',
            'social_account_id' => $this->account->id,
            'type' => 'media',
            'platform' => 'facebook',
            'variant_a_content' => 'Static image post',
            'variant_b_content' => 'Video post',
            'sample_size' => 300,
        ];
        $response = $this->post('/ab-testing', $data);
        $response->assertStatus(302);
        $this->assertDatabaseHas('ab_tests', [
            'type' => 'media',
        ]);
    }

    public function test_it_displays_stats_on_index(): void
    {
        AbTest::factory()->count(2)->create(['agency_id' => $this->agency->id, 'status' => 'draft']);
        AbTest::factory()->create(['agency_id' => $this->agency->id, 'status' => 'running']);
        AbTest::factory()->create(['agency_id' => $this->agency->id, 'status' => 'completed']);
        $response = $this->get('/ab-testing');
        $response->assertStatus(200);
        $response->assertViewHas('stats');
        $stats = $response->viewData('stats');
        $this->assertEquals(4, $stats['total']);
        $this->assertEquals(2, $stats['draft']);
        $this->assertEquals(1, $stats['running']);
        $this->assertEquals(1, $stats['completed']);
    }

    public function test_it_paginates_results(): void
    {
        AbTest::factory()->count(20)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/ab-testing');
        $response->assertStatus(200);
        $tests = $response->viewData('tests');
        $this->assertEquals(15, $tests->count());
    }

    public function test_it_tracks_engagement_event(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'running',
        ]);
        $response = $this->postJson("/ab-testing/{$test->id}/track/b/engagement");
        $response->assertStatus(200);
        $this->assertEquals(1, $test->fresh()->variant_b_engagement);
    }

    public function test_it_tracks_click_event(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'running',
        ]);
        $response = $this->postJson("/ab-testing/{$test->id}/track/a/click");
        $response->assertStatus(200);
        $this->assertEquals(1, $test->fresh()->variant_a_clicks);
    }
}
