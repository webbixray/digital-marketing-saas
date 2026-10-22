<?php

return [

    /*
    |--------------------------------------------------------------------------
    | CDN / Asset URL Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the CDN base URL for serving static assets. When a CDN is
    | configured, asset URLs (via the `asset()` helper and Vite) will be
    | prefixed with the CDN URL.
    |
    */

    'enabled' => env('CDN_ENABLED', false),

    'base_url' => env('CDN_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Asset Paths
    |--------------------------------------------------------------------------
    |
    | Paths that should be served from CDN when enabled.
    |
    */

    'paths' => [
        'css' => '/build/assets',
        'js' => '/build/assets',
        'images' => '/images',
        'fonts' => '/fonts',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Headers
    |--------------------------------------------------------------------------
    |
    | Default cache headers for CDN-served assets.
    |
    */

    'cache' => [
        'max_age' => env('CDN_CACHE_MAX_AGE', 31536000), // 1 year
        's_maxage' => env('CDN_CACHE_S_MAXAGE', 31536000),
        'public' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Invalidating Cache
    |--------------------------------------------------------------------------
    |
    | When deploying new assets, set CDN_CACHE_BUST_KEY to a new value to
    | force CDN cache invalidation.
    |
    */

    'bust_key' => env('CDN_CACHE_BUST_KEY', 'v1'),

];
