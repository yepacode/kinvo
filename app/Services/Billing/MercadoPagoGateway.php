<?php

namespace App\Services\Billing;

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Exceptions\FirmaDeWebhookInvalida;
use MercadoPago\Client\Invoice\InvoiceClient;
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
        $this->aplicarToken();
    }

    /**
     * Relee el token de la config y lo aplica al SDK. Se llama en cada
     * operación (vía guardConfigured) y no sólo en el constructor porque
     * desde 06-10-2026 el token puede cambiar en caliente desde
     * /admin/configuracion-pagos; como el gateway está registrado como
     * singleton, un token leído una sola vez se quedaría obsoleto dentro
     * del mismo request en que el admin lo actualiza.
     */
    private function aplicarToken(): void
    {
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
        // La periodicidad la decide `Plan::esAnual()`, que es la regla única:
        // antes esto miraba `interval`, un campo distinto del que se edita en
        // la ficha, así que un plan marcado "Anual" podía cobrarse cada mes.
        $frequency = $plan->esAnual() ? 12 : 1;

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

    /**
     * Comprueba que MercadoPago acepta cobrar ESTE plan, sin cobrar nada.
     *
     * Crea el preapproval con el mismo payload que el checkout real y lo
     * cancela acto seguido: no hay tarjeta ni cobro, sólo la pregunta. Lanza
     * si MercadoPago lo rechaza, con la causa dentro de la excepción.
     *
     * Existe porque "Probar conexión" sólo preguntaba de quién es el token
     * (/users/me) y eso responde bien aunque un plan concreto tenga un dato
     * que MercadoPago no acepta — moneda rara, importe fuera de rango,
     * periodicidad imposible. Esos fallos sólo aparecían cuando una persona
     * real intentaba pagar.
     */
    public function probarPlan(Plan $plan): void
    {
        $this->guardConfigured();

        if (blank($plan->precio) || (float) $plan->precio <= 0) {
            throw new \RuntimeException("El plan '{$plan->nombre}' no tiene precio.");
        }

        $client = new PreApprovalClient();
        $pre = $client->create([
            'reason'             => 'Kinvoo · '.$plan->nombre,
            'external_reference' => 'kinvoo_prueba_'.$plan->id.'_'.time(),
            'payer_email'        => 'prueba.'.$plan->id.'@kinvoo.test',
            'auto_recurring'     => [
                'frequency'          => $plan->esAnual() ? 12 : 1,
                'frequency_type'     => 'months',
                'transaction_amount' => (float) $plan->precio,
                'currency_id'        => $plan->moneda ?: config('billing.currency', 'MXN'),
            ],
            'back_url' => $this->urlDeVuelta(),
            'status'   => 'pending',
        ]);

        // No dejamos basura en la cuenta real.
        if ($pre->id) {
            try {
                $client->update($pre->id, ['status' => 'cancelled']);
            } catch (\Throwable $e) {
                report($e);
            }
        }
    }

    /** URL de vuelta válida: MercadoPago exige https, y en local no lo hay. */
    private function urlDeVuelta(): string
    {
        $url = route('billing.exitosa');

        return str_starts_with($url, 'https://') ? $url : 'https://gokinvoo.com/suscripcion/exitosa';
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
            throw new FirmaDeWebhookInvalida(
                'Falta el Secret de firma del webhook de MercadoPago. Captúralo en /admin/configuracion-pagos (o en MERCADOPAGO_WEBHOOK_SECRET del .env). Lo obtienes en MercadoPago → Tu aplicación → Webhooks → Secret de firma.'
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
            throw new FirmaDeWebhookInvalida('MercadoPago webhook signature header inválido');
        }

        // Manifest exacto que MP firma:
        //   id:<data.id>;request-id:<x-request-id>;ts:<ts>;
        $data      = json_decode($payload, true) ?: [];
        $dataId    = (string) ($data['data']['id'] ?? $data['id'] ?? '');
        $requestId = (string) (request()->header('x-request-id', ''));
        $manifest  = "id:{$dataId};request-id:{$requestId};ts:{$ts};";
        $expected  = hash_hmac('sha256', $manifest, $secret);
        if (! hash_equals($expected, $sig)) {
            throw new FirmaDeWebhookInvalida('MercadoPago webhook signature mismatch');
        }

        // Normalizamos el evento al MISMO shape que StripeGateway devuelve,
        // para que el WebhookController los procese con lógica idéntica.
        // MP webhooks entregan `topic`+`id`; hay que consultar API para
        // saber el status real del recurso.
        $topic = $data['type'] ?? $data['topic'] ?? '';

        // A partir de aquí la firma ya está validada: el aviso es auténtico.
        // Lo que sigue es consultarle a MercadoPago por el recurso, y eso
        // puede fallar por motivos que no son culpa de nadie —p. ej. un
        // 404 porque el cobro se rechazó y la factura nunca llegó a
        // existir—. Esos fallos NO deben tumbar el aviso: se devuelve
        // 'ignored' y el webhook contesta que lo recibió. Si se contestara
        // con error, MercadoPago reintentaría el mismo aviso sin parar y
        // acabaría desactivando el webhook por su cuenta.
        try {
            return $this->resolverEvento($topic, $dataId);
        } catch (\MercadoPago\Exceptions\MPNotFoundException $e) {
            report($e);

            return ['type' => 'ignored', 'data' => ['object' => ['id' => $dataId, 'no_encontrado' => true]]];
        }
    }

    /**
     * Traduce un aviso ya autenticado al evento interno, consultando a
     * MercadoPago el estado real del recurso.
     */
    private function resolverEvento(string $topic, string $dataId): array
    {
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

        // Cobro RECURRENTE de una suscripción. MP no lo manda como `payment`:
        // usa su propio tema `subscription_authorized_payment`, que aquí se
        // caía en el 'ignored' de abajo. Resultado: el primer pago entraba y
        // las renovaciones de los meses siguientes no se registraban nunca —
        // la membresía se apagaba sola al mes aunque el cobro hubiera salido.
        if ($topic === 'subscription_authorized_payment' || $topic === 'authorized_payment') {
            $invoice = (new InvoiceClient())->get((int) $dataId);

            // `payment` llega como array u objeto según versión del SDK.
            $pago = $invoice->payment;
            $pagoStatus = is_array($pago) ? ($pago['status'] ?? null) : ($pago->status ?? null);
            $pagoId     = is_array($pago) ? ($pago['id'] ?? null) : ($pago->id ?? null);

            $type = match ($pagoStatus) {
                'approved'                 => 'payment_succeeded',
                'refunded', 'charged_back' => 'refund',
                'rejected', 'cancelled'    => 'payment_failed',
                // Sin pago resuelto: sólo nos interesa si la cuota se dio por
                // perdida; 'scheduled'/'recycling' aún están en curso.
                default => $invoice->status === 'cancelled' ? 'payment_failed' : 'ignored',
            };

            return [
                'type' => $type,
                'data' => [
                    'object' => [
                        'id'           => (string) ($pagoId ?? $invoice->id),
                        'subscription' => (string) ($invoice->preapproval_id ?? ''),
                        'invoice'      => (string) $invoice->id,
                        'amount'       => (int) round(((float) $invoice->transaction_amount) * 100),
                        'currency'     => $invoice->currency_id,
                        'status'       => $pagoStatus ?? $invoice->status,
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
                'Falta el Access Token de MercadoPago. Captúralo en /admin/configuracion-pagos (o en MERCADOPAGO_ACCESS_TOKEN del .env). Lo obtienes en MercadoPago → Tu aplicación → Credenciales de producción.'
            );
        }

        // Reaplica por si el token cambió desde el panel en este mismo request.
        $this->aplicarToken();
    }
}
