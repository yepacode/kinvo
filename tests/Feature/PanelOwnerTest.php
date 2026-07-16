<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Filament\Resources\UserResource\Pages\ListUsers;
use App\Filament\Widgets\ResumenStats;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PanelOwnerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_ve_la_lista_de_usuarios(): void
    {
        $owner = User::factory()->admin()->create();
        User::factory()->pendiente()->create(['name' => 'Pendiente Perez']);

        $this->actingAs($owner)
            ->get('/admin/users')
            ->assertStatus(200)
            ->assertSee('Pendiente Perez');
    }

    public function test_no_admin_es_redirigido_fuera_del_panel(): void
    {
        $profesional = User::factory()->create();

        $this->actingAs($profesional)
            ->get('/admin/users')
            ->assertRedirect(route('dashboard'));
    }

    public function test_owner_aprueba_a_un_usuario_pendiente(): void
    {
        $owner = User::factory()->admin()->create();
        $pendiente = User::factory()->pendiente()->create();

        // Perfil sin publicar (el usuario no lo auto-publica).
        $perfil = $pendiente->professionalProfile()->create(['is_published' => false, 'headline' => 'Coach']);

        $this->actingAs($owner);

        Livewire::test(ListUsers::class)
            ->callTableAction('aprobar', $pendiente);

        // Aprobación única: activa la cuenta Y publica el perfil de una vez.
        $this->assertSame(EstadoUsuario::Activo, $pendiente->fresh()->estado);
        $this->assertTrue($perfil->fresh()->is_published);
    }

    public function test_owner_suspende_a_un_usuario_activo(): void
    {
        $owner = User::factory()->admin()->create();
        $activo = User::factory()->create(); // Professional activo

        $this->actingAs($owner);

        Livewire::test(ListUsers::class)
            ->callTableAction('suspender', $activo);

        $this->assertSame(EstadoUsuario::Suspendido, $activo->fresh()->estado);
    }

    public function test_el_resumen_muestra_metricas(): void
    {
        $owner = User::factory()->admin()->create();
        User::factory()->pendiente()->create();

        $this->actingAs($owner);

        Livewire::test(ResumenStats::class)
            ->assertSee('Pendientes de aprobación')
            ->assertSee('Contactos')
            ->assertSee('total en la plataforma');
    }
}
