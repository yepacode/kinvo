<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_csp_publico_permite_alpine(): void
    {
        // Alpine.js evalúa x-show/@click con Function(); la CSP debe permitir 'unsafe-eval'
        // o los dropdowns (notificaciones, menú de usuario) dejan de funcionar.
        $csp = $this->get('/')->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString('script-src', $csp);
        $this->assertStringContainsString("'unsafe-eval'", $csp);
    }

    public function test_cabeceras_de_seguridad_presentes(): void
    {
        $resp = $this->get('/');

        $this->assertSame('SAMEORIGIN', $resp->headers->get('X-Frame-Options'));
        $this->assertSame('nosniff', $resp->headers->get('X-Content-Type-Options'));
    }

    public function test_panel_admin_sin_csp(): void
    {
        // El panel Filament NO lleva CSP (usa sus propios estilos/scripts).
        $csp = $this->get('/admin')->headers->get('Content-Security-Policy');

        $this->assertNull($csp);
    }
}
