<?php

namespace App\Http\Controllers;

use App\Models\ProfessionalProfile;
use Illuminate\Http\Response;

class SeoController extends Controller
{
    /** Mapa del sitio con las URLs públicas indexables. */
    public function sitemap(): Response
    {
        $perfiles = ProfessionalProfile::visiblePublicamente()
            ->select('slug', 'updated_at')
            ->orderByDesc('updated_at')
            ->get();

        return response()
            ->view('seo.sitemap', ['perfiles' => $perfiles])
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
