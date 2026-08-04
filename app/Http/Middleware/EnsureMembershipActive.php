<?php

namespace App\Http\Middleware;

use App\Models\Subscription;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fase 2 · Bloquea el acceso a rutas que requieren suscripción vigente.
 * Los admins pasan siempre. Los users con suscripción active/past_due/canceled
 * en su periodo pagado también.
 *
 * Aplicable a rutas premium: buscador de talento, contenido con gate por plan,
 * etc. Es distinto al middleware `membresia` de Fase 1 (que solo revisa el
 * campo denormalizado `membership_expires_at`) porque toma en cuenta el
 * estado real de la suscripción.
 */
class EnsureMembershipActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }
        if ($user->esAdmin()) {
            return $next($request);
        }

        // ¿Tiene alguna suscripción vigente?
        // - active/trialing: SÍ (independiente del period_end, que puede estar
        //   sin llenarse durante el trial).
        // - past_due/canceled: solo si `current_period_end` NO es null y aún
        //   no venció. Sin este guard, una sub past_due sin period_end da
        //   acceso permanente (Seguridad MED-2).
        $vigente = Subscription::where('user_id', $user->id)
            ->where(function ($q) {
                $q->where(function ($q1) {
                    $q1->whereIn('status', [
                        Subscription::STATUS_ACTIVE,
                        Subscription::STATUS_TRIALING,
                    ])
                    ->where(function ($qq) {
                        $qq->whereNull('current_period_end')
                           ->orWhere('current_period_end', '>=', now());
                    });
                })
                ->orWhere(function ($q2) {
                    $q2->whereIn('status', [
                        Subscription::STATUS_PAST_DUE,
                        Subscription::STATUS_CANCELED,
                    ])
                    ->whereNotNull('current_period_end')
                    ->where('current_period_end', '>=', now());
                });
            })
            ->exists();

        // Fallback a la lógica Fase 1 (membership_expires_at) para no romper
        // usuarios ya activos vía panel manual.
        if (! $vigente && ! $user->tieneMembresiaActiva()) {
            return redirect()->route('membresias.index')
                ->with('status', 'membresia-requerida');
        }

        return $next($request);
    }
}
