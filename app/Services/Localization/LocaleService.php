<?php

namespace App\Services\Localization;

use App\Models\Language;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Session;

class LocaleService
{
    /**
     * Default fallback locale.
     */
    private const FALLBACK_LOCALE = 'en';

    /**
     * Session key for locale storage.
     */
    private const SESSION_KEY = 'user_locale';

    /**
     * Set the application locale.
     *
     * @param string $locale
     * @return bool True if locale was set successfully, false otherwise.
     */
    public function setLocale(string $locale): bool
    {
        if (! $this->isValidLocale($locale)) {
            return false;
        }

        App::setLocale($locale);
        Session::put(self::SESSION_KEY, $locale);

        return true;
    }

    /**
     * Get the current locale from user/agency/session/default.
     *
     * @return string
     */
    public function getLocale(): string
    {
        $user = auth()->user();

        // Priority 1: Authenticated user's locale
        if ($user && $user->locale && $this->isValidLocale($user->locale)) {
            return $user->locale;
        }

        // Priority 2: Agency locale
        if ($user && $user->agency && $user->agency->locale && $this->isValidLocale($user->agency->locale)) {
            return $user->agency->locale;
        }

        // Priority 3: Session locale
        $sessionLocale = Session::get(self::SESSION_KEY);
        if ($sessionLocale && $this->isValidLocale($sessionLocale)) {
            return $sessionLocale;
        }

        // Priority 4: Fallback
        return self::FALLBACK_LOCALE;
    }

    /**
     * Get the fallback locale.
     *
     * @return string
     */
    public function getFallbackLocale(): string
    {
        return self::FALLBACK_LOCALE;
    }

    /**
     * Get all supported locale codes.
     *
     * @return array<int, string>
     */
    public function getSupportedLocales(): array
    {
        return Cache::remember('supported_locales', 86400, function () {
            return Language::active()->pluck('code')->toArray();
        });
    }

    /**
     * Get all supported languages as a collection.
     *
     * @return \Illuminate\Support\Collection<int, Language>
     */
    public function getSupportedLanguages()
    {
        return Cache::remember('supported_languages_collection', 86400, function () {
            return Language::active()->orderBy('sort_order')->get();
        });
    }

    /**
     * Check if a locale is supported.
     *
     * @param string $locale
     * @return bool
     */
    public function isValidLocale(string $locale): bool
    {
        return in_array($locale, $this->getSupportedLocales(), true);
    }

    /**
     * Get language info by code.
     *
     * @param string $code
     * @return Language|null
     */
    public function getLanguageByCode(string $code): ?Language
    {
        return Language::byCode($code)->active()->first();
    }

    /**
     * Get the text direction for the current locale.
     *
     * @return string
     */
    public function getDirection(): string
    {
        return app(TranslationService::class)->getLanguageDirection($this->getLocale());
    }

    /**
     * Clear locale-related caches.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget('supported_locales');
        Cache::forget('supported_languages');
        Cache::forget('supported_languages_collection');
    }
}
