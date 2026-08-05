<?php

namespace App\Http\Controllers\Billing;

use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Mail\AvisoCobroExitoso;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Notifications\CobroExitosoNotification;
use App\Services\Billing\SubscriptionGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Fase 2 · Checkout de suscripciones.
 *
 * Flujo:
 *  1. Usuario elige un plan en /membresias y hace clic en "Suscribirme".
 *  2. Este controller crea la sesión de checkout en la pasarela y redirige.
 *  3. Al volver, /suscripcion/exitosa muestra el resumen; el webhook
 *     independientemente activa la suscripción cuando la pasarela confirma.
 *
 * En modo FakeGateway se puede confirmar el pago simulado visitando la URL
 * de retorno /billing/fake-checkout/{token}/confirmar.
 */
class CheckoutController extends Controller
{
    public function __construct(private SubscriptionGateway $gateway) {}

    /** POST /suscripcion/{plan} — arranca el checkout. */
    public function start(Request $request, Plan $plan): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        // Blindaje explícito: los admins no se suscriben, gestionan planes.
        if ($user->esAdmin()) {
            return back()->with('status', 'admin-no-suscribe');
        }

        // Validación de audiencia: los talentos solo ven planes 'individual',
        // los contratantes solo 'estudio'. Blindaje adicional al UI.
        if ($plan->audiencia === 'individual' && ! $user->esProfesional()) {
            return back()->with('status', 'plan-no-es-para-tu-rol');
        }
        if ($plan->audiencia === 'estudio' && ! $user->esContratante()) {
            return back()->with('status', 'plan-no-es-para-tu-rol');
        }

        // Blindaje: no permitir suscripción a plan sin precio real (mientras el
        // cliente no cargue precios, `precio` es null → cobraría el fallback $199).
        if (blank($plan->precio) || (float) $plan->precio <= 0) {
            return back()->with('status', 'plan-sin-precio');
        }

