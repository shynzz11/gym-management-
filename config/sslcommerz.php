<?php

return [
    'store_id' => env('SSLCOMMERZ_STORE_ID', ''),
    'store_password' => env('SSLCOMMERZ_STORE_PASSWORD', ''),
    'sandbox' => env('SSLCOMMERZ_SANDBOX', true),
    'sandbox_base_url' => env('SSLCOMMERZ_SANDBOX_BASE_URL', 'https://sandbox.sslcommerz.com'),
    'live_base_url' => env('SSLCOMMERZ_LIVE_BASE_URL', 'https://securepay.sslcommerz.com'),
];
