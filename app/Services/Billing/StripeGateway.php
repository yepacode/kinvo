<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\User;

/**
 * Implementación real para Stripe. SE ACTIVA al cerrar el kick-off con la
 * cliente: se completa el TODO de abajo con `stripe/stripe-php` y las claves
 * en `.env`.
 *
 * Hoy funciona como esqueleto — la app usa FakeGateway hasta que la cliente
 * confirme Stripe y comparta las claves.
 */
class StripeGateway implements SubscriptionGateway
{
    public function name(): string
    {
        return 'stripe';
    }

    public function createCheckoutUrl(User $user, Plan $plan, string $successUrl, string $cancelUrl): string
    {
        // TODO(F2·kickoff): implementar con Stripe\Checkout\Session::create()
        // usando $plan->provider_price_id como línea de la suscripción.
        throw new \RuntimeException('StripeGateway pendiente de configurar. Usa FakeGateway mientras.');
    }

    public function cancelSubscription(string $providerSubscriptionId): void
    {
        // TODO(F2·kickoff): Stripe\Subscription::retrieve($id)->cancel();
        throw new \RuntimeException('StripeGateway pendiente de configurar.');
    }

    public function verifyWebhook(string $payload, string $signatureHeader): array
    {
        // TODO(F2·kickoff): Stripe\Webhook::constructEvent(...) con la webhook secret.
        throw new \RuntimeException('StripeGateway pendiente de configurar.');
    }
}
