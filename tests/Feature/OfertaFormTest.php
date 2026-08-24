<?php

namespace Tests\Feature;

use App\Models\Discipline;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfertaFormTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Regresión (bug en prod, ago-2026): el formulario de nueva oferta poblaba
     * el select de Ciudad con `Location::orderBy('nombre')` + `$l->nombre`, pero
     * la tabla `locations` NO tiene columna `nombre` (usa `ciudad`/`region`, y
     * su etiqueta sale por `etiqueta()`). En MySQL eso reventaba con
     * "Unknown column 'nombre'" → 500 al abrir/publicar la oferta; en SQLite se
     * toleraba pero dejaba las opciones de Ciudad EN BLANCO. Este test fija que
     * el form renderice y que las ciudades salgan con su etiqueta real.
     */
    public function test_form_de_nueva_oferta_lista_ciudades_con_etiqueta_y_disciplinas(): void
    {
        $estudio = User::factory()->contratante()->create();
        Location::create(['ciudad' => 'Guadalajara', 'region' => 'Jalisco']);
        Discipline::create(['nombre' => 'Yoga', 'slug' => 'yoga', 'activo' => true]);

        $this->actingAs($estudio)
            ->get(route('ofertas.crear'))
            ->assertOk()
            ->assertSee('Guadalajara, Jalisco') // etiqueta() de Location, no $l->nombre
            ->assertSee('Yoga');                 // nombre de Discipline
    }
}
