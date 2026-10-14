<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Support\FalloDePasarela;
use App\Support\PulsoWebhook;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Revisión de raíz del circuito de pago · 14-10-2026.
 *
 * Cubre los puntos ciegos que hacían imposible saber por qué "no deja pagar":
 * la periodicidad repartida en dos campos, los cobros que se descartaban sin
 * dejar rastro, y el silencio absoluto cuando la pasarela no llamaba.
 */
class PasarelaDiagnosticoTest extends TestCase
{
    use RefreshDatabase;

    private function firma(string $payload): string
    {
        return 'sha256='.hash_hmac('sha256', $payload, (string) config('billing.webhook_secret'));
    }

    private function contratante(): User
    {
        $u = User::factory()->create(['email' => 'estudio.pasarela@test.com']);
        $u->forceFill([
            'nivel' => RolUsuario::Contractor,
            'estado' => EstadoUsuario::Activo,
        ])->save();

        return $u;
    }

    private function plan(array $extra = []): Plan
    {
        return Plan::create(array_merge([
            'nombre' => 'Plan Pasarela',
            'audiencia' => 'estudio',
            'precio' => 499,
            'moneda' => 'MXN',
            'periodo' => 'mensual',
            'activo' => true,
            'orden' => 1,
        ], $extra));
    }

    /**
     * La periodicidad vivía en dos campos: `periodo` ('mensual'|'anual'), que
     * es el que se edita y se enseña, e `interval` ('month'|'year'), que era
     * el que miraba la pasarela. Un plan marcado "Anual" con su `interval`
     * sin tocar se cobraba cada mes.
     */
    public function test_el_plan_anual_manda_sobre_el_campo_tecnico(): void
    {
        $anual = $this->plan(['periodo' => 'anual', 'interval' => 'month']);

        $this->assertTrue($anual->esAnual(), 'manda lo que dice la ficha del plan');
        $this->assertTrue($anual->vigenciaDesdeAhora()->gt(now()->addMonths(11)));

        $mensual = $this->plan(['nombre' => 'Mensual', 'periodo' => 'mensual', 'interval' => 'year']);
        $this->assertFalse($mensual->esAnual());
    }

    /** Un plan gratuito anual daba sólo un mes: comparaba contra 'year'. */
    public function test_el_plan_gratuito_anual_da_un_ano(): void
    {
        $user = $this->contratante();
        $plan = $this->plan(['nombre' => 'Gratis anual', 'precio' => 0, 'periodo' => 'anual']);

        $this->actingAs($user)->post(route('billing.start', $plan))->assertRedirect();

        $user->refresh();
        $this->assertNotNull($user->membership_expires_at);
        $this->assertTrue(
            $user->membership_expires_at->gt(now()->addMonths(11)),
            'un plan anual sin costo tiene que durar un año'
        );
    }

