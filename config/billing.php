<?php

return [

    /**
     * Pasarela activa. Marian eligió MercadoPago (México); Stripe queda
     * como alternativa por si algún día se cambia de proveedor.
     * Valores válidos: 'fake' (default dev), 'mercadopago', 'stripe'.
     *
     * En .env: BILLING_GATEWAY=mercadopago
     */
    'gateway' => env('BILLING_GATEWAY', 'fake'),

    /**
     * Secret compartido con el que se verifica la firma HMAC de los webhooks
     * en modo FakeGateway. Sirve para que /webhooks/billing esté firmado
     * incluso antes de conectar Stripe. En producción real usar los secrets
     * de la sección `stripe` o `mercadopago`.
     */
    'webhook_secret' => env('BILLING_WEBHOOK_SECRET', 'kinvoo-fake-dev-secret-cambiar-en-prod'),

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
