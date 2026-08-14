<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H3 · petición del cliente (docx PRUEBA KINVOO, jul-2026):
 * "El form de crear oferta debería tener los campos de ubicación,
 * días y horarios como cuando el perfil profesional lo llena."
 *
 * Ubicación ya existe (location_id + queda referencia por relación).
 * Se agregan colonia (dirección específica) y availability (slots
 * de días/horarios, mismo formato que ProfessionalProfile::availability).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->string('colonia', 120)->nullable()->after('location_id');
            $table->json('availability')->nullable()->after('contract_type');
        });
    }

    public function down(): void
    {
        Schema::table('offers', function (Blueprint $table) {
            $table->dropColumn(['colonia', 'availability']);
        });
    }
};
