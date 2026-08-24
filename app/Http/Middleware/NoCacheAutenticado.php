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

        // Usar headers->set (API de Symfony) y NO ->header() (macro de
        // Illuminate\Http\Response): así funciona también con respuestas de
        // archivo/stream (BinaryFileResponse/StreamedResponse) — p. ej. la ruta
        // privada que sirve videos de contenido — que no tienen ->header().
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }
}
