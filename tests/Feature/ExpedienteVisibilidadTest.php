<?php
namespace Tests\Feature;
use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\WellnessEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ExpedienteVisibilidadTest extends TestCase
{
    use RefreshDatabase;

    private function coach(string $email): User
    {
        $u = User::factory()->create(['email' => $email]);
        $u->forceFill(['nivel' => RolUsuario::Professional, 'estado' => EstadoUsuario::Activo])->save();
        return $u;
    }
    private function estudio(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['nivel' => RolUsuario::Contractor, 'estado' => EstadoUsuario::Activo,
            'membership_expires_at' => now()->addMonth()])->save();
        return $u;
    }

    public function test_coach_alterna_la_visibilidad_de_su_expediente(): void
    {
        $coach = $this->coach('c@t.com');
        $this->assertTrue((bool) $coach->fresh()->comparte_expediente); // default visible (de la BD)

        $this->actingAs($coach)->post(route('expediente.visibilidad'), ['comparte_expediente' => '0'])
            ->assertRedirect();
        $this->assertFalse((bool) $coach->fresh()->comparte_expediente);

        $this->actingAs($coach)->post(route('expediente.visibilidad'), ['comparte_expediente' => '1']);
        $this->assertTrue((bool) $coach->fresh()->comparte_expediente);
    }

    public function test_el_panel_del_estudio_excluye_al_coach_que_oculto_su_expediente(): void
    {
        $estudio = $this->estudio();
        $comparte = $this->coach('a@t.com');
        $oculta = $this->coach('b@t.com');
        $oculta->forceFill(['comparte_expediente' => false])->save();

        foreach ([$comparte, $oculta] as $c) {
            TeamMember::create(['contractor_user_id' => $estudio->id, 'professional_user_id' => $c->id,
                'status' => TeamMember::STATUS_ACTIVE, 'joined_at' => now()]);
            WellnessEntry::create(['professional_user_id' => $c->id,
                'type' => WellnessEntry::TYPE_TELEMEDICINE, 'occurred_on' => now()->subDay()]);
        }

        // Hay 2 entradas de telemedicina, pero solo 1 coach comparte → cuenta 1.
        $this->actingAs($estudio)->get(route('equipo.index'))
            ->assertOk()
            ->assertSeeInOrder(['Consultas médicas', '1']);
    }

    /**
     * Regresión: `/mi-expediente` reventaba (500 · stdClass::getKey()) en cuanto
     * el coach tenía vistas de contenido, por el merge de una Eloquent Collection
     * sobre objetos stdClass. Debe renderizar y mostrar el switch + la charla.
     */
    public function test_el_expediente_renderiza_aunque_el_coach_tenga_vistas(): void
    {
        $coach = $this->coach('e@t.com');
        $coach->forceFill(['membership_expires_at' => now()->addMonth()])->save();

        $item = \App\Models\ContentItem::create([
            'title' => 'Charla X', 'type' => 'video', 'is_published' => true,
        ]);
        \App\Models\ContentView::create([
            'user_id' => $coach->id, 'content_item_id' => $item->id, 'viewed_at' => now(),
        ]);

        $this->actingAs($coach)->get(route('expediente.index'))
            ->assertOk()
            ->assertSee('Compartir mi expediente')
            ->assertSee('Charla X');
    }
}
