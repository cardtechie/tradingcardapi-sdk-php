<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API URL
    |--------------------------------------------------------------------------
    |
    | The URL of the API to connect with your application. This value is used
    | by the client to perform operations on the API.
    */
    'url' => env('TRADINGCARDAPI_URL', ''),

    /*
    |--------------------------------------------------------------------------
    | Toggle SSL Verification Mode
    |--------------------------------------------------------------------------
    |
    | Toggle SSL verification mode with a boolean value. This usually won't
    | need to be modified, unless you are working with a local API or any
    | other API instance without a valid SSL certificate.
    */
    'ssl_verify' => (bool) env('TRADINGCARDAPI_SSL_VERIFY', true),

    /*
    |--------------------------------------------------------------------------
    | Trading Card API Client ID
    |--------------------------------------------------------------------------
    |
    | The ID of the client used to connect to the API. It is recommended
    | that you do not add your client ID to the line below. Instead, add
    | it to your environment file, so it doesn't get checked into your
    | code repo.
    */
    'client_id' => env('TRADINGCARDAPI_CLIENT_ID', ''),

    /*
    |--------------------------------------------------------------------------
    | Trading Card API Secret
    |--------------------------------------------------------------------------
    |
    | The secret of the client used to connect to the API .It is recommended
    | that you do not add your client secret to the line below. Instead, add
    | it to your environment file, so it doesn't get checked into your
    | code repo.
    */
    'client_secret' => env('TRADINGCARDAPI_CLIENT_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | OAuth Scopes
    |--------------------------------------------------------------------------
    |
    | The OAuth scopes to request when authenticating with the API. This
    | controls what permissions the access token will have.
    |
    | Available scopes:
    | - read:published (default): Access published content only
    | - read:draft: Access published and draft content
    | - read:all-status: Access all content regardless of status
    | - write: Create and update resources
    | - delete: Delete resources
    |
    | You can specify a single scope as a string or multiple scopes as a
    | space-separated string:
    |   'read:published'
    |   'read:all-status write delete'
    |
    | For backwards compatibility, if not set, requests will be made without
    | explicit scopes and receive the API's default scope (read:published).
    */
    'scope' => env('TRADINGCARDAPI_SCOPE', 'read:published'),

    /*
    |--------------------------------------------------------------------------
    | API Response Validation
    |--------------------------------------------------------------------------
    |
    | Configure how API responses are validated against expected schemas.
    | This helps catch API changes early and provides better debugging.
    */
    'validation' => [
        /*
        | Enable or disable response validation entirely.
        | Set to false in production if performance is critical.
        */
        'enabled' => (bool) env('TRADINGCARDAPI_VALIDATION', true),

        /*
        | Strict mode throws exceptions on validation failures.
        | In lenient mode, validation errors are only logged.
        */
        'strict_mode' => (bool) env('TRADINGCARDAPI_STRICT_VALIDATION', false),

        /*
        | Log validation errors for debugging and monitoring.
        | Useful for detecting API changes in production.
        */
        'log_validation_errors' => (bool) env('TRADINGCARDAPI_LOG_VALIDATION', true),

        /*
        | Cache parsed schemas for better performance.
        | Disable in development if you're modifying schemas frequently.
        */
        'cache_schemas' => (bool) env('TRADINGCARDAPI_CACHE_SCHEMAS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Options
    |--------------------------------------------------------------------------
    |
    | Configuration for debugging and development features.
    |
    */

    'ignore_status' => (bool) env('TRADINGCARDAPI_IGNORE_STATUS', 0),
];
