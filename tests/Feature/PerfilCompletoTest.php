<?php

namespace Tests\Feature;

use App\Models\Discipline;
use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerfilCompletoTest extends TestCase
{
    use RefreshDatabase;

    public function test_perfil_vacio_da_cero(): void
    {
        $profile = User::factory()->create()->professionalProfile()->create(['is_published' => false]);

        $this->assertSame(0, $profile->porcentajeCompleto());
        $this->assertContains('Foto', $profile->faltantesPerfil());
    }

    public function test_perfil_lleno_da_cien(): void
    {
        $loc = Location::create(['ciudad' => 'Guadalajara', 'region' => 'Jalisco']);
        $disc = Discipline::create(['nombre' => 'Yoga', 'slug' => 'yoga']);

        $profile = User::factory()->create()->professionalProfile()->create([
            'is_published' => true,
            'photo_path' => 'perfiles/foto.jpg',
            'headline' => 'Coach',
            'birthdate' => '1990-01-01',
            'bio' => 'Mi presentación completa.',
            'years_experience' => 5,
            'modalidad' => 'presencial',
            'availability' => ['lun_am', 'mar_pm'],
            'languages' => ['es', 'en'],
            'certifications_text' => 'NASM, RYT-200',
            'location_id' => $loc->id,
        ]);
        $profile->disciplines()->sync([$disc->id]);

        $this->assertSame(100, $profile->fresh()->porcentajeCompleto());
        $this->assertEmpty($profile->fresh()->faltantesPerfil());
    }

    public function test_dashboard_muestra_la_barra(): void
    {
        $owner = User::factory()->create();
        $owner->professionalProfile()->create(['is_published' => true, 'headline' => 'Coach']);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('Perfil completo');
    }
}
