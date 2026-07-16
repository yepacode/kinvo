<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WizardOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_profesional_pendiente_puede_llenar_su_perfil(): void
    {
        // El fix del flujo: un usuario aún NO aprobado debe poder completar su perfil.
        $pendiente = User::factory()->create(['estado' => EstadoUsuario::Pendiente]);

        $this->actingAs($pendiente)->get(route('professional.bienvenida'))->assertOk();
        $this->actingAs($pendiente)->get(route('professional.profile.edit'))->assertOk();
    }

    public function test_estudio_pendiente_puede_llenar_su_perfil(): void
    {
        $pendiente = User::factory()->contratante()->create(['estado' => EstadoUsuario::Pendiente]);

        $this->actingAs($pendiente)->get(route('company.bienvenida'))->assertOk();
        $this->actingAs($pendiente)->get(route('company.profile.edit'))->assertOk();
    }

    public function test_bienvenida_muestra_el_mensaje_y_boton_siguiente(): void
    {
        $pro = User::factory()->create(['estado' => EstadoUsuario::Pendiente]);

        $this->actingAs($pro)->get(route('professional.bienvenida'))
            ->assertOk()
            ->assertSee('Bienvenid')
            ->assertSee('Siguiente');
    }

    public function test_confirmacion_de_perfil_no_publicado_dice_en_revision(): void
    {
        $pro = User::factory()->create(['estado' => EstadoUsuario::Pendiente]);
        $pro->professionalProfile()->create(['is_published' => false]);

        $this->actingAs($pro)->get(route('professional.enviado'))
            ->assertOk()
            ->assertSee('será revisado');
    }

    public function test_confirmacion_de_perfil_publicado_dice_publicado(): void
    {
        $pro = User::factory()->create(); // activo
        $pro->professionalProfile()->create(['is_published' => true, 'headline' => 'Coach']);

        $this->actingAs($pro)->get(route('professional.enviado'))
            ->assertOk()
            ->assertSee('publicado');
    }
}
