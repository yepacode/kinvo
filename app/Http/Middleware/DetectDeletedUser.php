<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Detecta el caso "el usuario fue eliminado por otro (admin) mientras esta sesión
 * seguía viva". La sesión tiene `id()` pero al resolver el modelo con `user()`
 * devuelve null → limpiamos la sesión y llevamos a /login con un mensaje claro,
 * en lugar de dejarlo con un logout silencioso o un 500 en el siguiente request.
 */
class DetectDeletedUser
{
    public function handle(Request $request, Closure $next): Response
    {
        // Chequeamos por ID en sesión y luego consultamos DB directamente para
        // no depender del $guard->user() (que en tests con actingAs mantiene el
        // modelo cacheado en memoria aunque ya se haya borrado de la BD).
        $userId = Auth::guard('web')->id();

        if ($userId && ! \App\Models\User::whereKey($userId)->exists()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->with('status', 'admin-elimino-cuenta');
        }

        return $next($request);
    }
}
