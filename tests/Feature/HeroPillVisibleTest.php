<?php
namespace Tests\Feature;
use App\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class HeroPillVisibleTest extends TestCase
{
    use RefreshDatabase;
    public function test_por_defecto_la_etiqueta_del_hero_se_muestra(): void
    {
        $this->get('/')->assertOk()->assertSee('<span class="hero-pill">', false);
    }
    public function test_el_admin_puede_ocultar_la_etiqueta_del_hero(): void
    {
        SiteSetting::set('hero_pill_visible', '0');
        $this->get('/')->assertOk()->assertDontSee('<span class="hero-pill">', false);
    }
}
