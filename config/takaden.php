<?php

// Config for saagoor/takaden package
return [
    'bkash' => [
        'base_url'      => env('BKASH_BASE_URL', 'https://checkout.sandbox.bka.sh/v1.2.0-beta'),
        'script_url'    => env('BKASH_BASE_URL', 'https://scripts.sandbox.bka.sh/versions/1.2.0-beta/checkout/bKash-checkout-sandbox.js'),
        'intent'        => env('BKASH_BASE_URL', 'sale'),
        'app_key'       => env('BKASH_APP_KEY'),
        'app_secret'    => env('BKASH_APP_SECRET'),
        'username'      => env('BKASH_USERNAME', 'sandboxTestUser'),
        'password'      => env('BKASH_PASSWORD', 'hWD@8vtzw0'),
    ],
    'upay'  => [
        'base_url'          => env('UPAY_BASE_URL', 'https://uat-pg.upay.systems'),
        'merchant_id'       => env('UPAY_MERCHANT_ID'),
        'merchant_key'      => env('UPAY_MERCHANT_KEY'),
        'merchant_code'     => env('UPAY_MERCHANT_CODE', 'TEST5'),
        'merchant_name'     => env('UPAY_MERCHANT_NAME', 'TEST5'),
        'merchant_mobile'   => env('UPAY_MERCHANT_MOBILE', '01937800696'),
        'merchant_country'  => env('UPAY_MERCHANT_COUNTRY', 'BD'),
        'merchant_city'     => env('UPAY_MERCHANT_CITY', 'Dhaka'),
    ]
];
