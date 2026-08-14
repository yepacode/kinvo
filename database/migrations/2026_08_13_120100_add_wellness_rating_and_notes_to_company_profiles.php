<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H3 · petición del cliente (docx PRUEBA KINVOO, jul-2026):
 * "Agregar una calificación y un campo de texto al panel de bienestar".
 *
 * Se añaden al company_profiles:
 *  - wellness_rating: 1-5 estrellas (el estudio autoevalúa su bienestar).
 *  - wellness_notes:  texto libre (observaciones, próximas acciones).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->unsignedTinyInteger('wellness_rating')->nullable()->after('media_type');
            $table->text('wellness_notes')->nullable()->after('wellness_rating');
        });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn(['wellness_rating', 'wellness_notes']);
        });
    }
};
