<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Pagination Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default pagination behavior globally.
    |
    */

    'default' => env('PAGINATION_DRIVER', 'page'),
    'per_page' => env('PAGINATION_PER_PAGE', 10),
    'max_per_page' => env('PAGINATION_MAX_PER_PAGE', 100),
    'page_name' => env('PAGINATION_PAGE_NAME', 'page'),

    /*
    |--------------------------------------------------------------------------
    | Platform Integration Settings
    |--------------------------------------------------------------------------
    |
    | OAuth credentials and API endpoints for social media platforms.
    | Override per-agency using agency_settings table.
    |
    */

    'platforms' => [
        'facebook' => [
            'base_url' => 'https://graph.facebook.com/v18.0',
            'scopes' => [
                'pages_manage_posts',
                'pages_read_engagement',
                'pages_manage_metadata',
                'instagram_basic',
                'instagram_content_publish',
            ],
        ],
        'instagram' => [
            'base_url' => 'https://graph.facebook.com/v18.0',
            'media_upload_url' => 'https://graph.facebook.com/v18.0/{media-id}/media',
            'scopes' => [
                'instagram_basic',
                'instagram_content_publish',
                'pages_read_engagement',
            ],
        ],
        'twitter' => [
            'base_url' => 'https://api.twitter.com/2',
            'api_url' => 'https://api.twitter.com/2/tweets',
            'scopes' => [
                'tweet.read',
                'tweet.write',
                'users.read',
                'offline.access',
            ],
        ],
        'linkedin' => [
            'base_url' => 'https://api.linkedin.com/v2',
            'ugc_post_url' => 'https://api.linkedin.com/v2/ugcPosts',
            'scopes' => [
                'w_member_social',
                'w_organization_social',
                'r_liteprofile',
            ],
        ],
        'tiktok' => [
            'base_url' => 'https://open.tiktokapis.com/v2',
            'scopes' => [
                'user.info.basic',
                'video.upload.publish',
                'video.list',
            ],
        ],
        'pinterest' => [
            'base_url' => 'https://api.pinterest.com/v5',
            'scopes' => [
                'pinterest.0.0018',
                'pinterest.0.0019',
                'pinterest.0.0020',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Multi-provider AI gateway settings. Providers are tried in order of
    | preference. Each agency can customize their provider choice.
    |
    */

    'ai' => [
        'providers' => [
            'openai' => [
                'model' => env('AI_OPENAI_MODEL', 'gpt-4o'),
                'fallback_model' => 'gpt-4o-mini',
                'temperature' => 0.7,
                'max_tokens' => 2048,
            ],
            'anthropic' => [
                'model' => env('AI_ANTHROPIC_MODEL', 'claude-3-5-sonnet-20241022'),
                'fallback_model' => 'claude-3-haiku-20240307',
                'temperature' => 0.7,
                'max_tokens' => 4096,
            ],
            'google' => [
                'model' => env('AI_GOOGLE_MODEL', 'gemini-1.5-pro'),
                'fallback_model' => 'gemini-1.5-flash',
                'temperature' => 0.7,
                'max_tokens' => 8192,
            ],
            'mistral' => [
                'model' => env('AI_MISTRAL_MODEL', 'mistral-large'),
                'fallback_model' => 'mistral-small',
                'base_url' => 'https://api.mistral.ai/v1',
                'temperature' => 0.7,
                'max_tokens' => 4096,
            ],
            'groq' => [
                'api_key' => env('AI_GROQ_API_KEY'),
                'api_base_url' => env('AI_GROQ_API_BASE_URL', 'https://api.groq.com/openai/v1'),
                'model' => env('AI_GROQ_MODEL', 'openai/gpt-oss-20b'),
                'fallback_model' => 'qwen/qwen3.8-27b',
                'temperature' => 0.7,
                'max_tokens' => 2048,
            ],
            'openrouter' => [
                'model' => env('AI_OPENROUTER_MODEL', 'openai/gpt-4o'),
                'fallback_models' => [
                    'openai/gpt-4o-mini',
                    'meta-llama/llama-3.1-70b-instruct',
                ],
                'temperature' => 0.7,
                'max_tokens' => 4096,
            ],
            'nvidia_nim' => [
                'model' => env('AI_NVIDIA_MODEL', 'meta/llama-3.1-8b-instruct'),
                'fallback_model' => 'meta/llama-3.1-70b-instruct',
                'api_base_url' => 'https://integrate.api.nvidia.com/v1',
                'temperature' => 0.7,
                'max_tokens' => 4096,
            ],
            'nous_portal' => [
                'api_key' => env('AI_NOUS_API_KEY'),
                'api_base_url' => env('AI_NOUS_API_BASE_URL', 'https://portal.nous.co/api/v1'),
                'model' => env('AI_NOUS_MODEL', 'hermes-3-llama-3.1-70b'),
                'fallback_model' => 'hermes-3-llama-3.1-8b',
                'temperature' => 0.7,
                'max_tokens' => 8192,
            ],
            'ollama' => [
                'api_base_url' => env('AI_OLLAMA_API_BASE_URL', 'http://localhost:11434'),
                'model' => env('AI_OLLAMA_MODEL', 'llama3.1:8b'),
                'fallback_model' => 'llama3.2:3b',
                'temperature' => 0.7,
                'max_tokens' => 4096,
            ],
        ],
        'default_provider' => env('AI_DEFAULT_PROVIDER', 'openai'),
        'api_base_url' => env('AI_API_BASE_URL', 'https://api.openai.com/v1'),
        'api_key' => env('AI_API_KEY'),
        'nvidia_api_key' => env('AI_NVIDIA_API_KEY'),
        'enable_fallback' => true,
        'routing' => [
            'reasoning' => ['nous_portal:hermes-3-llama-3.1-70b', 'openai:gpt-4o', 'anthropic:claude-3-5-sonnet-20241022', 'google:gemini-1.5-pro'],
            'fast' => ['nous_portal:hermes-3-llama-3.1-8b', 'groq:llama-3.1-8b', 'openai:gpt-4o-mini', 'anthropic:claude-3-haiku-20240307', 'google:gemini-1.5-flash'],
            'creative' => ['nous_portal:hermes-3-llama-3.1-70b', 'openai:gpt-4o', 'anthropic:claude-3-5-sonnet-20241022', 'google:gemini-1.5-pro'],
            'analysis' => ['nous_portal:hermes-3-llama-3.1-70b', 'openai:gpt-4o', 'anthropic:claude-3-5-sonnet-20241022', 'google:gemini-1.5-pro'],
            'embedding' => ['openai:text-embedding-3-small', 'google:text-embedding-004'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plan Settings
    |--------------------------------------------------------------------------
    |
    | Subscription plan definitions with feature limits and quotas.
    |
    */

    'plans' => [
        'free' => [
            'name' => 'Free',
            'price' => 0,
            'interval' => 'month',
            'users' => 1,
            'social_accounts' => 1,
            'posts_per_month' => 20,
            'campaigns' => 1,
            'clients' => 1,
            'ai_requests_per_month' => 20,
            'ai_generations_per_month' => 10,
            'landing_pages' => 0,
            'forms' => 0,
            'features' => [],
        ],
        'starter' => [
            'name' => 'Starter',
            'price' => 19,
            'interval' => 'month',
            'users' => 3,
            'social_accounts' => 3,
            'posts_per_month' => 100,
            'campaigns' => 3,
            'clients' => 5,
            'ai_requests_per_month' => 100,
            'ai_generations_per_month' => 50,
            'landing_pages' => 2,
            'forms' => 2,
            'features' => [
                'analytics',
                'scheduling',
                'content_library',
            ],
        ],
        'pro' => [
            'name' => 'Pro',
            'price' => 49,
            'interval' => 'month',
            'users' => 10,
            'social_accounts' => 10,
            'posts_per_month' => 500,
            'campaigns' => 10,
            'clients' => 25,
            'ai_requests_per_month' => 500,
            'ai_generations_per_month' => 200,
            'landing_pages' => 10,
            'forms' => 10,
            'features' => [
                'analytics',
                'scheduling',
                'content_library',
                'campaign_manager',
                'client_portal',
                'analytics_dashboard',
                'performance_predictor',
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'price' => 199,
            'interval' => 'month',
            'users' => -1, // unlimited
            'social_accounts' => -1, // unlimited
            'posts_per_month' => -1, // unlimited
            'campaigns' => -1,
            'clients' => -1,
            'ai_requests_per_month' => -1,
            'ai_generations_per_month' => -1,
            'landing_pages' => -1,
            'forms' => -1,
            'features' => [
                'analytics',
                'scheduling',
                'content_library',
                'campaign_manager',
                'client_portal',
                'analytics_dashboard',
                'performance_predictor',
                'workflow_engine',
                'social_inbox',
                'custom_branding',
                'api_access',
                'priority_support',
                'dedicated_account_manager',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Social Posting Limits
    |--------------------------------------------------------------------------
    */

    'social' => [
        'rate_limits' => [
            'facebook' => ['requests_per_hour' => 100, 'requests_per_day' => 1000],
            'instagram' => ['requests_per_hour' => 50, 'requests_per_day' => 500],
            'twitter' => ['requests_per_hour' => 300, 'requests_per_day' => 3000],
            'linkedin' => ['requests_per_hour' => 100, 'requests_per_day' => 1000],
            'tiktok' => ['requests_per_hour' => 50, 'requests_per_day' => 500],
            'pinterest' => ['requests_per_hour' => 50, 'requests_per_day' => 500],
        ],
        'supported_types' => [
            'facebook' => ['image', 'video', 'text', 'link'],
            'instagram' => ['image', 'video', 'carousel'],
            'twitter' => ['text', 'image', 'video', 'poll'],
            'linkedin' => ['text', 'image', 'video', 'link'],
            'tiktok' => ['video', 'image'],
            'pinterest' => ['image', 'video', 'pin'],
        ],
        'max_image_size' => 10 * 1024 * 1024, // 10MB
        'max_video_size' => 50 * 1024 * 1024, // 50MB
        'supported_image_formats' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'supported_video_formats' => ['mp4', 'mov', 'avi', 'webm'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Quality Scoring
    |--------------------------------------------------------------------------
    */

    'quality_scoring' => [
        'min_score' => 0,
        'max_score' => 100,
        'thresholds' => [
            'excellent' => 80,
            'good' => 60,
            'fair' => 40,
            'poor' => 20,
        ],
        'rules' => [
            'length_min' => 20,
            'length_max' => 280,
            'hashtag_count_min' => 1,
            'hashtag_count_max' => 10,
            'cta_required' => false,
            'media_required' => false,
            'link_count_max' => 3,
            'emoji_count_min' => 0,
            'emoji_count_max' => 5,
        ],
        'weights' => [
            'length' => 10,
            'hashtags' => 15,
            'cta' => 15,
            'media' => 15,
            'links' => 10,
            'emojis' => 10,
            'readability' => 15,
            'sentiment' => 10,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue & Scheduler Settings
    |--------------------------------------------------------------------------
    */

    'queue' => [
        'driver' => env('QUEUE_CONNECTION', 'database'),
        'retry_after' => 90,
        'max_attempts' => 3,
        'backoff' => [60, 120, 300],
        'failed_job_table' => 'failed_jobs',
    ],

    'scheduler' => [
        'heartbeat_file' => storage_path('logs/scheduler.lastrun'),
        'cleanup_older_than_days' => 30,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notification Settings
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'post_published' => [
            'channels' => ['mail', 'database'],
            'send_to' => ['team'],
        ],
        'post_failed' => [
            'channels' => ['mail', 'database'],
            'send_to' => ['team'],
        ],
        'ai_limit_reached' => [
            'channels' => ['mail', 'database'],
            'send_to' => ['owner'],
        ],
        'quota_high_usage' => [
            'channels' => ['mail', 'database'],
            'send_to' => ['owner'],
            'threshold' => 80, // percent
        ],
        'subscription_expiring' => [
            'channels' => ['mail', 'database'],
            'send_to' => ['owner'],
            'days_before' => 7,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Gates
    |--------------------------------------------------------------------------
    |
    | Features that can be gated by plan or agency settings.
    |
    */

    'features' => [
        'analytics' => 'Analytics Dashboard',
        'scheduling' => 'Content Scheduling',
        'content_library' => 'Content Library',
        'campaign_manager' => 'Campaign Manager',
        'client_portal' => 'Client Portal',
        'performance_predictor' => 'AI Performance Prediction',
        'workflow_engine' => 'Workflow Automation',
        'social_inbox' => 'Social Inbox',
        'custom_branding' => 'Custom Branding',
        'api_access' => 'API Access',
        'email_marketing' => 'Email Marketing',
        'landing_pages' => 'Landing Pages',
        'form_builder' => 'Form Builder',
        'priority_support' => 'Priority Support',
        'dedicated_account_manager' => 'Dedicated Account Manager',
    ],

];
