<?php

namespace Tests\Feature\Auth;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_professional_registers_as_pending_and_is_redirected(): void
    {
        $response = $this->post('/register', [
            'name' => 'Coach Ana',
            'tipo' => 'professional',
            'email' => 'coach@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'acepta_legales' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('account.pending'));

        $user = User::where('email', 'coach@example.com')->first();
        $this->assertSame(RolUsuario::Professional, $user->nivel);
        $this->assertSame(EstadoUsuario::Pendiente, $user->estado);
    }

    public function test_contractor_registers_with_contractor_role(): void
    {
        $this->post('/register', [
            'name' => 'Gimnasio X',
            'tipo' => 'contractor',
            'email' => 'gym@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'acepta_legales' => '1',
        ]);

        $user = User::where('email', 'gym@example.com')->first();
        $this->assertSame(RolUsuario::Contractor, $user->nivel);
        $this->assertSame(EstadoUsuario::Pendiente, $user->estado);
    }

    public function test_registration_requires_accepting_legal_terms(): void
    {
        $response = $this->post('/register', [
            'name' => 'Sin Aceptar',
            'tipo' => 'professional',
            'email' => 'legal@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            // acepta_legales ausente
        ]);

        $response->assertSessionHasErrors('acepta_legales');
        $this->assertGuest();
        $this->assertNull(User::where('email', 'legal@example.com')->first());
    }

    public function test_registration_requires_a_valid_tipo(): void
    {
        $response = $this->post('/register', [
            'name' => 'Sin Tipo',
            'tipo' => 'admin', // no permitido desde registro público
            'email' => 'notipo@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertSessionHasErrors('tipo');
        $this->assertGuest();
    }

    public function test_pending_user_is_blocked_from_dashboard(): void
    {
        $pending = User::factory()->create([
            'nivel' => RolUsuario::Professional,
            'estado' => EstadoUsuario::Pendiente,
        ]);

        $this->actingAs($pending)
            ->get('/dashboard')
            ->assertRedirect(route('account.pending'));
    }

    public function test_active_user_reaches_dashboard(): void
    {
        $active = User::factory()->create([
            'nivel' => RolUsuario::Professional,
            'estado' => EstadoUsuario::Activo,
        ]);

        $this->actingAs($active)
            ->get('/dashboard')
            ->assertStatus(200);
    }
}
