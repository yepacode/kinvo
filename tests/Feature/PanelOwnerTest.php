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

    public function test_owner_rechaza_a_un_usuario_pendiente(): void
    {
        $owner = User::factory()->admin()->create();
        $pendiente = User::factory()->pendiente()->create();
        $perfil = $pendiente->professionalProfile()->create([
            'is_published' => false,
            'is_verified' => true,
            'verified_at' => now(),
            'headline' => 'Coach',
        ]);

        $this->actingAs($owner);

        Livewire::test(ListUsers::class)
            ->callTableAction('rechazar', $pendiente);

        $this->assertSame(EstadoUsuario::Suspendido, $pendiente->fresh()->estado);
        $perfil->refresh();
        $this->assertFalse($perfil->is_published);
        $this->assertFalse($perfil->is_verified);
        $this->assertNull($perfil->verified_at);
    }

    public function test_owner_elimina_una_cuenta_desde_el_panel(): void
    {
        $owner = User::factory()->admin()->create();
        $victima = User::factory()->create();
        $victima->professionalProfile()->create(['headline' => 'Coach']);

        $this->actingAs($owner);

        Livewire::test(ListUsers::class)
            ->callTableAction('eliminar', $victima);

        $this->assertDatabaseMissing('users', ['id' => $victima->id]);
        $this->assertDatabaseMissing('professional_profiles', ['user_id' => $victima->id]);
    }

    public function test_owner_no_puede_eliminarse_ni_a_otro_admin_desde_el_panel(): void
    {
        $owner = User::factory()->admin()->create();
        $otroAdmin = User::factory()->admin()->create();

        $this->actingAs($owner);

        // La acción sobre uno mismo o sobre otro admin ni siquiera está disponible.
        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('eliminar', $owner)
            ->assertTableActionHidden('eliminar', $otroAdmin);
    }

    public function test_owner_cambia_el_tipo_de_cuenta(): void
    {
        $owner = User::factory()->admin()->create();
        $user = User::factory()->contratante()->create();
        $user->companyProfile()->create(['company_name' => 'Gym Error']);

        $this->actingAs($owner);

        Livewire::test(ListUsers::class)
            ->callTableAction('cambiar_tipo', $user, data: ['tipo' => \App\Enums\RolUsuario::Professional->value]);

        $this->assertSame(\App\Enums\RolUsuario::Professional, $user->fresh()->nivel);
        $this->assertDatabaseMissing('company_profiles', ['user_id' => $user->id]);
    }

    public function test_rechazar_notifica_al_usuario_con_campanita(): void
    {
        $owner = User::factory()->admin()->create();
        $pendiente = User::factory()->pendiente()->create();
        $pendiente->professionalProfile()->create(['is_published' => false, 'headline' => 'Coach']);

        $this->actingAs($owner);
        Livewire::test(ListUsers::class)->callTableAction('rechazar', $pendiente);

        $this->assertSame(1, $pendiente->fresh()->unreadNotifications()->count());
        $notif = $pendiente->fresh()->notifications()->latest()->first();
        $this->assertSame('cuenta_rechazada', $notif->data['tipo']);
    }

    public function test_rechazar_no_falla_si_el_pendiente_es_contratante_sin_perfil(): void
    {
        // Regresión: el `professionalProfile?->update` es nullsafe, pero si mañana
        // alguien lo cambia a Model::update crashea. Este test protege el caso.
        $owner = User::factory()->admin()->create();
        $pendiente = User::factory()->contratante()->create([
            'estado' => \App\Enums\EstadoUsuario::Pendiente,
        ]);

        $this->actingAs($owner);
        Livewire::test(ListUsers::class)->callTableAction('rechazar', $pendiente);

        $this->assertSame(EstadoUsuario::Suspendido, $pendiente->fresh()->estado);
    }

    public function test_rechazar_no_aparece_para_un_usuario_ya_activo(): void
    {
        $owner = User::factory()->admin()->create();
        $activo = User::factory()->create();

        $this->actingAs($owner);
        Livewire::test(ListUsers::class)
            ->assertTableActionHidden('rechazar', $activo);
    }

    public function test_cambiar_tipo_a_admin_es_rechazado(): void
    {
        // Regresión de la ruta de escalado detectada por la auditoría: sin la
        // whitelist server-side, un submit malicioso con tipo=0 (Admin.value)
        // pasaba RolUsuario::from() y ascendía al usuario a owner.
        $owner = User::factory()->admin()->create();
        $victima = User::factory()->create();

        $this->actingAs($owner);

        try {
            Livewire::test(ListUsers::class)
                ->callTableAction('cambiar_tipo', $victima, data: ['tipo' => (string) \App\Enums\RolUsuario::Admin->value]);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            $this->assertSame(422, $e->getStatusCode());
        }

        $this->assertNotSame(\App\Enums\RolUsuario::Admin, $victima->fresh()->nivel);
    }

    public function test_cambiar_tipo_borra_contactos_y_membresia_del_rol_anterior(): void
    {
        $owner = User::factory()->admin()->create();
        $contratante = User::factory()->contratante()->create();
        $this->seed(\Database\Seeders\PlanSeeder::class);
        $plan = \App\Models\Plan::first();
        $contratante->forceFill([
            'membership_plan_id' => $plan->id,
            'membership_expires_at' => now()->addMonth(),
        ])->save();
        $contratante->companyProfile()->create(['company_name' => 'Gym']);

        // El contratante había mandado contactos a alguien.
        $victima = User::factory()->create();
        $perfil = $victima->professionalProfile()->create(['is_published' => true, 'headline' => 'Coach']);
        $perfil->contacts()->create([
            'contractor_user_id' => $contratante->id,
            'contact_name' => 'Estudio Zen',
            'contact_email' => 'zen@example.com',
            'message' => 'Hola tengo vacante.',
            'estado' => \App\Enums\EstadoContacto::NoLeido,
        ]);

        $this->actingAs($owner);
        Livewire::test(ListUsers::class)
            ->callTableAction('cambiar_tipo', $contratante, data: ['tipo' => (string) \App\Enums\RolUsuario::Professional->value]);

        $contratante->refresh();
        $this->assertSame(\App\Enums\RolUsuario::Professional, $contratante->nivel);
        $this->assertNull($contratante->membership_plan_id);
        $this->assertNull($contratante->membership_expires_at);
        $this->assertSame(0, \App\Models\Contact::where('contractor_user_id', $contratante->id)->count());
    }

    public function test_cambiar_tipo_borra_saves_polimorficos_del_perfil_viejo(): void
    {
        $owner = User::factory()->admin()->create();
        $victima = User::factory()->create();
        $perfilPro = $victima->professionalProfile()->create(['is_published' => true, 'headline' => 'Coach']);
        // Otro usuario había guardado este perfil (Save polimórfico → sin FK).
        $fan = User::factory()->contratante()->create();
        $fan->saves()->create([
            'saveable_type' => \App\Models\ProfessionalProfile::class,
            'saveable_id' => $perfilPro->id,
        ]);

        $this->actingAs($owner);
        Livewire::test(ListUsers::class)
            ->callTableAction('cambiar_tipo', $victima, data: ['tipo' => (string) \App\Enums\RolUsuario::Contractor->value]);

        $this->assertSame(0, \App\Models\Save::where('saveable_type', \App\Models\ProfessionalProfile::class)
            ->where('saveable_id', $perfilPro->id)->count());
    }

    public function test_cambiar_tipo_notifica_al_usuario(): void
    {
        $owner = User::factory()->admin()->create();
        $contratante = User::factory()->contratante()->create();
        $contratante->companyProfile()->create(['company_name' => 'Gym']);

        $this->actingAs($owner);
        Livewire::test(ListUsers::class)
            ->callTableAction('cambiar_tipo', $contratante, data: ['tipo' => (string) \App\Enums\RolUsuario::Professional->value]);

        $notif = $contratante->fresh()->notifications()->latest()->first();
        $this->assertNotNull($notif);
        $this->assertSame('tipo_cuenta_cambiado', $notif->data['tipo']);
    }

    public function test_eliminar_desde_el_panel_borra_archivos_del_disco(): void
    {
        \Illuminate\Support\Facades\Storage::fake('public');
        \Illuminate\Support\Facades\Storage::fake('local');

        $owner = User::factory()->admin()->create();
        $victima = User::factory()->create();

        // Sube foto + adjunto (usa el flujo real del controller).
        $this->actingAs($victima)->put('/mi-perfil', [
            'photo' => \Illuminate\Http\UploadedFile::fake()->image('f.jpg'),
            'certification_file' => \Illuminate\Http\UploadedFile::fake()->create('c.pdf', 100, 'application/pdf'),
        ]);
        $profile = $victima->professionalProfile()->first();
        $photo = $profile->photo_path;
        $cert = $profile->certification_file_path;

        $this->actingAs($owner);
        Livewire::test(ListUsers::class)->callTableAction('eliminar', $victima);

        $this->assertDatabaseMissing('users', ['id' => $victima->id]);
        \Illuminate\Support\Facades\Storage::disk('public')->assertMissing($photo);
        \Illuminate\Support\Facades\Storage::disk('local')->assertMissing($cert);
    }

    public function test_el_resumen_muestra_metricas(): void
    {
        $owner = User::factory()->admin()->create();
        User::factory()->pendiente()->create();

        $this->actingAs($owner);

        Livewire::test(ResumenStats::class)
            ->assertSee('Aprobaciones pendientes')
            ->assertSee('Perfiles en revisión')
            ->assertSee('Contactos');
    }
}
