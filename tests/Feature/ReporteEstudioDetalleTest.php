<?php
namespace Tests\Feature;
use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
class ReporteEstudioDetalleTest extends TestCase
{
    use RefreshDatabase;
    public function test_reporte_estudios_tiene_accion_ver_detalle(): void
    {
        $admin = User::factory()->create();
        $admin->forceFill(['nivel' => RolUsuario::Admin, 'estado' => EstadoUsuario::Activo])->save();
        $estudio = User::factory()->create(['name' => 'Estudio Reporte']);
        $estudio->forceFill(['nivel' => RolUsuario::Contractor, 'estado' => EstadoUsuario::Activo])->save();

        $this->actingAs($admin)
            ->get('/admin/reporte-estudios')
            ->assertOk()
            ->assertSee('Estudio Reporte')
            ->assertSee('Ver detalle');
    }
}
