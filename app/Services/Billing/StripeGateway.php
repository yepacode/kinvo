<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Checkout\Session as StripeCheckoutSession;
use Stripe\Stripe;
use Stripe\Subscription as StripeSubscription;
use Stripe\Webhook as StripeWebhook;

/**
 * Implementación real para Stripe.
 *
 * Flujo (estándar de Stripe Checkout con suscripciones):
 *  1. `createCheckoutUrl` crea una Subscription local en estado INCOMPLETE
 *     (con provider IDs en null) y una Stripe Checkout Session con
 *     `mode=subscription` y el `provider_price_id` del plan.
 *  2. Al pagar, Stripe redirige al `success_url` (pantalla "gracias") y
 *     dispara webhooks: `checkout.session.completed` (vincula la Subscription
 *     local con los IDs reales), `invoice.payment_succeeded` (crea Payment,
 *     activa membresía), etc.
 *  3. `cancelSubscription` usa `cancel_at_period_end` para conservar acceso
 *     hasta que termine el periodo pagado (comportamiento SaaS estándar).
 *  4. `verifyWebhook` valida la firma HMAC con Stripe\Webhook::constructEvent
 *     y devuelve el evento como array — el WebhookController lo procesa
 *     con la misma lógica normalizada que FakeGateway.
 *
 * Activación: pon en .env `BILLING_GATEWAY=stripe`, `STRIPE_SECRET`,
 * `STRIPE_WEBHOOK_SECRET` y crea el Price ID de cada plan en Stripe
 * Dashboard → Products, luego pégalo en el admin Filament de cada plan.
 */
class StripeGateway implements SubscriptionGateway
{
    public function __construct()
    {
        // Configuración lazy: si no hay clave (dev sin llaves), no se toca
        // el SDK. Los 3 métodos abortan explícitamente cuando la clave falta.
        $key = config('billing.stripe.secret');
        if ($key) {
            Stripe::setApiKey($key);
            Stripe::setApiVersion('2024-06-20');
            Stripe::setAppInfo('Kinvoo', '1.0', 'https://kinvoo.com');
        }
    }

    public function name(): string
    {
        return 'stripe';
    }

    public function createCheckoutUrl(User $user, Plan $plan, string $successUrl, string $cancelUrl): string
    {
        $this->guardConfigured();
        if (blank($plan->provider_price_id)) {
            throw new \RuntimeException(
                "El plan '{$plan->nombre}' no tiene provider_price_id. Crea el Price en Stripe Dashboard → Products → tu producto → Prices, copia el ID (formato price_...) y pégalo en /admin/plans → editar plan → sección Pasarela de pago (Stripe)."
            );
        }

        // Guardamos la Subscription local en INCOMPLETE. El webhook
        // checkout.session.completed la vinculará con los IDs reales.
        // client_reference_id = user_id nos permite hacer el match sin
        // depender del email (evita colisiones si el user cambia email).
        $sub = Subscription::create([
            'user_id'  => $user->id,
            'plan_id'  => $plan->id,
            'provider' => 'stripe',
            'status'   => Subscription::STATUS_INCOMPLETE,
        ]);

        $session = StripeCheckoutSession::create([
            'mode'                => 'subscription',
            'line_items'          => [[
                'price'    => $plan->provider_price_id,
                'quantity' => 1,
            ]],
            // Query string {CHECKOUT_SESSION_ID} lo reemplaza Stripe en el
            // redirect, útil si algún día queremos verificar en la landing.
            'success_url'         => $successUrl.'?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url'          => $cancelUrl,
            'client_reference_id' => (string) $user->id,
            'customer_email'      => $user->email,
            'metadata'            => [
                'user_id'              => $user->id,
                'plan_id'              => $plan->id,
                'local_subscription_id' => $sub->id,
            ],
            'subscription_data'   => [
                'metadata' => [
                    'user_id'              => $user->id,
                    'plan_id'              => $plan->id,
                    'local_subscription_id' => $sub->id,
                ],
            ],
            'allow_promotion_codes' => true,
        ]);

        return $session->url;
    }

    public function cancelSubscription(string $providerSubscriptionId): void
    {
        if (blank($providerSubscriptionId)) {
            return;
        }
        $this->guardConfigured();
        // cancel_at_period_end mantiene el acceso hasta que termine el
        // periodo pagado — cuando termine, Stripe dispara customer.
        // subscription.deleted y nuestro webhook cambia el status local.
        StripeSubscription::update($providerSubscriptionId, [
            'cancel_at_period_end' => true,
        ]);
    }

    public function verifyWebhook(string $payload, string $signatureHeader): array
    {
        $secret = config('billing.stripe.webhook_secret');
        if (! $secret) {
            throw new \RuntimeException(
                'STRIPE_WEBHOOK_SECRET no está configurado en .env. Consíguelo en Stripe Dashboard → Developers → Webhooks → tu endpoint → Signing secret.'
            );
        }
        // constructEvent tira SignatureVerificationException si la firma no
        // coincide — el WebhookController la convierte en HTTP 400.
        $event = StripeWebhook::constructEvent($payload, $signatureHeader, $secret);
        return json_decode(json_encode($event->toArray()), true);
    }

    private function guardConfigured(): void
    {
        if (! config('billing.stripe.secret')) {
            throw new \RuntimeException(
                'STRIPE_SECRET no está configurado en .env. Consíguelo en Stripe Dashboard → Developers → API keys → Secret key.'
            );
        }
    }
}
