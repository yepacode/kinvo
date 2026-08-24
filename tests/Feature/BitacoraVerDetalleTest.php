<?php
namespace Tests\Feature;
use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Filament\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class BitacoraVerDetalleTest extends TestCase
{
    use RefreshDatabase;
    public function test_ver_bitacora_muestra_el_detalle(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['nivel' => RolUsuario::Admin, 'estado' => EstadoUsuario::Activo])->save();
        $log = AuditLog::create([
            'actor_user_id' => $admin->id,
            'subject_type' => User::class, 'subject_id' => $admin->id,
            'action' => 'test_action_bitacora',
            'old' => ['status' => 'antes'], 'new' => ['status' => 'despues'],
            'ip' => '1.2.3.4', 'user_agent' => 'TestAgent', 'created_at' => now(),
        ]);
        $this->actingAs($admin)
            ->get(AuditLogResource::getUrl('view', ['record' => $log]))
            ->assertOk()
            ->assertSee('test_action_bitacora')
            ->assertSee('Datos anteriores')
            ->assertSee('1.2.3.4');
    }
}
