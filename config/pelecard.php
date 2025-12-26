<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Multi-Tenancy Configuration
    |--------------------------------------------------------------------------
    |
    | Enable multi-tenant mode to support multiple Pelecard accounts in the
    | same application. When enabled, credentials are resolved per tenant.
    |
    */

    'multi_tenant' => env('PELECARD_MULTI_TENANT', false),

    /*
    |--------------------------------------------------------------------------
    | Credentials Resolver
    |--------------------------------------------------------------------------
    |
    | Custom callback to resolve tenant-specific credentials. Receives the
    | billable entity and should return a PelecardCredentials instance.
    |
    | Example: fn($billable) => $billable->team->pelecardCredentials
    |
    */

    'credentials_resolver' => null,

    /*
    |--------------------------------------------------------------------------
    | Tenant Column
    |--------------------------------------------------------------------------
    |
    | Column name used for tenant identification in multi-tenant mode.
    |
    */

    'tenant_column' => env('PELECARD_TENANT_COLUMN', 'team_id'),

    /*
    |--------------------------------------------------------------------------
    | Default API Credentials
    |--------------------------------------------------------------------------
    |
    | Default Pelecard API credentials. Used when multi-tenancy is disabled
    | or as fallback credentials.
    |
    */

    'terminal' => env('PELECARD_TERMINAL'),
    'user' => env('PELECARD_USER'),
    'password' => env('PELECARD_PASSWORD'),

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    |
    | Pelecard environment: 'sandbox' or 'production'
    |
    */

    'environment' => env('PELECARD_ENV', 'sandbox'),

    /*
    |--------------------------------------------------------------------------
    | Gateway URLs
    |--------------------------------------------------------------------------
    |
    | API endpoints for different Pelecard environments.
    |
    */

    'gateway_urls' => [
        'sandbox' => 'https://gateway20.pelecard.biz/services',
        'production' => 'https://gateway21.pelecard.biz/services',
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency
    |--------------------------------------------------------------------------
    |
    | Default currency for transactions (ISO 4217 code).
    |
    */

    'currency' => env('PELECARD_CURRENCY', 'ILS'),

    /*
    |--------------------------------------------------------------------------
    | Language
    |--------------------------------------------------------------------------
    |
    | Default language for Pelecard responses: 'he' (Hebrew) or 'en' (English)
    |
    */

    'language' => env('PELECARD_LANGUAGE', 'he'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for webhook handling and signature validation.
    |
    */

    'webhook' => [
        'enabled' => env('PELECARD_WEBHOOK_ENABLED', true),
        'path' => env('PELECARD_WEBHOOK_PATH', 'pelecard/webhook'),
        'signature_validation' => env('PELECARD_WEBHOOK_SIGNATURE_VALIDATION', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Enable logging of API requests and responses for debugging.
    |
    */

    'logging' => [
        'enabled' => env('PELECARD_LOGGING_ENABLED', false),
        'channel' => env('PELECARD_LOGGING_CHANNEL', 'stack'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    |
    | Cache settings for credentials and API responses.
    |
    */

    'cache' => [
        'enabled' => env('PELECARD_CACHE_ENABLED', true),
        'ttl' => env('PELECARD_CACHE_TTL', 3600), // 1 hour
        'prefix' => 'pelecard',
    ],

];
