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

        // ¿Tiene alguna suscripción vigente? La regla ya no se escribe aquí:
        // vive en `Subscription::scopeVigentes` y la comparten el modelo, el
        // observer que mantiene la membresía y este middleware. Tenerla
        // duplicada era el origen de que el panel y la web contestaran
        // distinto sobre el mismo usuario.
        $vigente = Subscription::where('user_id', $user->id)->vigentes()->exists();

        // Fallback a la lógica Fase 1 (membership_expires_at) para no romper
        // usuarios ya activos vía panel manual.
        if (! $vigente && ! $user->tieneMembresiaActiva()) {
            return redirect()->route('membresias.index')
                ->with('status', 'membresia-requerida');
        }

        return $next($request);
    }
}
