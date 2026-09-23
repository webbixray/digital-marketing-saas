<?php

namespace App\View\Composers;

use App\Services\Localization\LocaleService;
use App\Services\Localization\TranslationService;
use Illuminate\Support\Facades\App;
use Illuminate\View\View;

class LanguageComposer
{
    /**
     * @var LocaleService
     */
    protected LocaleService $localeService;

    /**
     * @var TranslationService
     */
    protected TranslationService $translationService;

    public function __construct(LocaleService $localeService, TranslationService $translationService)
    {
        $this->localeService = $localeService;
        $this->translationService = $translationService;
    }

    /**
     * Bind data to the view.
     */
    public function compose(View $view): void
    {
        $currentLocale = App::getLocale();
        $isRTL = $this->isRTLLocale($currentLocale);
        $supportedLanguages = $this->localeService->getSupportedLanguages();

        $view->with('currentLocale', $currentLocale);
        $view->with('isRTL', $isRTL);
        $view->with('supportedLanguages', $this->formatLanguagesForPicker($supportedLanguages));
        $view->with('currentLanguage', $this->getCurrentLanguageData($supportedLanguages, $currentLocale));
    }

    /**
     * Determine if the given locale is RTL.
     */
    public static function isRTLLocale(?string $locale = null): bool
    {
        if ($locale === null) {
            $locale = App::getLocale();
        }

        return app(TranslationService::class)->isRTL($locale);
    }

    /**
     * Format language collection for the picker dropdown.
     *
     * @param  \Illuminate\Database\Eloquent\Collection<int, \App\Models>  $supportedLanguages
     * @return array<string, array{code: string, name: string, native: string, flag: string, rtl: bool}>
     */
    protected function formatLanguagesForPicker($supportedLanguages): array
    {
        $formatted = [];

        foreach ($supportedLanguages as $language) {
            $formatted[$language->code] = [
                'code' => $language->code,
                'name' => $language->name ?? $language->code,
                'native' => $language->native_name ?? $language->name ?? $language->code,
                'flag' => $language->flag_emoji ?? '🌐',
                'rtl' => (bool) ($language->is_rtl ?? false),
            ];
        }

        // Ensure all configured locales are present even if no Language model exists
        $defaultLanguages = [
            'en' => ['code' => 'en', 'name' => 'English', 'native' => 'English', 'flag' => '🇬🇧', 'rtl' => false],
            'es' => ['code' => 'es', 'name' => 'Spanish', 'native' => 'Español', 'flag' => '🇪🇸', 'rtl' => false],
            'fr' => ['code' => 'fr', 'name' => 'French', 'native' => 'Français', 'flag' => '🇫🇷', 'rtl' => false],
            'ar' => ['code' => 'ar', 'name' => 'Arabic', 'native' => 'العربية', 'flag' => '🇸🇦', 'rtl' => true],
        ];

        // Merge: Language model data takes priority, then fall back to defaults
        foreach ($defaultLanguages as $code => $data) {
            if (!isset($formatted[$code])) {
                $formatted[$code] = $data;
            }
        }

        return $formatted;
    }

    /**
     * Get the current language metadata for the picker button.
     *
     * @return array{code: string, name: string, native: string, flag: string, rtl: bool}
     */
    protected function getCurrentLanguageData($supportedLanguages, string $currentLocale): array
    {
        $formatted = $this->formatLanguagesForPicker($supportedLanguages);
        return $formatted[$currentLocale] ?? [
            'code' => $currentLocale,
            'name' => strtoupper($currentLocale),
            'native' => strtoupper($currentLocale),
            'flag' => '🌐',
            'rtl' => false,
        ];
    }

    /**
     * Get all supported languages.
     *
     * @return array<string, array{code: string, name: string, native: string, flag: string, rtl: bool}>
     */
    public static function getSupportedLanguages(): array
    {
        $service = app(LocaleService::class);
        $languages = $service->getSupportedLanguages();
        $instance = new static($service, app(TranslationService::class));
        return $instance->formatLanguagesForPicker($languages);
    }

    /**
     * Check if a locale is supported.
     */
    public static function isSupported(string $locale): bool
    {
        return app(LocaleService::class)->isValidLocale($locale);
    }
}
