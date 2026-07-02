<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Aprobación activada: un usuario no-admin cuyo estado no es "Activo"
 * (pendiente o suspendido) no accede a las áreas protegidas; se le envía
 * al aviso de cuenta pendiente.
 */
class EnsureCuentaActiva
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->esAdmin() && ! $user->estaActivo()) {
            return redirect()->route('account.pending');
        }

        return $next($request);
    }
}
