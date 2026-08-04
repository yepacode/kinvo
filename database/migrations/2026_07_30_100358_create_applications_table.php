<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 · Postulaciones a las ofertas de trabajo.
 *
 * Estados:
 *   - 'submitted' → recién enviada por el profesional
 *   - 'seen'      → el estudio la abrió
 *   - 'in_contact'→ estudio marcó "me interesa" y contactó
 *   - 'accepted'  → contratación cerrada
 *   - 'rejected'  → estudio descartó
 *   - 'withdrawn' → profesional retiró su postulación
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_id')->constrained('offers')->cascadeOnDelete();
            $table->foreignId('professional_user_id')->constrained('users')->cascadeOnDelete();

            $table->text('cover_letter')->nullable();
            $table->string('status', 20)->default('submitted');
            $table->timestamp('status_changed_at')->nullable();
            $table->text('notes')->nullable(); // notas privadas del estudio

            $table->timestamps();

            // Un profesional solo puede postular una vez a la misma oferta.
            $table->unique(['offer_id', 'professional_user_id']);
            $table->index(['professional_user_id', 'status']);
            $table->index(['offer_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
