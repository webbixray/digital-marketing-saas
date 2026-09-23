<?php

namespace Tests\Feature\Localization;

use App\Models\Agency;
use App\Models\Language;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\App;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    private Agency $agency;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->agency = Agency::factory()->create();
        $this->user = User::factory()->create(['agency_id' => $this->agency->id]);
        $this->seed(\Database\Seeders\LanguageSeeder::class);
    }

    /**
     * Test that language index returns all active languages.
     */
    public function test_language_index_returns_supported_languages(): void
    {
        $response = $this->actingAs($this->user)->getJson(route('languages.index'));

        $response->assertOk();
        $response->assertJsonCount(8, 'data');
        $response->assertJsonFragment(['code' => 'en']);
        $response->assertJsonFragment(['code' => 'ar']);
    }

    /**
     * Test that locale switching updates the user's locale.
     */
    public function test_locale_switching_updates_user_locale(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('languages.switch'), ['locale' => 'ar']);

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'locale' => 'ar',
                'direction' => 'rtl',
            ]);

        $this->assertEquals('ar', $this->user->fresh()->locale);
    }

    /**
     * Test that Arabic is detected as RTL.
     */
    public function test_arabic_is_detected_as_rtl(): void
    {
        $this->assertTrue(
            \App\View\Composers\LanguageComposer::isRTLLocale('ar')
        );
    }

    /**
     * Test that non-RTL languages return LTR direction.
     */
    public function test_non_rtl_languages_return_ltr_direction(): void
    {
        $this->assertFalse(
            \App\View\Composers\LanguageComposer::isRTLLocale('en')
        );
        $this->assertFalse(
            \App\View\Composers\LanguageComposer::isRTLLocale('es')
        );
    }

    /**
     * Test that SetLocale middleware sets app locale.
     */
    public function test_set_locale_middleware_sets_app_locale(): void
    {
        $this->user->locale = 'ar';
        $this->user->save();

        $response = $this->actingAs($this->user)->getJson(route('languages.current'));

        $response->assertOk();
        $this->assertEquals('ar', App::getLocale());
    }

    /**
     * Test that switching locale requires authentication.
     */
    public function test_locale_switching_requires_authentication(): void
    {
        $response = $this->postJson(route('languages.switch'), ['locale' => 'ar']);

        $response->assertStatus(401);
    }

    /**
     * Test that invalid locale is rejected with validation error.
     */
    public function test_invalid_locale_is_rejected(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('languages.switch'), ['locale' => 'xx']);

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    /**
     * Test that language seeder creates 8 languages.
     */
    public function test_language_seeder_creates_eight_languages(): void
    {
        // Already seeded in setUp
        $this->assertEquals(8, Language::count());
        $this->assertEquals(1, Language::where('code', 'ar')->where('is_rtl', true)->count());
        $this->assertEquals(1, Language::where('code', 'my')->count());
    }

    /**
     * Test that current locale endpoint returns correct info.
     */
    public function test_current_locale_returns_correct_info(): void
    {
        $this->user->locale = 'ar';
        $this->user->save();

        $response = $this->actingAs($this->user)->getJson(route('languages.current'));

        $response->assertOk()
            ->assertJson([
                'success' => true,
                'data' => [
                    'locale' => 'ar',
                    'direction' => 'rtl',
                ],
            ]);
    }

    /**
     * Test that fallback locale is English.
     */
    public function test_fallback_locale_is_english(): void
    {
        $service = app(\App\Services\Localization\LocaleService::class);
        $this->assertEquals('en', $service->getFallbackLocale());
    }

    /**
     * Test that language model scopes work correctly.
     */
    public function test_language_model_scopes_work(): void
    {
        // scopeActive
        Language::where('code', 'en')->update(['is_active' => false]);
        $this->assertEquals(7, Language::active()->count());

        // scopeRtl
        $this->assertEquals(1, Language::rtl()->count());
        $this->assertEquals('ar', Language::rtl()->first()->code);

        // scopeByCode
        $this->assertEquals('my', Language::byCode('my')->first()->code);
    }

    /**
     * Test that supported locales returns array of active codes.
     */
    public function test_supported_locales_returns_active_codes(): void
    {
        $service = app(\App\Services\Localization\LocaleService::class);
        $locales = $service->getSupportedLocales();

        $this->assertContains('en', $locales);
        $this->assertContains('ar', $locales);
        $this->assertContains('my', $locales);
    }

    /**
     * Test that isValidLocale validates correctly.
     */
    public function test_is_valid_locale_validates_correctly(): void
    {
        $service = app(\App\Services\Localization\LocaleService::class);
        $this->assertTrue($service->isValidLocale('en'));
        $this->assertTrue($service->isValidLocale('ar'));
        $this->assertFalse($service->isValidLocale('xx'));
        $this->assertFalse($service->isValidLocale('invalid'));
    }
}
