<?php

namespace Tests\Feature;

use App\Enums\EstadoUsuario;
use App\Enums\RolUsuario;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 2 · Flujo end-to-end de checkout con FakeGateway.
 * Cubre: iniciar checkout → simular pago exitoso → suscripción activa +
 * membresía + pago registrado + webhook idempotente.
 */
class Fase2CheckoutTest extends TestCase
{
    use RefreshDatabase;

    /** Firma un payload como lo haría la pasarela real. */
    private function firma(string $payload): string
    {
        return 'sha256='.hash_hmac('sha256', $payload, (string) config('billing.webhook_secret'));
    }

    private function contratante(): User
    {
        $u = User::factory()->create(['email' => 'coach.estudio@test.com']);
        $u->forceFill([
            'nivel' => RolUsuario::Contractor,
            'estado' => EstadoUsuario::Activo,
        ])->save();
        return $u;
    }

    private function profesional(): User
    {
        $u = User::factory()->create(['email' => 'coach.pro@test.com']);
        $u->forceFill([
            'nivel' => RolUsuario::Professional,
            'estado' => EstadoUsuario::Activo,
        ])->save();
        return $u;
    }

    private function planEstudio(): Plan
    {
        return Plan::create([
            'nombre' => 'Plan Estudio Test',
            'audiencia' => 'estudio',
            'precio' => 199,
            'moneda' => 'MXN',
            'periodo' => 'mensual',
            'activo' => true,
            'orden' => 1,
        ]);
    }

    public function test_iniciar_checkout_crea_suscripcion_incomplete_y_redirige(): void
    {
        $user = $this->contratante();
        $plan = $this->planEstudio();

        $r = $this->actingAs($user)->post(route('billing.start', $plan));

        $r->assertRedirect();
        $this->assertStringContainsString('/billing/fake-checkout/', $r->headers->get('Location'));

        // Se creó suscripción en estado incomplete.
        $sub = Subscription::where('user_id', $user->id)->first();
        $this->assertNotNull($sub);
        $this->assertSame(Subscription::STATUS_INCOMPLETE, $sub->status);
        $this->assertSame('fake', $sub->provider);
    }

    /**
     * Regresión · feedback Karla 13-10-2026.
     *
     * Un intento de pago sin completar NO debe impedir volver a intentarlo.
     * Antes la INCOMPLETE bloqueaba durante 30 minutos, así que alguien que
     * abandonaba el checkout y lo reintentaba enseguida se topaba con
     * "Ya tienes una suscripción activa" — pese a no tener acceso a nada.
     */
    public function test_reintentar_el_pago_tras_abandonar_el_checkout(): void
    {
        $user = $this->contratante();
        $plan = $this->planEstudio();

        // Primer intento: se anota la suscripción y se va a la pasarela.
        $this->actingAs($user)->post(route('billing.start', $plan))->assertRedirect();
        $primera = Subscription::where('user_id', $user->id)->firstOrFail();
        $this->assertSame(Subscription::STATUS_INCOMPLETE, $primera->status);

        // Segundo intento inmediato (la persona se arrepintió y volvió).
        $r = $this->actingAs($user)->post(route('billing.start', $plan));

        $r->assertRedirect();
        $this->assertStringContainsString('/billing/fake-checkout/', $r->headers->get('Location'));
        $this->assertNotSame('ya-tienes-suscripcion', session('status'));

        // La anterior quedó cerrada y hay exactamente una en curso.
        $this->assertSame(Subscription::STATUS_CANCELED, $primera->refresh()->status);
        $this->assertSame(1, Subscription::where('user_id', $user->id)
            ->where('status', Subscription::STATUS_INCOMPLETE)->count());
    }

