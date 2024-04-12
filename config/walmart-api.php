<?php

return [
    'seller' => [
        /**
         * A name to identify the Seller and owner of Credentials.
         */
        'name' => env('WALMART_API_SELLER_NAME', env('APP_NAME', 'test')),
    ],
    'credentials' => [
        /**
         * Application's Client ID.
         */
        'client_id' => env('WALMART_API_CLIENT_ID'),
        /**
         * Application's Client Secret.
         */
        'client_secret' => env('WALMART_API_CLIENT_SECRET'),
        /**
         * Application's Consumer ID. This is the same as Client ID just used
         * in a different authorization context.
         */
        'consumer_id' => env('WALMART_API_CLIENT_ID'),
        /**
         * Application's Private Key. This is the same as Client Secret just
         * used in a different authorization context.
         */
        'private_key' => env('WALMART_API_CLIENT_SECRET'),
        /**
         * Required for Canada.
         */
        'channel_type' => env('WALMART_API_CHANNEL_TYPE'),
        /**
         * Required when using the Supplier APIs.
         */
        'partner_id' => env('WALMART_API_PARTNER_ID'),
        /**
         * Type of grant requested.
         * Available grant types: authorization_code, refresh_token and client_credentials.
         */
        'grant_type' => env('WALMART_API_AUTH_MODE', 'client_credentials'),
        /**
         * Apply these credentials to a region.
         */
        'country' => env('WALMART_API_COUNTRY', 'us'),
    ],
    /**
     * OAuth redirect url for authorization. Used when grant_type is
     * authorization_code.
     */
    'redirect_url' => env('WALMART_API_REDIRECT_URL'),
    /**
     * Enable debug mode.
     */
    'debug' => env('WALMART_API_DEBUG', false),
    /**
     * File to write debug info to.
     */
    'debug_file' => env('WALMART_API_DEBUG_FILE', 'php://output'),
];
