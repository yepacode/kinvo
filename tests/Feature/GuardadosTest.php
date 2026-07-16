<?php

namespace Tests\Feature;

use App\Models\ProfessionalProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GuardadosTest extends TestCase
{
    use RefreshDatabase;

    private function perfilPublicado(): ProfessionalProfile
    {
        return User::factory()->create(['name' => 'Ana Coach'])
            ->professionalProfile()->create(['is_published' => true]);
    }

    public function test_usuario_guarda_y_quita_un_perfil(): void
    {
        $profile = $this->perfilPublicado();
        $user = User::factory()->contratante()->create();

        // Guardar
        $this->actingAs($user)->post(route('saves.toggleProfile', $profile->slug))->assertRedirect();
        $this->assertTrue($user->fresh()->haGuardado($profile));

        // Quitar
        $this->actingAs($user)->post(route('saves.toggleProfile', $profile->slug))->assertRedirect();
        $this->assertFalse($user->fresh()->haGuardado($profile));
    }

    public function test_guardados_muestra_los_perfiles(): void
    {
        $profile = $this->perfilPublicado();
        $user = User::factory()->contratante()->create();
        $this->actingAs($user)->post(route('saves.toggleProfile', $profile->slug));

        $this->actingAs($user)
            ->get(route('saves.index'))
            ->assertStatus(200)
            ->assertSee('Ana Coach');
    }

    public function test_invitado_no_puede_guardar(): void
    {
        $profile = $this->perfilPublicado();

        $this->post(route('saves.toggleProfile', $profile->slug))
            ->assertRedirect(route('login'));
    }

    public function test_profesional_no_accede_a_guardados(): void
    {
        // Guardados es función de estudios: el talento no puede usarla ni ver tarjetas.
        $pro = User::factory()->create(); // profesional
        $otro = $this->perfilPublicado();

        $this->actingAs($pro)->get(route('saves.index'))->assertRedirect($pro->homeRoute());
        $this->actingAs($pro)->post(route('saves.toggleProfile', $otro->slug))
            ->assertRedirect($pro->homeRoute());
    }
}
