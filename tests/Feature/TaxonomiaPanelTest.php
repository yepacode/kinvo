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

        $this->actingAs($this->owner())
            ->get('/admin/locations')
            ->assertStatus(200)
            ->assertSee('Guadalajara');
    }

    public function test_no_admin_no_accede_al_panel(): void
    {
        $profesional = User::factory()->create(); // Professional activo por defecto

        $this->actingAs($profesional)
            ->get('/admin/disciplines')
            ->assertStatus(403);
    }
}
