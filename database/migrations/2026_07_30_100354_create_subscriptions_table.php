<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 · Suscripciones a planes.
 *
 * Estados posibles (guardados como string por portabilidad SQLite/Postgres):
 *   - 'trialing'   → periodo de prueba (si aplica)
 *   - 'active'     → suscripción vigente
 *   - 'past_due'   → un cobro falló, en reintentos
 *   - 'canceled'   → cancelada por user/admin, acceso hasta current_period_end
 *   - 'unpaid'     → todos los reintentos fallaron, sin acceso
 *   - 'incomplete' → checkout iniciado pero pago no confirmado
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();

            $table->string('provider', 20)->default('stripe');
            $table->string('provider_subscription_id')->nullable()->unique();
            $table->string('provider_customer_id')->nullable()->index();

            $table->string('status', 20)->default('incomplete');
            $table->timestamp('current_period_start')->nullable();
            $table->timestamp('current_period_end')->nullable();
            $table->timestamp('canceled_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'current_period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