    /** Una suscripción ACTIVA sí debe impedir contratar otra (doble cobro). */
    public function test_suscripcion_activa_si_bloquea_contratar_otra(): void
    {
        $user = $this->contratante();
        $plan = $this->planEstudio();

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'provider' => 'fake',
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_end' => now()->addMonth(),
        ]);

        $this->actingAs($user)
            ->post(route('billing.start', $plan))
            ->assertSessionHas('status', 'ya-tienes-suscripcion');
    }

    public function test_plan_de_estudio_no_es_para_profesional(): void
    {
        $user = $this->profesional();
        $plan = $this->planEstudio();

        $r = $this->actingAs($user)->post(route('billing.start', $plan));

        // Redirige de vuelta con status (no crea suscripción).
        $r->assertRedirect();
        $r->assertSessionHas('status', 'plan-no-es-para-tu-rol');
        $this->assertSame(0, Subscription::where('user_id', $user->id)->count());
    }

    public function test_confirmar_pago_activa_suscripcion_y_membresia_y_crea_payment(): void
    {
        $user = $this->contratante();
        $plan = $this->planEstudio();
        $this->actingAs($user)->post(route('billing.start', $plan));

        $sub = Subscription::where('user_id', $user->id)->first();
        $token = str_replace('fake_sub_', '', $sub->provider_subscription_id);

        $r = $this->actingAs($user)->post(url("/billing/fake-checkout/{$token}/confirmar"));
        $r->assertRedirect();

        $sub->refresh();
        $user->refresh();

        $this->assertSame(Subscription::STATUS_ACTIVE, $sub->status);
        $this->assertNotNull($sub->current_period_end);
        $this->assertTrue($sub->current_period_end->isFuture());

        // La membresía del user se activó.
        $this->assertSame($plan->id, $user->membership_plan_id);
        $this->assertTrue($user->tieneMembresiaActiva());

        // Se registró exactamente 1 pago exitoso.
        $payments = Payment::where('user_id', $user->id)->get();
        $this->assertCount(1, $payments);
        $this->assertSame(Payment::STATUS_SUCCEEDED, $payments->first()->status);
        $this->assertSame(19900, $payments->first()->amount_cents);
    }

    public function test_webhook_payment_succeeded_es_idempotente(): void
    {
        $user = $this->contratante();
        $plan = $this->planEstudio();
        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'provider' => 'fake',
            'provider_subscription_id' => 'sub_test_wh',
            'status' => Subscription::STATUS_INCOMPLETE,
        ]);

        $payload = json_encode([
            'type' => 'payment_succeeded',
            'data' => [
                'object' => [
                    'id' => 'pay_wh_test_1',
                    'subscription' => 'sub_test_wh',
                    'amount' => 19900,
                    'currency' => 'MXN',
                ],
            ],
        ]);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_SIGNATURE' => $this->firma($payload),
        ];

        // Primera llamada al webhook: crea el pago.
        $r1 = $this->call('POST', route('billing.webhook'),
            [], [], [], $headers, $payload);
        $r1->assertOk();
        $this->assertSame(1, Payment::where('provider_payment_id', 'pay_wh_test_1')->count());

        // Segunda llamada con el MISMO payment id: NO duplica.
        $r2 = $this->call('POST', route('billing.webhook'),
            [], [], [], $headers, $payload);
        $r2->assertOk();
        $this->assertSame(1, Payment::where('provider_payment_id', 'pay_wh_test_1')->count());
    }

    public function test_webhook_con_firma_invalida_devuelve_400(): void
    {
        // Payload JSON válido pero SIN firma HMAC correcta → rechazado.
        $r = $this->call('POST', route('billing.webhook'),
            [], [], [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_SIGNATURE' => 'sha256=firmafalsa'],
            '{"type":"payment_succeeded"}');
        $r->assertStatus(400);
    }

    public function test_gate_membresia_activa_bloquea_sin_suscripcion(): void
    {
        // Nueva ruta protegida por membresia.activa (test-only)
        \Route::middleware(['web', 'auth', 'membresia.activa'])
            ->get('/_test/premium', fn () => 'ok')->name('_test.premium');

        $user = $this->contratante();

        // Sin suscripción → redirige a /membresias
        $this->actingAs($user)->get('/_test/premium')
            ->assertRedirect(route('membresias.index'));

        // Con suscripción activa → pasa
        Subscription::create([
            'user_id' => $user->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_end' => now()->addMonth(),
        ]);
        $this->actingAs($user)->get('/_test/premium')->assertOk();
    }

    /**
     * Regresión · diagnóstico 14-10-2026.
     *
     * Cancelar desde el panel sólo escribía `subscriptions.status`. La web lee
     * el campo denormalizado `users.membership_expires_at`, así que quedaban
     * dos verdades: la suscripción decía "Cancelada" y /membresias seguía
     * diciendo "MEMBRESÍA ACTIVA" — y sin plan alguno a la vista, nadie podía
     * pagar. Ahora la membresía se recalcula sola al guardar la suscripción.
     */
    public function test_cancelar_la_suscripcion_retira_la_membresia_del_usuario(): void
    {
        $user = $this->contratante();
        $plan = $this->planEstudio();

        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'provider' => 'fake',
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_end' => now()->addMonth(),
        ]);

        // El observer la concedió al crearse la suscripción activa.
        $this->assertTrue($user->refresh()->tieneMembresiaActiva());
        $this->assertSame($plan->id, $user->membership_plan_id);

        // Cancelación con corte inmediato, como la del panel.
        $sub->forceFill([
            'status' => Subscription::STATUS_CANCELED,
            'canceled_at' => now(),
            'current_period_end' => now()->subSecond(),
        ])->save();

        $user->refresh();
        $this->assertFalse($user->tieneMembresiaActiva());
        $this->assertNull($user->membership_plan_id);
        $this->assertNull($user->membership_expires_at);
    }

    /** Cancelar sin cortar el acceso: conserva lo pagado y deja recontratar. */
    public function test_cancelada_dentro_del_periodo_conserva_acceso_pero_permite_contratar(): void
    {
        $user = $this->contratante();
        $plan = $this->planEstudio();

        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'provider' => 'fake',
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_end' => now()->addMonth(),
        ]);
        $sub->forceFill([
            'status' => Subscription::STATUS_CANCELED,
            'canceled_at' => now(),
        ])->save();

        $user->refresh();
        $this->assertTrue($user->tieneMembresiaActiva(), 'el periodo pagado sigue dando acceso');
        $this->assertFalse($user->tieneSuscripcionEnCurso(), 'ya no hay cobro en curso');

        // Y el checkout la acepta: botón y backend dicen lo mismo.
        $this->actingAs($user)->post(route('billing.start', $plan))
            ->assertRedirect();
        $this->assertNotSame('ya-tienes-suscripcion', session('status'));
    }

    /**
     * La página de membresías ofrece planes en cuanto no hay cobro en curso.
     * Antes los escondía mirando la membresía, y una desincronización dejaba
     * la página sin un solo plen contratable.
     */
    public function test_membresias_ofrece_planes_tras_cancelar(): void
    {
        $user = $this->contratante();
        $plan = $this->planEstudio();

        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'provider' => 'fake',
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_end' => now()->addMonth(),
        ]);

        // Con suscripción en curso NO se ofrece contratar.
        $this->actingAs($user)->get(route('membresias.index'))
            ->assertOk()
            ->assertDontSee(route('billing.start', $plan), escape: false);

        $sub->forceFill([
            'status' => Subscription::STATUS_CANCELED,
            'canceled_at' => now(),
        ])->save();

        // Cancelada: el plan vuelve a estar a la vista.
        $this->actingAs($user)->get(route('membresias.index'))
            ->assertOk()
            ->assertSee(route('billing.start', $plan), escape: false);
    }

    /** Una membresía dada de alta a mano (sin suscripción) no se borra sola. */
    public function test_el_alta_manual_de_membresia_sobrevive_a_la_sincronizacion(): void
    {
        $user = $this->contratante();
        $plan = $this->planEstudio();
        $otro = Plan::create([
            'nombre' => 'Otro plan', 'audiencia' => 'estudio', 'precio' => 99,
            'moneda' => 'MXN', 'periodo' => 'mensual', 'activo' => true, 'orden' => 2,
        ]);

        // El panel le regala 6 meses del plan $otro, sin suscripción detrás.
        $user->forceFill([
            'membership_plan_id' => $otro->id,
            'membership_expires_at' => now()->addMonths(6),
        ])->save();

        // Se cancela una suscripción de OTRO plan: no debe tocar el alta manual.
        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'provider' => 'fake',
            'status' => Subscription::STATUS_CANCELED,
            'canceled_at' => now(),
            'current_period_end' => now()->subDay(),
        ]);

        $user->refresh();
        $this->assertSame($otro->id, $user->membership_plan_id);
        $this->assertTrue($user->tieneMembresiaActiva());
    }

    /** Seguridad MED-2 · una past_due sin fecha de fin no da acceso eterno. */
    public function test_past_due_sin_periodo_no_da_acceso(): void
    {
        $user = $this->contratante();
        $plan = $this->planEstudio();

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'provider' => 'fake',
            'status' => Subscription::STATUS_PAST_DUE,
            'current_period_end' => null,
        ]);

        $this->assertFalse($user->refresh()->tieneMembresiaActiva());
    }
}
