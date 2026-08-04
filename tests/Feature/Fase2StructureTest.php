<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\AuditLog;
use App\Models\ContentItem;
use App\Models\Offer;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\TeamMember;
use App\Models\User;
use App\Models\WellnessEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Fase 2 · Verifica que la estructura nueva (7 tablas + relaciones) migra
 * limpio, los constants de estado existen y las relaciones básicas cargan.
 * Actúa como smoke test antes de escribir la lógica de cobros/webhooks.
 */
class Fase2StructureTest extends TestCase
{
    use RefreshDatabase;

    public function test_tablas_nuevas_migraron(): void
    {
        foreach ([
            'subscriptions', 'payments', 'offers', 'applications',
            'content_items', 'wellness_entries', 'team_members', 'audit_logs',
        ] as $tabla) {
            $this->assertTrue(
                \Schema::hasTable($tabla),
                "La tabla '$tabla' no existe."
            );
        }
    }

    public function test_plans_tiene_columnas_de_pasarela(): void
    {
        foreach (['provider_price_id', 'is_recurring', 'interval'] as $col) {
            $this->assertTrue(
                \Schema::hasColumn('plans', $col),
                "plans no tiene la columna '$col'."
            );
        }
    }

    public function test_subscription_relaciona_user_y_plan(): void
    {
        $user = User::factory()->create();
        $plan = Plan::create(['nombre' => 'Test Plan', 'audiencia' => 'talento', 'orden' => 1]);

        $sub = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => Subscription::STATUS_ACTIVE,
            'current_period_end' => now()->addMonth(),
        ]);

        $this->assertSame($user->id, $sub->user->id);
        $this->assertSame($plan->id, $sub->plan->id);
        $this->assertTrue($sub->estaVigente());
    }

    public function test_payment_relaciona_user_y_subscription(): void
    {
        $user = User::factory()->create();
        $sub = Subscription::create([
            'user_id' => $user->id,
            'status' => Subscription::STATUS_ACTIVE,
        ]);
        $payment = Payment::create([
            'user_id' => $user->id,
            'subscription_id' => $sub->id,
            'amount_cents' => 19900,
            'currency' => 'MXN',
            'status' => Payment::STATUS_SUCCEEDED,
            'paid_at' => now(),
        ]);

        $this->assertSame($user->id, $payment->user->id);
        $this->assertSame($sub->id, $payment->subscription->id);
        $this->assertSame('199.00 MXN', $payment->montoFormateado());
    }

    public function test_offer_genera_slug_automatico_y_es_unico(): void
    {
        $studio = User::factory()->contratante()->create();

        $o1 = Offer::create([
            'contractor_user_id' => $studio->id,
            'title' => 'Coach de yoga fin de semana',
            'description' => 'Buscamos coach para sábados.',
            'status' => Offer::STATUS_DRAFT,
        ]);
        $o2 = Offer::create([
            'contractor_user_id' => $studio->id,
            'title' => 'Coach de yoga fin de semana',
            'description' => 'Otra oferta similar.',
            'status' => Offer::STATUS_DRAFT,
        ]);

        $this->assertSame('coach-de-yoga-fin-de-semana', $o1->slug);
        $this->assertSame('coach-de-yoga-fin-de-semana-2', $o2->slug);
    }

    public function test_application_actualiza_contador_del_offer(): void
    {
        $studio = User::factory()->contratante()->create();
        $offer = Offer::create([
            'contractor_user_id' => $studio->id,
            'title' => 'Test', 'description' => 'x', 'status' => Offer::STATUS_PUBLISHED,
        ]);
        $coach = User::factory()->create();

        Application::create([
            'offer_id' => $offer->id,
            'professional_user_id' => $coach->id,
            'status' => Application::STATUS_SUBMITTED,
        ]);

        $this->assertSame(1, $offer->fresh()->applications_count);
    }

    public function test_application_no_duplica_para_mismo_offer_mismo_profesional(): void
    {
        $studio = User::factory()->contratante()->create();
        $offer = Offer::create([
            'contractor_user_id' => $studio->id,
            'title' => 'X', 'description' => 'y', 'status' => Offer::STATUS_PUBLISHED,
        ]);
        $coach = User::factory()->create();

        Application::create([
            'offer_id' => $offer->id,
            'professional_user_id' => $coach->id,
            'status' => Application::STATUS_SUBMITTED,
        ]);

        // Segundo intento debe fallar por unique constraint.
        $this->expectException(\Illuminate\Database\QueryException::class);
        Application::create([
            'offer_id' => $offer->id,
            'professional_user_id' => $coach->id,
            'status' => Application::STATUS_SUBMITTED,
        ]);
    }

    public function test_content_item_gate_por_rol_y_por_plan(): void
    {
        $plan = Plan::create(['nombre' => 'Test Plan', 'audiencia' => 'talento', 'orden' => 1]);
        $publicItem = ContentItem::create([
            'title' => 'Público', 'type' => 'video', 'is_published' => true,
        ]);
        $proOnly = ContentItem::create([
            'title' => 'Solo talento', 'type' => 'video',
            'gate_role' => 'professional', 'is_published' => true,
        ]);
        $planLocked = ContentItem::create([
            'title' => 'Requiere plan', 'type' => 'video',
            'gate_plan_id' => $plan->id, 'is_published' => true,
        ]);

        $coach = User::factory()->create();
        $studio = User::factory()->contratante()->create();

        $this->assertTrue($publicItem->esAccesiblePor($coach));
        $this->assertTrue($publicItem->esAccesiblePor($studio));

        $this->assertTrue($proOnly->esAccesiblePor($coach));
        $this->assertFalse($proOnly->esAccesiblePor($studio));

        // Sin membresía del plan requerido, el item bloqueado no es accesible.
        $this->assertFalse($planLocked->esAccesiblePor($coach));
    }

    public function test_wellness_entry_relaciona_coach_y_admin(): void
    {
        $coach = User::factory()->create();
        $admin = User::factory()->admin()->create();

        $entry = WellnessEntry::create([
            'professional_user_id' => $coach->id,
            'created_by_admin_id' => $admin->id,
            'type' => WellnessEntry::TYPE_TELEMEDICINE,
            'occurred_on' => now(),
            'provider' => 'Dra. Sánchez',
        ]);

        $this->assertSame($coach->id, $entry->professional->id);
        $this->assertSame($admin->id, $entry->admin->id);
        $this->assertSame('Telemedicina', $entry->label());
    }

    public function test_team_member_estados_y_unicidad(): void
    {
        $studio = User::factory()->contratante()->create();
        $coach = User::factory()->create();

        $tm = TeamMember::create([
            'contractor_user_id' => $studio->id,
            'professional_user_id' => $coach->id,
            'status' => TeamMember::STATUS_INVITED,
            'invited_at' => now(),
        ]);
        $this->assertFalse($tm->esActivo());

        $tm->update(['status' => TeamMember::STATUS_ACTIVE, 'joined_at' => now()]);
        $this->assertTrue($tm->fresh()->esActivo());
    }

    public function test_audit_log_registra_accion_polimorfica(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->create();

        $log = AuditLog::record($admin, $user, 'suspended',
            old: ['estado' => 'active'],
            new: ['estado' => 'suspended']
        );

        $this->assertSame($admin->id, $log->actor_user_id);
        $this->assertSame(User::class, $log->subject_type);
        $this->assertSame($user->id, $log->subject_id);
        $this->assertSame('suspended', $log->action);
        $this->assertSame('active', $log->old['estado']);
    }

    public function test_esDemoFase2_detecta_prefijo(): void
    {
        $real = User::factory()->create(['email' => 'ana@example.com']);
        $demo = User::factory()->create(['email' => 'demo.f2.coach.ana@kinvoo.test']);

        $this->assertFalse($real->esDemoFase2());
        $this->assertTrue($demo->esDemoFase2());
    }
}
