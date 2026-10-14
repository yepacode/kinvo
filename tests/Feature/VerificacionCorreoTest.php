<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * La verificación de correo vive detrás de un interruptor.
 *
 * Encenderla sin que los correos lleguen de verdad deja fuera a todo el que
 * se registre: no recibe el enlace y no puede entrar. Hoy el dominio no tiene
 * SPF ni DMARC, así que nace apagada y se enciende desde el panel el día que
 * los correos lleguen.
 */
class VerificacionCorreoTest extends TestCase
{
    use RefreshDatabase;

    private function coach(array $extra = []): User
    {
        $u = User::factory()->create($extra);
        $u->forceFill([
            'nivel' => RolUsuario::Professional,
            'estado' => EstadoUsuario::Activo,
        ])->save();

        return $u;
    }

    private function interruptor(bool $activa): void
    {
        SiteSetting::set('verificacion_email_activa', $activa ? '1' : '0');
    }

    /** Apagado (como hoy): nadie queda bloqueado. */
    public function test_apagado_no_bloquea_a_nadie(): void
    {
        $this->interruptor(false);

        $user = $this->coach(['email_verified_at' => null]);

        $this->assertTrue($user->hasVerifiedEmail(), 'con el interruptor apagado se da por verificado');
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    /** Apagado: tampoco se manda el correo de verificación al registrarse. */
    public function test_apagado_no_manda_correo_de_verificacion(): void
    {
        Notification::fake();
        $this->interruptor(false);

        $this->post(route('register'), [
            'name' => 'Coach Sin Verificar',
            'email' => 'sin.verificar@kinvoo.test',
            'password' => 'QaKinvoo!2026',
            'password_confirmation' => 'QaKinvoo!2026',
            'tipo' => 'professional',
            'acepta_legales' => '1',
        ]);

        Notification::assertNothingSent();
    }

    /** Encendido: quien no ha confirmado no pasa al panel. */
    public function test_encendido_bloquea_hasta_confirmar(): void
    {
        $this->interruptor(true);

        $user = $this->coach(['email_verified_at' => null]);

        $this->assertFalse($user->hasVerifiedEmail());
        $this->actingAs($user)->get('/dashboard')->assertRedirect(route('verification.notice'));
    }

    /** Encendido: quien ya confirmó entra con normalidad. */
    public function test_encendido_deja_pasar_a_quien_ya_confirmo(): void
    {
        $this->interruptor(true);

        $user = $this->coach(['email_verified_at' => now()]);

        $this->assertTrue($user->hasVerifiedEmail());
        $this->actingAs($user)->get('/dashboard')->assertOk();
    }

    /**
     * Lo que protege la migración: quien ya estaba registrado antes de
     * encender el interruptor no se queda fuera, porque su correo quedó
     * marcado con la fecha de su alta.
     */
    public function test_quien_ya_estaba_registrado_no_se_queda_fuera(): void
    {
        // Alguien de siempre, con su correo al día por la migración.
        $veterano = $this->coach(['email_verified_at' => now()->subMonths(3)]);

        $this->interruptor(true);

        $this->actingAs($veterano)->get('/dashboard')->assertOk();
    }

    /** Encendido: al registrarse sí llega el correo con el enlace. */
    public function test_encendido_manda_el_correo_al_registrarse(): void
    {
        Notification::fake();
        $this->interruptor(true);

        $this->post(route('register'), [
            'name' => 'Coach Por Verificar',
            'email' => 'por.verificar@kinvoo.test',
            'password' => 'QaKinvoo!2026',
            'password_confirmation' => 'QaKinvoo!2026',
            'tipo' => 'professional',
            'acepta_legales' => '1',
        ]);

        $user = User::where('email', 'por.verificar@kinvoo.test')->first();
        $this->assertNotNull($user, 'la cuenta se crea igual');
        Notification::assertSentTo($user, VerifyEmail::class);
    }
}
