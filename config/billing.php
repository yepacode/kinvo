<?php

return [

    /**
     * Pasarela activa. Se elige en el kick-off con la cliente.
     * Valores válidos: 'fake' (default), 'stripe', 'mercadopago' (futuro).
     *
     * En .env: BILLING_GATEWAY=stripe (o el que corresponda).
     */
    'gateway' => env('BILLING_GATEWAY', 'fake'),

    'stripe' => [
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'publishable_key' => env('STRIPE_PUBLISHABLE_KEY'),
    ],

    'mercadopago' => [
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
    ],

    // Divisa por default para nuevos cobros. México → MXN.
    'currency' => env('BILLING_CURRENCY', 'MXN'),

];
