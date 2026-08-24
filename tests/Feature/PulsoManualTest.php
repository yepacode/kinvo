<?php
namespace Tests\Feature;
use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Filament\Resources\PulseResponseResource;
use App\Filament\Resources\PulseResponseResource\Pages\CreatePulseResponse;
use App\Models\PulseResponse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
class PulsoManualTest extends TestCase
{
    use RefreshDatabase;
    private function admin(): User
    {
        $u = User::factory()->create();
        $u->forceFill(['nivel' => RolUsuario::Admin, 'estado' => EstadoUsuario::Activo])->save();
        return $u;
    }
    private function estudio(string $name = 'Estudio Manual'): User
    {
        $u = User::factory()->create(['name' => $name]);
        $u->forceFill(['nivel' => RolUsuario::Contractor, 'estado' => EstadoUsuario::Activo])->save();
        return $u;
    }

    public function test_admin_abre_el_form_de_crear_pulso(): void
    {
        $this->actingAs($this->admin())
            ->get(PulseResponseResource::getUrl('create'))
            ->assertOk()->assertSee('Estudio')->assertSee('Calificación');
    }

    public function test_admin_registra_pulso_manual_sin_coach(): void
    {
        $admin = $this->admin();
        $estudio = $this->estudio();
        Livewire::actingAs($admin)->test(CreatePulseResponse::class)
            ->fillForm([
                'contractor_user_id' => $estudio->id,
                'rating' => 5,
                'answer_support' => 'nota manual del admin',
            ])
            ->call('create')
            ->assertHasNoFormErrors();
        $this->assertDatabaseHas('pulse_responses', [
            'contractor_user_id' => $estudio->id, 'rating' => 5,
            'answer_support' => 'nota manual del admin', 'user_id' => null,
        ]);
    }

    public function test_ver_pulso_muestra_el_estudio_y_comentario(): void
    {
        $estudio = $this->estudio('Estudio Visible XYZ');
        $pulse = PulseResponse::create([
            'contractor_user_id' => $estudio->id, 'user_id' => null,
            'rating' => 4, 'answer_energy' => 'buen ambiente laboral',
        ]);
        $this->actingAs($this->admin())
            ->get(PulseResponseResource::getUrl('view', ['record' => $pulse]))
            ->assertOk()
            ->assertSee('Estudio Visible XYZ')
            ->assertSee('buen ambiente laboral');
    }
}
