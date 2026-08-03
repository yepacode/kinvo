<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\User;

/**
 * Fase 2 · Interfaz agnóstica de pasarela de pagos.
 *
 * La app habla con esta interfaz, no con Stripe directamente. Al cerrar el
 * kick-off basta con inyectar StripeGateway o MercadoPagoGateway sin tocar
 * controllers, jobs ni webhooks.
 *
 * La implementación por default es FakeGateway (sin cobros reales) para
 * desarrollo y tests. En producción se activa la implementación real vía
 * `config('billing.gateway')` en el ServiceProvider.
 */
interface SubscriptionGateway
{
    /**
     * Crea (o reactiva) una URL de checkout para que el user pague la primera
     * factura de una suscripción a un plan. Devuelve la URL a la que hay que
     * redirigir al usuario.
     */
    public function createCheckoutUrl(User $user, Plan $plan, string $successUrl, string $cancelUrl): string;

    /**
     * Cancela una suscripción existente. El acceso se mantiene hasta
     * `current_period_end` (comportamiento estándar Stripe/MP).
     */
    public function cancelSubscription(string $providerSubscriptionId): void;

    /**
     * Verifica la firma HMAC de un webhook entrante. Devuelve el payload
     * decodificado si la firma es válida, o lanza \RuntimeException si no.
     */
    public function verifyWebhook(string $payload, string $signatureHeader): array;

    /** Identificador legible del proveedor (para logs, columna `provider`). */
    public function name(): string;
}
