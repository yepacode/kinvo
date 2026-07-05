<?php

namespace Tests\Feature;

use App\Filament\Pages\ConfiguracionSitio;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class ConfiguracionSitioTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush(); // evita caché de settings entre tests
    }

    public function test_get_devuelve_default_si_no_hay_override(): void
    {
        $this->assertSame('Bienvenido a Kinvoo', SiteSetting::get('hero_eyebrow'));
    }

    public function test_set_sobrescribe_el_default(): void
    {
        SiteSetting::set('hero_eyebrow', 'Hola mundo');

        $this->assertSame('Hola mundo', SiteSetting::get('hero_eyebrow'));
    }

    public function test_landing_rich_aplica_enfasis_y_escapa(): void
    {
        SiteSetting::set('hero_title', "*Fuerte* <script>");

        $html = (string) landing_rich('hero_title');

        $this->assertStringContainsString('<em>Fuerte</em>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_el_home_refleja_los_cambios(): void
    {
        SiteSetting::set('hero_eyebrow', 'ANTETITULO_DE_PRUEBA');

        $this->get('/')->assertSee('ANTETITULO_DE_PRUEBA');
    }

    public function test_owner_accede_a_la_configuracion(): void
    {
        $owner = User::factory()->admin()->create();

        $this->actingAs($owner)
            ->get('/admin/configuracion-sitio')
            ->assertStatus(200);
    }

    public function test_no_admin_no_accede_a_la_configuracion(): void
    {
        $prof = User::factory()->create();

        $this->actingAs($prof)
            ->get('/admin/configuracion-sitio')
            ->assertRedirect(route('dashboard'));
    }

    public function test_guardar_desde_el_panel_persiste(): void
    {
        $owner = User::factory()->admin()->create();
        $this->actingAs($owner);

        Livewire::test(ConfiguracionSitio::class)
            ->fillForm(['hero_body' => 'Un texto totalmente nuevo.'])
            ->call('save');

        $this->assertSame('Un texto totalmente nuevo.', SiteSetting::get('hero_body'));
    }
}
