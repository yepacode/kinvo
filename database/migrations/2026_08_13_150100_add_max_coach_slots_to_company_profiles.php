<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * H5 · Cupos por estudio (petición cliente, docx PRUEBA KINVOO):
 * "Admin determina cuántos cupos tiene cada estudio conforme a lo pagado".
 *
 * NULL = sin límite (plan free/default). Un número positivo = máximo de
 * coaches activos que ese estudio puede tener a la vez. La UI del panel
 * de bienestar mostrará "usados / cupos" cuando esté fijado.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->unsignedInteger('max_coach_slots')->nullable()->after('wellness_notes');
        });
    }

    public function down(): void
    {
        Schema::table('company_profiles', function (Blueprint $table) {
            $table->dropColumn('max_coach_slots');
        });
    }
};
