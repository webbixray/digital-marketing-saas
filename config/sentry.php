<?php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),

    // capture release as git sha
    'release' => trim(exec('git --git-dir '.base_path('.git').' log --pretty="%h" -1 HEAD')),

    // When left empty or `null` the Laravel environment will be used
    'environment' => env('SENTRY_ENVIRONMENT'),

    'breadcrumbs' => [
        // Capture Laravel logs in breadcrumbs
        'logs' => true,

        // Capture SQL queries in breadcrumbs
        'sql_queries' => true,

        // Capture queue job information in breadcrumbs
        'queue_info' => true,

        // Capture command information in breadcrumbs
        'command_info' => true,
    ],

    'tracing' => [
        // Trace queue jobs as their own transactions
        'queue_job_transactions' => env('SENTRY_TRACE_QUEUE_ENABLED', false),

        // Capture queue jobs as spans when executed on the sync driver
        'queue_jobs' => true,

        // Capture SQL queries as spans
        'sql_queries' => true,

        // Capture SQL query bindings (parameters) in spans
        'sql_bindings' => false,

        // Capture HTTP client requests as spans
        'http_client_requests' => true,
    ],

    // @see: https://docs.sentry.io/platforms/php/guides/laravel/configuration/options/#send-default-pii
    'send_default_pii' => env('SENTRY_SEND_DEFAULT_PII', false),

    // @see: https://docs.sentry.io/platforms/php/guides/laravel/configuration/options/#traces-sample-rate
    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE') === null ? null : (float) env('SENTRY_TRACES_SAMPLE_RATE'),

    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE') === null ? null : (float) env('SENTRY_PROFILES_SAMPLE_RATE'),
];
