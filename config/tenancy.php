<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom Domains (preparation only)
    |--------------------------------------------------------------------------
    |
    | Per-tenant custom domains are not yet implemented. These settings
    | document the intended configuration for a future release.
    |
    */

    'custom_domains' => [
        'enabled' => env('TENANCY_CUSTOM_DOMAINS_ENABLED', false),
        'base_domain' => env('TENANCY_BASE_DOMAIN', 'jalwala.test'),
        'verify_ssl' => env('TENANCY_VERIFY_SSL', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Billing (stub)
    |--------------------------------------------------------------------------
    |
    | Hooks for a future payment provider integration. No billing is
    | processed in Phase 10 — tenant_subscriptions records trial metadata only.
    |
    */

    'billing' => [
        'provider' => env('TENANCY_BILLING_PROVIDER', 'stripe'),
        'webhook_secret' => env('TENANCY_BILLING_WEBHOOK_SECRET'),
    ],

    'trial_days' => (int) env('TENANCY_TRIAL_DAYS', 14),

];
