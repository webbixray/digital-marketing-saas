<?php

namespace App\Providers;

use App\Models\WhiteLabelSetting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class WhiteLabelServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Share white-label data with all views
        View::composer('*', function ($view) {
            if (auth()->check() && auth()->user()->agency_id) {
                $agencyId = auth()->user()->agency_id;
                $settings = WhiteLabelSetting::where('agency_id', $agencyId)
                    ->where('enabled', true)
                    ->first();

                if ($settings) {
                    $view->with('whiteLabel', $settings);
                }
            }
        });
    }
}
