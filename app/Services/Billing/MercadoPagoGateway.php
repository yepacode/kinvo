<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use MercadoPago\Client\Payment\PaymentClient;
use MercadoPago\Client\PreApproval\PreApprovalClient;
use MercadoPago\MercadoPagoConfig;

/**
 * Implementación real para MercadoPago (elegida por Marian — México).
 *
 * Modelo de suscripciones = PreApproval:
 *   1. `createCheckoutUrl` crea una Subscription local INCOMPLETE + un
 *      PreApproval en MP con status=pending → devuelve `init_point`.
 *   2. Al pagar, MP redirige al `back_url` y dispara webhooks (payment y
 *      preapproval). `verifyWebhook` consulta la API de MP para resolver el
 *      status real (approved/rejected/authorized/cancelled) y devuelve un
 *      evento NORMALIZADO al mismo shape que StripeGateway — el
 *      WebhookController los procesa con la misma lógica.
 *   3. `cancelSubscription` marca el preapproval como cancelled (MP mantiene
 *      el acceso hasta el fin del periodo pagado).
 *
 * Activación: `.env` con `BILLING_GATEWAY=mercadopago`, `MERCADOPAGO_ACCESS_TOKEN`
 * y `MERCADOPAGO_WEBHOOK_SECRET`. Los planes deben tener `precio` y `moneda`
 * — MP los usa para crear el auto_recurring on-the-fly.
 */
class MercadoPagoGateway implements SubscriptionGateway
{
    public function __construct()
    {
        // Configuración lazy: si no hay token, el SDK no se toca. Los 3
        // métodos abortan explícitamente cuando falta el token.
        $token = config('billing.mercadopago.access_token');
        if ($token) {
            MercadoPagoConfig::setAccessToken($token);
        }
    }

    public function name(): string
    {
        return 'mercadopago';
    }

    public function createCheckoutUrl(User $user, Plan $plan, string $successUrl, string $cancelUrl): string
    {
        $this->guardConfigured();
        if (blank($plan->precio) || (float) $plan->precio <= 0) {
            throw new \RuntimeException(
                "El plan '{$plan->nombre}' no tiene precio. MercadoPago necesita un monto para crear el auto_recurring — captura el precio en /admin/plans."
            );
        }

        // Subscription local INCOMPLETE con external_reference único; el
        // webhook la resuelve a ACTIVE cuando MP autoriza el preapproval.
        $sub = Subscription::create([
            'user_id'  => $user->id,
            'plan_id'  => $plan->id,
            'provider' => 'mercadopago',
            'status'   => Subscription::STATUS_INCOMPLETE,
        ]);

        // MP interpreta `frequency=1,frequency_type=months` como mensual, y
        // `frequency=12,frequency_type=months` como anual (no hay 'years').
        $frequency = $plan->interval === 'year' ? 12 : 1;

        $client = new PreApprovalClient();
        $preapproval = $client->create([
            'reason'              => 'Kinvoo · '.$plan->nombre,
            'external_reference'  => 'kinvoo_sub_'.$sub->id,
            'payer_email'         => $user->email,
            'auto_recurring'      => [
                'frequency'          => $frequency,
                'frequency_type'     => 'months',
                'transaction_amount' => (float) $plan->precio,
                'currency_id'        => $plan->moneda ?: config('billing.currency', 'MXN'),
            ],
            'back_url'            => $successUrl,
            'status'              => 'pending',
        ]);

        // Guardamos el preapproval_id (aunque aún esté pending) para poder
        // consultar/cancelar antes de que llegue el primer webhook.
        $sub->update(['provider_subscription_id' => $preapproval->id]);

        // MP retorna sandbox_init_point en TEST y init_point en LIVE.
        // El SDK entrega el correcto según el modo del token activo.
        return $preapproval->init_point;
    }

    public function cancelSubscription(string $providerSubscriptionId): void
    {
        if (blank($providerSubscriptionId)) {
            return;
        }
        $this->guardConfigured();
        // Cambiar a cancelled corta las siguientes cobranzas. El acceso local
        // lo maneja el WebhookController cuando llega el evento preapproval
        // con status=cancelled.
        $client = new PreApprovalClient();
        $client->update($providerSubscriptionId, ['status' => 'cancelled']);
    }

