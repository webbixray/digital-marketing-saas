<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Uptime Monitoring Configuration
    |--------------------------------------------------------------------------
    |
    | Endpoints that should be monitored for uptime and alerting.
    |
    */

    'enabled' => env('UPTIME_MONITORING_ENABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Alert Thresholds
    |--------------------------------------------------------------------------
    |
    | Thresholds for triggering alerts based on error rates and response times.
    |
    */

    'error_rate_threshold_percent' => env('UPTIME_ERROR_RATE_THRESHOLD', 1.0),
    'response_time_threshold_ms' => env('UPTIME_RESPONSE_TIME_THRESHOLD', 500),

    /*
    |--------------------------------------------------------------------------
    | Endpoints to Monitor
    |--------------------------------------------------------------------------
    |
    | List of endpoints that should be checked for uptime.
    |
    */

    'endpoints' => [
        [
            'name' => 'Health Check',
            'url' => env('APP_URL', 'http://localhost') . '/up',
            'method' => 'GET',
            'expected_status' => 200,
            'check_interval_seconds' => 60,
        ],
        [
            'name' => 'API Health',
            'url' => env('APP_URL', 'http://localhost') . '/api/health',
            'method' => 'GET',
            'expected_status' => 200,
            'check_interval_seconds' => 60,
        ],
        [
            'name' => 'Landing Page',
            'url' => env('APP_URL', 'http://localhost') . '/',
            'method' => 'GET',
            'expected_status' => 200,
            'check_interval_seconds' => 300,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alert Channels
    |--------------------------------------------------------------------------
    |
    | Channels to use for sending uptime alerts.
    |
    */

    'alert_channels' => [
        'slack' => env('UPTIME_SLACK_WEBHOOK_URL', null),
        'email' => env('UPTIME_ALERT_EMAIL', null),
        'sms' => env('UPTIME_ALERT_SMS', null),
    ],

];
