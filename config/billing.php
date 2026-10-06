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

    /**
     * Desde 06-10-2026 estos valores se pueden capturar también en
     * /admin/configuracion-pagos; BillingConfigServiceProvider los sobrescribe
     * en boot() cuando hay una fila activa en `payment_credentials`. Lo de
     * aquí queda como fallback para instalaciones que sigan usando .env.
     */
    'mercadopago' => [
        'access_token' => env('MERCADOPAGO_ACCESS_TOKEN'),
        'webhook_secret' => env('MERCADOPAGO_WEBHOOK_SECRET'),
        // Sólo se usaría con Checkout Bricks (frontend). Hoy el flujo es por
        // redirect a init_point, así que no es obligatoria.
        'public_key' => env('MERCADOPAGO_PUBLIC_KEY'),
    ],

    // Divisa por default para nuevos cobros. México → MXN.
    'currency' => env('BILLING_CURRENCY', 'MXN'),

];