    public function verifyWebhook(string $payload, string $signatureHeader): array
    {
        $secret = config('billing.mercadopago.webhook_secret');
        if (! $secret) {
            throw new \RuntimeException(
                'MERCADOPAGO_WEBHOOK_SECRET no está configurado en .env. Consíguelo en MercadoPago → Tu aplicación → Webhooks → Secret firma.'
            );
        }

        // MP firma con formato: `ts=<epoch>,v1=<hmac_sha256_hex>`
        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            $kv = explode('=', trim($part), 2);
            if (count($kv) === 2) {
                $parts[$kv[0]] = $kv[1];
            }
        }
        $ts  = $parts['ts'] ?? '';
        $sig = $parts['v1'] ?? '';
        if (! $ts || ! $sig) {
            throw new \RuntimeException('MercadoPago webhook signature header inválido');
        }

        // Manifest exacto que MP firma:
        //   id:<data.id>;request-id:<x-request-id>;ts:<ts>;
        $data      = json_decode($payload, true) ?: [];
        $dataId    = (string) ($data['data']['id'] ?? $data['id'] ?? '');
        $requestId = (string) (request()->header('x-request-id', ''));
        $manifest  = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $expected  = hash_hmac('sha256', $manifest, $secret);
        if (! hash_equals($expected, $sig)) {
            throw new \RuntimeException('MercadoPago webhook signature mismatch');
        }

        // Normalizamos el evento al MISMO shape que StripeGateway devuelve,
        // para que el WebhookController los procese con lógica idéntica.
        // MP webhooks entregan `topic`+`id`; hay que consultar API para
        // saber el status real del recurso.
        $topic = $data['type'] ?? $data['topic'] ?? '';

        if ($topic === 'payment') {
            $paymentClient = new PaymentClient();
            $payment = $paymentClient->get($dataId);
            $type = match ($payment->status) {
                'approved'                       => 'payment_succeeded',
                'refunded', 'charged_back'       => 'refund',
                'rejected', 'cancelled'          => 'payment_failed',
                default                          => 'ignored',
            };
            return [
                'type' => $type,
                'data' => [
                    'object' => [
                        'id'           => (string) $payment->id,
                        // Vinculamos al preapproval si el payment lo indica.
                        'subscription' => (string) ($payment->metadata->preapproval_id ?? $payment->point_of_interaction?->transaction_data?->subscription_id ?? ''),
                        'invoice'      => null,
                        // MP entrega monto en decimal; nuestro modelo Payment
                        // usa `amount_cents` (entero). Convertimos.
                        'amount'       => (int) round(((float) $payment->transaction_amount) * 100),
                        'currency'     => $payment->currency_id,
                        'status'       => $payment->status,
                    ],
                ],
            ];
        }

        if (str_starts_with($topic, 'preapproval') || $topic === 'subscription_preapproval') {
            $client = new PreApprovalClient();
            $pre = $client->get($dataId);
            $type = match ($pre->status) {
                'authorized'      => 'checkout_completed',
                'cancelled'       => 'subscription_canceled',
                'paused'          => 'payment_failed',
                default           => 'ignored',
            };
            // El `external_reference` viene como 'kinvoo_sub_<id>' — extraemos
            // el id local para vincular la Subscription creada en checkout.
            $localSubId = null;
            if ($pre->external_reference && str_starts_with($pre->external_reference, 'kinvoo_sub_')) {
                $localSubId = (int) substr($pre->external_reference, strlen('kinvoo_sub_'));
            }
            return [
                'type' => $type,
                'data' => [
                    'object' => [
                        'id'                    => (string) $pre->id,
                        'subscription'          => (string) $pre->id,
                        'external_reference'    => $pre->external_reference,
                        'metadata'              => ['local_subscription_id' => $localSubId],
                        'client_reference_id'   => $localSubId ? (string) $localSubId : null,
                        'customer'              => $pre->payer_id ? (string) $pre->payer_id : null,
                        'status'                => $pre->status,
                    ],
                ],
            ];
        }

        // Evento no relevante para nosotros (test webhooks del dashboard, etc.).
        return ['type' => 'ignored', 'data' => ['object' => []]];
    }

    private function guardConfigured(): void
    {
        if (! config('billing.mercadopago.access_token')) {
            throw new \RuntimeException(
                'MERCADOPAGO_ACCESS_TOKEN no está configurado en .env. Consíguelo en MercadoPago → Tu aplicación → Credenciales de producción/prueba.'
            );
        }
    }
}
