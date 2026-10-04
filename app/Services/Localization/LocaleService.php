<?php

namespace App\Services\Localization;

use App\Models\Language;
use Illuminate\Support\Collection;
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
     */
    public function getFallbackLocale(): string
    {
        return self::FALLBACK_LOCALE;
    }

    /**
     * Get all supported locale codes.
     *
     * Falls back to the configured locale list when the database is
     * unavailable so locale resolution never fails a request.
     *
     * @return array<int, string>
     */
    public function getSupportedLocales(): array
    {
        $cached = Cache::get('supported_locales');
        if ($cached !== null) {
            return $cached;
        }

        try {
            $locales = Language::active()->pluck('code')->toArray();
        } catch (\Throwable $e) {
            // DB unavailable: fall back to config WITHOUT caching, so the DB
            // is retried once it recovers.
            return config('app.supported_locales', ['en']);
        }

        if ($locales === []) {
            return config('app.supported_locales', ['en']);
        }

        Cache::put('supported_locales', $locales, 86400);

        return $locales;
    }

    /**
     * Get all supported languages as a collection.
     *
     * @return Collection<int, Language>
     */
    public function getSupportedLanguages()
    {
        $cached = Cache::get('supported_languages_collection');
        if ($cached !== null) {
            return $cached;
        }

        try {
            $languages = Language::active()->orderBy('sort_order')->get();
            Cache::put('supported_languages_collection', $languages, 86400);

            return $languages;
        } catch (\Throwable $e) {
            // DB unavailable: return empty collection WITHOUT caching it.
            return collect();
        }
    }

    /**
     * Check if a locale is supported.
     */
    public function isValidLocale(string $locale): bool
    {
        return in_array($locale, $this->getSupportedLocales(), true);
    }

    /**
     * Get language info by code.
     */
    public function getLanguageByCode(string $code): ?Language
    {
        return Language::byCode($code)->active()->first();
    }

    /**
     * Get the text direction for the current locale.
     */
    public function getDirection(): string
    {
        return app(TranslationService::class)->getLanguageDirection($this->getLocale());
    }

    /**
     * Clear locale-related caches.
     */
    public function clearCache(): void
    {
        Cache::forget('supported_locales');
        Cache::forget('supported_languages');
        Cache::forget('supported_languages_collection');
    }
}
