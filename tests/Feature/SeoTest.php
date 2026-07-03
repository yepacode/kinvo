<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_incluye_perfiles_publicados_y_excluye_ocultos(): void
    {
        $pub = User::factory()->create()->professionalProfile()->create(['is_published' => true]);
        $oculto = User::factory()->create()->professionalProfile()->create(['is_published' => false]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/xml');
        $response->assertSee($pub->slug, false);
        $response->assertDontSee($oculto->slug, false);
    }

    public function test_robots_bloquea_areas_privadas_y_apunta_al_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertStatus(200)
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /dashboard')
            ->assertSee('Sitemap:');
    }

    public function test_home_tiene_canonical_y_datos_estructurados(): void
    {
        $this->get('/')
            ->assertSee('rel="canonical"', false)
            ->assertSee('"@type":"Organization"', false);
    }
}
