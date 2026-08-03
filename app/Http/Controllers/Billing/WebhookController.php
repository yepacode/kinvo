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
            str_contains($type, 'payment_succeeded'), str_contains($type, 'payment.approved') => 'payment_succeeded',
            str_contains($type, 'payment_failed'), str_contains($type, 'payment.rejected')     => 'payment_failed',
            str_contains($type, 'subscription_deleted'), str_contains($type, 'subscription.canceled') => 'subscription_canceled',
            str_contains($type, 'refund')                                                     => 'refund',
            default => $type,
        };
    }

    private function onPaymentSucceeded(array $data): void
    {
        $providerPaymentId = $data['id'] ?? $data['payment_id'] ?? null;
        if (! $providerPaymentId) return;

        // Idempotencia: si ya lo procesamos, no repetimos.
        if (Payment::where('provider_payment_id', $providerPaymentId)->exists()) {
            return;
        }

        $sub = Subscription::where('provider_subscription_id', $data['subscription'] ?? '')->first();
        $user = $sub?->user ?? \App\Models\User::find($data['user_id'] ?? 0);
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

        AuditLog::record(null, $user, 'payment_succeeded', new: ['provider_payment_id' => $providerPaymentId]);

        try {
            Mail::to($user->email)->send(new AvisoCobroExitoso($user, $sub));
        } catch (\Throwable $e) { report($e); }
    }

    private function onPaymentFailed(array $data): void
    {
        $sub = Subscription::where('provider_subscription_id', $data['subscription'] ?? '')->first();
        if (! $sub) return;

        $sub->update(['status' => Subscription::STATUS_PAST_DUE]);

        Payment::create([
            'user_id' => $sub->user_id,
            'subscription_id' => $sub->id,
            'provider' => $this->gateway->name(),
            'provider_payment_id' => $data['id'] ?? null,
            'amount_cents' => (int) ($data['amount'] ?? 0),
            'currency' => strtoupper($data['currency'] ?? config('billing.currency', 'MXN')),
            'status' => Payment::STATUS_FAILED,
            'failure_code' => $data['failure_code'] ?? null,
            'failure_message' => $data['failure_message'] ?? null,
        ]);

        AuditLog::record(null, $sub, 'payment_failed', new: ['status' => 'past_due']);

        try {
            Mail::to($sub->user->email)->send(new AvisoCobroFallido($sub->user, $sub));
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
