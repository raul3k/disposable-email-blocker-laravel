<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Checker Type
    |--------------------------------------------------------------------------
    |
    | Specify which checker to use for disposable email detection.
    | Available: 'file', 'database', 'pattern', 'chain'
    |
    | - 'file': Uses the bundled disposable domains list (recommended)
    | - 'database': Uses database table for domain lookup
    | - 'pattern': Adds pattern matching (combines with use_bundled_list if enabled)
    | - 'chain': Combines multiple checkers
    |
    */
    'checker' => env('DISPOSABLE_CHECKER', 'file'),

    /*
    |--------------------------------------------------------------------------
    | Use Bundled List
    |--------------------------------------------------------------------------
    |
    | Whether to include the bundled disposable domains list from the core
    | package. This list is updated regularly and contains known disposable
    | email providers.
    |
    */
    'use_bundled_list' => true,

    /*
    |--------------------------------------------------------------------------
    | Pattern Detection
    |--------------------------------------------------------------------------
    |
    | Enable heuristic pattern detection to identify disposable email domains
    | that aren't in any list. This uses common patterns found in disposable
    | email domain names.
    |
    */
    'pattern_detection' => false,

    /*
    |--------------------------------------------------------------------------
    | Whitelist
    |--------------------------------------------------------------------------
    |
    | Domains that should never be considered disposable, even if they match
    | a pattern or are in a list. Useful for company domains or specific
    | providers you want to allow.
    |
    */
    'whitelist' => [
        // 'mycompany.com',
        // 'trusted-domain.org',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Configure caching for domain lookups. Caching improves performance
    | significantly when checking the same domains multiple times.
    |
    */
    'cache' => [
        'enabled' => true,
        'store' => env('DISPOSABLE_CACHE_STORE', 'default'),
        'ttl' => 3600, // Time to live in seconds
        'prefix' => 'disposable_email:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Configure database settings when using the 'database' checker.
    |
    */
    'database' => [
        'table' => 'disposable_domains',
        'connection' => null,
    ],
];
