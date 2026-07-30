<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fase 2 · Gestión de equipo del estudio.
 *
 * Un estudio (Contractor) arma su equipo agregando profesionales por email.
 * El profesional recibe una invitación y puede aceptarla o rechazarla.
 *
 * Estados:
 *   - 'invited'   → estudio invitó, profesional aún no respondió
 *   - 'active'    → profesional aceptó y forma parte del equipo
 *   - 'declined'  → profesional rechazó
 *   - 'removed'   → estudio o profesional lo sacaron del equipo
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contractor_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('professional_user_id')->constrained('users')->cascadeOnDelete();

            $table->string('status', 20)->default('invited');
            $table->timestamp('invited_at')->useCurrent();
            $table->timestamp('joined_at')->nullable();
            $table->timestamp('left_at')->nullable();

            $table->timestamps();

            // Una sola relación por par; si se remueve y re-invita, se re-usa la fila.
            $table->unique(['contractor_user_id', 'professional_user_id']);
            $table->index(['professional_user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_members');
    }
};
