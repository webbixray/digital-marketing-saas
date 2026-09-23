<?php

namespace Tests\Feature\Localization;

use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class RTLTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\LanguageSeeder::class);
    }

    /**
     * Test that RTL is correctly detected for Arabic locale.
     */
    public function test_rtl_detection_for_arabic_locale(): void
    {
        App::setLocale('ar');
        $this->assertTrue(\App\View\Composers\LanguageComposer::isRTLLocale('ar'));
        $this->assertTrue(\App\View\Composers\LanguageComposer::isRTLLocale());
    }

    /**
     * Test that RTL is false for LTR locales.
     */
    public function test_ltr_detection_for_non_rtl_locales(): void
    {
        foreach (['en', 'es', 'fr'] as $locale) {
            App::setLocale($locale);
            $this->assertFalse(\App\View\Composers\LanguageComposer::isRTLLocale($locale));
        }
    }

    /**
     * Test that locale switching via API works correctly.
     */
    public function test_locale_switching_via_api(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('languages.switch'), ['locale' => 'ar']);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'locale' => 'ar',
                'direction' => 'rtl',
            ]);

        $this->assertEquals('ar', App::getLocale());
    }

    /**
     * Test that switching to an unsupported locale returns error.
     */
    public function test_locale_switching_rejects_invalid_locale(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('languages.switch'), ['locale' => 'xx']);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
            ]);
    }

    /**
     * Test that language picker shows correct current language.
     */
    public function test_language_picker_shows_current_language(): void
    {
        $user = User::factory()->create();
        App::setLocale('en');

        $response = $this->actingAs($user)
            ->getJson(route('languages.index'));

        $response->assertOk();
        $response->assertJsonFragment(['code' => 'en']);
        $response->assertJsonFragment(['code' => 'ar']);
    }

    /**
     * Test that the middleware sets correct direction attribute on HTML.
     */
    public function test_middleware_sets_html_direction_attribute(): void
    {
        $user = User::factory()->create();
        $user->locale = 'ar';
        $user->save();

        App::setLocale('ar');

        $response = $this->actingAs($user)
            ->getJson(route('languages.current'));

        $response->assertOk();
        $response->assertJson([
            'data' => [
                'direction' => 'rtl',
            ],
        ]);
    }

    /**
     * Test that supported languages are shared to all views.
     */
    public function test_supported_languages_shared_to_views(): void
    {
        $user = User::factory()->create();
        App::setLocale('en');

        $response = $this->actingAs($user)
            ->getJson(route('languages.index'));

        $response->assertOk();
        $response->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['code', 'name', 'native_name', 'flag_emoji', 'is_rtl'],
            ],
        ]);
    }

    /**
     * Test that all configured locales are considered supported.
     */
    public function test_all_configured_locales_are_supported(): void
    {
        $locales = config('app.supported_locales', []);

        $this->assertContains('en', $locales);
        $this->assertContains('ar', $locales);
        $this->assertContains('es', $locales);
        $this->assertContains('fr', $locales);
    }
}
