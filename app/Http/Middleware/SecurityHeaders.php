<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cabeceras de seguridad básicas: anti-clickjacking, anti-sniffing y referrer.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-Permitted-Cross-Domain-Policies', 'none');

        // HSTS solo sobre HTTPS (producción); nunca en local para no bloquear http.
        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // CSP: en el panel de Filament NO se aplica (usa nonces/estilos propios y una
        // política estricta podría romperlo). En el sitio público reduce el impacto de
        // cualquier XSS. Permite las fuentes que usamos (Google Fonts, Fontshare, y en
        // dev el fallback por CDN de Tailwind/Alpine).
        if (! $request->is('admin', 'admin/*')) {
            $csp = implode('; ', [
                "default-src 'self'",
                "base-uri 'self'",
                "frame-ancestors 'self'",
                "img-src 'self' data:",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://api.fontshare.com",
                "font-src 'self' data: https://fonts.gstatic.com https://api.fontshare.com https://cdn.fontshare.com",
                // 'unsafe-eval' es necesario para Alpine.js (evalúa x-show/@click con Function()).
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net",
                "connect-src 'self'",
                "form-action 'self'",
            ]);
            $response->headers->set('Content-Security-Policy', $csp);
        }

        return $response;
    }
}
