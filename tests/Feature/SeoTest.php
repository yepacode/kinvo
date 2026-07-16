<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_no_incluye_perfiles_privados(): void
    {
        // Los perfiles de talento son privados: NO deben aparecer en el sitemap.
        $pub = User::factory()->create()->professionalProfile()->create(['is_published' => true]);

        $response = $this->get('/sitemap.xml');

        $response->assertStatus(200)
            ->assertHeader('Content-Type', 'application/xml')
            ->assertSee('/membresias', false)
            ->assertDontSee($pub->slug, false);
    }

    public function test_robots_bloquea_areas_privadas_y_apunta_al_sitemap(): void
    {
        $this->get('/robots.txt')
            ->assertStatus(200)
            ->assertSee('Disallow: /admin')
            ->assertSee('Disallow: /dashboard')
            ->assertSee('Disallow: /talento')
            ->assertSee('Disallow: /estudio')
            ->assertSee('Sitemap:');
    }

    public function test_home_tiene_canonical_y_datos_estructurados(): void
    {
        $this->get('/')
            ->assertSee('rel="canonical"', false)
            ->assertSee('"@context":"https:\/\/schema.org"', false)
            ->assertSee('"@type":"Organization"', false)
            // El @context NO debe compilarse como directiva Blade (bug de JSON-LD).
            ->assertDontSee('__contextArgs', false);
    }

    public function test_json_ld_del_talento_es_valido(): void
    {
        $profile = User::factory()->create(['name' => 'Coach JSON'])
            ->professionalProfile()->create(['headline' => 'Coach', 'is_published' => true]);

        $this->actingAsSocio();
        $this->get(route('talento.show', $profile->slug))
            ->assertSee('"@context":"https:\/\/schema.org"', false)
            ->assertDontSee('__contextArgs', false);
    }
}
