<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H6 · Matriz de beneficios: 2 tablas nuevas
 *
 *  benefit_requests  — Solicitudes de "Respaldo" del coach (telemedicina o
 *                      fisioterapia). El coach envía, el admin gestiona.
 *  pulse_responses   — Encuesta de Pulso Kinvoo: el coach contesta,
 *                      el estudio ve resultados agregados de su equipo.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('benefit_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('type', 20); // telemedicine | physio
            $table->text('note')->nullable();
            $table->string('preferred_slot', 200)->nullable();
            $table->string('status', 20)->default('pending');
                // pending | scheduled | done | cancelled
            $table->timestamp('scheduled_for')->nullable();
            $table->text('admin_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['status', 'created_at']);
        });

        Schema::create('pulse_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            // Se guarda el estudio en el momento de la respuesta para reportes
            // — así el estudio ve resultados aunque el coach salga del equipo.
            $table->foreignId('contractor_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            // 1-5 estrellas: cómo se siente el coach este período.
            $table->unsignedTinyInteger('rating');
            // 3 respuestas libres cortas (opcionales).
            $table->string('answer_energy', 500)->nullable();
            $table->string('answer_growth', 500)->nullable();
            $table->string('answer_support', 500)->nullable();
            $table->date('period_start')->nullable();
            $table->timestamps();

            $table->index(['contractor_user_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pulse_responses');
        Schema::dropIfExists('benefit_requests');
    }
};
