<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Defensa en profundidad: 403 si el usuario no es admin activo.
 * Auditoría ago-2026: sin este middleware, la ruta admin.certificacion sólo
 * checaba admin dentro del controller — un cambio accidental exponía archivos
 * privados (INE/credenciales) a cualquier usuario logueado.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user && $user->esAdmin() && $user->estaActivo(), 403);

        return $next($request);
    }
}
