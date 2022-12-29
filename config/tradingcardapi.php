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

    'cards-connection' => env('CARDS_CONNECTION', 'cards'),
];
