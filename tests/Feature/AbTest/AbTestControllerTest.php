<?php

namespace Tests\Feature\AbTest;

use App\Models\AbTest;
use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbTestControllerTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;
    private SocialAccount $socialAccount;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
        $this->socialAccount = SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'is_active' => true,
        ]);
    }

    public function test_index_requires_auth(): void
    {
        $response = $this->get(route('ab-testing.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_index_returns_tests_for_own_agency(): void
    {
        AbTest::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        AbTest::factory()->count(2)->create(['agency_id' => Agency::factory()->create()->id]);

        $response = $this->actingAs($this->user)->get(route('ab-testing.index'));

        $response->assertOk();
        $tests = $response->viewData('tests');
        $this->assertCount(3, $tests);
    }

    public function test_index_filters_by_status(): void
    {
        AbTest::factory()->create(['agency_id' => $this->agency->id, 'status' => 'draft']);
        AbTest::factory()->create(['agency_id' => $this->agency->id, 'status' => 'running']);

        $response = $this->actingAs($this->user)->get(route('ab-testing.index', ['status' => 'running']));

        $response->assertOk();
        $tests = $response->viewData('tests');
        $this->assertCount(1, $tests);
        $this->assertEquals('running', $tests->first()->status);
    }

    public function test_create_returns_form_with_social_accounts(): void
    {
        $response = $this->actingAs($this->user)->get(route('ab-testing.create'));

        $response->assertOk();
        $response->assertViewHas('accounts');
    }

    public function test_store_creates_new_ab_test(): void
    {
        $response = $this->actingAs($this->user)->post(route('ab-testing.store'), [
            'name' => 'Test Campaign',
            'social_account_id' => $this->socialAccount->id,
            'type' => 'content',
            'platform' => 'facebook',
            'hypothesis' => 'Testing variant B',
            'variant_a_content' => 'Original post',
            'variant_b_content' => 'Modified post',
            'sample_size' => 100,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('ab_tests', [
            'agency_id' => $this->agency->id,
            'name' => 'Test Campaign',
            'status' => 'draft',
        ]);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post(route('ab-testing.store'), []);

        $response->assertSessionHasErrors(['name', 'social_account_id', 'type', 'platform', 'variant_a_content', 'variant_b_content', 'sample_size']);
    }

    public function test_show_returns_test_details(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->socialAccount->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('ab-testing.show', $test));

        $response->assertOk();
        $response->assertViewHas('test');
    }

    public function test_show_prevents_cross_agency_access(): void
    {
        $otherAgency = Agency::factory()->create();
        $test = AbTest::factory()->create(['agency_id' => $otherAgency->id]);

        $response = $this->actingAs($this->user)->get(route('ab-testing.show', $test));

        $response->assertForbidden();
    }

    public function test_analyze_returns_json_analysis(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'social_account_id' => $this->socialAccount->id,
            'variant_a_impressions' => 500,
            'variant_b_impressions' => 500,
            'variant_a_engagement' => 50,
            'variant_b_engagement' => 75,
        ]);

        $response = $this->actingAs($this->user)->getJson(route('ab-testing.analyze', $test));

        $response->assertOk();
        $response->assertJsonStructure(['winner', 'confidence', 'chi_squared', 'p_value', 'significant']);
    }

    public function test_start_draft_test(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'draft',
        ]);

        $response = $this->actingAs($this->user)->post(route('ab-testing.start', $test));

        $response->assertRedirect();
        $this->assertDatabaseHas('ab_tests', [
            'id' => $test->id,
            'status' => 'running',
        ]);
    }

    public function test_track_event_records_engagement(): void
    {
        $test = AbTest::factory()->create([
            'agency_id' => $this->agency->id,
            'status' => 'running',
            'social_account_id' => $this->socialAccount->id,
        ]);

        $response = $this->actingAs($this->user)->postJson(route('ab-testing.track', ['test' => $test, 'variant' => 'a', 'event' => 'engagement']));

        $response->assertOk();
        $response->assertJson(['success' => true, 'variant' => 'a', 'event' => 'engagement']);
    }
}
