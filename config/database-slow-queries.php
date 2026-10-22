<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Slow Query Log Configuration
    |--------------------------------------------------------------------------
    |
    | Log queries that exceed the configured threshold (in milliseconds) to
    | help identify performance bottlenecks in production.
    |
    */

    'slow_query_threshold_ms' => env('DB_SLOW_QUERY_THRESHOLD_MS', 100),

    /*
    |--------------------------------------------------------------------------
    | Enable slow query logging
    |--------------------------------------------------------------------------
    |
    | When true, queries exceeding the threshold will be logged to the
    | application log channel.
    |
    */

    'enable_slow_query_log' => env('DB_ENABLE_SLOW_QUERY_LOG', true),

    /*
    |--------------------------------------------------------------------------
    | Query log channel
    |--------------------------------------------------------------------------
    |
    | The log channel to use for slow query entries.
    |
    */

    'query_log_channel' => env('DB_QUERY_LOG_CHANNEL', 'stack'),

    /*
    |--------------------------------------------------------------------------
    | Index optimization recommendations
    |--------------------------------------------------------------------------
    |
    | Tables that should have performance indexes verified regularly.
    |
    */

    'tables_to_optimize' => [
        'agencies' => ['subscription_plan', 'status', 'created_at'],
        'users' => ['agency_id', 'role', 'email', 'created_at'],
        'social_posts' => ['agency_id', 'status', 'platform', 'created_at', 'scheduled_at'],
        'social_accounts' => ['agency_id', 'platform', 'is_connected'],
        'campaigns' => ['agency_id', 'status', 'start_date', 'end_date'],
        'clients' => ['agency_id', 'status', 'industry'],
        'invoices' => ['agency_id', 'client_id', 'status', 'due_date'],
        'reports' => ['agency_id', 'status', 'created_at'],
        'media_assets' => ['agency_id', 'file_type', 'folder_id'],
        'analytics_events' => ['agency_id', 'event_type', 'created_at'],
        'ai_content_logs' => ['agency_id', 'action', 'created_at'],
        'jobs' => ['queue', 'reserved_at', 'created_at'],
        'failed_jobs' => ['failed_at', 'connection', 'queue'],
        'audit_logs' => ['agency_id', 'action', 'created_at'],
        'activity_logs' => ['agency_id', 'action', 'created_at'],
        'chat_channels' => ['agency_id', 'is_archived'],
        'chat_messages' => ['channel_id', 'user_id', 'created_at'],
        'workflows' => ['agency_id', 'status', 'created_at'],
        'workflow_executions' => ['workflow_id', 'status', 'created_at'],
    ],

];
