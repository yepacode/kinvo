<?php

namespace Tests\Feature\Auth;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Mail\BienvenidaEstudio;
use App\Mail\BienvenidaTalento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
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
            'password' => 'Str0ng!Pass',
            'password_confirmation' => 'Str0ng!Pass',
            'acepta_legales' => '1',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect(route('professional.bienvenida')); // arranca el wizard

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
            'password' => 'Str0ng!Pass',
            'password_confirmation' => 'Str0ng!Pass',
            'acepta_legales' => '1',
        ]);

        $user = User::where('email', 'gym@example.com')->first();
        $this->assertSame(RolUsuario::Contractor, $user->nivel);
        $this->assertSame(EstadoUsuario::Pendiente, $user->estado);
    }

    public function test_registration_rechaza_contrasena_debil(): void
    {
        $response = $this->post('/register', [
            'name' => 'Débil',
            'tipo' => 'professional',
            'email' => 'debil@example.com',
            'password' => 'password', // débil: sin mayúsculas, números ni símbolos
            'password_confirmation' => 'password',
            'acepta_legales' => '1',
        ]);

        $response->assertSessionHasErrors('password');
        $this->assertGuest();
    }

    public function test_registration_requires_accepting_legal_terms(): void
    {
        $response = $this->post('/register', [
            'name' => 'Sin Aceptar',
            'tipo' => 'professional',
            'email' => 'legal@example.com',
            'password' => 'Str0ng!Pass',
            'password_confirmation' => 'Str0ng!Pass',
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
            'password' => 'Str0ng!Pass',
            'password_confirmation' => 'Str0ng!Pass',
        ]);

        $response->assertSessionHasErrors('tipo');
        $this->assertGuest();
    }

    public function test_registro_de_talento_envia_correo_de_bienvenida(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Coach Ana',
            'tipo' => 'professional',
            'email' => 'coach.mail@example.com',
            'password' => 'Str0ng!Pass',
            'password_confirmation' => 'Str0ng!Pass',
            'acepta_legales' => '1',
        ]);

        Mail::assertSent(BienvenidaTalento::class, function ($mail) {
            return $mail->hasTo('coach.mail@example.com');
        });
        Mail::assertNotSent(BienvenidaEstudio::class);
    }

    public function test_registro_de_estudio_envia_correo_de_bienvenida(): void
    {
        Mail::fake();

        $this->post('/register', [
            'name' => 'Gym Norte',
            'tipo' => 'contractor',
            'email' => 'gym.mail@example.com',
            'password' => 'Str0ng!Pass',
            'password_confirmation' => 'Str0ng!Pass',
            'acepta_legales' => '1',
        ]);

        Mail::assertSent(BienvenidaEstudio::class, function ($mail) {
            return $mail->hasTo('gym.mail@example.com');
        });
        Mail::assertNotSent(BienvenidaTalento::class);
    }

    public function test_fallo_del_correo_no_rompe_el_registro(): void
    {
        // Simula un fallo total del sistema de mail (ej. queue caída, SMTP muerto).
        // El try/catch en RegisteredUserController debe absorberlo y dejar entrar al usuario.
        Mail::shouldReceive('to')->andThrow(new \RuntimeException('mail down'));

        $this->post('/register', [
            'name' => 'Coach Resiliente',
            'tipo' => 'professional',
            'email' => 'resiliente@example.com',
            'password' => 'Str0ng!Pass',
            'password_confirmation' => 'Str0ng!Pass',
            'acepta_legales' => '1',
        ])->assertRedirect(route('professional.bienvenida'));

        $this->assertAuthenticated();
        $this->assertNotNull(User::where('email', 'resiliente@example.com')->first());
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
