<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SeoController extends Controller
{
    /**
     * Mapa del sitio: solo las URLs públicas indexables. Los perfiles de talento
     * y los estudios son PRIVADOS (requieren login/membresía) → no van al sitemap.
     */
    public function sitemap(): Response
    {
        return response()
            ->view('seo.sitemap')
            ->header('Content-Type', 'application/xml');
    }

    /** robots.txt: bloquea áreas privadas y apunta al sitemap. */
    public function robots(): Response
    {
        $lineas = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /dashboard',
            'Disallow: /talento',
            'Disallow: /estudio',
            'Disallow: /mi-perfil',
            'Disallow: /mi-empresa',
            'Disallow: /cuenta',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /profile',
            '',
            'Sitemap: '.url('/sitemap.xml'),
        ];

        return response(implode("\n", $lineas))
            ->header('Content-Type', 'text/plain');
    }
}
