<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fija el idioma de la app según la cookie `locale`. Si no hay cookie o el valor
 * no está en la lista permitida, cae al `APP_LOCALE` (español por defecto).
 * Se aplica en el kernel HTTP para que todas las rutas web hereden el idioma.
 */
class SetLocale
{
    public const SUPPORTED = ['es', 'en'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->cookie('locale');

        if (in_array($locale, self::SUPPORTED, true)) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
