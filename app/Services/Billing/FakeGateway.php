<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Implementación FAKE que simula una pasarela real. Se usa en:
 *   - Desarrollo local (sin claves reales)
 *   - Tests automatizados (control determinístico)
 *   - Kickoff cliente: puede probar el flujo antes de configurar Stripe
 *
 * NO cobra dinero real. Cuando `createCheckoutUrl()` se llama, deja la
 * suscripción en estado 'incomplete' y devuelve una URL de un endpoint
 * local /billing/fake-checkout/{token} que el flujo de demo puede confirmar
 * manualmente para simular el webhook exitoso.
 */
class FakeGateway implements SubscriptionGateway
{
    public function name(): string
    {
        return 'fake';
    }

    public function createCheckoutUrl(User $user, Plan $plan, string $successUrl, string $cancelUrl): string
    {
        // Crea/actualiza la suscripción en estado 'incomplete' y devuelve
        // una URL local que el user puede visitar para simular el pago exitoso.
        $token = Str::random(32);

        Subscription::updateOrCreate(
            [
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'status' => Subscription::STATUS_INCOMPLETE,
            ],
            [
                'provider' => $this->name(),
                'provider_subscription_id' => 'fake_sub_'.$token,
                'provider_customer_id' => 'fake_cus_'.$user->id,
            ]
        );

        return url('/billing/fake-checkout/'.$token
            .'?success='.urlencode($successUrl)
            .'&cancel='.urlencode($cancelUrl));
    }

    public function cancelSubscription(string $providerSubscriptionId): void
    {
        Subscription::where('provider_subscription_id', $providerSubscriptionId)
            ->update([
                'status' => Subscription::STATUS_CANCELED,
                'canceled_at' => now(),
                'ends_at' => now()->addMonth(),
            ]);
    }

    public function verifyWebhook(string $payload, string $signatureHeader): array
    {
        // En modo Fake, cualquier payload JSON válido pasa (útil para tests).
        // En producción con Stripe, aquí va la verificación HMAC real.
        $data = json_decode($payload, true);
        if (! is_array($data)) {
            throw new \RuntimeException('Webhook payload no es JSON válido.');
        }
        return $data;
    }
}
