<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Application Version
    |--------------------------------------------------------------------------
    |
    | The current semantic version of the application. Follows semver:
    | MAJOR.MINOR.PATCH
    | - MAJOR: Breaking changes
    | - MINOR: New features (backward compatible)
    | - PATCH: Bug fixes (backward compatible)
    |
    */
    'version' => env('APP_VERSION', '1.0.0'),

    /*
    |--------------------------------------------------------------------------
    | Version Meta
    |--------------------------------------------------------------------------
    */
    'codename' => env('APP_CODENAME', 'Genesis'),
    'release_date' => env('APP_RELEASE_DATE', '2026-09-05'),
    'minimum_php' => '8.4',
    'minimum_laravel' => '13.0',

    /*
    |--------------------------------------------------------------------------
    | API Versions
    |--------------------------------------------------------------------------
    */
    'api' => [
        'latest' => 'v1',
        'supported' => ['v1'],
        'deprecated' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Changelog Settings
    |--------------------------------------------------------------------------
    */
    'changelog' => [
        'per_page' => 10,
        'show_pr_links' => true,
        'show_author' => true,
        'categories' => [
            'Added' => 'New features',
            'Changed' => 'Changes to existing functionality',
            'Deprecated' => 'Soon-to-be removed features',
            'Removed' => 'Removed features',
            'Fixed' => 'Bug fixes',
            'Security' => 'Security patches',
        ],
    ],
];
