<?php

use App\Providers\AgentServiceProvider;
use App\Providers\AiGatewayServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\SentryServiceProvider;
use App\Providers\TelescopeServiceProvider;

return [
    AgentServiceProvider::class,
    AiGatewayServiceProvider::class,
    AppServiceProvider::class,
    SentryServiceProvider::class,
    TelescopeServiceProvider::class,
];
