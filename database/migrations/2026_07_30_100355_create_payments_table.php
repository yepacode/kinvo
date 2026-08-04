<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 · Historial de cobros individuales.
 *
 * Cada intento de cobro (exitoso, fallido, reembolsado) queda como una fila
 * inmutable, para poder auditar contablemente. Nunca se editan filas — solo
 * se agregan.
 *
 * Estados:
 *   - 'pending'   → creado, esperando webhook
 *   - 'succeeded' → cobro exitoso
 *   - 'failed'    → cobro rechazado por la pasarela
 *   - 'refunded'  → reembolsado total
 *   - 'partial_refund' → reembolso parcial
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_id')->nullable()->constrained('subscriptions')->nullOnDelete();

            $table->string('provider', 20)->default('stripe');
            $table->string('provider_payment_id')->nullable()->unique();
            $table->string('provider_invoice_id')->nullable()->index();

            // Monto en la unidad menor (cents) para evitar redondeos.
            $table->unsignedBigInteger('amount_cents');
            $table->string('currency', 3)->default('MXN');

            $table->string('status', 20)->default('pending');
            $table->string('failure_code')->nullable();
            $table->string('failure_message')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
