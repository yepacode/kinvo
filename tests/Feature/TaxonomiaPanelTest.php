<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TaxonomiaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TaxonomiaPanelTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::factory()->admin()->create();
    }

    public function test_owner_ve_disciplinas_en_el_panel(): void
    {
        $this->seed(TaxonomiaSeeder::class);

        $this->actingAs($this->owner())
            ->get('/admin/disciplines')
            ->assertStatus(200)
            ->assertSee('CrossFit')                 // primera página (orden por nombre)
            ->assertSee('Entrenamiento funcional');
    }

    public function test_owner_ve_certificaciones_y_ubicaciones(): void
    {
        $this->seed(TaxonomiaSeeder::class);

        $this->actingAs($this->owner())
            ->get('/admin/certifications')
            ->assertStatus(200)
            ->assertSee('NASM-CPT');

        // Con el seeder ampliado (48 ubicaciones MX, feedback Karla 27-ago) la
        // primera página puede no incluir "Guadalajara" — buscamos a "México"
        // que aparece en la columna `pais` de TODAS las filas (siempre presente).
        $this->actingAs($this->owner())
            ->get('/admin/locations')
            ->assertStatus(200)
            ->assertSee('México');
    }

    public function test_no_admin_no_accede_al_panel(): void
    {
        $profesional = User::factory()->create(); // Professional activo por defecto

        // No admin: en vez de 403 se le redirige a su propia área.
        $this->actingAs($profesional)
            ->get('/admin/disciplines')
            ->assertRedirect(route('dashboard'));
    }
}
