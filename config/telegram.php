<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Configuration
    |--------------------------------------------------------------------------
    |
    | Configure your Telegram bot credentials here. Get your bot token
    | from @BotFather on Telegram.
    |
    */
    'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
    'bot_username' => env('TELEGRAM_BOT_USERNAME', ''),

    /*
    |--------------------------------------------------------------------------
    | Webhook Settings
    |--------------------------------------------------------------------------
    */
    'webhook' => [
        'secret_token' => env('TELEGRAM_WEBHOOK_SECRET', ''),
        'max_connections' => 40,
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Toggles
    |--------------------------------------------------------------------------
    */
    'features' => [
        'enabled' => env('TELEGRAM_ENABLED', false),
        'commands' => true,
        'inline_queries' => false,
        'callback_queries' => true,
    ],
];
