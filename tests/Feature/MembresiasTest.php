<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembresiasTest extends TestCase
{
    use RefreshDatabase;

    public function test_muestra_planes_activos_y_oculta_inactivos(): void
    {
        Plan::create(['nombre' => 'Esencial Talento', 'audiencia' => 'individual', 'activo' => true, 'orden' => 1]);
        Plan::create(['nombre' => 'Pro Estudio', 'audiencia' => 'estudio', 'activo' => true, 'destacado' => true, 'orden' => 2]);
        Plan::create(['nombre' => 'Plan Oculto', 'audiencia' => 'individual', 'activo' => false, 'orden' => 3]);

        $this->get(route('membresias.index'))
            ->assertOk()
            ->assertSee('Esencial Talento')
            ->assertSee('Pro Estudio')
            ->assertSee('Recomendado')            // el destacado
            ->assertDontSee('Plan Oculto');       // inactivo no aparece
    }

    public function test_precio_o_a_consultar(): void
    {
        Plan::create(['nombre' => 'Con Precio', 'audiencia' => 'individual', 'activo' => true, 'precio' => 999]);
        Plan::create(['nombre' => 'Sin Precio', 'audiencia' => 'estudio', 'activo' => true]);

        $this->get(route('membresias.index'))
            ->assertSee('999')
            ->assertSee('A consultar');
    }

    public function test_beneficios_se_listan(): void
    {
        Plan::create([
            'nombre' => 'Pro',
            'audiencia' => 'individual',
            'activo' => true,
            'beneficios' => ['Acceso al directorio', 'Soporte prioritario'],
        ]);

        $this->get(route('membresias.index'))
            ->assertSee('Acceso al directorio')
            ->assertSee('Soporte prioritario');
    }

    public function test_membresias_en_sitemap(): void
    {
        $this->get('/sitemap.xml')
            ->assertOk()
            ->assertSee('/membresias', false);
    }
}
