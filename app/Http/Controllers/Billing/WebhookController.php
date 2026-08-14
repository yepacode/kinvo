<?php

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Mail\AvisoCobroExitoso;
use App\Mail\AvisoCobroFallido;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Subscription;
use App\Services\Billing\SubscriptionGateway;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;

/**
 * Fase 2 · Endpoint que recibe eventos de la pasarela (Stripe/MP).
 *
 * Eventos que maneja:
 *   - payment_succeeded       → activa membresía, envía correo
 *   - payment_failed          → marca subscription en 'past_due', avisa
 *   - subscription_canceled   → status='canceled', mantiene acceso hasta period_end
 *   - refund                  → registra el reembolso, revoca acceso
 *
 * Verifica la firma HMAC via el gateway.verifyWebhook(). Si la firma es
 * inválida devuelve 400. Idempotente: si el evento ya fue procesado
 * (identificado por provider_payment_id), no lo repite.
 */
class WebhookController extends Controller
{
    public function __construct(private SubscriptionGateway $gateway) {}

    public function handle(Request $request): Response
    {
        try {
            $event = $this->gateway->verifyWebhook(
                $request->getContent(),
                $request->header('Stripe-Signature') ?? $request->header('X-Signature') ?? ''
            );
        } catch (\Throwable $e) {
            report($e);
            return response('Invalid signature', 400);
        }

        // Registro del webhook para trazabilidad. type puede venir como
        // 'invoice.payment_succeeded' (Stripe) o 'payment.approved' (MP);
        // se normaliza a un conjunto interno.
        $type = $this->normalizarTipo($event['type'] ?? '');
        $data = $event['data']['object'] ?? $event['data'] ?? [];

        match ($type) {
            'checkout_completed'     => $this->onCheckoutCompleted($data),
            'payment_succeeded'      => $this->onPaymentSucceeded($data),
            'payment_failed'         => $this->onPaymentFailed($data),
            'subscription_canceled'  => $this->onSubscriptionCanceled($data),
            'refund'                 => $this->onRefund($data),
            default                  => null, // ignorar eventos no relevantes
        };

        return response('ok', 200);
    }

    private function normalizarTipo(string $type): string
    {
        return match (true) {
            // Stripe: checkout.session.completed llega inmediatamente después
            // de que el user paga; contiene subscription_id, customer_id y
            // client_reference_id — lo usamos para vincular la Subscription
            // local (creada en INCOMPLETE por StripeGateway) con los IDs reales.
            str_contains($type, 'checkout.session.completed')                                 => 'checkout_completed',
            str_contains($type, 'payment_succeeded'), str_contains($type, 'payment.approved') => 'payment_succeeded',
            str_contains($type, 'payment_failed'), str_contains($type, 'payment.rejected')     => 'payment_failed',
            // Stripe usa `customer.subscription.deleted` cuando la sub se cierra
            // (después del período pagado si fue cancel_at_period_end).
            str_contains($type, 'subscription_deleted'), str_contains($type, 'subscription.canceled'), str_contains($type, 'customer.subscription.deleted') => 'subscription_canceled',
            str_contains($type, 'refund')                                                     => 'refund',
            default => $type,
        };
    }

    /**
     * Stripe `checkout.session.completed`: primer evento que llega tras el
     * pago exitoso. Contiene `subscription`, `customer`, `client_reference_id`
     * (= user_id) y `metadata.local_subscription_id`. Vinculamos la
     * Subscription local INCOMPLETE con los IDs reales. Idempotente:
     * si ya está vinculada, no-op.
     */
    private function onCheckoutCompleted(array $data): void
    {
        $localSubId = $data['metadata']['local_subscription_id'] ?? null;
        $userId     = $data['client_reference_id'] ?? $data['metadata']['user_id'] ?? null;
        $stripeSubId = $data['subscription'] ?? null;
        $customerId  = $data['customer'] ?? null;

        if (! $stripeSubId) return;

        // Preferimos match por local_subscription_id (que pusimos en metadata),
        // caemos a user_id + status=incomplete si no viene por alguna razón.
        $sub = null;
        if ($localSubId) {
            $sub = Subscription::find($localSubId);
        }
        if (! $sub && $userId) {
            $sub = Subscription::where('user_id', $userId)
                ->where('status', Subscription::STATUS_INCOMPLETE)
                ->where('provider', 'stripe')
                ->latest()->first();
        }
        if (! $sub) return;

        // Idempotencia: si ya está vinculada, no repetir.
        if ($sub->provider_subscription_id === $stripeSubId) return;

        $sub->update([
            'provider_subscription_id' => $stripeSubId,
            'provider_customer_id'     => $customerId,
            'status'                   => Subscription::STATUS_ACTIVE,
        ]);

        AuditLog::record(null, $sub, 'subscription_linked_stripe',
            new: ['provider_subscription_id' => $stripeSubId, 'provider_customer_id' => $customerId]);
    }

