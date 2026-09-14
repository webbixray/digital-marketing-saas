<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CancellationTest extends TestCase
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

    // =====================================================
    // Auth Middleware Tests
    // =====================================================

    public function test_survey_requires_authentication(): void
    {
        $response = $this->get(route('cancellation.survey'));
        $response->assertRedirect(route('login'));
    }

    public function test_submit_survey_requires_authentication(): void
    {
        $response = $this->post(route('cancellation.submit'), [
            'reason' => 'too_expensive',
        ]);
        $response->assertRedirect(route('login'));
    }

    public function test_confirm_requires_authentication(): void
    {
        $response = $this->get(route('cancellation.confirm'));
        $response->assertRedirect(route('login'));
    }

    // =====================================================
    // Agency Middleware Tests
    // =====================================================

    public function test_survey_requires_agency_assignment(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $response = $this->actingAs($user)->get(route('cancellation.survey'));

        $response->assertStatus(403);
    }

    public function test_submit_survey_requires_agency_assignment(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $response = $this->actingAs($user)->post(route('cancellation.submit'), [
            'reason' => 'too_expensive',
        ]);

        $response->assertStatus(403);
    }

    public function test_confirm_requires_agency_assignment(): void
    {
        $user = User::factory()->create(['agency_id' => null]);

        $response = $this->actingAs($user)->get(route('cancellation.confirm'));

        $response->assertStatus(403);
    }

    // =====================================================
    // Survey (GET /cancel) Tests
    // =====================================================

    public function test_survey_returns_successful_response(): void
    {
        $response = $this->actingAs($this->user)->get(route('cancellation.survey'));

        $response->assertStatus(200);
        $response->assertViewIs('cancellation.survey');
    }

    public function test_survey_passes_offer_to_view(): void
    {
        $response = $this->actingAs($this->user)->get(route('cancellation.survey'));

        $response->assertViewHas('offer');
    }

    // =====================================================
    // Submit Survey (POST /cancel/survey) Tests
    // =====================================================

    public function test_submit_survey_stores_response_and_redirects(): void
    {
        $response = $this->actingAs($this->user)->post(route('cancellation.submit'), [
            'reason' => 'too_expensive',
            'feedback' => 'Found a cheaper alternative.',
        ]);

        $response->assertRedirect(route('cancellation.confirm'));
        $response->assertSessionHas('reason', 'too_expensive');
    }

    public function test_submit_survey_without_feedback_succeeds(): void
    {
        $response = $this->actingAs($this->user)->post(route('cancellation.submit'), [
            'reason' => 'not_using',
        ]);

        $response->assertRedirect(route('cancellation.confirm'));
        $response->assertSessionHas('reason', 'not_using');
    }

    public function test_submit_survey_requires_reason(): void
    {
        $response = $this->actingAs($this->user)->post(route('cancellation.submit'), [
            'feedback' => 'Some feedback without reason.',
        ]);

        $response->assertSessionHasErrors('reason');
    }

    public function test_submit_survey_rejects_invalid_reason(): void
    {
        $response = $this->actingAs($this->user)->post(route('cancellation.submit'), [
            'reason' => 'invalid_reason',
        ]);

        $response->assertSessionHasErrors('reason');
    }

    public function test_submit_survey_rejects_feedback_over_1000_chars(): void
    {
        $response = $this->actingAs($this->user)->post(route('cancellation.submit'), [
            'reason' => 'other',
            'feedback' => str_repeat('a', 1001),
        ]);

        $response->assertSessionHasErrors('feedback');
    }

    public function test_submit_survey_accepts_all_valid_reasons(): void
    {
        $reasons = ['too_expensive', 'missing_features', 'not_using', 'switching', 'other'];

        foreach ($reasons as $reason) {
            $user = User::factory()->create(['agency_id' => $this->agency->id]);

            $response = $this->actingAs($user)->post(route('cancellation.submit'), [
                'reason' => $reason,
                'feedback' => "Feedback for {$reason}",
            ]);

            $response->assertRedirect(route('cancellation.confirm'));
            $response->assertSessionHas('reason', $reason);
        }
    }

    // =====================================================
    // Confirm Cancellation (GET /cancel/confirm) Tests
    // =====================================================

    public function test_confirm_returns_successful_response(): void
    {
        $response = $this->actingAs($this->user)->get(route('cancellation.confirm'));

        $response->assertStatus(200);
        $response->assertViewIs('cancellation.confirm');
    }

    public function test_full_cancellation_flow(): void
    {
        // Step 1: View survey
        $surveyResponse = $this->actingAs($this->user)->get(route('cancellation.survey'));
        $surveyResponse->assertStatus(200);
        $surveyResponse->assertViewHas('offer');

        // Step 2: Submit survey
        $submitResponse = $this->actingAs($this->user)->post(route('cancellation.submit'), [
            'reason' => 'switching',
            'feedback' => 'Moving to a competitor.',
        ]);
        $submitResponse->assertRedirect(route('cancellation.confirm'));

        // Step 3: View confirmation
        $confirmResponse = $this->actingAs($this->user)->get(route('cancellation.confirm'));
        $confirmResponse->assertStatus(200);
        $confirmResponse->assertViewIs('cancellation.confirm');
    }
}
