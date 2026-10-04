<?php

namespace App\Providers;

use App\Models\WhiteLabelSetting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class WhiteLabelServiceProvider extends ServiceProvider
{
    /**
     * Cache TTL for white-label lookups (5 minutes). Short enough that
     * branding changes appear quickly, long enough to avoid a DB query on
     * every view render for every authenticated request.
     */
    private const CACHE_TTL = 300;

    public function boot(): void
    {
        // Share white-label data with all views
        View::composer('*', function ($view) {
            $user = auth()->user();

            if (! $user || ! $user->agency_id) {
                return;
            }

            $view->with('whiteLabel', $this->resolveWhiteLabel($user->agency_id));
        });
    }

    /**
     * Resolve the enabled white-label settings for an agency, with caching
     * and graceful degradation when the database is unavailable.
     */
    private function resolveWhiteLabel(int $agencyId): ?WhiteLabelSetting
    {
        $cacheKey = "white_label_settings_{$agencyId}";

        $cached = Cache::get($cacheKey);
        if ($cached !== null) {
            return $cached instanceof WhiteLabelSetting ? $cached : null;
        }

        try {
            $settings = WhiteLabelSetting::where('agency_id', $agencyId)
                ->where('enabled', true)
                ->first();
        } catch (\Throwable $e) {
            // DB unavailable: degrade to platform branding without caching
            // the failure, so lookups resume once the database recovers.
            return null;
        }

        // Cache the result (including nulls) so absent settings do not hit
        // the DB on every render.
        Cache::put($cacheKey, $settings, self::CACHE_TTL);

        return $settings;
    }
}
