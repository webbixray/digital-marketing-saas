<?php

namespace Tests\Feature\WhiteLabel;

use App\Models\Agency;
use App\Models\Client;
use App\Models\ClientReport;
use App\Models\User;
use App\Models\WhiteLabelSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhiteLabelMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_page_shows_custom_brand_name(): void
    {
        $whiteLabel = WhiteLabelSetting::factory()->create([
            'brand_name' => 'Acme Agency',
            'brand_color' => '#ff5500',
            'hide_powered_by' => false,
            'enabled' => true,
        ]);

        $user = User::factory()->create(['agency_id' => $whiteLabel->agency_id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Acme Agency');
    }

    public function test_authenticated_page_hides_powered_by_when_configured(): void
    {
        $whiteLabel = WhiteLabelSetting::factory()->create([
            'brand_name' => 'Acme Pro',
            'hide_powered_by' => true,
            'enabled' => true,
        ]);

        $user = User::factory()->create(['agency_id' => $whiteLabel->agency_id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertDontSee('Powered by');
    }

    public function test_authenticated_page_shows_powered_by_when_not_hidden(): void
    {
        $whiteLabel = WhiteLabelSetting::factory()->create([
            'brand_name' => 'Acme Pro',
            'hide_powered_by' => false,
            'enabled' => true,
        ]);

        $user = User::factory()->create(['agency_id' => $whiteLabel->agency_id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('Powered by');
    }

    public function test_authenticated_page_shows_custom_logo(): void
    {
        $whiteLabel = WhiteLabelSetting::factory()->create([
            'logo_url' => 'https://example.com/logo.png',
            'enabled' => true,
        ]);

        $user = User::factory()->create(['agency_id' => $whiteLabel->agency_id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('https://example.com/logo.png');
    }

    public function test_authenticated_page_shows_default_logo_when_no_custom(): void
    {
        $user = User::factory()->create(['agency_id' => Agency::factory()->create()->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('fa-bolt'); // Default logo icon
    }

    public function test_authenticated_page_shows_custom_favicon(): void
    {
        $whiteLabel = WhiteLabelSetting::factory()->create([
            'favicon_url' => 'https://example.com/favicon.ico',
            'enabled' => true,
        ]);

        $user = User::factory()->create(['agency_id' => $whiteLabel->agency_id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee('https://example.com/favicon.ico');
    }

    public function test_white_label_disabled_uses_defaults(): void
    {
        $whiteLabel = WhiteLabelSetting::factory()->create([
            'brand_name' => 'Acme Pro',
            'enabled' => false,
        ]);

        $user = User::factory()->create(['agency_id' => $whiteLabel->agency_id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertSee(config('app.name'));
    }

    public function test_public_login_page_does_not_show_white_label(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee(config('app.name'));
    }

    public function test_client_report_shows_white_label_branding(): void
    {
        $whiteLabel = WhiteLabelSetting::factory()->create([
            'brand_name' => 'Report Brand',
            'enabled' => true,
        ]);

        $client = Client::factory()->create(['agency_id' => $whiteLabel->agency_id]);
        $report = ClientReport::factory()->create([
            'agency_id' => $whiteLabel->agency_id,
            'client_id' => $client->id,
            'status' => 'published',
        ]);

        $response = $this->get('/r/' . $report->slug . '/' . $report->access_token);

        $response->assertOk();
        $response->assertSee('Report Brand');
    }

    public function test_client_report_shows_custom_css(): void
    {
        $whiteLabel = WhiteLabelSetting::factory()->create([
            'custom_css' => 'body { background: red; }',
            'enabled' => true,
        ]);

        $client = Client::factory()->create(['agency_id' => $whiteLabel->agency_id]);
        $report = ClientReport::factory()->create([
            'agency_id' => $whiteLabel->agency_id,
            'client_id' => $client->id,
            'status' => 'published',
        ]);

        $response = $this->get('/r/' . $report->slug . '/' . $report->access_token);

        $response->assertOk();
        $response->assertSee('body { background: red; }');
    }

    public function test_non_authenticated_user_sees_default_branding(): void
    {
        WhiteLabelSetting::factory()->create([
            'brand_name' => 'Acme Pro',
            'enabled' => true,
        ]);

        $response = $this->get('/login');

        $response->assertOk();
        $response->assertSee(config('app.name'));
        $response->assertDontSee('Acme Pro');
    }
}
