<?php

/**
 * Config for `saagoor/takaden` package
 * Author: MH Sagor (https://github.com/saagoor)
 * License MIT
 */

return [
    'defaults' => [
        'currency' => env('TAKADEN_DEFAULT_CURRENCY', 'BDT'),
    ],
    'checkout'  => [
        'route_prefix' => env('TAKADEN_CHECKOUT_ROUTE_PREFIX', '/takaden/checkout'),
    ],
    'redirects' => [
        'success' => env('TAKADEN_REDIRECT_SUCCESS', '/checkout/success'),
        'failure' => env('TAKADEN_REDIRECT_FAILURE', '/checkout/failure'),
        'cancel' => env('TAKADEN_REDIRECT_CANCEL', '/checkout/cancel'),
    ],
    'providers' => [
        'bkash' => [
            'base_url' => env('BKASH_BASE_URL', 'https://checkout.sandbox.bka.sh/v1.2.0-beta'),
            'script_url' => env('BKASH_SCRIPT_URL', 'https://scripts.sandbox.bka.sh/versions/1.2.0-beta/checkout/bKash-checkout-sandbox.js'),
            'intent' => env('BKASH_PAYMENT_INTENT', 'sale'),
            'app_key' => env('BKASH_APP_KEY', '5tunt4masn6pv2hnvte1sb5n3j'),
            'app_secret' => env('BKASH_APP_SECRET', '1vggbqd4hqk9g96o9rrrp2jftvek578v7d2bnerim12a87dbrrka'),
            'username' => env('BKASH_USERNAME', 'sandboxTestUser'),
            'password' => env('BKASH_PASSWORD', 'hWD@8vtzw0'),
        ],
        'nagad' => [
            'base_url' => env('NAGAD_BASE_URL', 'http://sandbox.mynagad.com:10080/remote-payment-gateway-1.0/api/dfs'),
            'merchant_id' => env('NAGAD_MERCHANT_ID', '683002007104225'),
            'merchant_phone' => env('NAGAD_MERCHANT_PHONE', '01638333555'),
            'public_key' => env('NAGAD_PUBLIC_KEY', 'MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAjBH1pFNSSRKPuMcNxmU5jZ1x8K9LPFM4XSu11m7uCfLUSE4SEjL30w3ockFvwAcuJffCUwtSpbjr34cSTD7EFG1Jqk9Gg0fQCKvPaU54jjMJoP2toR9fGmQV7y9fz31UVxSk97AqWZZLJBT2lmv76AgpVV0k0xtb/0VIv8pd/j6TIz9SFfsTQOugHkhyRzzhvZisiKzOAAWNX8RMpG+iqQi4p9W9VrmmiCfFDmLFnMrwhncnMsvlXB8QSJCq2irrx3HG0SJJCbS5+atz+E1iqO8QaPJ05snxv82Mf4NlZ4gZK0Pq/VvJ20lSkR+0nk+s/v3BgIyle78wjZP1vWLU4wIDAQAB'),
            'private_key' => env('NAGAD_PRIVATE_KEY', 'MIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQCJakyLqojWTDAVUdNJLvuXhROV+LXymqnukBrmiWwTYnJYm9r5cKHj1hYQRhU5eiy6NmFVJqJtwpxyyDSCWSoSmIQMoO2KjYyB5cDajRF45v1GmSeyiIn0hl55qM8ohJGjXQVPfXiqEB5c5REJ8Toy83gzGE3ApmLipoegnwMkewsTNDbe5xZdxN1qfKiRiCL720FtQfIwPDp9ZqbG2OQbdyZUB8I08irKJ0x/psM4SjXasglHBK5G1DX7BmwcB/PRbC0cHYy3pXDmLI8pZl1NehLzbav0Y4fP4MdnpQnfzZJdpaGVE0oI15lq+KZ0tbllNcS+/4MSwW+afvOw9bazAgMBAAECggEAIkenUsw3GKam9BqWh9I1p0Xmbeo+kYftznqai1pK4McVWW9//+wOJsU4edTR5KXK1KVOQKzDpnf/CU9SchYGPd9YScI3n/HR1HHZW2wHqM6O7na0hYA0UhDXLqhjDWuM3WEOOxdE67/bozbtujo4V4+PM8fjVaTsVDhQ60vfv9CnJJ7dLnhqcoovidOwZTHwG+pQtAwbX0ICgKSrc0elv8ZtfwlEvgIrtSiLAO1/CAf+uReUXyBCZhS4Xl7LroKZGiZ80/JE5mc67V/yImVKHBe0aZwgDHgtHh63/50/cAyuUfKyreAH0VLEwy54UCGramPQqYlIReMEbi6U4GC5AQKBgQDfDnHCH1rBvBWfkxPivl/yNKmENBkVikGWBwHNA3wVQ+xZ1Oqmjw3zuHY0xOH0GtK8l3Jy5dRL4DYlwB1qgd/Cxh0mmOv7/C3SviRk7W6FKqdpJLyaE/bqI9AmRCZBpX2PMje6Mm8QHp6+1QpPnN/SenOvoQg/WWYM1DNXUJsfMwKBgQCdtddE7A5IBvgZX2o9vTLZY/3KVuHgJm9dQNbfvtXw+IQfwssPqjrvoU6hPBWHbCZl6FCl2tRh/QfYR/N7H2PvRFfbbeWHw9+xwFP1pdgMug4cTAt4rkRJRLjEnZCNvSMVHrri+fAgpv296nOhwmY/qw5Smi9rMkRY6BoNCiEKgQKBgAaRnFQFLF0MNu7OHAXPaW/ukRdtmVeDDM9oQWtSMPNHXsx+crKY/+YvhnujWKwhphcbtqkfj5L0dWPDNpqOXJKV1wHt+vUexhKwus2mGF0flnKIPG2lLN5UU6rs0tuYDgyLhAyds5ub6zzfdUBG9Gh0ZrfDXETRUyoJjcGChC71AoGAfmSciL0SWQFU1qjUcXRvCzCK1h25WrYS7E6pppm/xia1ZOrtaLmKEEBbzvZjXqv7PhLoh3OQYJO0NM69QMCQi9JfAxnZKWx+m2tDHozyUIjQBDehve8UBRBRcCnDDwU015lQN9YNb23Fz+3VDB/LaF1D1kmBlUys3//r2OV0Q4ECgYBnpo6ZFmrHvV9IMIGjP7XIlVa1uiMCt41FVyINB9SJnamGGauW/pyENvEVh+ueuthSg37e/l0Xu0nm/XGqyKCqkAfBbL2Uj/j5FyDFrpF27PkANDo99CdqL5A4NQzZ69QRlCQ4wnNCq6GsYy2WEJyU2D+K8EBSQcwLsrI7QL7fvQ=='),
        ],
        'upay' => [
            'base_url' => env('UPAY_BASE_URL', 'https://uat-pg.upay.systems'),
            'merchant_id' => env('UPAY_MERCHANT_ID', '1110101010000002'),
            'merchant_key' => env('UPAY_MERCHANT_KEY', 'q1116pgDpVUuU82na9OzAJyrBKY344b7'),
            'merchant_code' => env('UPAY_MERCHANT_CODE', 'TEST5'),
            'merchant_name' => env('UPAY_MERCHANT_NAME', 'TEST5'),
            'merchant_mobile' => env('UPAY_MERCHANT_MOBILE', '01638333555'),
            'merchant_country' => env('UPAY_MERCHANT_COUNTRY', 'BD'),
            'merchant_city' => env('UPAY_MERCHANT_CITY', 'Dhaka'),
        ],
    ],
];
