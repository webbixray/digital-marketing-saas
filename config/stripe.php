<?php

return [
    'key' => env('STRIPE_KEY'),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'currency' => env('STRIPE_CURRENCY', 'usd'),

    'plans' => [
        'free' => [
            'name' => 'Free',
            'price' => 0,
            'features' => [
                'posts_per_month' => 10,
                'ai_generations_per_month' => 5,
                'social_accounts' => 2,
                'team_members' => 1,
                'landing_pages' => 1,
                'forms' => 1,
            ],
        ],
        'starter' => [
            'name' => 'Starter',
            'monthly' => env('STRIPE_STARTER_MONTHLY', 'price_xxx'),
            'yearly' => env('STRIPE_STARTER_YEARLY', 'price_xxx'),
            'price' => 29,
            'features' => [
                'posts_per_month' => 100,
                'ai_generations_per_month' => 50,
                'social_accounts' => 5,
                'team_members' => 3,
                'landing_pages' => 5,
                'forms' => 5,
            ],
        ],
        'pro' => [
            'name' => 'Pro',
            'monthly' => env('STRIPE_PRO_MONTHLY', 'price_xxx'),
            'yearly' => env('STRIPE_PRO_YEARLY', 'price_xxx'),
            'price' => 79,
            'features' => [
                'posts_per_month' => 500,
                'ai_generations_per_month' => 200,
                'social_accounts' => 15,
                'team_members' => 10,
                'landing_pages' => 20,
                'forms' => 20,
            ],
        ],
        'enterprise' => [
            'name' => 'Enterprise',
            'monthly' => env('STRIPE_ENTERPRISE_MONTHLY', 'price_xxx'),
            'yearly' => env('STRIPE_ENTERPRISE_YEARLY', 'price_xxx'),
            'price' => 199,
            'features' => [
                'posts_per_month' => -1, // unlimited
                'ai_generations_per_month' => -1,
                'social_accounts' => -1,
                'team_members' => -1,
                'landing_pages' => -1,
                'forms' => -1,
            ],
        ],
    ],
];
