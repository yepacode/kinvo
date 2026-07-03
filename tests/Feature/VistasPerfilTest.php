<?php

namespace Tests\Feature;

use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VistasPerfilTest extends TestCase
{
    use RefreshDatabase;

    private function perfil(): ProfessionalProfile
    {
        return User::factory()->create()->professionalProfile()->create(['is_published' => true]);
    }

    public function test_una_visita_de_otro_usuario_se_registra(): void
    {
        $profile = $this->perfil();
        $viewer = User::factory()->contratante()->create();

        $this->actingAs($viewer)->get(route('talento.show', $profile->slug))->assertStatus(200);

        $this->assertSame(1, $profile->views()->count());
    }

    public function test_el_dueno_no_cuenta_como_vista(): void
    {
        $profile = $this->perfil();

        $this->actingAs($profile->user)->get(route('talento.show', $profile->slug));

        $this->assertSame(0, $profile->views()->count());
    }

    public function test_recargar_el_mismo_dia_no_duplica(): void
    {
        $profile = $this->perfil();
        $viewer = User::factory()->contratante()->create();

        $this->actingAs($viewer)->get(route('talento.show', $profile->slug));
        $this->actingAs($viewer)->get(route('talento.show', $profile->slug));

        $this->assertSame(1, $profile->views()->count());
    }

    public function test_el_dashboard_muestra_el_conteo_de_vistas(): void
    {
        $owner = User::factory()->create();
        $profile = $owner->professionalProfile()->create(['is_published' => true]);
        User::factory()->count(3)->create()->each(
            fn ($u) => $profile->views()->create(['viewer_user_id' => $u->id])
        );

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertStatus(200)
            ->assertSee('Quién vio tu perfil');
    }
}