    private function onPaymentSucceeded(array $data): void
    {
        $providerPaymentId = $data['id'] ?? $data['payment_id'] ?? null;
        if (! $providerPaymentId) return;

        // Idempotencia: si ya lo procesamos, no repetimos.
        if (Payment::where('provider_payment_id', $providerPaymentId)->exists()) {
            return;
        }

        // Seguridad HIGH-3 bis: NUNCA confiar en `user_id` del payload —
        // se debe resolver estrictamente vía la subscription conocida.
        // Sin sub asociada, ignoramos el evento (evita crear Payments
        // huérfanos o vincular pagos a users arbitrarios).
        $sub = Subscription::where('provider_subscription_id', $data['subscription'] ?? '')->first();
        if (! $sub) return;
        $user = $sub->user;
        if (! $user) return;

        Payment::create([
            'user_id' => $user->id,
            'subscription_id' => $sub?->id,
            'provider' => $this->gateway->name(),
            'provider_payment_id' => $providerPaymentId,
            'provider_invoice_id' => $data['invoice'] ?? null,
            'amount_cents' => (int) ($data['amount'] ?? $data['amount_cents'] ?? 0),
            'currency' => strtoupper($data['currency'] ?? config('billing.currency', 'MXN')),
            'status' => Payment::STATUS_SUCCEEDED,
            'paid_at' => now(),
        ]);

        if ($sub) {
            $sub->update([
                'status' => Subscription::STATUS_ACTIVE,
                'current_period_start' => now(),
                'current_period_end' => now()->addMonth(),
            ]);
            if ($sub->plan_id) {
                $user->forceFill([
                    'membership_plan_id' => $sub->plan_id,
                    'membership_expires_at' => now()->addMonth(),
                ])->save();
            }
        }

        // H5 · registro de conversión (petición cliente): guardar la fecha
        // del PRIMER pago exitoso para el reporte "registro vs conversión".
        // No sobrescribir si ya está marcado (idempotente frente a reintentos).
        if (! $user->converted_to_paid_at) {
            $user->forceFill(['converted_to_paid_at' => now()])->save();
        }

        AuditLog::record(null, $user, 'payment_succeeded', new: ['provider_payment_id' => $providerPaymentId]);

        // Notif in-app (campana) al user.
        $payment = Payment::where('provider_payment_id', $providerPaymentId)->first();
        if ($payment) {
            try {
                $user->notify(new \App\Notifications\CobroExitosoNotification($payment));
            } catch (\Throwable $e) { report($e); }
        }

        try {
            // Pasamos el modelo (no el string) para que Laravel llame preferredLocale()
            // y el correo salga en el idioma del user aun cuando App::getLocale()='es'.
            Mail::to($user)->send(new AvisoCobroExitoso($user, $sub));
        } catch (\Throwable $e) { report($e); }
    }

    private function onPaymentFailed(array $data): void
    {
        $sub = Subscription::where('provider_subscription_id', $data['subscription'] ?? '')->first();
        if (! $sub) return;

        // Idempotencia: si ya registramos este intento fallido, no repetimos
        // (evita spam de correos y filas duplicadas al reintentar el webhook).
        $providerPaymentId = $data['id'] ?? null;
        if ($providerPaymentId
            && Payment::where('provider_payment_id', $providerPaymentId)->exists()) {
            return;
        }

        $sub->update(['status' => Subscription::STATUS_PAST_DUE]);

        Payment::create([
            'user_id' => $sub->user_id,
            'subscription_id' => $sub->id,
            'provider' => $this->gateway->name(),
            'provider_payment_id' => $providerPaymentId,
            'amount_cents' => (int) ($data['amount'] ?? 0),
            'currency' => strtoupper($data['currency'] ?? config('billing.currency', 'MXN')),
            'status' => Payment::STATUS_FAILED,
            'failure_code' => $data['failure_code'] ?? null,
            'failure_message' => $data['failure_message'] ?? null,
        ]);

        AuditLog::record(null, $sub, 'payment_failed', new: ['status' => 'past_due']);

        try {
            Mail::to($sub->user)->send(new AvisoCobroFallido($sub->user, $sub));
        } catch (\Throwable $e) { report($e); }
    }

    private function onSubscriptionCanceled(array $data): void
    {
        $sub = Subscription::where('provider_subscription_id', $data['id'] ?? $data['subscription'] ?? '')->first();
        if (! $sub) return;

        $sub->update([
            'status' => Subscription::STATUS_CANCELED,
            'canceled_at' => now(),
            'ends_at' => $sub->current_period_end,
        ]);

        AuditLog::record(null, $sub, 'subscription_canceled', new: ['status' => 'canceled']);
    }

    private function onRefund(array $data): void
    {
        $payment = Payment::where('provider_payment_id', $data['payment_id'] ?? $data['id'] ?? '')->first();
        if (! $payment) return;

        // Idempotencia: si ya lo marcamos reembolsado, no re-machacamos el
        // refunded_at ni duplicamos el AuditLog.
        if ($payment->status === Payment::STATUS_REFUNDED) {
            return;
        }

        $payment->update([
            'status' => Payment::STATUS_REFUNDED,
            'refunded_at' => now(),
        ]);

        // Revocar membresía asociada
        if ($payment->subscription && $payment->subscription->user) {
            $payment->subscription->user->forceFill([
                'membership_expires_at' => now(),
            ])->save();
        }

        AuditLog::record(null, $payment, 'refunded');
    }
}
