<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\BenefitRequest;
use App\Models\Plan;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiciosMembresiaTest extends TestCase
{
    use RefreshDatabase;

    private function plan(array $serviceIds = []): Plan
    {
        $plan = Plan::create([
            'nombre' => 'Premium', 'audiencia' => 'individual',
            'precio' => 199, 'moneda' => 'MXN', 'periodo' => 'mensual',
        ]);
        if ($serviceIds) {
            $plan->services()->sync($serviceIds);
        }

        return $plan;
    }

    private function servicio(string $nombre = 'Nutrición'): Service
    {
        return Service::create(['nombre' => $nombre, 'icono' => '🥗', 'activo' => true]);
    }

    private function miembro(?Plan $plan): User
    {
        $u = User::factory()->create();
        $u->forceFill([
            'nivel' => RolUsuario::Professional,
            'estado' => EstadoUsuario::Activo,
            'membership_plan_id' => $plan?->id,
            'membership_expires_at' => $plan ? now()->addMonth() : null,
        ])->save();

        return $u;
    }

    public function test_miembro_ve_los_servicios_de_su_plan(): void
    {
        $servicio = $this->servicio('Nutrición');
        $plan = $this->plan([$servicio->id]);

        $this->actingAs($this->miembro($plan))
            ->get(route('servicios.index'))
            ->assertOk()
            ->assertSee('Nutrición');
    }

    public function test_sin_membresia_no_ve_servicios(): void
    {
        $this->servicio('Nutrición');

        $this->actingAs($this->miembro(null))
            ->get(route('servicios.index'))
            ->assertOk()
            ->assertDontSee('Nutrición')
            ->assertSee('no incluye servicios');
    }

    public function test_miembro_solicita_un_servicio_incluido(): void
    {
        $servicio = $this->servicio();
        $plan = $this->plan([$servicio->id]);
        $miembro = $this->miembro($plan);

        $this->actingAs($miembro)
            ->post(route('servicios.solicitar', $servicio), ['note' => 'Necesito ayuda'])
            ->assertRedirect()
            ->assertSessionHas('status', 'servicio-solicitado');

        $this->assertDatabaseHas('benefit_requests', [
            'user_id' => $miembro->id,
            'service_id' => $servicio->id,
            'type' => 'service',
            'status' => 'pending',
        ]);
    }

    public function test_no_puede_solicitar_un_servicio_fuera_de_su_plan(): void
    {
        $incluido = $this->servicio('Incluido');
        $fuera = $this->servicio('Fuera');
        $plan = $this->plan([$incluido->id]); // el plan NO incluye "Fuera"

        $this->actingAs($this->miembro($plan))
            ->post(route('servicios.solicitar', $fuera), [])
            ->assertForbidden();

        $this->assertDatabaseMissing('benefit_requests', ['service_id' => $fuera->id]);
    }

    public function test_no_duplica_una_solicitud_abierta(): void
    {
        $servicio = $this->servicio();
        $plan = $this->plan([$servicio->id]);
        $miembro = $this->miembro($plan);

        $this->actingAs($miembro)->post(route('servicios.solicitar', $servicio), []);
        $this->actingAs($miembro)
            ->post(route('servicios.solicitar', $servicio), [])
            ->assertSessionHas('status', 'servicio-ya-solicitado');

        $this->assertSame(1, BenefitRequest::where('service_id', $servicio->id)->count());
    }

    public function test_admin_abre_el_form_de_servicio(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['nivel' => RolUsuario::Admin, 'estado' => EstadoUsuario::Activo])->save();

        $this->actingAs($admin)
            ->get('/admin/services/create')
            ->assertOk()
            ->assertSee('¿Qué planes lo incluyen?');
    }

    // ---------- El estudio paga y cubre a su equipo (Punto 5-A extendido) ----------

    private function estudioConPlan(array $serviceIds): User
    {
        $plan = $this->plan($serviceIds);
        $estudio = User::factory()->create();
        $estudio->forceFill([
            'nivel' => RolUsuario::Contractor,
            'estado' => EstadoUsuario::Activo,
            'membership_plan_id' => $plan->id,
            'membership_expires_at' => now()->addMonth(),
        ])->save();

        return $estudio;
    }

    private function coachSinMembresia(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['nivel' => RolUsuario::Professional, 'estado' => EstadoUsuario::Activo])->save();

        return $u;
    }

    private function agregarAlEquipo(User $estudio, User $coach, string $status): void
    {
        \App\Models\TeamMember::create([
            'contractor_user_id' => $estudio->id,
            'professional_user_id' => $coach->id,
            'status' => $status,
            'joined_at' => $status === \App\Models\TeamMember::STATUS_ACTIVE ? now() : null,
        ]);
    }

    public function test_colaborador_activo_usa_los_servicios_del_estudio_que_paga(): void
    {
        $servicio = $this->servicio('Nutrición');
        $estudio = $this->estudioConPlan([$servicio->id]);
        $coach = $this->coachSinMembresia(); // NO paga él
        $this->agregarAlEquipo($estudio, $coach, \App\Models\TeamMember::STATUS_ACTIVE);

        $this->actingAs($coach)->get(route('servicios.index'))->assertOk()->assertSee('Nutrición');

        $this->actingAs($coach)
            ->post(route('servicios.solicitar', $servicio), [])
            ->assertSessionHas('status', 'servicio-solicitado');

        $this->assertDatabaseHas('benefit_requests', [
            'user_id' => $coach->id, 'service_id' => $servicio->id, 'status' => 'pending',
        ]);
    }

    public function test_colaborador_no_recibe_servicios_si_el_estudio_no_tiene_membresia_vigente(): void
    {
        $servicio = $this->servicio('Nutrición');
        $estudio = $this->estudioConPlan([$servicio->id]);
        $estudio->forceFill(['membership_expires_at' => now()->subDay()])->save(); // vencida
        $coach = $this->coachSinMembresia();
        $this->agregarAlEquipo($estudio, $coach, \App\Models\TeamMember::STATUS_ACTIVE);

        $this->actingAs($coach)->get(route('servicios.index'))->assertOk()->assertDontSee('Nutrición');
        $this->actingAs($coach)->post(route('servicios.solicitar', $servicio), [])->assertForbidden();
    }

    public function test_colaborador_solo_invitado_aun_no_recibe_servicios(): void
    {
        $servicio = $this->servicio('Nutrición');
        $estudio = $this->estudioConPlan([$servicio->id]);
        $coach = $this->coachSinMembresia();
        $this->agregarAlEquipo($estudio, $coach, \App\Models\TeamMember::STATUS_INVITED); // no ha aceptado

        $this->actingAs($coach)->get(route('servicios.index'))->assertOk()->assertDontSee('Nutrición');
    }
}
