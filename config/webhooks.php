<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for webhook processing including deduplication,
    | retry behavior, and signature verification.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Allow Unsigned Webhooks
    |--------------------------------------------------------------------------
    |
    | When true, webhooks without a signature will still be processed.
    | Set to false in production to reject unsigned webhooks.
    |
    */
    'allow_unsigned' => env('WEBHOOK_ALLOW_UNSIGNED', false),

    /*
    |--------------------------------------------------------------------------
    | Deduplication TTL
    |--------------------------------------------------------------------------
    |
    | Time in seconds to cache webhook IDs for deduplication.
    |
    */
    'deduplication_ttl' => env('WEBHOOK_DEDUP_TTL', 300),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Maximum retry attempts and exponential backoff delays in seconds.
    |
    */
    'max_attempts' => env('WEBHOOK_MAX_ATTEMPTS', 5),

    'retry_delays' => [60, 300, 900, 3600, 3600],

    /*
    |--------------------------------------------------------------------------
    | Dead Letter Queue
    |--------------------------------------------------------------------------
    |
    | When true, failed webhooks after max attempts are moved to dead letter.
    |
    */
    'dead_letter_enabled' => env('WEBHOOK_DEAD_LETTER_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Queue Connection
    |--------------------------------------------------------------------------
    |
    | The queue connection to use for webhook processing jobs.
    |
    */
    'queue_connection' => env('WEBHOOK_QUEUE_CONNECTION', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Queue Name
    |--------------------------------------------------------------------------
    |
    | The queue name to use for webhook processing jobs.
    |
    */
    'queue_name' => env('WEBHOOK_QUEUE_NAME', 'webhooks'),
];
