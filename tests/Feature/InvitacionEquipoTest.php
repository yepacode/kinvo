<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\TeamMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvitacionEquipoTest extends TestCase
{
    use RefreshDatabase;

    private function estudio(): User
    {
        $u = User::factory()->create();
        $u->forceFill([
            'nivel' => RolUsuario::Contractor,
            'estado' => EstadoUsuario::Activo,
            'membership_expires_at' => now()->addMonth(),
        ])->save();

        return $u;
    }

    private function coach(string $email = 'coach@t.com'): User
    {
        $u = User::factory()->create(['email' => $email]);
        $u->forceFill(['nivel' => RolUsuario::Professional, 'estado' => EstadoUsuario::Activo])->save();
        $u->professionalProfile()->firstOrCreate([], ['headline' => 'Coach', 'is_published' => true]);

        return $u;
    }

    public function test_invitacion_notifica_y_el_coach_acepta_desde_notificaciones(): void
    {
        $estudio = $this->estudio();
        $coach = $this->coach();

        $this->actingAs($estudio)
            ->post(route('equipo.invitar'), ['email' => $coach->email])
            ->assertSessionHas('status', 'invitacion-enviada');

        $tm = TeamMember::where('professional_user_id', $coach->id)->firstOrFail();
        $this->assertSame(TeamMember::STATUS_INVITED, $tm->status);

        // Dejó rastro: notificación en la campana del coach.
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $coach->id]);

        // El coach ve la invitación CON botones aceptar/rechazar (Punto 7).
        $this->actingAs($coach)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(route('equipo.aceptar', $tm), false)
            ->assertSee(route('equipo.rechazar', $tm), false);

        // Y puede aceptar.
        $this->actingAs($coach)
            ->post(route('equipo.aceptar', $tm))
            ->assertSessionHas('status', 'invitacion-aceptada');

        $this->assertSame(TeamMember::STATUS_ACTIVE, $tm->fresh()->status);
    }

    public function test_el_coach_puede_rechazar_la_invitacion(): void
    {
        $estudio = $this->estudio();
        $coach = $this->coach('coach2@t.com');

        $this->actingAs($estudio)->post(route('equipo.invitar'), ['email' => $coach->email]);
        $tm = TeamMember::where('professional_user_id', $coach->id)->firstOrFail();

        $this->actingAs($coach)
            ->post(route('equipo.rechazar', $tm))
            ->assertSessionHas('status');

        $this->assertSame(TeamMember::STATUS_DECLINED, $tm->fresh()->status);
    }

    public function test_una_invitacion_ya_resuelta_no_muestra_botones(): void
    {
        $estudio = $this->estudio();
        $coach = $this->coach('coach3@t.com');
        $this->actingAs($estudio)->post(route('equipo.invitar'), ['email' => $coach->email]);
        $tm = TeamMember::where('professional_user_id', $coach->id)->firstOrFail();

        // Ya aceptada → los botones no deben salir.
        $this->actingAs($coach)->post(route('equipo.aceptar', $tm));

        $this->actingAs($coach)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertDontSee(route('equipo.aceptar', $tm), false);
    }
}
