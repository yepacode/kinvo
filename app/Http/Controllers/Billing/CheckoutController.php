<?php

namespace App\Http\Controllers\Billing;

use App\Enums\RolUsuario;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Billing\SubscriptionGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        // Validación de audiencia: los talentos solo ven planes 'individual',
        // los contratantes solo 'estudio'. Blindaje adicional al UI.
        if ($plan->audiencia === 'individual' && ! $user->esProfesional()) {
            return back()->with('status', 'plan-no-es-para-tu-rol');
        }
        if ($plan->audiencia === 'estudio' && ! $user->esContratante()) {
            return back()->with('status', 'plan-no-es-para-tu-rol');
        }

        $url = $this->gateway->createCheckoutUrl(
            $user,
            $plan,
            successUrl: route('billing.exitosa'),
            cancelUrl: route('billing.fallida'),
        );

        AuditLog::record($user, $user, 'checkout_started', new: ['plan_id' => $plan->id]);

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

        return view('billing.fake-checkout', [
            'subscription' => $sub,
            'successUrl' => $request->query('success', route('billing.exitosa')),
            'cancelUrl' => $request->query('cancel', route('billing.fallida')),
        ]);
    }

    /** POST /billing/fake-checkout/{token}/confirmar — simula webhook exitoso. */
    public function fakeConfirm(string $token, Request $request): RedirectResponse
    {
        abort_unless($this->gateway->name() === 'fake', 404);

        $sub = Subscription::where('provider_subscription_id', 'fake_sub_'.$token)->firstOrFail();
        $sub->update([
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        Payment::create([
            'user_id' => $sub->user_id,
            'subscription_id' => $sub->id,
            'provider' => 'fake',
            'provider_payment_id' => 'fake_pay_'.$token,
            'amount_cents' => (int) round(($sub->plan?->precio ?? 199) * 100),
            'currency' => config('billing.currency', 'MXN'),
            'status' => Payment::STATUS_SUCCEEDED,
            'paid_at' => now(),
        ]);

        // Activar la membresía del user (aún la Fase 1 gate se apoya en esto)
        if ($sub->plan_id) {
            $sub->user->forceFill([
                'membership_plan_id' => $sub->plan_id,
                'membership_expires_at' => now()->addMonth(),
            ])->save();
        }

        AuditLog::record($sub->user, $sub, 'subscription_activated',
            new: ['status' => 'active', 'via' => 'fake_checkout']);

        return redirect($request->input('success', route('billing.exitosa')));
    }
}
