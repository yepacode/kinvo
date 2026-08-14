<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\Application;
use App\Models\ContentItem;
use App\Models\Offer;
use App\Models\Plan;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\WellnessEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 2 · Cubre Hito 3: bolsa de trabajo, contenido con gate, expediente
 * y equipo del estudio con panel de bienestar.
 */
class Fase2ProductoTest extends TestCase
{
    use RefreshDatabase;

    private function coach(string $email = 'coach@t.com'): User
    {
        $u = User::factory()->create(['email' => $email]);
        $u->forceFill(['nivel' => RolUsuario::Professional, 'estado' => EstadoUsuario::Activo])->save();
        $u->professionalProfile()->firstOrCreate([], ['headline' => 'Coach test', 'is_published' => true]);
        return $u;
    }

    private function estudio(string $email = 'estudio@t.com'): User
    {
        $u = User::factory()->create(['email' => $email]);
        $u->forceFill([
            'nivel' => RolUsuario::Contractor,
            'estado' => EstadoUsuario::Activo,
            'membership_expires_at' => now()->addMonth(),
        ])->save();
        return $u;
    }

    private function ofertaPublicada(User $estudio): Offer
    {
        return Offer::create([
            'contractor_user_id' => $estudio->id,
            'title' => 'Coach de yoga',
            'description' => 'Buscamos coach de yoga.',
            'status' => Offer::STATUS_PUBLISHED,
            'published_at' => now()->subDay(),
        ]);
    }

    // ---------- Bolsa de trabajo ----------

    public function test_coach_puede_postular_a_una_oferta(): void
    {
        $estudio = $this->estudio();
        $coach = $this->coach();
        $offer = $this->ofertaPublicada($estudio);

        $r = $this->actingAs($coach)->post(route('ofertas.postular', $offer->slug), [
            'cover_letter' => 'Me interesa mucho.',
        ]);
        $r->assertRedirect();
        $r->assertSessionHas('status', 'postulacion-enviada');
        $this->assertSame(1, Application::where('offer_id', $offer->id)->count());
    }

    public function test_coach_no_puede_postular_dos_veces_a_la_misma_oferta(): void
    {
        $estudio = $this->estudio();
        $coach = $this->coach();
        $offer = $this->ofertaPublicada($estudio);

        $this->actingAs($coach)->post(route('ofertas.postular', $offer->slug));
        $r = $this->actingAs($coach)->post(route('ofertas.postular', $offer->slug));
        $r->assertSessionHas('status', 'ya-postulaste');
        $this->assertSame(1, Application::where('offer_id', $offer->id)->count());
    }

    public function test_estudio_cambia_estado_de_una_postulacion(): void
    {
        $estudio = $this->estudio();
        $coach = $this->coach();
        $offer = $this->ofertaPublicada($estudio);
        $app = Application::create([
            'offer_id' => $offer->id,
            'professional_user_id' => $coach->id,
            'status' => Application::STATUS_SUBMITTED,
        ]);

        $this->actingAs($estudio)
            ->post(route('ofertas.postulacion.estado', $app), ['status' => 'accepted'])
            ->assertRedirect();

        $this->assertSame('accepted', $app->fresh()->status);
    }

    public function test_estudio_no_puede_cambiar_estado_de_postulacion_ajena(): void
    {
        $estudio = $this->estudio();
        $otroEstudio = $this->estudio('otro@t.com');
        $coach = $this->coach();
        $offer = $this->ofertaPublicada($estudio);
        $app = Application::create([
            'offer_id' => $offer->id,
            'professional_user_id' => $coach->id,
            'status' => Application::STATUS_SUBMITTED,
        ]);

        $this->actingAs($otroEstudio)
            ->post(route('ofertas.postulacion.estado', $app), ['status' => 'accepted'])
            ->assertForbidden();
    }

    // ---------- Contenido ----------

    public function test_contenido_publico_visible_para_cualquier_user_autenticado(): void
    {
        $coach = $this->coach();
        ContentItem::create([
            'title' => 'Bienvenida', 'type' => 'video', 'is_published' => true,
        ]);
        $this->actingAs($coach)->get('/desarrollo')->assertOk()->assertSee('Bienvenida');
    }

    public function test_contenido_por_rol_oculta_al_rol_incorrecto(): void
    {
        $coach = $this->coach();
        $estudio = $this->estudio();
        ContentItem::create([
            'title' => 'Solo para estudios', 'type' => 'video',
            'gate_role' => 'contractor', 'is_published' => true,
        ]);
        $this->actingAs($estudio)->get('/desarrollo')->assertSee('Solo para estudios');
        $this->actingAs($coach)->get('/desarrollo')->assertDontSee('Solo para estudios');
    }

    // ---------- Equipo + impacto ----------

    public function test_estudio_invita_a_un_coach_y_el_coach_puede_aceptar(): void
    {
        $estudio = $this->estudio();
        $coach = $this->coach();

        $this->actingAs($estudio)
            ->post(route('equipo.invitar'), ['email' => $coach->email])
            ->assertSessionHas('status', 'invitacion-enviada');

        $tm = TeamMember::first();
        $this->assertSame(TeamMember::STATUS_INVITED, $tm->status);

        $this->actingAs($coach)
            ->post(route('equipo.aceptar', $tm))
            ->assertSessionHas('status', 'invitacion-aceptada');

        $this->assertSame(TeamMember::STATUS_ACTIVE, $tm->fresh()->status);
        $this->assertNotNull($tm->fresh()->joined_at);
    }

    public function test_panel_impacto_muestra_conteos_del_expediente_del_equipo(): void
    {
        $estudio = $this->estudio();
        $coach = $this->coach();

        TeamMember::create([
            'contractor_user_id' => $estudio->id,
            'professional_user_id' => $coach->id,
            'status' => TeamMember::STATUS_ACTIVE,
            'joined_at' => now(),
        ]);
        // 2 telemedicine + 1 physio del coach
        foreach (['telemedicine', 'telemedicine', 'physio'] as $i => $type) {
            WellnessEntry::create([
                'professional_user_id' => $coach->id,
                'type' => $type,
                'occurred_on' => now()->subDays($i + 1),
            ]);
        }

        $this->actingAs($estudio)->get(route('equipo.index'))
            ->assertSee('Consultas médicas')
            ->assertSeeInOrder(['Consultas médicas', '2'])
            ->assertSeeInOrder(['Sesiones de fisio', '1']);
    }
}
