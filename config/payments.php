<?php

declare(strict_types=1);

return [

    'default' => env('PAYMENT_GATEWAY', 'mono'),

    'prices' => [
        15  => (int) env('PRICE_15_DAYS_KOPECKS', 10000),   // 100 грн
        30  => (int) env('PRICE_30_DAYS_KOPECKS', 20000),   // 200 грн
        90  => (int) env('PRICE_90_DAYS_KOPECKS', 50000),   // 500 грн
    ],

    'gateways' => [

        'stripe' => [
            'key'            => env('STRIPE_KEY'),
            'secret'         => env('STRIPE_SECRET'),
            'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        ],

        'mono' => [
            'token'          => env('MONO_TOKEN'),
            'webhook_secret' => env('MONO_WEBHOOK_SECRET'),
            'public_key'     => env('MONO_PUBLIC_KEY'),
        ],

        'wayforpay' => [
            // Основний мерчант для прийому платежів
            'merchant_account'  => env('WFP_MERCHANT_ACCOUNT'),
            'merchant_password' => env('WFP_MERCHANT_PASSWORD'),
            'merchant_domain'   => env('WFP_MERCHANT_DOMAIN', config('app.url')),
            'checkout_mode'     => env('WFP_CHECKOUT_MODE', 'form'), // 'form' | 'hosted'

            // P2P cardtransfer (виплати на картку)
            'p2p_account'       => env('WFP_P2P_ACCOUNT'),

            // P2P credit (кредитні виплати)
            'p2p_credit_account' => env('WFP_P2P_CREDIT_ACCOUNT'),
        ],

        'liqpay' => [
            'public_key'  => env('LIQPAY_PUBLIC_KEY'),
            'private_key' => env('LIQPAY_PRIVATE_KEY'),
        ],

    ],

];