    /**
     * En los cobros recurrentes MercadoPago no siempre manda el id del
     * preapproval dentro del pago. Antes eso bastaba para descartar el evento
     * y perder el cobro; ahora se cae al `external_reference`, que sí viaja
     * siempre porque lo ponemos nosotros.
     */
    public function test_un_cobro_sin_id_de_suscripcion_se_vincula_por_referencia(): void
    {
        $user = $this->contratante();
        $plan = $this->plan();
        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'provider' => 'mercadopago',
            'provider_subscription_id' => 'preapproval_abc',
            'status' => Subscription::STATUS_INCOMPLETE,
        ]);

        $payload = json_encode([
            'type' => 'payment_succeeded',
            'data' => ['object' => [
                'id' => 'pago_sin_sub_1',
                'subscription' => '',                        // MP no lo mandó
                'external_reference' => 'kinvoo_sub_'.$sub->id,
                'amount' => 49900,
                'currency' => 'MXN',
            ]],
        ]);

        $this->call('POST', route('billing.webhook'), [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => $this->firma($payload)],
            $payload)->assertOk();

        $this->assertSame(1, Payment::where('provider_payment_id', 'pago_sin_sub_1')->count());
        $this->assertSame(Subscription::STATUS_ACTIVE, $sub->refresh()->status);
        $this->assertTrue($user->refresh()->tieneMembresiaActiva());
    }

    /** Y si de verdad no se puede vincular, queda anotado en lugar de perderse. */
    public function test_un_cobro_imposible_de_vincular_queda_registrado(): void
    {
        FalloDePasarela::limpiar();

        $payload = json_encode([
            'type' => 'payment_succeeded',
            'data' => ['object' => [
                'id' => 'pago_huerfano_1',
                'subscription' => 'no_existe',
                'amount' => 19900,
                'currency' => 'MXN',
            ]],
        ]);

        $this->call('POST', route('billing.webhook'), [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => $this->firma($payload)],
            $payload)->assertOk();

        // Seguimos sin crear el pago a ciegas…
        $this->assertSame(0, Payment::where('provider_payment_id', 'pago_huerfano_1')->count());

        // …pero ya no desaparece sin dejar rastro.
        $fallo = FalloDePasarela::ultimo();
        $this->assertNotNull($fallo);
        $this->assertStringContainsString('no se pudo vincular', $fallo['motivo']);
        $this->assertSame('pago_huerfano_1', $fallo['contexto']['pago']);
    }

    /**
     * El pulso del webhook distingue tres situaciones que antes eran la misma
     * cosa vista desde el panel: nunca llamaron, llamaron y se aceptó, y
     * llamaron pero la firma no cuadra.
     */
    public function test_el_pulso_distingue_aceptado_de_rechazado(): void
    {
        $this->assertFalse(PulsoWebhook::huboAlgunaLlamada());

        // Firma incorrecta.
        $this->call('POST', route('billing.webhook'), [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => 'sha256=mentira'],
            '{"type":"payment_succeeded"}')->assertStatus(400);

        $this->assertTrue(PulsoWebhook::huboAlgunaLlamada());
        $this->assertNotNull(PulsoWebhook::ultimoRechazado());
        $this->assertNull(PulsoWebhook::ultimoAceptado());

        // Firma correcta.
        $payload = json_encode(['type' => 'ignored', 'data' => ['object' => []]]);
        $this->call('POST', route('billing.webhook'), [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => $this->firma($payload)],
            $payload)->assertOk();

        $this->assertNotNull(PulsoWebhook::ultimoAceptado());
    }

    /** Un cobro de plan anual da acceso por un año, no por treinta días. */
    public function test_el_cobro_de_un_plan_anual_da_acceso_un_ano(): void
    {
        $user = $this->contratante();
        $plan = $this->plan(['periodo' => 'anual', 'precio' => 4990]);
        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'provider' => 'mercadopago',
            'provider_subscription_id' => 'preapproval_anual',
            'status' => Subscription::STATUS_INCOMPLETE,
        ]);

        $payload = json_encode([
            'type' => 'payment_succeeded',
            'data' => ['object' => [
                'id' => 'pago_anual_1',
                'subscription' => 'preapproval_anual',
                'amount' => 499000,
                'currency' => 'MXN',
            ]],
        ]);

        $this->call('POST', route('billing.webhook'), [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => $this->firma($payload)],
            $payload)->assertOk();

        $this->assertTrue(
            $sub->refresh()->current_period_end->gt(now()->addMonths(11)),
            'el periodo cobrado es el del plan, no un mes fijo'
        );
        $this->assertTrue($user->refresh()->membership_expires_at->gt(now()->addMonths(11)));
    }

    /**
     * Regresión · diagnóstico 14-10-2026.
     *
     * La moneda era un campo de texto libre en el panel. MercadoPago exige el
     * código ISO exacto en mayúsculas y rechaza 'mxn', 'MX' o cualquier otra
     * cosa con un error 400 — que no salía al guardar el plan, sino el día
     * que alguien intentaba pagarlo. Ahora se normaliza al guardar.
     */
    public function test_la_moneda_del_plan_se_normaliza(): void
    {
        $this->assertSame('MXN', $this->plan(['moneda' => ' mxn '])->moneda);
        $this->assertSame('MXN', $this->plan(['nombre' => 'B', 'moneda' => ''])->moneda);
        $this->assertSame('COP', $this->plan(['nombre' => 'C', 'moneda' => 'cop'])->moneda);
    }
}
