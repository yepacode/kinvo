<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bloquea el acceso al directorio de talento a los contratantes (estudios) que
 * no tienen una membresía vigente. No afecta a visitantes anónimos ni a
 * profesionales; el owner activa la membresía manualmente desde el panel.
 */
class EnsureContractorMembership
{
    public function handle(Request $request, Closure $next, ?string $flashKey = null): Response
    {
        $user = $request->user();

        if ($user && $user->esContratante() && $user->estaActivo() && ! $user->tieneMembresiaActiva()) {
            // H6 · el llamador puede pasar un flash específico
            // (ej. 'plan-necesario-contacto') para mostrar el mensaje de upsell
            // más ajustado a la acción que se intentó.
            return redirect()->route('membresias.index')
                ->with('status', $flashKey ?: 'membresia-requerida');
        }

        return $next($request);
    }
}
