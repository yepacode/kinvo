<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembresiaGateTest extends TestCase
{
    use RefreshDatabase;

    public function test_contratante_sin_membresia_es_redirigido_del_directorio(): void
    {
        $user = User::factory()->contratante()->sinMembresia()->create();

        $this->actingAs($user)->get(route('talento.index'))
            ->assertRedirect(route('membresias.index'));
    }

    public function test_contratante_con_membresia_activa_entra_al_directorio(): void
    {
        $user = User::factory()->contratante()->create(); // membresía activa por defecto

        $this->actingAs($user)->get(route('talento.index'))->assertOk();
    }

    public function test_membresia_vencida_bloquea_el_directorio(): void
    {
        $user = User::factory()->contratante()->create();
        $user->forceFill(['membership_expires_at' => now()->subDay()])->save();

        $this->actingAs($user)->get(route('talento.index'))
            ->assertRedirect(route('membresias.index'));
    }

    public function test_membresia_que_vence_hoy_sigue_activa(): void
    {
        $user = User::factory()->contratante()->create();
        $user->forceFill(['membership_expires_at' => today()])->save();

        $this->actingAs($user)->get(route('talento.index'))->assertOk();
    }

    public function test_anonimo_es_redirigido_al_login(): void
    {
        // El directorio es privado: el público general no entra.
        $this->get(route('talento.index'))->assertRedirect(route('login'));
    }

    public function test_profesional_no_ve_el_directorio(): void
    {
        // El talento NO navega el directorio; se le redirige a su área.
        $pro = User::factory()->create(); // profesional activo

        $this->actingAs($pro)->get(route('talento.index'))
            ->assertRedirect($pro->homeRoute());
    }

    public function test_profesional_si_puede_ver_su_propio_perfil(): void
    {
        // Vista previa: el profesional puede ver SU perfil público, no el de otros.
        $pro = User::factory()->create();
        $profile = $pro->professionalProfile()->create(['is_published' => true, 'headline' => 'Coach']);

        $this->actingAs($pro)->get(route('talento.show', $profile->slug))->assertOk();
    }

    public function test_admin_puede_ver_el_directorio(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('talento.index'))->assertOk();
    }

    public function test_contratante_sin_membresia_no_puede_contactar(): void
    {
        $profile = User::factory()->create()->professionalProfile()->create(['is_published' => true]);
        $user = User::factory()->contratante()->sinMembresia()->create();

        $this->actingAs($user)->get(route('contacto.create', $profile->slug))
            ->assertRedirect(route('membresias.index'));
    }
}