        // Blindaje: si el user ya tiene una suscripción activa vigente, no
        // creamos una nueva (evita duplicados por doble click / F5).
        $subVigente = Subscription::where('user_id', $user->id)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING])
            ->where(function ($q) {
                $q->whereNull('current_period_end')
                  ->orWhere('current_period_end', '>=', now());
            })
            ->exists();
        if ($subVigente) {
            return back()->with('status', 'ya-tienes-suscripcion');
        }

        $url = $this->gateway->createCheckoutUrl(
            $user,
            $plan,
            successUrl: route('billing.exitosa'),
            cancelUrl: route('billing.fallida'),
        );

        AuditLog::record($user, $user, 'checkout_started', new: [
            'plan_id'    => $plan->id,
            'plan_nombre'=> $plan->nombre,
            'plan_precio'=> $plan->precio,
            'gateway'    => $this->gateway->name(),
        ]);

        return redirect()->away($url);
    }

    /** GET /suscripcion/exitosa — pantalla de "gracias, tu suscripción está activándose". */
    public function exitosa(): View
    {
        return view('billing.exitosa');
    }

    /** GET /suscripcion/fallida — pantalla al cancelar o fallar el checkout. */
    public function fallida(): View
    {
        return view('billing.fallida');
    }

    /** GET /billing/fake-checkout/{token} — SOLO para FakeGateway. */
    public function fakeCheckout(string $token, Request $request): View
    {
        abort_unless($this->gateway->name() === 'fake', 404);

        $sub = Subscription::where('provider_subscription_id', 'fake_sub_'.$token)->firstOrFail();

        // Seguridad: solo el dueño de la sub puede ver su checkout.
        $user = $request->user();
        abort_unless($user && $user->id === $sub->user_id, 403);

        return view('billing.fake-checkout', [
            'subscription' => $sub,
            'successUrl' => $this->sanitizarRetorno($request->query('success'), route('billing.exitosa')),
            'cancelUrl'  => $this->sanitizarRetorno($request->query('cancel'),  route('billing.fallida')),
        ]);
    }

    /** POST /billing/fake-checkout/{token}/confirmar — simula webhook exitoso. */
    public function fakeConfirm(string $token, Request $request): RedirectResponse
    {
        abort_unless($this->gateway->name() === 'fake', 404);

        $sub = Subscription::where('provider_subscription_id', 'fake_sub_'.$token)->firstOrFail();

        // Seguridad HIGH-1: solo el dueño de la sub puede confirmar su propio checkout.
        // Sin este guard, cualquier user autenticado que conozca un token puede
        // activar la sub de otro y hasta grabarle un Payment ajeno.
        $user = $request->user();
        abort_unless($user && $user->id === $sub->user_id, 403);

        // Todo en una transacción: si ya existe un Payment para este token
        // (reintento), no extendemos la sub ni la membresía otra vez.
        $pago = DB::transaction(function () use ($sub, $token, $user) {
            $pagoPrevio = Payment::where('provider_payment_id', 'fake_pay_'.$token)->first();
            if ($pagoPrevio) {
                return null; // idempotente — no re-notificar tampoco
            }

            $sub->update([
                'status' => Subscription::STATUS_ACTIVE,
                'current_period_start' => now(),
                'current_period_end'   => now()->addMonth(),
            ]);

            $nuevoPago = Payment::create([
                'user_id'             => $sub->user_id,
                'subscription_id'     => $sub->id,
                'provider'            => 'fake',
                'provider_payment_id' => 'fake_pay_'.$token,
                'amount_cents'        => (int) round(($sub->plan?->precio ?? 199) * 100),
                'currency'            => config('billing.currency', 'MXN'),
                'status'              => Payment::STATUS_SUCCEEDED,
                'paid_at'             => now(),
            ]);

            if ($sub->plan_id) {
                $sub->user->forceFill([
                    'membership_plan_id'    => $sub->plan_id,
                    'membership_expires_at' => now()->addMonth(),
                ])->save();
            }

            // Seguridad MED-1: actor = ejecutante real (no dueño de la sub).
            AuditLog::record($user, $sub, 'subscription_activated',
                new: ['status' => 'active', 'via' => 'fake_checkout']);

            return $nuevoPago;
        });

        // Fuera de la transacción, notif in-app + correo. Solo si REALMENTE se
        // creó el pago (no en reintento idempotente). Igual comportamiento que
        // el webhook real, para que el modo Fake reproduzca la experiencia.
        if ($pago) {
            try { $sub->user->notify(new CobroExitosoNotification($pago)); } catch (\Throwable $e) { report($e); }
            try { Mail::to($sub->user)->send(new AvisoCobroExitoso($sub->user, $sub)); } catch (\Throwable $e) { report($e); }
        }

        return redirect()->to($this->sanitizarRetorno($request->input('success'), route('billing.exitosa')));
    }

    /** POST /suscripcion/cancelar — el propio user cancela su suscripción. */
    public function cancelarPropia(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user, 403);

        $sub = Subscription::where('user_id', $user->id)
            ->whereIn('status', [Subscription::STATUS_ACTIVE, Subscription::STATUS_TRIALING, Subscription::STATUS_PAST_DUE])
            ->latest()
            ->first();

        if (! $sub) {
            return back()->with('status', 'sin-suscripcion-activa');
        }

        // En modo fake, cortamos localmente. En prod, la pasarela hace la
        // cancelación y devuelve un webhook subscription_canceled.
        try {
            $this->gateway->cancelSubscription($sub->provider_subscription_id ?? '');
        } catch (\Throwable $e) {
            report($e);
        }

        // Marcamos ends_at con el periodo pagado — el user mantiene acceso
        // hasta que termine el periodo por el que ya pagó.
        $sub->update([
            'status'      => Subscription::STATUS_CANCELED,
            'canceled_at' => now(),
            'ends_at'     => $sub->current_period_end ?? now()->addMonth(),
        ]);

        AuditLog::record($user, $sub, 'subscription_canceled_by_user',
            new: ['status' => 'canceled', 'via' => 'self_service']);

        return back()->with('status', 'suscripcion-cancelada');
    }

    /**
     * Seguridad HIGH-2: acepta SOLO URLs internas (same-host).
     * Cualquier redirect externo o esquema raro cae al default.
     */
    private function sanitizarRetorno(?string $candidato, string $fallback): string
    {
        if (! $candidato) {
            return $fallback;
        }
        $parsed = parse_url($candidato);
        $host = $parsed['host'] ?? null;
        $ourHost = parse_url(url('/'), PHP_URL_HOST);
        // relativa (sin host) OK; absoluta con nuestro host OK; el resto no.
        if ($host === null || $host === $ourHost) {
            return $candidato;
        }
        return $fallback;
    }
}
