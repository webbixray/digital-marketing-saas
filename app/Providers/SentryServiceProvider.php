<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Sentry\ClientBuilder;
use Sentry\SentrySdk;
use Sentry\State\Hub;
use Sentry\State\Scope;

class SentryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Hub::class, function () {
            $dsn = config('services.sentry.dsn', env('SENTRY_LARAVEL_DSN'));

            if (empty($dsn)) {
                return new Hub;
            }

            $clientBuilder = ClientBuilder::create([
                'dsn' => $dsn,
                'release' => config('services.sentry.release', '1.0.0'),
                'environment' => app()->environment(),
                'traces_sample_rate' => (float) config('services.sentry.traces_sample_rate', 0.1),
                'profiles_sample_rate' => (float) config('services.sentry.profiles_sample_rate', 0.1),
                'attach_stacktrace' => true,
                'send_default_pii' => false,
            ]);

            $client = $clientBuilder->getClient();
            $hub = new Hub($client);

            $hub->pushScope(function (Scope $scope) {
                $scope->setContext('app', [
                    'name' => config('app.name'),
                    'env' => app()->environment(),
                    'version' => config('services.sentry.release', '1.0.0'),
                ]);

                if (auth()->check()) {
                    $scope->setUser([
                        'id' => auth()->id(),
                        'email' => auth()->user()?->email,
                        'agency_id' => auth()->user()?->agency_id,
                    ]);
                }
            });

            SentrySdk::init()->setHub($hub);

            return $hub;
        });
    }

    public function boot(): void
    {
        //
    }
}
