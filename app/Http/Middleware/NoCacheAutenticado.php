<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Evita que el navegador cachee páginas autenticadas. Así, tras cerrar sesión,
 * el botón "atrás" no muestra el perfil desde caché: el navegador revalida,
 * el servidor ve que no hay sesión y redirige al login.
 */
class NoCacheAutenticado
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        return $response
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache')
            ->header('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');
    }
}
