<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Feedback Karla 17-sep: cuando cierra sesión con un rol y quiere entrar
 * con otro (admin → coach → estudio → …) sale error de credenciales, y sólo
 * se resuelve abriendo otro perfil de Chrome. Causa: cookies y autofill del
 * navegador conservan estado del user anterior, y el CSRF token que quedó
 * en la memoria del form de login queda invalidado.
 *
 * Este middleware añade la cabecera `Clear-Site-Data` a la respuesta de
 * cualquier logout (frontend o admin). Chrome/Firefox/Edge borran cookies,
 * cache y storage del sitio al recibirla, dejando la siguiente pantalla en
 * un estado limpio como si abriera un perfil nuevo del navegador. La sesión
 * ya está invalidada del lado servidor por el logout de Laravel — este
 * cambio complementa esa limpieza en el cliente.
 *
 * Nota: `executionContexts` NO se incluye porque cerraría la pestaña con la
 * respuesta a medio recibir en algunos navegadores.
 */
class ClearBrowserStateOnLogout
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $path = rtrim(parse_url($request->getRequestUri(), PHP_URL_PATH) ?? '', '/');
        $esLogout = $request->isMethod('POST')
            && ($path === '/logout' || $path === '/admin/logout');

        // Solo enviar la cabecera si el logout fue exitoso (< 400). Un 419
        // (CSRF inválido) o un 500 no debe borrar el navegador del user — la
        // sesión servidor no se invalidó y el user seguiría "adentro" pero
        // sin cookies, quedando en un limbo confuso.
        if ($esLogout && $response->getStatusCode() < 400) {
            // Comillas dobles alrededor de cada directiva, separadas por coma.
            // Ver https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Clear-Site-Data
            $response->headers->set('Clear-Site-Data', '"cookies", "storage", "cache"');
        }

        return $response;
    }
}
