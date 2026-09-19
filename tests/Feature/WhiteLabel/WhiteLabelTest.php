<?php

namespace Tests\Feature\WhiteLabel;

use App\Models\Agency;
use App\Models\User;
use App\Models\WhiteLabelSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhiteLabelTest extends TestCase
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

    public function test_it_shows_white_label_settings(): void
    {
        $response = $this->actingAs($this->user)->get(route('white-label.index'));

        $response->assertOk();
        $response->assertViewIs('white-label.index');
        $response->assertViewHas('settings');
    }

    public function test_it_updates_white_label_settings(): void
    {
        $response = $this->actingAs($this->user)->post(route('white-label.update'), [
            'brand_name' => 'My Brand',
            'brand_color' => '#ff0000',
            'from_name' => 'My Company',
            'from_email' => 'info@mycompany.com',
            'enabled' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('white_label_settings', [
            'agency_id' => $this->agency->id,
            'brand_name' => 'My Brand',
            'enabled' => true,
        ]);
    }

    public function test_it_requires_auth(): void
    {
        $response = $this->get(route('white-label.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_it_validates_brand_color_format(): void
    {
        $response = $this->actingAs($this->user)->post(route('white-label.update'), [
            'brand_name' => 'Test',
            'brand_color' => 'invalid',
        ]);

        $response->assertSessionHasErrors('brand_color');
    }

    public function test_it_validates_from_email(): void
    {
        $response = $this->actingAs($this->user)->post(route('white-label.update'), [
            'from_email' => 'not-an-email',
        ]);

        $response->assertSessionHasErrors('from_email');
    }

    public function test_it_shows_existing_settings(): void
    {
        WhiteLabelSetting::factory()->create([
            'agency_id' => $this->agency->id,
            'brand_name' => 'Existing Brand',
            'enabled' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('white-label.index'));

        $response->assertOk();
        $response->assertViewHas('settings', function ($settings) {
            return $settings->brand_name === 'Existing Brand';
        });
    }

    public function test_it_creates_new_settings_when_none_exist(): void
    {
        $response = $this->actingAs($this->user)->post(route('white-label.update'), [
            'brand_name' => 'New Brand',
            'brand_color' => '#123456',
            'enabled' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('white_label_settings', [
            'agency_id' => $this->agency->id,
            'brand_name' => 'New Brand',
        ]);
    }

    public function test_it_updates_existing_settings(): void
    {
        WhiteLabelSetting::factory()->create([
            'agency_id' => $this->agency->id,
            'brand_name' => 'Old Brand',
        ]);

        $response = $this->actingAs($this->user)->post(route('white-label.update'), [
            'brand_name' => 'Updated Brand',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('white_label_settings', [
            'agency_id' => $this->agency->id,
            'brand_name' => 'Updated Brand',
        ]);
    }

    public function test_it_can_enable_hide_powered_by(): void
    {
        $response = $this->actingAs($this->user)->post(route('white-label.update'), [
            'hide_powered_by' => true,
            'enabled' => true,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('white_label_settings', [
            'agency_id' => $this->agency->id,
            'hide_powered_by' => true,
        ]);
    }

    public function test_it_can_save_custom_css(): void
    {
        $css = 'body { background: #f0f0f0; }';

        $response = $this->actingAs($this->user)->post(route('white-label.update'), [
            'custom_css' => $css,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('white_label_settings', [
            'agency_id' => $this->agency->id,
            'custom_css' => $css,
        ]);
    }

    public function test_it_can_setup_custom_domain(): void
    {
        $response = $this->actingAs($this->user)->post(route('white-label.domain'), [
            'domain' => 'app.example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('white_label_settings', [
            'agency_id' => $this->agency->id,
            'custom_domain' => 'app.example.com',
        ]);
    }

    public function test_it_requires_domain_for_setup(): void
    {
        $response = $this->actingAs($this->user)->post(route('white-label.domain'), []);

        $response->assertSessionHasErrors('domain');
    }

    public function test_it_prevents_duplicate_domain_across_agencies(): void
    {
        $otherAgency = Agency::factory()->create();
        WhiteLabelSetting::factory()->create([
            'agency_id' => $otherAgency->id,
            'custom_domain' => 'taken.example.com',
        ]);

        $response = $this->actingAs($this->user)->post(route('white-label.domain'), [
            'domain' => 'taken.example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error');
    }

    public function test_it_can_validate_domain(): void
    {
        $response = $this->actingAs($this->user)->postJson(route('white-label.validate-domain'), [
            'domain' => 'example.com',
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['valid', 'domain']);
    }

    public function test_it_requires_auth_for_domain_setup(): void
    {
        $response = $this->post(route('white-label.domain'), [
            'domain' => 'app.example.com',
        ]);

        $response->assertRedirect(route('login'));
    }

    public function test_it_requires_auth_for_settings_update(): void
    {
        $response = $this->post(route('white-label.update'), [
            'brand_name' => 'Test',
        ]);

        $response->assertRedirect(route('login'));
    }
}
