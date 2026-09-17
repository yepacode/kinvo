<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Feedback Karla 17-sep: tras cerrar sesión en /admin, Filament deja al user
 * en /admin/login. Si ahí intenta entrar como coach o estudio, canAccessPanel
 * rechaza el login y sale "credenciales no coinciden" — confuso.
 *
 * Este middleware detecta SOLO la respuesta al POST /admin/logout y la
 * redirige a /login (frontend), donde coach y estudio sí pueden entrar. El
 * flujo normal (visitar /admin sin sesión → redirigido a /admin/login) queda
 * intacto: sólo cambiamos el destino inmediatamente después del logout.
 */
class RedirectAdminLogoutToFrontend
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // El logout de Filament vive en POST /admin/logout (nombre de ruta
        // `filament.admin.auth.logout`). Sólo redirigimos ese caso.
        $esLogoutDelAdmin = $request->isMethod('POST')
            && rtrim(parse_url($request->getRequestUri(), PHP_URL_PATH) ?? '', '/') === '/admin/logout';

        if ($esLogoutDelAdmin && $response instanceof RedirectResponse) {
            return redirect('/login');
        }

        return $response;
    }
}
