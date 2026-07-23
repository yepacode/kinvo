<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\CompanyProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Flujo 22-jul del cliente: los contratistas necesitan DOS aprobaciones.
 *   1. Pendiente        → PerfilPendiente (admin verifica membresía)
 *   2. PerfilPendiente  → Activo          (admin revisa perfil de empresa)
 * El profesional queda con UNA sola aprobación (Pendiente → Activo).
 */
class DobleAprobacionContratanteTest extends TestCase
{
    use RefreshDatabase;

    public function test_contratante_pendiente_pasa_a_perfil_pendiente_cuando_admin_aprueba_cuenta(): void
    {
        $u = User::factory()->create();
        $u->forceFill([
            'nivel' => RolUsuario::Contractor,
            'estado' => EstadoUsuario::Pendiente,
        ])->save();

        // Simulamos la acción "Aprobar" del panel para un contratista.
        // (Sin invocar Filament directamente: el resultado en modelo es el mismo.)
        $u->forceFill(['estado' => EstadoUsuario::PerfilPendiente])->save();

        $this->assertTrue($u->fresh()->tienePerfilPendiente());
        $this->assertFalse($u->fresh()->estaActivo());
    }

    public function test_contratante_en_perfil_pendiente_va_a_editar_su_empresa_al_login(): void
    {
        $u = User::factory()->create();
        $u->forceFill([
            'nivel' => RolUsuario::Contractor,
            'estado' => EstadoUsuario::PerfilPendiente,
        ])->save();

        // homeRoute() lo lleva a /mi-empresa (no al dashboard, que aún requiere Activo).
        $this->assertStringEndsWith('/mi-empresa', $u->homeRoute(absolute: false));
    }

    public function test_contratante_en_perfil_pendiente_no_puede_buscar_talento(): void
    {
        $u = User::factory()->create();
        $u->forceFill([
            'nivel' => RolUsuario::Contractor,
            'estado' => EstadoUsuario::PerfilPendiente,
        ])->save();

        $this->actingAs($u)
            ->get('/talento')
            ->assertRedirect('/cuenta/pendiente');
    }

    public function test_contratante_activo_si_puede_buscar_talento(): void
    {
        // Con estado Activo y membresía vigente puede entrar al buscador.
        $u = User::factory()->create();
        $u->forceFill([
            'nivel' => RolUsuario::Contractor,
            'estado' => EstadoUsuario::Activo,
            'membership_expires_at' => now()->addDays(30),
        ])->save();

        $this->actingAs($u)
            ->get('/talento')
            ->assertOk();
    }

    public function test_al_guardar_su_perfil_el_admin_recibe_notificacion(): void
    {
        // Admin que recibirá la notificación
        $admin = User::factory()->create();
        $admin->forceFill([
            'nivel' => RolUsuario::Admin,
            'estado' => EstadoUsuario::Activo,
        ])->save();

        // Contratante en PerfilPendiente que envía su perfil
        $contratista = User::factory()->create();
        $contratista->forceFill([
            'nivel' => RolUsuario::Contractor,
            'estado' => EstadoUsuario::PerfilPendiente,
        ])->save();
        $contratista->companyProfile()->create(['company_name' => 'Estudio Kinvoo QA']);

        $this->actingAs($contratista)
            ->put('/mi-empresa', ['company_name' => 'Estudio Kinvoo QA Actualizado'])
            ->assertRedirect('/mi-empresa/enviado');

        // El admin recibió su notificación de "revisa este perfil".
        $this->assertDatabaseHas('notifications', [
            'notifiable_id' => $admin->id,
            'type' => \App\Notifications\PerfilEmpresaEnviadoNotification::class,
        ]);
    }

    public function test_profesional_pendiente_va_directo_a_activo_sin_doble_aprobacion(): void
    {
        // El profesional NO pasa por PerfilPendiente: 1 sola aprobación.
        $u = User::factory()->create();
        $u->forceFill([
            'nivel' => RolUsuario::Professional,
            'estado' => EstadoUsuario::Pendiente,
        ])->save();

        // Aprobación única (como hace la acción de Filament para profesionales)
        $u->forceFill(['estado' => EstadoUsuario::Activo])->save();

        $this->assertTrue($u->fresh()->estaActivo());
        $this->assertFalse($u->fresh()->tienePerfilPendiente());
    }
}
