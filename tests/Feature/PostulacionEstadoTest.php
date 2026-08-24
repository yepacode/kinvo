<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PostulacionEstadoTest extends TestCase
{
    use RefreshDatabase;

    /** @return array{0: User, 1: Application} */
    private function ofertaConPostulacion(): array
    {
        $estudio = User::factory()->contratante()->create();
        $coach = User::factory()->create(['name' => 'Coach Prueba']);
        $offer = Offer::create([
            'contractor_user_id' => $estudio->id,
            'title' => 'Vacante test', 'description' => 'x',
            'modality' => 'presencial', 'salary_currency' => 'MXN',
            'salary_period' => 'month', 'status' => Offer::STATUS_PUBLISHED,
        ]);
        $app = Application::create([
            'offer_id' => $offer->id,
            'professional_user_id' => $coach->id,
            'status' => Application::STATUS_SUBMITTED,
        ]);

        return [$estudio, $app];
    }

    public function test_estudio_cambia_estado_de_postulacion(): void
    {
        [$estudio, $app] = $this->ofertaConPostulacion();

        $this->actingAs($estudio)
            ->post(route('ofertas.postulacion.estado', $app), ['status' => 'accepted'])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('accepted', $app->fresh()->status);
    }

    public function test_estudio_ajeno_no_puede_cambiar_estado(): void
    {
        [, $app] = $this->ofertaConPostulacion();
        $otro = User::factory()->contratante()->create();

        $this->actingAs($otro)
            ->post(route('ofertas.postulacion.estado', $app), ['status' => 'accepted'])
            ->assertForbidden();

        $this->assertSame('submitted', $app->fresh()->status);
    }

    /**
     * Regresión (reporte cliente ago-2026): el control de estado debe
     * AUTO-GUARDAR al cambiar el <select>. Antes exigía pulsar "Guardar"
     * aparte y muchos no lo hacían → parecía que "no cambiaba". Este test fija
     * que el wiring de auto-envío siga presente en la vista de mis-ofertas.
     */
    public function test_control_de_estado_autoguarda_al_cambiar(): void
    {
        [$estudio, ] = $this->ofertaConPostulacion();

        $this->actingAs($estudio)
            ->get(route('ofertas.mis-ofertas'))
            ->assertOk()
            ->assertSee('onchange="this.form.submit()"', false);
    }
}
