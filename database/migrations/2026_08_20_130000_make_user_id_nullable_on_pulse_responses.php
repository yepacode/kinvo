<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Punto 16 (ago-2026): el admin puede registrar respuestas de Pulso MANUALMENTE
 * (calificación + comentarios/notas) para un estudio, sin atarlas necesariamente
 * a un coach. Por eso `user_id` pasa a ser nullable.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pulse_responses', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        // No forzamos NOT NULL de vuelta para no romper filas manuales sin coach.
    }
};
