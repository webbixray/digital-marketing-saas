<?php

namespace App\Providers;

use App\View\Composers\LanguageComposer;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class LocalizationServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Set default locale from config
        App::setLocale(config('app.locale', 'en'));

        // Register LanguageComposer to share locale data with all views
        View::composer('*', LanguageComposer::class);

        // Blade directive: @rtl ... @endrtl — only render content in RTL locales
        Blade::if('rtl', function () {
            return LanguageComposer::isRTLLocale();
        });

        // Blade directive: @ltr ... @endltr — only render content in LTR locales
        Blade::if('ltr', function () {
            return ! LanguageComposer::isRTLLocale();
        });

        // Blade directive: @locale('ar') — render for specific locale
        Blade::if('locale', function ($locale) {
            return app()->getLocale() === $locale;
        });
    }
}
