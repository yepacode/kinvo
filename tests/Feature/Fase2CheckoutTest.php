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

        // Primera llamada al webhook: crea el pago.
        $r1 = $this->call('POST', route('billing.webhook'),
            [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $r1->assertOk();
        $this->assertSame(1, Payment::where('provider_payment_id', 'pay_wh_test_1')->count());

        // Segunda llamada con el MISMO payment id: NO duplica.
        $r2 = $this->call('POST', route('billing.webhook'),
            [], [], [], ['CONTENT_TYPE' => 'application/json'], $payload);
        $r2->assertOk();
        $this->assertSame(1, Payment::where('provider_payment_id', 'pay_wh_test_1')->count());
    }

    public function test_webhook_con_firma_invalida_devuelve_400(): void
    {
        // FakeGateway solo valida que sea JSON parseable; enviamos texto plano.
        $r = $this->call('POST', route('billing.webhook'),
            [], [], [], ['CONTENT_TYPE' => 'application/json'], 'no es json valido {');
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
}
