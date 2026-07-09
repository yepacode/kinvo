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
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->esContratante() && $user->estaActivo() && ! $user->tieneMembresiaActiva()) {
            return redirect()->route('membresias.index')->with('status', 'membresia-requerida');
        }

        return $next($request);
    }
}
