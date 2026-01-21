<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Enable Runtime Insight
    |--------------------------------------------------------------------------
    |
    | Master switch to enable or disable Runtime Insight entirely.
    |
    */
    'enabled' => env('RUNTIME_INSIGHT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the AI provider for generating explanations. Supported
    | providers: openai, anthropic, ollama.
    |
    */
    'ai' => [
        'enabled' => env('RUNTIME_INSIGHT_AI_ENABLED', true),
        'provider' => env('RUNTIME_INSIGHT_AI_PROVIDER', 'openai'),
        'model' => env('RUNTIME_INSIGHT_AI_MODEL', 'gpt-4.1-mini'),
        'api_key' => env('RUNTIME_INSIGHT_AI_KEY'),
        'timeout' => env('RUNTIME_INSIGHT_AI_TIMEOUT', 5),
        'max_tokens' => env('RUNTIME_INSIGHT_AI_MAX_TOKENS', 1000),
        'base_url' => env('RUNTIME_INSIGHT_AI_BASE_URL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Context Collection
    |--------------------------------------------------------------------------
    |
    | Configure what context information is collected around errors.
    |
    */
    'context' => [
        // Number of source code lines to include around the error
        'source_lines' => 10,

        // Include HTTP request context
        'include_request' => true,

        // Include route/controller information
        'include_route' => true,

        // Include authenticated user info (ID only, sanitized)
        'include_user' => true,

        // Sanitize sensitive input data
        'sanitize_inputs' => true,

        // Fields to always redact from context
        'redact_fields' => [
            'password',
            'password_confirmation',
            'credit_card',
            'cvv',
            'ssn',
            'token',
            'secret',
            'api_key',
            'authorization',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Environment Configuration
    |--------------------------------------------------------------------------
    |
    | Control which environments Runtime Insight is active in.
    |
    */
    'environments' => ['local', 'staging'],
    'disabled_environments' => ['production'],

    /*
    |--------------------------------------------------------------------------
    | Output Configuration
    |--------------------------------------------------------------------------
    |
    | Configure how explanations are output.
    |
    */
    'output' => [
        // Output channel: log, console, both
        'channel' => 'log',

        // Log channel to use
        'log_channel' => env('RUNTIME_INSIGHT_LOG_CHANNEL', 'stack'),

        // Log level for explanations
        'log_level' => 'debug',
    ],

    /*
    |--------------------------------------------------------------------------
    | Caching
    |--------------------------------------------------------------------------
    |
    | Cache identical error explanations to reduce API calls.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'store' => env('RUNTIME_INSIGHT_CACHE_STORE', 'file'),
    ],
];

