<?php

namespace Tests\Feature\Onboarding;

use App\Models\Agency;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnboardingTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Agency $agency;

    protected function setUp(): void
    {
        parent::setUp();

        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create([
            'agency_id' => $this->agency->id,
            'role' => 'owner',
        ]);
    }

    public function test_step1_create_agency_requires_authentication(): void
    {
        $response = $this->get(route('onboarding.step1'));
        $response->assertRedirect(route('login'));
    }

    public function test_step2_social_returns_connected_accounts(): void
    {
        SocialAccount::factory()->create([
            'agency_id' => $this->agency->id,
            'platform' => 'facebook',
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('onboarding.step2'));

        $response->assertOk();
        $response->assertViewIs('onboarding.step2_social');
        $response->assertViewHas('platforms');
    }

    public function test_step5_ai_page_shows_settings(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('onboarding.step5'));

        $response->assertOk();
        $response->assertViewIs('onboarding.step5_ai');
        $response->assertViewHas('aiSettings');
    }
}
