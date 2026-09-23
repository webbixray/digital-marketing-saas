<?php

use App\Providers\AgentServiceProvider;
use App\Providers\AiGatewayServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\DashboardInsightsServiceProvider;
use App\Providers\LocalizationServiceProvider;
use App\Providers\SentryServiceProvider;
use App\Providers\TelescopeServiceProvider;
use App\Providers\WhiteLabelServiceProvider;

return [
    AgentServiceProvider::class,
    AiGatewayServiceProvider::class,
    AppServiceProvider::class,
    DashboardInsightsServiceProvider::class,
    LocalizationServiceProvider::class,
    SentryServiceProvider::class,
    TelescopeServiceProvider::class,
    WhiteLabelServiceProvider::class,
];
