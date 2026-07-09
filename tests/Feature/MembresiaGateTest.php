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

    public function test_anonimo_no_es_bloqueado(): void
    {
        $this->get(route('talento.index'))->assertOk();
    }

    public function test_profesional_no_es_bloqueado(): void
    {
        $pro = User::factory()->create(); // profesional activo

        $this->actingAs($pro)->get(route('talento.index'))->assertOk();
    }

    public function test_contratante_sin_membresia_no_puede_contactar(): void
    {
        $profile = User::factory()->create()->professionalProfile()->create(['is_published' => true]);
        $user = User::factory()->contratante()->sinMembresia()->create();

        $this->actingAs($user)->get(route('contacto.create', $profile->slug))
            ->assertRedirect(route('membresias.index'));
    }
}
