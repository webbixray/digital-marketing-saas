<?php

namespace Tests\Feature;

use App\Models\AbTest;
use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AbTestTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->actingAs($this->user);
    }

    public function test_it_lists_ab_tests(): void
    {
        AbTest::factory()->count(3)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/ab-testing');
        $response->assertStatus(200);
        $response->assertViewHas('tests');
    }

    public function test_it_filters_by_status(): void
    {
        AbTest::factory()->create(['agency_id' => $this->agency->id, 'status' => 'draft']);
        AbTest::factory()->create(['agency_id' => $this->agency->id, 'status' => 'running']);
        $response = $this->get('/ab-testing?status=draft');
        $response->assertStatus(200);
        $tests = $response->viewData('tests');
        $this->assertCount(1, $tests);
    }

    public function test_it_shows_create_form(): void
    {
        SocialAccount::factory()->count(2)->create(['agency_id' => $this->agency->id]);
        $response = $this->get('/ab-testing/create');
        $response->assertStatus(200);
        $response->assertViewHas('accounts');
    }

    public function test_it_creates_ab_test(): void
    {
        $account = SocialAccount::factory()->create(['agency_id' => $this->agency->id]);
        $data = [
            'name' => 'Test A/B',
            'social_account_id' => $account->id,
            'type' => 'content',
            'platform' => 'facebook',
            'hypothesis' => 'Test hypothesis',
            'variant_a_content' => 'Variant A',
            'variant_b_content' => 'Variant B',
            'sample_size' => 100,
        ];
        $response = $this->post('/ab-testing', $data);
        $response->assertStatus(302);
        $this->assertDatabaseHas('ab_tests', ['name' => 'Test A/B', 'agency_id' => $this->agency->id]);
    }

    public function test_it_validates_required_fields(): void
    {
        $response = $this->post('/ab-testing', []);
        $response->assertStatus(302);
        $response->assertSessionHasErrors(['name', 'social_account_id', 'type', 'platform', 'variant_a_content', 'variant_b_content', 'sample_size']);
    }

    public function test_it_shows_single_test(): void
    {
        $test = AbTest::factory()->create(['agency_id' => $this->agency->id]);
        $response = $this->get("/ab-testing/{$test->id}");
        $response->assertStatus(200);
        $response->assertViewHas('test');
    }

    public function test_it_starts_test(): void
    {
        $test = AbTest::factory()->create(['agency_id' => $this->agency->id, 'status' => 'draft']);
        $response = $this->post("/ab-testing/{$test->id}/start");
        $response->assertStatus(302);
        $this->assertDatabaseHas('ab_tests', ['id' => $test->id, 'status' => 'running']);
    }

    public function test_it_cannot_start_non_draft_test(): void
    {
        $test = AbTest::factory()->create(['agency_id' => $this->agency->id, 'status' => 'running']);
        $response = $this->post("/ab-testing/{$test->id}/start");
        $response->assertStatus(302);
        $response->assertSessionHas('error');
    }

    public function test_it_pauses_test(): void
    {
        $test = AbTest::factory()->create(['agency_id' => $this->agency->id, 'status' => 'running']);
        $response = $this->post("/ab-testing/{$test->id}/pause");
        $response->assertStatus(302);
        $this->assertDatabaseHas('ab_tests', ['id' => $test->id, 'status' => 'paused']);
    }

    public function test_it_completes_test(): void
    {
        $test = AbTest::factory()->create(['agency_id' => $this->agency->id, 'status' => 'running']);
        $response = $this->post("/ab-testing/{$test->id}/complete");
        $response->assertStatus(302);
        $this->assertDatabaseHas('ab_tests', ['id' => $test->id, 'status' => 'completed']);
    }

    public function test_it_prevents_accessing_other_agency_tests(): void
    {
        $otherAgency = Agency::factory()->create();
        $test = AbTest::factory()->create(['agency_id' => $otherAgency->id]);
        $response = $this->get("/ab-testing/{$test->id}");
        $response->assertStatus(403);
    }

    public function test_it_requires_authentication(): void
    {
        auth()->logout();
        $response = $this->get('/ab-testing');
        $response->assertRedirect('/login');
    }
}
