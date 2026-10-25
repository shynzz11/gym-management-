<?php

return [
    'bdt_to_usd_rate' => (float) env('PAYMENT_BDT_TO_USD_RATE', 120),
    'default_gateway_by_country' => [
        'BD' => 'sslcommerz',
    ],
    'gateways' => [
        'sslcommerz' => [
            'enabled' => filter_var(env('SSLCOMMERZ_ENABLED', true), FILTER_VALIDATE_BOOL),
        ],
        'stripe' => [
            'enabled' => filter_var(env('STRIPE_ENABLED', true), FILTER_VALIDATE_BOOL),
        ],
    ],
];
