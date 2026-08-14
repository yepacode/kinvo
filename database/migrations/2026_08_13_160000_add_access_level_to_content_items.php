<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H6 · Modelo free/paid (petición cliente, docx PRUEBA KINVOO):
 * "El perfil de profesional gratis solo accede al nivel 1 de contenido,
 *  se monetiza con el desbloqueo de los otros niveles".
 *
 * Nivel 1 = gratis (visible a todos).
 * Nivel 2, 3 = premium (requiere membresía activa).
 *
 * gate_plan_id sigue existiendo para bloqueos por plan específico.
 * access_level es un gate más simple y transversal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            $table->unsignedTinyInteger('access_level')->default(1)->after('gate_plan_id');
        });
    }

    public function down(): void
    {
        Schema::table('content_items', function (Blueprint $table) {
            $table->dropColumn('access_level');
        });
    }
};
