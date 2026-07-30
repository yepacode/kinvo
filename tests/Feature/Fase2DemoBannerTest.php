<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 2 · Banner "Cuenta de demostración" solo se muestra a usuarios
 * cuyo email comienza con demo.f2.* Los reales NO deben verlo.
 */
class Fase2DemoBannerTest extends TestCase
{
    use RefreshDatabase;

    private function coachActivo(string $email): User
    {
        $u = User::factory()->create(['email' => $email]);
        $u->forceFill([
            'nivel' => RolUsuario::Professional,
            'estado' => EstadoUsuario::Activo,
        ])->save();
        return $u;
    }

    public function test_user_real_no_ve_el_banner_demo(): void
    {
        $u = $this->coachActivo('coach.real@example.com');

        $this->actingAs($u)
            ->get('/dashboard')
            ->assertOk()
            ->assertDontSee('Cuenta de demostración');
    }

    public function test_user_demo_ve_el_banner(): void
    {
        $u = $this->coachActivo('demo.f2.coach.ana@kinvoo.test');

        $this->actingAs($u)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Cuenta de demostración');
    }

    public function test_helper_esDemoFase2_detecta_el_prefijo_correctamente(): void
    {
        $real = User::factory()->create(['email' => 'ana@example.com']);
        $demoF2 = User::factory()->create(['email' => 'demo.f2.coach.ana@kinvoo.test']);
        $demoOtro = User::factory()->create(['email' => 'demo.coach@gokinvoo.com']); // NO es F2

        $this->assertFalse($real->esDemoFase2());
        $this->assertTrue($demoF2->esDemoFase2());
        $this->assertFalse($demoOtro->esDemoFase2(), 'Solo demo.f2.* debe activar el banner');
    }
}
