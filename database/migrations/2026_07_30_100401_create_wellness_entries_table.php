<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 · Expediente de cuidado del coach.
 *
 * El admin alimenta este expediente manualmente desde el panel: cada consulta
 * de telemedicina, sesión de fisio, charla asistida o renovación de seguro
 * queda como una entrada.
 *
 * El coach ve su propio expediente en /mi-expediente como timeline.
 *
 * `type`: telemedicine|physio|talk|insurance|other
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wellness_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('professional_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('created_by_admin_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('type', 20);
            $table->date('occurred_on');
            $table->string('provider')->nullable(); // médico, fisio, ponente, aseguradora
            $table->text('notes')->nullable();

            // Para 'insurance' — fecha de vigencia
            $table->date('valid_until')->nullable();

            $table->timestamps();

            $table->index(['professional_user_id', 'occurred_on']);
            $table->index(['type', 'occurred_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wellness_entries');
    }
};
